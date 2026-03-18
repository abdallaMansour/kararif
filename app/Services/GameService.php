<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\GameSession;
use App\Models\SessionAnswer;
use App\Models\Question;
use App\Models\Stage;
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

    /**
     * Returns 0-based [startIndex, endIndex] for question_ids that belong to the given round.
     * Round numbers are 1-based. Remainder questions are assigned to the last round.
     */
    public function getRoundQuestionRange(GameSession $session, int $roundNumber): array
    {
        $questionIds = $session->question_ids ?? [];
        $totalQuestions = count($questionIds);
        $roundsCount = max(1, (int) ($session->room->rounds ?? 1));
        if ($roundNumber < 1 || $roundNumber > $roundsCount || $totalQuestions === 0) {
            return [0, -1];
        }
        $perRound = (int) floor($totalQuestions / $roundsCount);
        $remainder = $totalQuestions % $roundsCount;
        $startIndex = 0;
        for ($r = 1; $r < $roundNumber; $r++) {
            $roundSize = $perRound + ($r <= $remainder ? 1 : 0);
            $startIndex += $roundSize;
        }
        $roundSize = $perRound + ($roundNumber <= $remainder ? 1 : 0);
        $endIndex = $startIndex + $roundSize - 1;
        return [$startIndex, min($endIndex, $totalQuestions - 1)];
    }

    /**
     * Returns the 1-based round number that the current question (current_round) belongs to.
     */
    public function getCurrentRoundNumber(GameSession $session): int
    {
        $questionIds = $session->question_ids ?? [];
        $totalQuestions = count($questionIds);
        $roundsCount = max(1, (int) ($session->room->rounds ?? 1));
        if ($totalQuestions === 0) {
            return 1;
        }
        $currentQuestionIndex = $session->current_round - 1; // 0-based
        $perRound = (int) floor($totalQuestions / $roundsCount);
        $remainder = $totalQuestions % $roundsCount;
        $seen = 0;
        for ($r = 1; $r <= $roundsCount; $r++) {
            $roundSize = $perRound + ($r <= $remainder ? 1 : 0);
            if ($currentQuestionIndex < $seen + $roundSize) {
                return $r;
            }
            $seen += $roundSize;
        }
        return $roundsCount;
    }

    /**
     * Count of finished game sessions in rooms created by this room's creator for the same subcategory.
     * Optionally exclude one session (e.g. the current session when computing effective stage type).
     */
    public function getCreatorFinishedSessionsCountForSubcategory(Room $room, ?GameSession $excludeSession = null): int
    {
        $hasCreator = ($room->created_by !== null && $room->created_by !== '')
            || ($room->created_by_adventurer_id !== null && $room->created_by_adventurer_id !== '');
        if (!$hasCreator) {
            return 0;
        }

        $roomIds = Room::where('subcategory_id', $room->subcategory_id)
            ->where(function ($q) use ($room) {
                if ($room->created_by !== null && $room->created_by !== '') {
                    $q->where('created_by', $room->created_by);
                }
                if ($room->created_by_adventurer_id !== null && $room->created_by_adventurer_id !== '') {
                    $q->orWhere('created_by_adventurer_id', $room->created_by_adventurer_id);
                }
            })
            ->pluck('id');

        if ($roomIds->isEmpty()) {
            return 0;
        }

        $query = GameSession::whereIn('room_id', $roomIds)->where('status', 'finished');
        if ($excludeSession !== null) {
            $query->where('id', '!=', $excludeSession->id);
        }
        return $query->count();
    }

    /**
     * Effective stage type for the room (and optional current round).
     * When subcategory has no linked stage, alternates between questions_group and life_points by round,
     * with round 1 type determined by creator's previous finished session count for this subcategory.
     */
    public function getEffectiveStageType(Room $room, ?GameSession $session = null, ?int $currentRoundNumber = null): string
    {
        $room->loadMissing('subcategory.stage');
        $subcategory = $room->subcategory;
        if ($subcategory && $subcategory->use_stage && $subcategory->stage_id && $subcategory->stage) {
            return $subcategory->stage->stage_type;
        }

        $roundNumber = $currentRoundNumber ?? ($session ? $this->getCurrentRoundNumber($session) : 1);
        $creatorCount = $this->getCreatorFinishedSessionsCountForSubcategory($room, $session);
        $startWithQuestionsGroup = ($creatorCount % 2) === 0;
        $typeIndex = ($startWithQuestionsGroup ? 0 : 1) + ($roundNumber - 1);
        return ($typeIndex % 2) === 0 ? Stage::TYPE_QUESTIONS_GROUP : Stage::TYPE_LIFE_POINTS;
    }

    public function getOrCreateSession(Room $room): GameSession
    {
        $session = $room->gameSessions()->whereIn('status', ['waiting', 'playing', 'starting', 'paused'])->first();
        if ($session) {
            return $session;
        }

        $totalQuestions = (int) ($room->questions_count ?? $room->rounds ?? 0);
        if ($totalQuestions <= 0) {
            // Safety fallback: avoid generating an invalid session.
            $totalQuestions = (int) ($room->rounds ?? 0);
        }

        $questionIds = Question::where('type_id', $room->type_id)
            ->where('category_id', $room->category_id)
            ->where('subcategory_id', $room->subcategory_id)
            ->where('status', true)
            ->inRandomOrder()
            ->limit($totalQuestions)
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
        // First time we go to playing: do NOT start the question timer yet.
        // TV will explicitly start the first question after the beginning video.
        $session->update([
            'status' => 'playing',
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
        $shapes = ['triangle', 'circle', 'x', 'square']; // PS controller: o1=triangle, o2=circle, o3=x, o4=square
        $correctId = 'o1';
        if ($question->is_correct_2) {
            $correctId = 'o2';
        } elseif ($question->is_correct_3) {
            $correctId = 'o3';
        } elseif ($question->is_correct_4) {
            $correctId = 'o4';
        }

        return [
            'id' => (string) $question->id,
            'title' => $question->name,
            'text' => $question->name,
            'question_kind' => $question->question_kind ?? 'normal',
            'word_data' => $question->question_kind === Question::KIND_WORDS
                ? ($question->word_data ?? [])
                : null,
            'correctAnswerId' => $correctId,
            'answers' => [
                ['id' => 'o1', 'text' => $question->answer_1, 'shape' => $shapes[0]],
                ['id' => 'o2', 'text' => $question->answer_2, 'shape' => $shapes[1]],
                ['id' => 'o3', 'text' => $question->answer_3, 'shape' => $shapes[2]],
                ['id' => 'o4', 'text' => $question->answer_4, 'shape' => $shapes[3]],
            ],
            'options' => [
                ['id' => 'o1', 'text' => $question->answer_1, 'shape' => $shapes[0]],
                ['id' => 'o2', 'text' => $question->answer_2, 'shape' => $shapes[1]],
                ['id' => 'o3', 'text' => $question->answer_3, 'shape' => $shapes[2]],
                ['id' => 'o4', 'text' => $question->answer_4, 'shape' => $shapes[3]],
            ],
            'image' => $question->getMediaUrlOrNull('image'),
            'voice' => $question->getMediaUrlOrNull('voice'),
            'video' => $question->getMediaUrlOrNull('video'),
            'start_video' => $question->getMediaUrlOrNull('start_video'),
            'lunch_video' => $question->getMediaUrlOrNull('lunch_video'),
            'question_video' => $question->getMediaUrlOrNull('question_video'),
            'correct_answer_video' => $question->getMediaUrlOrNull('correct_answer_video'),
            'wrong_answer_video' => $question->getMediaUrlOrNull('wrong_answer_video'),
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

        // Load room, stage, and players for life-points calculations
        $session->load('room.subcategory.stage', 'room.roomPlayers');
        $room = $session->room;
        $stageType = $this->getEffectiveStageType($room, $session, $this->getCurrentRoundNumber($session));
        $isLifePointsStage = $stageType === Stage::TYPE_LIFE_POINTS;

        // Prevent duplicate answers from the same leader for the same question
        $existingAnswer = SessionAnswer::where('game_session_id', $session->id)
            ->where('question_id', $questionId)
            ->where('room_player_id', $roomPlayerId)
            ->first();
        if ($existingAnswer) {
            return [
                'correct' => $existingAnswer->correct,
                'scoreDelta' => (int) $existingAnswer->score_delta,
                'nextQuestionAvailable' => true,
            ];
        }

        // Prevent surrendered teams from answering
        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);
        $roomPlayerForTeam = RoomPlayer::find($roomPlayerId);
        if ($roomPlayerForTeam && in_array((string) $roomPlayerForTeam->team_id, $surrenderedTeamIds, true)) {
            return [
                'correct' => false,
                'scoreDelta' => 0,
                'nextQuestionAvailable' => true,
            ];
        }

        // Prevent eliminated teams from answering in life-points stages
        if ($isLifePointsStage) {
            $roomPlayerForTeam = RoomPlayer::find($roomPlayerId);
            $teamId = $roomPlayerForTeam?->team_id;
            if ($teamId !== null) {
                $teamPlayerIds = $room->roomPlayers->where('team_id', $teamId)->pluck('id');
                $wrongCountForTeam = SessionAnswer::where('game_session_id', $session->id)
                    ->whereIn('room_player_id', $teamPlayerIds)
                    ->where('correct', false)
                    ->count();
                $initialLives = 10;
                $lifePoints = max(0, $initialLives - $wrongCountForTeam);
                if ($lifePoints <= 0) {
                    return [
                        'correct' => false,
                        'scoreDelta' => 0,
                        'nextQuestionAvailable' => true,
                    ];
                }
            }
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

        // Pause when every team has submitted an answer (team-based, not leader-based)
        $answeredRoomPlayerIds = SessionAnswer::where('game_session_id', $session->id)
            ->where('question_id', $questionId)
            ->pluck('room_player_id');

        $answeredTeamIds = $answeredRoomPlayerIds->isEmpty()
            ? collect()
            : RoomPlayer::whereIn('id', $answeredRoomPlayerIds->toArray())
                ->pluck('team_id')
                ->filter()
                ->unique()
                ->values();

        if ($isLifePointsStage) {
            $answeredTeamIds = $answeredTeamIds->filter(function ($teamId) use ($room, $session) {
                $teamPlayerIds = $room->roomPlayers->where('team_id', $teamId)->pluck('id');
                $wrongCount = SessionAnswer::where('game_session_id', $session->id)
                    ->whereIn('room_player_id', $teamPlayerIds)
                    ->where('correct', false)
                    ->count();
                return max(0, 10 - $wrongCount) > 0;
            })->values();
        }

        // Exclude surrendered teams from expected count and from answered count
        $answeredTeamIds = $answeredTeamIds->reject(fn ($tid) => in_array((string) $tid, $surrenderedTeamIds, true))->values();
        $activeTeamIds = $room->roomPlayers()->pluck('team_id')->filter()->unique()->values();
        $activeTeamIds = $activeTeamIds->reject(fn ($tid) => in_array((string) $tid, $surrenderedTeamIds, true))->values();
        $expectedTeams = $activeTeamIds->count();
        $answeredCount = $answeredTeamIds->count();

        $allTeamsAnswered = $expectedTeams > 0 && $answeredCount >= $expectedTeams;

        // Safeguard: if room has 2+ teams, never pause with only 1 answer
        if ((int) $room->teams >= 2 && $answeredCount < 2) {
            $allTeamsAnswered = false;
        }

        // Check if question time (30 seconds) has elapsed
        $timeLimitSeconds = 30;
        $timedOut = $session->question_started_at
            ? $session->question_started_at->diffInSeconds(now()) >= $timeLimitSeconds
            : false;

        $nextRound = $session->current_round + 1;
        $totalQuestions = count($questionIds);
        $nextQuestionAvailable = $nextRound <= $totalQuestions;

        if ($allTeamsAnswered || $timedOut) {
            // Pause the game and show stats / animations
            $session->update(['status' => 'paused']);
            $this->firebaseSync->syncSessionPaused($session->fresh(), $correct);
        } else {
            // Keep playing this question, just sync updated scores
            $this->firebaseSync->syncScores($session->fresh());
        }

        return [
            'correct' => $correct,
            'scoreDelta' => $scoreDelta,
            'nextQuestionAvailable' => $nextQuestionAvailable,
        ];
    }

    public function startCurrentQuestion(GameSession $session): array
    {
        if ($session->status !== 'playing') {
            return ['ok' => false, 'reason' => 'invalid_status'];
        }

        // If the timer is already started, do nothing (idempotent).
        if ($session->question_started_at) {
            return ['ok' => true, 'reason' => 'already_started'];
        }

        $session->update([
            'question_started_at' => now(),
        ]);

        // Re-sync question + teams + stage, now with questionStartedAt set
        $this->firebaseSync->syncSessionStart($session->fresh());

        return ['ok' => true, 'reason' => 'started'];
    }

    public function advanceToNextQuestion(GameSession $session): array
    {
        if ($session->status !== 'paused') {
            return ['finished' => false, 'invalid' => true];
        }

        $questionIds = $session->question_ids ?? [];
        $nextRound = $session->current_round + 1;
        $total = count($questionIds);

        // Load room, stage, and players for life-points calculations
        $session->load('room.subcategory.stage', 'room.roomPlayers', 'sessionAnswers');
        $room = $session->room;
        $stageType = $this->getEffectiveStageType($room, $session, $this->getCurrentRoundNumber($session));
        $isLifePointsStage = $stageType === Stage::TYPE_LIFE_POINTS;
        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);

        $winnerTeamIds = null;

        if ($isLifePointsStage) {
            // Compute life points per team based on wrong answers (surrendered teams are considered eliminated)
            $byTeam = $room->roomPlayers->groupBy('team_id');
            $lifeByTeam = [];
            foreach ($byTeam as $teamId => $players) {
                $teamPlayerIds = $players->pluck('id');
                $wrongCountForTeam = $session->sessionAnswers
                    ->whereIn('room_player_id', $teamPlayerIds)
                    ->where('correct', false)
                    ->count();
                $initialLives = 10;
                $lifePoints = max(0, $initialLives - $wrongCountForTeam);
                $lifeByTeam[(string) $teamId] = $lifePoints;
            }

            $aliveTeams = collect($lifeByTeam)->filter(function ($life, $teamId) use ($surrenderedTeamIds) {
                return $life > 0 && !in_array((string) $teamId, $surrenderedTeamIds, true);
            });

            // If only one team left alive, or no team alive, finish the session now
            if ($aliveTeams->count() <= 1 || $nextRound > $total) {
                $maxLife = $aliveTeams->count() > 0 ? $aliveTeams->max() : 0;
                // Winners are teams with the highest life (allowing ties), even if 0
                $winnerTeamIds = collect($lifeByTeam)
                    ->filter(fn ($life) => $life === $maxLife)
                    ->keys()
                    ->values()
                    ->all();

                $session->update(['status' => 'finished', 'current_round' => min($nextRound, $total + 1)]);
                $session->room->update(['status' => 'finished']);
                $this->updatePointsForFinishedSession($session->fresh());
                $this->firebaseSync->syncSessionEnd($session->fresh(), $winnerTeamIds);
                return ['finished' => true, 'round' => null];
            }
        } else {
            // Non life-points stage: finish when only one active team left (others surrendered) or questions exhausted
            $activeTeamIds = $room->roomPlayers->pluck('team_id')->unique()->filter()->reject(
                fn ($tid) => in_array((string) $tid, $surrenderedTeamIds, true)
            )->values();
            if ($activeTeamIds->count() === 1) {
                $winnerTeamIds = $activeTeamIds->map(fn ($id) => (string) $id)->all();
                $session->update(['status' => 'finished', 'current_round' => $nextRound]);
                $session->room->update(['status' => 'finished']);
                $this->updatePointsForFinishedSession($session->fresh());
                $this->firebaseSync->syncSessionEnd($session->fresh(), $winnerTeamIds);
                return ['finished' => true, 'round' => null];
            }
            if ($nextRound > $total) {
                $session->update(['status' => 'finished', 'current_round' => $nextRound]);
                $session->room->update(['status' => 'finished']);
                $this->updatePointsForFinishedSession($session->fresh());
                $this->firebaseSync->syncSessionEnd($session->fresh());
                return ['finished' => true, 'round' => null];
            }
        }

        $session->update([
            'status' => 'playing',
            'current_round' => $nextRound,
            'question_started_at' => now(),
        ]);
        $this->firebaseSync->syncSessionStart($session->fresh());
        return ['finished' => false, 'round' => $nextRound];
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
