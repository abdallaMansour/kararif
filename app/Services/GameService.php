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
        $session = $room->gameSessions()->whereIn('status', ['waiting', 'playing'])->first();
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

        $session = $room->gameSessions()->create([
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now(),
            'question_ids' => $questionIds,
        ]);

        $room->update(['status' => 'playing']);

        $this->firebaseSync->syncSessionStart($session);
        $this->firebaseSync->syncQuestion($session);

        return $session;
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
            'text' => $question->name,
            'options' => [
                ['id' => 'o1', 'text' => $question->answer_1],
                ['id' => 'o2', 'text' => $question->answer_2],
                ['id' => 'o3', 'text' => $question->answer_3],
                ['id' => 'o4', 'text' => $question->answer_4],
            ],
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
