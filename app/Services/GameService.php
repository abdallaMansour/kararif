<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\GameSession;
use App\Models\SessionAnswer;
use App\Models\Question;
use App\Models\TvDisplay;

class GameService
{
    public function __construct(
        protected FirebaseGameSyncService $firebaseSync
    ) {}
    public function generateRoomCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Room::where('code', $code)->exists());

        return $code;
    }

    public function generateTvDisplayCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (TvDisplay::where('code', $code)->exists());

        return $code;
    }

    public function getOrCreateTvDisplay(string $deviceId): TvDisplay
    {
        $expiresAt = now()->addMinutes(15);

        $existing = TvDisplay::where('device_id', $deviceId)
            ->where('status', TvDisplay::STATUS_WAITING)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $existing;
        }

        return TvDisplay::create([
            'device_id' => $deviceId,
            'code' => $this->generateTvDisplayCode(),
            'status' => TvDisplay::STATUS_WAITING,
            'expires_at' => $expiresAt,
        ]);
    }

    public function getOrCreateSession(Room $room): GameSession
    {
        $session = $room->gameSessions()->whereIn('status', ['waiting', 'playing', 'starting'])->first();
        if ($session) {
            return $session;
        }

        $questionIds = Question::where('type_id', $room->type_id)
            ->where('category_id', $room->category_id)
            ->where('subcategory_id', $room->subcategory_id)
            ->where('status', true)
            ->inRandomOrder()
            ->limit($room->rounds)
            ->pluck('id')
            ->values()
            ->toArray();

        shuffle($questionIds);

        $countdownSeconds = config('game.start_countdown_seconds', 5);
        $startTimerEndsAt = now()->addSeconds($countdownSeconds);

        $session = $room->gameSessions()->create([
            'status' => 'starting',
            'started_at' => now(),
            'start_timer_ends_at' => $startTimerEndsAt,
            'question_started_at' => null,
            'question_ids' => $questionIds,
        ]);

        $room->update(['status' => 'playing']);

        $this->firebaseSync->syncSessionStarting($session);

        return $session;
    }

    public function ensureSessionPlaying(GameSession $session): GameSession
    {
        if ($session->status !== 'starting') {
            return $session;
        }
        if ($session->start_timer_ends_at && now()->lt($session->start_timer_ends_at)) {
            return $session;
        }

        return $this->transitionSessionToPlaying($session);
    }

    public function maybeStartSessionWhenAllJoined(Room $room): ?GameSession
    {
        $session = $room->gameSessions()->where('status', 'starting')->latest()->first();
        if (!$session) {
            return null;
        }

        $room->load('roomPlayers');
        $total = $room->roomPlayers->count();
        if ($total === 0) {
            return null;
        }

        $joinedCount = $room->roomPlayers->filter(fn ($rp) => $rp->tv_view_joined_at !== null)->count();
        if ($joinedCount < $total) {
            return null;
        }

        return $this->transitionSessionToPlaying($session->fresh());
    }

    private function transitionSessionToPlaying(GameSession $session): GameSession
    {
        $session->update([
            'status' => 'playing',
            'question_started_at' => now(),
        ]);

        $this->firebaseSync->syncSessionStart($session->fresh());

        return $session->fresh();
    }

    public function getCurrentQuestion(GameSession $session): ?array
    {
        $questionIds = $session->question_ids ?? [];
        if (empty($questionIds)) {
            return null;
        }
        $index = $session->current_round - 1;
        if (!isset($questionIds[$index])) {
            return null;
        }
        $question = Question::find($questionIds[$index]);
        if (!$question) {
            return null;
        }
        return [
            'id' => (string) $question->id,
            'title' => $question->name,
            'text' => $question->name,
            'question_kind' => $question->question_kind ?? 'normal',
            'word_data' => $question->question_kind === Question::KIND_WORDS ? $question->word_data : null,
            'answers' => [
                ['id' => 'o1', 'text' => $question->answer_1],
                ['id' => 'o2', 'text' => $question->answer_2],
                ['id' => 'o3', 'text' => $question->answer_3],
                ['id' => 'o4', 'text' => $question->answer_4],
            ],
            'options' => [
                ['id' => 'o1', 'text' => $question->answer_1],
                ['id' => 'o2', 'text' => $question->answer_2],
                ['id' => 'o3', 'text' => $question->answer_3],
                ['id' => 'o4', 'text' => $question->answer_4],
            ],
            'image' => $question->getFirstMediaUrl('image'),
            'voice' => $question->getFirstMediaUrl('voice'),
            'video' => $question->getFirstMediaUrl('video'),
            'start_video' => $question->getFirstMediaUrl('start_video'),
            'lunch_video' => $question->getFirstMediaUrl('lunch_video'),
            'question_video' => $question->getFirstMediaUrl('question_video'),
            'correct_answer_video' => $question->getFirstMediaUrl('correct_answer_video'),
            'wrong_answer_video' => $question->getFirstMediaUrl('wrong_answer_video'),
        ];
    }

    public function submitAnswer(GameSession $session, int $roomPlayerId, int $answerIndex): array
    {
        $questionIds = $session->question_ids ?? [];
        $roundIndex = $session->current_round - 1;
        if (!isset($questionIds[$roundIndex])) {
            return ['correct' => false, 'scoreDelta' => 0, 'nextQuestion' => null];
        }

        $questionId = $questionIds[$roundIndex];
        $question = Question::find($questionId);
        if (!$question) {
            return ['correct' => false, 'scoreDelta' => 0, 'nextQuestion' => null];
        }

        $correct = false;
        $scoreDelta = 0;
        if ($answerIndex === 1 && $question->is_correct_1) {
            $correct = true;
            $scoreDelta = 10;
        } elseif ($answerIndex === 2 && $question->is_correct_2) {
            $correct = true;
            $scoreDelta = 10;
        } elseif ($answerIndex === 3 && $question->is_correct_3) {
            $correct = true;
            $scoreDelta = 10;
        } elseif ($answerIndex === 4 && $question->is_correct_4) {
            $correct = true;
            $scoreDelta = 10;
        }

        SessionAnswer::create([
            'game_session_id' => $session->id,
            'question_id' => $questionId,
            'room_player_id' => $roomPlayerId,
            'answer_index' => $answerIndex,
            'correct' => $correct,
            'score_delta' => $scoreDelta,
        ]);

        $roomPlayer = RoomPlayer::find($roomPlayerId);
        if ($roomPlayer) {
            $roomPlayer->increment('score', $scoreDelta);
        }

        $this->firebaseSync->syncScores($session->fresh());

        $nextRound = $session->current_round + 1;
        if ($nextRound <= count($questionIds)) {
            $session->update(['current_round' => $nextRound, 'question_started_at' => now()]);
            $this->firebaseSync->syncQuestion($session->fresh());
            $nextQuestion = $this->getCurrentQuestion($session->fresh());
        } else {
            $session->update(['status' => 'finished', 'current_round' => $nextRound]);
            $session->room->update(['status' => 'finished']);
            $this->updatePointsForFinishedSession($session->fresh());
            $this->firebaseSync->syncSessionEnd($session->fresh());
            $nextQuestion = null;
        }

        return [
            'correct' => $correct,
            'scoreDelta' => $scoreDelta,
            'nextQuestion' => $nextQuestion,
        ];
    }

    public function updatePointsForFinishedSession(GameSession $session): void
    {
        $session->load('room.roomPlayers.adventurer', 'room.roomPlayers.user');
        $teamScores = $session->room->roomPlayers->groupBy('team_id')
            ->map(fn ($players) => $players->sum('score'));
        $maxScore = $teamScores->max();
        $winnerTeamIds = $teamScores->filter(fn ($s) => $s >= $maxScore)->keys();

        foreach ($session->room->roomPlayers as $rp) {
            $player = $rp->adventurer ?? $rp->user;
            if (!$player) {
                continue;
            }
            $delta = $winnerTeamIds->contains((string) $rp->team_id) ? 1 : -1;
            $player->increment('points', $delta);
        }
    }
}
