<?php

namespace App\Services;

use App\Models\Adventurer;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\GameSession;
use App\Models\User;
use App\Models\SessionAnswer;
use App\Models\Question;
use App\Models\CustomQuestion;
use App\Models\CreatorSubcategoryStageTail;
use App\Models\CustomStage;
use App\Models\Stage;
use App\Models\TvDisplay;

class GameService
{
    /** Must stay in sync with clients / TV question timer. */
    public const QUESTION_TIME_LIMIT_SECONDS = 30;

    public function __construct(
        protected FirebaseGameSyncService $firebaseSync,
        protected CustomContentUsageService $customContentUsage
    ) {
    }
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
     * Which 1-based "game round" (chunk of questions among room.rounds) contains this question index (0-based).
     */
    public function getGameRoundNumberForQuestionIndex(GameSession $session, int $zeroBasedQuestionIndex): int
    {
        $session->loadMissing('room');
        $questionIds = $session->question_ids ?? [];
        $totalQuestions = count($questionIds);
        $roundsCount = max(1, (int) ($session->room->rounds ?? 1));
        if ($totalQuestions === 0) {
            return 1;
        }
        $currentQuestionIndex = max(0, min($zeroBasedQuestionIndex, $totalQuestions - 1));
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
     * Life cost per wrong answer in a given game round (stage life_points_per_question or 1).
     */
    public function getLifeCostForGameRound(Room $room, GameSession $session, int $gameRoundNumber): float
    {
        if ((bool) $room->is_custom) {
            $room->loadMissing('customStage');
            $cs = $room->customStage;
            if ($cs instanceof CustomStage && $cs->life_points_per_question !== null) {
                return max(0.01, (float) $cs->life_points_per_question);
            }

            return 1.0;
        }

        $stage = $this->getEffectiveStageForRound($room, $session, $gameRoundNumber);
        if ($stage && $stage->life_points_per_question !== null) {
            return max(0.01, (float) $stage->life_points_per_question);
        }

        return 1.0;
    }

    /**
     * Apply a score change to a team leader row, never below zero.
     *
     * @return int Actual delta applied (may be less than requested when flooring at 0).
     */
    public function applyTeamScoreDelta(RoomPlayer $player, int $requestedDelta): int
    {
        if ($requestedDelta === 0) {
            return 0;
        }

        $current = (int) $player->score;
        $newScore = max(0, $current + $requestedDelta);
        $applied = $newScore - $current;
        if ($applied !== 0) {
            $player->update(['score' => $newScore]);
        }

        return $applied;
    }

    /**
     * Wrong answers in this game round only (team = all players on that team_id).
     */
    public function countWrongAnswersForTeamInGameRound(GameSession $session, Room $room, int $teamId, int $gameRoundNumber): int
    {
        $questionIds = $session->question_ids ?? [];
        if ($questionIds === []) {
            return 0;
        }

        $isCustom = (bool) $room->is_custom;
        $teamPlayerIds = $room->roomPlayers->where('team_id', $teamId)->pluck('id')->all();

        return (int) SessionAnswer::query()
            ->where('game_session_id', $session->id)
            ->whereIn('room_player_id', $teamPlayerIds)
            ->where('correct', false)
            ->get()
            ->filter(function ($a) use ($session, $questionIds, $isCustom, $gameRoundNumber) {
                $qid = $isCustom ? $a->custom_question_id : $a->question_id;
                if ($qid === null) {
                    return false;
                }
                $idx = array_search($qid, $questionIds, false);
                if ($idx === false) {
                    return false;
                }

                return $this->getGameRoundNumberForQuestionIndex($session, (int) $idx) === $gameRoundNumber;
            })
            ->count();
    }

    /**
     * Remaining lives for a team within one game round (resets each life-points round; wrongs from other rounds ignored).
     */
    public function getRemainingLivesForTeamInGameRound(GameSession $session, Room $room, int $teamId, int $gameRoundNumber): int
    {
        $stageType = $this->getEffectiveStageType($room, $session, $gameRoundNumber);
        if ($stageType !== Stage::TYPE_LIFE_POINTS) {
            return max(1, (int) ($room->life_points ?? 5));
        }

        $initial = max(1, (int) ($room->life_points ?? 5));
        $cost = $this->getLifeCostForGameRound($room, $session, $gameRoundNumber);
        $wrongs = $this->countWrongAnswersForTeamInGameRound($session, $room, $teamId, $gameRoundNumber);

        return max(0, (int) floor($initial - $wrongs * $cost));
    }

    /**
     * When at least two teams are competing in life-points and at most one team still has lives in this game round,
     * finish the session immediately (DB + Firebase). Does nothing for single-team / solo rooms.
     */
    public function attemptFinishLifePointsSession(GameSession $session, ?int $gameRoundNumber = null): bool
    {
        if ($session->status === 'finished') {
            return false;
        }

        $session->load('room.subcategory.stage', 'room.roomPlayers', 'sessionAnswers');
        $room = $session->room;
        if (!$room) {
            return false;
        }

        $roundIndex = max(0, (int) $session->current_round - 1);
        $gameRoundNumber ??= $this->getGameRoundNumberForQuestionIndex($session, $roundIndex);

        if ($this->getEffectiveStageType($room, $session, $gameRoundNumber) !== Stage::TYPE_LIFE_POINTS) {
            return false;
        }

        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);
        $activeTeamIds = $room->roomPlayers->pluck('team_id')->unique()->filter()->reject(
            fn ($tid) => in_array((string) $tid, $surrenderedTeamIds, true)
        )->values();

        if ($activeTeamIds->count() < 2) {
            return false;
        }

        $lifeByTeam = [];
        foreach ($activeTeamIds as $teamId) {
            $lifeByTeam[(string) $teamId] = $this->getRemainingLivesForTeamInGameRound($session, $room, (int) $teamId, $gameRoundNumber);
        }

        $aliveTeams = collect($lifeByTeam)->filter(function ($life, $teamId) use ($surrenderedTeamIds) {
            return $life > 0 && !in_array((string) $teamId, $surrenderedTeamIds, true);
        });

        if ($aliveTeams->count() > 1) {
            return false;
        }

        $maxLife = $aliveTeams->count() > 0 ? $aliveTeams->max() : 0;
        $winnerTeamIds = collect($lifeByTeam)
            ->filter(fn ($life) => $life === $maxLife)
            ->keys()
            ->values()
            ->all();

        $questionIds = $session->question_ids ?? [];
        $total = count($questionIds);
        $nextRound = (int) $session->current_round + 1;

        $session->update([
            'status' => 'finished',
            'current_round' => min($nextRound, $total + 1),
            'winner_team_ids' => array_values(array_map('strval', $winnerTeamIds)),
        ]);
        $room->update(['status' => 'finished']);

        $finished = $session->fresh();
        $this->updatePointsForFinishedSession($finished);
        $this->recordLastRoundStageTail($finished);
        $this->firebaseSync->syncSessionEnd($finished, $winnerTeamIds);

        return true;
    }

    /**
     * Determine winning team id(s) for a finished session (life-points, surrender, or score).
     *
     * @return list<string>
     */
    public function resolveWinnerTeamIds(GameSession $session): array
    {
        $session->loadMissing('room.roomPlayers', 'sessionAnswers');
        $room = $session->room;
        if (!$room) {
            return [];
        }

        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);
        $allTeamIds = $room->roomPlayers->pluck('team_id')->unique()->filter()->map(fn ($id) => (string) $id)->values();
        $activeTeamIds = $allTeamIds->reject(fn ($tid) => in_array($tid, $surrenderedTeamIds, true))->values();

        if ($activeTeamIds->count() === 1) {
            return $activeTeamIds->all();
        }

        $lastQuestionIndex = max(0, min((int) $session->current_round - 1, count($session->question_ids ?? []) - 1));
        $gameRoundNumber = $this->getGameRoundNumberForQuestionIndex($session, $lastQuestionIndex);
        $stageType = $this->getEffectiveStageType($room, $session, $gameRoundNumber);

        if ($stageType === Stage::TYPE_LIFE_POINTS) {
            $lifeByTeam = [];
            foreach ($activeTeamIds as $teamId) {
                $lifeByTeam[$teamId] = $this->getRemainingLivesForTeamInGameRound(
                    $session,
                    $room,
                    (int) $teamId,
                    $gameRoundNumber
                );
            }

            if ($lifeByTeam !== []) {
                $aliveTeams = collect($lifeByTeam)->filter(fn ($life) => $life > 0);
                if ($aliveTeams->count() === 1) {
                    return $aliveTeams->keys()->map(fn ($id) => (string) $id)->values()->all();
                }

                $maxLife = max($lifeByTeam);
                $byLife = collect($lifeByTeam)
                    ->filter(fn ($life) => $life === $maxLife)
                    ->keys()
                    ->map(fn ($id) => (string) $id)
                    ->values()
                    ->all();

                if ($byLife !== []) {
                    return $byLife;
                }
            }
        }

        $teamScores = $room->roomPlayers
            ->groupBy('team_id')
            ->reject(fn ($_, $teamId) => in_array((string) $teamId, $surrenderedTeamIds, true))
            ->map(fn ($players) => max(0, (int) $players->sum('score')));

        if ($teamScores->isEmpty()) {
            return [];
        }

        $maxScore = $teamScores->max();
        return $teamScores
            ->filter(fn ($score) => $score === $maxScore)
            ->keys()
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
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
     * When subcategory has no linked stage: uses random stage per round (round_stage_ids),
     * or falls back to alternating questions_group/life_points if no stages exist in DB.
     */
    public function getEffectiveStageType(Room $room, ?GameSession $session = null, ?int $currentRoundNumber = null): string
    {
        if ((bool) $room->is_custom) {
            return Stage::TYPE_LIFE_POINTS;
        }

        $room->loadMissing('subcategory.stage');
        $subcategory = $room->subcategory;
        if ($subcategory && $subcategory->use_stage && $subcategory->stage_id && $subcategory->stage) {
            return $subcategory->stage->stage_type;
        }

        $roundNumber = $currentRoundNumber ?? ($session ? $this->getCurrentRoundNumber($session) : 1);
        $stage = $this->getEffectiveStageForRound($room, $session, $roundNumber);
        if ($stage) {
            return $stage->stage_type;
        }

        // Fallback when no stages exist in DB: alternate by round; round 1 avoids repeating previous session's last type (tail).
        $creatorCount = $this->getCreatorFinishedSessionsCountForSubcategory($room, $session);
        $tail = $this->getTailForRoom($room);
        if ($tail !== null && $roundNumber === 1) {
            // Previous session ended with tail type → start new session with the opposite family.
            $startWithQuestionsGroup = $tail->last_round_stage_type === Stage::TYPE_LIFE_POINTS;
        } else {
            $startWithQuestionsGroup = ($creatorCount % 2) === 0;
        }
        $typeIndex = ($startWithQuestionsGroup ? 0 : 1) + ($roundNumber - 1);
        return ($typeIndex % 2) === 0 ? Stage::TYPE_QUESTIONS_GROUP : Stage::TYPE_LIFE_POINTS;
    }

    /**
     * When subcategory has no linked stage: returns the Stage model for the given round.
     * Uses round_stage_ids from session (random stage per round). Returns null if no stages exist.
     */
    public function getEffectiveStageForRound(Room $room, ?GameSession $session, int $roundNumber): ?Stage
    {
        if ((bool) $room->is_custom) {
            // Custom rooms use `custom_stages` via Firebase `stage` payload only, not normal `Stage` rows.
            return null;
        }

        $room->loadMissing('subcategory.stage');
        $subcategory = $room->subcategory;
        if ($subcategory && $subcategory->use_stage && $subcategory->stage_id && $subcategory->stage) {
            return $subcategory->stage;
        }

        $roundStageIds = $session?->round_stage_ids ?? [];
        $stageId = $roundStageIds[(string) $roundNumber] ?? $roundStageIds[$roundNumber] ?? null;
        if ($stageId) {
            return Stage::find($stageId);
        }
        return null;
    }

    /**
     * Compute stage IDs per round when subcategory has no linked stage.
     * Picks stages so consecutive rounds prefer a different stage_type and different stage id when possible;
     * round 1 avoids repeating the same stage_type as the creator's last finished session for this subcategory (tail).
     * Returns associative array: ["1" => stageId, "2" => stageId, ...]
     */
    public function computeRoundStageIds(Room $room): ?array
    {
        if ((bool) $room->is_custom) {
            // Stage media for custom games comes from `rooms.custom_stage_id` / `custom_stages`, not per-round `Stage`.
            return null;
        }

        $room->loadMissing('subcategory.stage');
        $subcategory = $room->subcategory;
        if ($subcategory && $subcategory->use_stage && $subcategory->stage_id) {
            return null;
        }

        $stages = Stage::where('status', true)->orderBy('id')->get();
        if ($stages->isEmpty()) {
            return null;
        }

        $roundsCount = max(1, (int) ($room->rounds ?? 1));
        $tail = $this->getTailForRoom($room);

        $result = [];
        $previousStageId = null;
        $previousType = null;

        for ($r = 1; $r <= $roundsCount; $r++) {
            $avoidType = null;
            if ($r === 1 && $tail !== null) {
                $avoidType = $tail->last_round_stage_type;
            } elseif ($r > 1 && $previousType !== null) {
                $avoidType = $previousType;
            }

            $candidates = $stages;
            if ($avoidType !== null) {
                $filtered = $stages->where('stage_type', '!=', $avoidType)->values();
                if ($filtered->isNotEmpty()) {
                    $candidates = $filtered;
                }
            }
            if ($previousStageId !== null && $candidates->count() > 1) {
                $byOtherId = $candidates->where('id', '!=', $previousStageId)->values();
                if ($byOtherId->isNotEmpty()) {
                    $candidates = $byOtherId;
                }
            }

            /** @var \App\Models\Stage $picked */
            $picked = $candidates->random();
            $result[(string) $r] = $picked->id;
            $previousStageId = $picked->id;
            $previousType = $picked->stage_type;
        }

        return $result;
    }

    public function getOrCreateSession(Room $room): GameSession
    {
        // Use the latest session attempt; older "waiting" rows can exist and block regeneration.
        $session = $room->gameSessions()
            ->whereIn('status', ['waiting', 'playing', 'starting', 'paused'])
            ->latest()
            ->first();

        if ($session) {
            $existingQuestionIds = $session->question_ids ?? [];
            if (is_array($existingQuestionIds) && !empty($existingQuestionIds)) {
                return $session;
            }
            // If a stale session row exists but has no questions, re-initialize it below.
        }

        $totalQuestions = (int) ($room->questions_count ?? $room->rounds ?? 0);
        if ($totalQuestions <= 0) {
            // Safety fallback: avoid generating an invalid session.
            $totalQuestions = (int) ($room->rounds ?? 0);
        }

        if ((bool) $room->is_custom) {
            $questionIds = CustomQuestion::query()
                ->where('custom_category_id', $room->custom_category_id)
                ->where('status', true)
                ->where('question_kind', CustomQuestion::KIND_NORMAL)
                ->inRandomOrder()
                ->limit($totalQuestions)
                ->pluck('id')
                ->values()
                ->toArray();
        } else {
            $questionIds = Question::where('type_id', $room->type_id)
                ->where('category_id', $room->category_id)
                ->where('subcategory_id', $room->subcategory_id)
                ->where('status', true)
                ->inRandomOrder()
                ->limit($totalQuestions)
                ->pluck('id')
                ->values()
                ->toArray();
        }

        shuffle($questionIds);

        $countdownSeconds = config('game.start_countdown_seconds', 5);
        $startTimerEndsAt = now()->addSeconds($countdownSeconds);

        $roundStageIds = $this->computeRoundStageIds($room);

        $payload = [
            'status' => 'starting',
            'started_at' => now(),
            'start_timer_ends_at' => $startTimerEndsAt,
            'question_started_at' => null,
            'question_ids' => $questionIds,
            'round_stage_ids' => $roundStageIds,
        ];

        if ($session) {
            $session->update($payload);
            $session = $session->fresh();
        } else {
            $session = $room->gameSessions()->create($payload);
        }

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

        $joinedCount = $room->roomPlayers->filter(fn($rp) => $rp->tv_view_joined_at !== null)->count();
        if ($joinedCount < $total) {
            return null;
        }

        return $this->transitionSessionToPlaying($session->fresh());
    }

    private function transitionSessionToPlaying(GameSession $session): GameSession
    {
        // First time we go to playing: do NOT start the question timer yet.
        // TV will explicitly start the first question after the beginning video.
        $wasStarting = $session->status === 'starting';
        $session->update([
            'status' => 'playing',
        ]);

        $fresh = $session->fresh();
        if ($wasStarting) {
            $this->customContentUsage->recordCustomCategorySessionStart($fresh);
        }

        $this->firebaseSync->syncSessionStart($fresh);

        return $fresh->fresh();
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
        $question = (bool) $session->room?->is_custom
            ? CustomQuestion::find($questionIds[$index])
            : Question::find($questionIds[$index]);
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
            'word_data' => null,
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
            'image' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('image') : null,
            'voice' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('voice') : null,
            'video' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('video') : null,
            'start_video' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('start_video') : null,
            'lunch_video' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('lunch_video') : null,
            'question_video' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('question_video') : null,
            'correct_answer_video' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('correct_answer_video') : null,
            'wrong_answer_video' => method_exists($question, 'getMediaUrlOrNull') ? $question->getMediaUrlOrNull('wrong_answer_video') : null,
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
        $question = (bool) $session->room?->is_custom
            ? CustomQuestion::find($questionId)
            : Question::find($questionId);
        if (!$question) {
            return ['correct' => false, 'scoreDelta' => 0, 'nextQuestion' => null];
        }
        $isCustomRoom = (bool) $session->room?->is_custom;
        $questionKey = $isCustomRoom ? 'custom_question_id' : 'question_id';

        // Load room, stage, and players for life-points calculations
        $session->load('room.subcategory.stage', 'room.roomPlayers');
        $room = $session->room;
        $gameRoundNumber = $this->getGameRoundNumberForQuestionIndex($session, $roundIndex);
        $stageType = $this->getEffectiveStageType($room, $session, $gameRoundNumber);
        $isLifePointsStage = $stageType === Stage::TYPE_LIFE_POINTS;

        // Prevent duplicate answers from the same leader for the same question
        $existingAnswer = SessionAnswer::where('game_session_id', $session->id)
            ->where($questionKey, $questionId)
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

        // Prevent eliminated teams from answering in life-points stages (lives reset each LP game round)
        if ($isLifePointsStage) {
            $roomPlayerForTeam = RoomPlayer::find($roomPlayerId);
            $teamId = $roomPlayerForTeam?->team_id;
            if ($teamId !== null) {
                $remaining = $this->getRemainingLivesForTeamInGameRound($session, $room, (int) $teamId, $gameRoundNumber);
                if ($remaining <= 0) {
                    return [
                        'correct' => false,
                        'scoreDelta' => 0,
                        'nextQuestionAvailable' => true,
                    ];
                }
            }
        }

        $correct = false;
        if ($answerIndex === 1 && $question->is_correct_1) {
            $correct = true;
        } elseif ($answerIndex === 2 && $question->is_correct_2) {
            $correct = true;
        } elseif ($answerIndex === 3 && $question->is_correct_3) {
            $correct = true;
        } elseif ($answerIndex === 4 && $question->is_correct_4) {
            $correct = true;
        }

        // Team score on leaders only: QG wrong = 0; LP correct +10 / wrong -10
        if ($stageType === Stage::TYPE_QUESTIONS_GROUP) {
            $scoreDelta = $correct ? 10 : 0;
        } else {
            $scoreDelta = $correct ? 10 : -10;
        }

        $roomPlayer = RoomPlayer::find($roomPlayerId);
        $appliedScoreDelta = $roomPlayer
            ? $this->applyTeamScoreDelta($roomPlayer, $scoreDelta)
            : $scoreDelta;

        SessionAnswer::create([
            'game_session_id' => $session->id,
            'question_id' => $isCustomRoom ? null : $questionId,
            'custom_question_id' => $isCustomRoom ? $questionId : null,
            'room_player_id' => $roomPlayerId,
            'answer_index' => $answerIndex,
            'correct' => $correct,
            'score_delta' => $appliedScoreDelta,
        ]);

        $session = $session->fresh(['sessionAnswers']);
        if ($this->attemptFinishLifePointsSession($session, $gameRoundNumber)) {
            return [
                'correct' => $correct,
                'scoreDelta' => $appliedScoreDelta,
                'nextQuestionAvailable' => false,
                'sessionFinished' => true,
            ];
        }

        $activeTeamIds = $room->roomPlayers()->pluck('team_id')->filter()->unique()->values();
        $activeTeamIds = $activeTeamIds->reject(fn ($tid) => in_array((string) $tid, $surrenderedTeamIds, true))->values();

        if ($isLifePointsStage) {
            // Pause when every team that still has lives has had its leader answer (eliminated teams no longer count).
            $sessionReload = $session->fresh(['sessionAnswers']);
            $teamsWithLives = $activeTeamIds->filter(function ($tid) use ($sessionReload, $room, $gameRoundNumber) {
                return $this->getRemainingLivesForTeamInGameRound($sessionReload, $room, (int) $tid, $gameRoundNumber) > 0;
            });

            if ($teamsWithLives->isEmpty()) {
                // No team with lives left: do not treat as "all answered" (avoids vacuous true on empty foreach).
                $allTeamsAnswered = false;
            } else {
                $allTeamsAnswered = true;
                foreach ($teamsWithLives as $tid) {
                    $leader = $this->getAnsweringRoomPlayerForTeam($room, (int) $tid);
                    if (!$leader) {
                        $allTeamsAnswered = false;
                        continue;
                    }
                    $has = SessionAnswer::where('game_session_id', $session->id)
                        ->where($questionKey, $questionId)
                        ->where('room_player_id', $leader->id)
                        ->exists();
                    if (!$has) {
                        $allTeamsAnswered = false;
                        break;
                    }
                }
            }
        } else {
            // Pause when every team has submitted an answer (team-based, not leader-based)
            $answeredRoomPlayerIds = SessionAnswer::where('game_session_id', $session->id)
                ->where($questionKey, $questionId)
                ->pluck('room_player_id');

            $answeredTeamIds = $answeredRoomPlayerIds->isEmpty()
                ? collect()
                : RoomPlayer::whereIn('id', $answeredRoomPlayerIds->toArray())
                    ->pluck('team_id')
                    ->filter()
                    ->unique()
                    ->values();

            $answeredTeamIds = $answeredTeamIds->reject(fn ($tid) => in_array((string) $tid, $surrenderedTeamIds, true))->values();
            $expectedTeams = $activeTeamIds->count();
            $answeredCount = $answeredTeamIds->count();

            $allTeamsAnswered = $expectedTeams > 0 && $answeredCount >= $expectedTeams;

            if ((int) $room->teams >= 2 && $answeredCount < 2) {
                $allTeamsAnswered = false;
            }
        }

        // Check if question time has elapsed (same limit as POST timeout / TV)
        $timedOut = $session->question_started_at
            ? $session->question_started_at->diffInSeconds(now()) >= self::QUESTION_TIME_LIMIT_SECONDS
            : false;

        $nextRound = $session->current_round + 1;
        $totalQuestions = count($questionIds);
        $nextQuestionAvailable = $nextRound <= $totalQuestions;

        if ($timedOut) {
            // Apply synthetic wrongs (−10 / life cost) before pause; pausing first would block applyPlayingQuestionTimeout.
            $session = $session->fresh(['room']);
            if ($session->status === 'playing') {
                $timeoutResult = $this->applyPlayingQuestionTimeout($session);
                if (!empty($timeoutResult['session_finished'])) {
                    return [
                        'correct' => $correct,
                        'scoreDelta' => $appliedScoreDelta,
                        'nextQuestionAvailable' => false,
                        'sessionFinished' => true,
                    ];
                }
            }
        } elseif ($allTeamsAnswered) {
            // Pause the game and show stats / animations
            $session->update(['status' => 'paused']);
            // Firebase: do not use only the submitter's row — clients may treat this as "everyone was correct".
            $everyAnswerCorrectThisQuestion = !SessionAnswer::query()
                ->where('game_session_id', $session->id)
                ->where($questionKey, $questionId)
                ->where('correct', false)
                ->exists();
            $this->firebaseSync->syncSessionPaused($session->fresh(), $everyAnswerCorrectThisQuestion);
            // If this was the last question, finish without requiring a separate nextQuestion call (TV often skips it).
            $this->advanceFinishedSessionIfLastQuestion($session->fresh());
        } else {
            // Keep playing this question, just sync updated scores
            $this->firebaseSync->syncScores($session->fresh());
        }

        return [
            'correct' => $correct,
            'scoreDelta' => $appliedScoreDelta,
            'nextQuestionAvailable' => $nextQuestionAvailable,
        ];
    }

    /**
     * Leader if set, otherwise first player on that team (so timeout still applies when join forgot isLeader).
     */
    private function getAnsweringRoomPlayerForTeam(Room $room, int $teamId): ?RoomPlayer
    {
        $room->loadMissing('roomPlayers');
        $players = $room->roomPlayers->where('team_id', $teamId)->values();
        if ($players->isEmpty()) {
            return null;
        }
        $leader = $players->first(fn ($rp) => (bool) $rp->is_leader);

        return $leader ?? $players->sortBy('id')->first();
    }

    /**
     * One answering player per non-surrendered team (for timeout / synthetic misses).
     *
     * @return array<int, RoomPlayer>
     */
    private function getAnsweringPlayersByTeamForTimeout(Room $room, array $surrenderedTeamIds): array
    {
        $room->loadMissing('roomPlayers');
        $targets = [];
        foreach ($room->roomPlayers->pluck('team_id')->unique()->filter() as $teamId) {
            if (in_array((string) $teamId, $surrenderedTeamIds, true)) {
                continue;
            }
            $player = $this->getAnsweringRoomPlayerForTeam($room, (int) $teamId);
            if ($player) {
                $targets[(int) $teamId] = $player;
            }
        }

        return $targets;
    }

    /**
     * Close the current question after the timer: LP synthetic wrongs (−10) or QG timeout rows (0 score); pause; Firebase sync.
     * Used by POST .../timeout and by POST .../next-question when the session was still "playing"
     * because no request had evaluated the deadline yet (missing answers).
     *
     * @param bool $inferMissingQuestionStartedAt When true (explicit POST .../timeout), if the server never
     *        received start-question, assume the window has elapsed so LP/QG penalties still apply. Do not use
     *        from submitAnswer auto-hooks (would false-trigger on early submits).
     *
     * @return array{applied: bool, reason: string, session: GameSession, all_leaders_answered?: bool}
     */
    public function applyPlayingQuestionTimeout(GameSession $session, bool $inferMissingQuestionStartedAt = false): array
    {
        $session->loadMissing('room');
        $wasPaused = $session->status === 'paused';
        if (!in_array($session->status, ['playing', 'paused'], true)) {
            return ['applied' => false, 'reason' => 'not_playing', 'session' => $session];
        }

        $limit = self::QUESTION_TIME_LIMIT_SECONDS;

        if ($inferMissingQuestionStartedAt && !$session->question_started_at) {
            $qids = $session->question_ids ?? [];
            if ($session->current_round >= 1 && count($qids) > 0) {
                $session->update(['question_started_at' => now()->subSeconds($limit)]);
                $session->refresh();
            }
        }

        if (!$session->question_started_at) {
            return ['applied' => false, 'reason' => 'question_not_started', 'session' => $session];
        }

        if ($session->question_started_at->diffInSeconds(now()) < $limit) {
            return ['applied' => false, 'reason' => 'timer_not_elapsed', 'session' => $session];
        }

        $room = $session->room()->with(['roomPlayers', 'subcategory.stage', 'customStage'])->first();
        if (!$room) {
            return ['applied' => false, 'reason' => 'no_room', 'session' => $session];
        }

        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);
        $targets = $this->getAnsweringPlayersByTeamForTimeout($room, $surrenderedTeamIds);
        $targetIds = collect($targets)->pluck('id')->values();

        $questionIds = $session->question_ids ?? [];
        $questionCount = count($questionIds);
        $roundIndex = $questionCount > 0 ? max(0, min($session->current_round - 1, $questionCount - 1)) : 0;
        $questionId = $questionIds[$roundIndex] ?? null;
        $isCustomRoom = (bool) $room->is_custom;
        $questionKey = $isCustomRoom ? 'custom_question_id' : 'question_id';

        $allLeadersAnswered = false;
        if ($questionId && $targetIds->isNotEmpty()) {
            $answeredCount = SessionAnswer::where('game_session_id', $session->id)
                ->where($questionKey, $questionId)
                ->whereIn('room_player_id', $targetIds)
                ->pluck('room_player_id')
                ->unique()
                ->count();
            $allLeadersAnswered = $answeredCount >= $targetIds->count();
        }

        $gameRoundNumber = $this->getGameRoundNumberForQuestionIndex($session, $roundIndex);
        $stageType = $this->getEffectiveStageType($room, $session, $gameRoundNumber);

        $penaltiesApplied = $this->applySyntheticTimeoutAnswersForUnansweredTeams(
            $session,
            $room,
            $targets,
            $questionId,
            $questionKey,
            $isCustomRoom,
            $stageType,
            $gameRoundNumber
        );

        if ($wasPaused) {
            if (!$penaltiesApplied) {
                return ['applied' => false, 'reason' => 'already_handled', 'session' => $session];
            }
            $session = $session->fresh(['sessionAnswers']);
            if ($this->attemptFinishLifePointsSession($session, $gameRoundNumber)) {
                return [
                    'applied' => true,
                    'reason' => 'life_points_finished',
                    'session' => $session->fresh(),
                    'all_leaders_answered' => $allLeadersAnswered,
                    'session_finished' => true,
                ];
            }
            $paused = $session->fresh();
            $everyAnswerCorrectThisQuestion = !SessionAnswer::query()
                ->where('game_session_id', $session->id)
                ->where($questionKey, $questionId)
                ->where('correct', false)
                ->exists();
            $this->firebaseSync->syncSessionPaused($paused, $everyAnswerCorrectThisQuestion);
            $this->advanceFinishedSessionIfLastQuestion($paused->fresh());

            return [
                'applied' => true,
                'reason' => 'timeout_penalties_recovered',
                'session' => $session->fresh(),
                'all_leaders_answered' => $allLeadersAnswered,
                'session_finished' => $session->fresh()->status === 'finished',
            ];
        }

        $session->refresh();
        if ($this->attemptFinishLifePointsSession($session->fresh(['sessionAnswers']), $gameRoundNumber)) {
            return [
                'applied' => true,
                'reason' => 'life_points_finished',
                'session' => $session->fresh(),
                'all_leaders_answered' => $allLeadersAnswered,
                'session_finished' => true,
            ];
        }

        $session->update(['status' => 'paused']);
        $paused = $session->fresh();
        $this->firebaseSync->syncSessionPaused($paused, false);
        $this->advanceFinishedSessionIfLastQuestion($paused->fresh());

        return [
            'applied' => true,
            'reason' => 'timeout',
            'session' => $session->fresh(),
            'all_leaders_answered' => $allLeadersAnswered,
            'session_finished' => $session->fresh()->status === 'finished',
        ];
    }

    /**
     * @param array<int, RoomPlayer> $targets
     */
    private function applySyntheticTimeoutAnswersForUnansweredTeams(
        GameSession $session,
        Room $room,
        array $targets,
        mixed $questionId,
        string $questionKey,
        bool $isCustomRoom,
        string $stageType,
        int $gameRoundNumber
    ): bool {
        if (!$questionId || !in_array($stageType, [Stage::TYPE_LIFE_POINTS, Stage::TYPE_QUESTIONS_GROUP], true)) {
            return false;
        }

        $applied = false;
        $snapshot = $session->fresh(['sessionAnswers']);
        foreach ($targets as $teamId => $player) {
            $alreadyAnswered = SessionAnswer::where('game_session_id', $session->id)
                ->where($questionKey, $questionId)
                ->where('room_player_id', $player->id)
                ->exists();
            if ($alreadyAnswered) {
                continue;
            }
            if ($stageType === Stage::TYPE_LIFE_POINTS) {
                $remaining = $this->getRemainingLivesForTeamInGameRound($snapshot, $room, (int) $teamId, $gameRoundNumber);
                if ($remaining <= 0) {
                    continue;
                }
                $appliedDelta = $this->applyTeamScoreDelta($player, -10);
                SessionAnswer::create([
                    'game_session_id' => $session->id,
                    'question_id' => $isCustomRoom ? null : $questionId,
                    'custom_question_id' => $isCustomRoom ? $questionId : null,
                    'room_player_id' => $player->id,
                    'answer_index' => 0,
                    'correct' => false,
                    'score_delta' => $appliedDelta,
                ]);
            } else {
                SessionAnswer::create([
                    'game_session_id' => $session->id,
                    'question_id' => $isCustomRoom ? null : $questionId,
                    'custom_question_id' => $isCustomRoom ? $questionId : null,
                    'room_player_id' => $player->id,
                    'answer_index' => 0,
                    'correct' => false,
                    'score_delta' => 0,
                ]);
            }
            $applied = true;
        }

        return $applied;
    }

    /**
     * True when the current question is the last one in the session schedule (next advance would exceed question_ids).
     */
    public function isOnLastScheduledQuestion(GameSession $session): bool
    {
        $questionIds = $session->question_ids ?? [];
        if ($questionIds === []) {
            return false;
        }

        return ((int) $session->current_round + 1) > count($questionIds);
    }

    /**
     * Close sessions left playing/paused after the final question (DB + Firebase) so profile stats stay accurate.
     */
    public function finalizeStuckSessionsForParticipant(User|Adventurer $user): void
    {
        $roomPlayerQuery = RoomPlayer::query()->whereHas('room.gameSessions', function ($q) {
            $q->whereIn('status', ['playing', 'paused']);
        });

        if ($user instanceof Adventurer) {
            $roomPlayerQuery->where('adventurer_id', $user->id);
        } else {
            $roomPlayerQuery->where('user_id', $user->id);
        }

        $roomPlayers = $roomPlayerQuery->with([
            'room.gameSessions' => fn ($q) => $q->whereIn('status', ['playing', 'paused'])->latest('id')->limit(1),
        ])->get();

        foreach ($roomPlayers->unique('room_id') as $rp) {
            $session = $rp->room->gameSessions->first();
            if (! $session || ! $this->isOnLastScheduledQuestion($session)) {
                continue;
            }

            if ($session->status === 'playing') {
                $timerElapsed = $session->question_started_at
                    && $session->question_started_at->diffInSeconds(now()) >= self::QUESTION_TIME_LIMIT_SECONDS;
                if (! $timerElapsed) {
                    continue;
                }
                $this->applyPlayingQuestionTimeout($session->fresh(), true);
                $session = $session->fresh();
            }

            if ($session->status === 'paused') {
                $this->advanceFinishedSessionIfLastQuestion($session);
            }
        }
    }

    /**
     * When the final question just paused, advance immediately so status becomes finished and custom usage is recorded.
     * Intermediate rounds still rely on POST .../next-question after TV animations.
     */
    public function advanceFinishedSessionIfLastQuestion(GameSession $session): void
    {
        if ($session->status !== 'paused' || ! $this->isOnLastScheduledQuestion($session)) {
            return;
        }
        $this->advanceToNextQuestion($session->fresh());
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

        $started = $session->fresh();
        $this->customContentUsage->recordCustomQuestionShown($started);

        // Re-sync question + teams + stage, now with questionStartedAt set
        $this->firebaseSync->syncSessionStart($started);

        return ['ok' => true, 'reason' => 'started'];
    }

    public function advanceToNextQuestion(GameSession $session): array
    {
        if ($session->status !== 'paused') {
            return ['finished' => false, 'invalid' => true];
        }

        $questionIds = $session->question_ids ?? [];
        $nextRound = (int) $session->current_round + 1;
        $total = count($questionIds);

        if ($total === 0) {
            return ['finished' => false, 'invalid' => true];
        }

        // Load room, stage, and players for life-points calculations
        $session->load('room.subcategory.stage', 'room.roomPlayers', 'sessionAnswers');
        $room = $session->room;
        $completedGameRound = $this->getGameRoundNumberForQuestionIndex($session, max(0, (int) $session->current_round - 1));
        $stageTypeForCompletedRound = $this->getEffectiveStageType($room, $session, $completedGameRound);
        $isLifePointsStage = $stageTypeForCompletedRound === Stage::TYPE_LIFE_POINTS;
        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);

        $winnerTeamIds = null;

        if ($isLifePointsStage) {
            // Lives per team for this game round only (wrong answers from other rounds do not apply)
            $byTeam = $room->roomPlayers->groupBy('team_id');
            $lifeByTeam = [];
            foreach ($byTeam as $teamId => $_players) {
                $lifePoints = $this->getRemainingLivesForTeamInGameRound($session, $room, (int) $teamId, $completedGameRound);
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
                    ->reject(fn ($_, $teamId) => in_array((string) $teamId, $surrenderedTeamIds, true))
                    ->filter(fn ($life) => $life === $maxLife)
                    ->keys()
                    ->values()
                    ->all();

                $session->update([
                    'status' => 'finished',
                    'current_round' => min($nextRound, $total + 1),
                    'winner_team_ids' => array_values(array_map('strval', $winnerTeamIds)),
                ]);
                $session->room->update(['status' => 'finished']);
                $finished = $session->fresh();
                $this->updatePointsForFinishedSession($finished);
                $this->recordLastRoundStageTail($finished);
                $this->firebaseSync->syncSessionEnd($finished, $winnerTeamIds);
                return ['finished' => true, 'round' => null];
            }
        } else {
            // Non life-points stage: finish when only one active team left (others surrendered) or questions exhausted
            $activeTeamIds = $room->roomPlayers->pluck('team_id')->unique()->filter()->reject(
                fn($tid) => in_array((string) $tid, $surrenderedTeamIds, true)
            )->values();
            if ($activeTeamIds->count() === 1) {
                $winnerTeamIds = $activeTeamIds->map(fn($id) => (string) $id)->all();
                $session->update([
                    'status' => 'finished',
                    'current_round' => $nextRound,
                    'winner_team_ids' => $winnerTeamIds,
                ]);
                $session->room->update(['status' => 'finished']);
                $finished = $session->fresh();
                $this->updatePointsForFinishedSession($finished);
                $this->recordLastRoundStageTail($finished);
                $this->firebaseSync->syncSessionEnd($finished, $winnerTeamIds);
                return ['finished' => true, 'round' => null];
            }
            if ($nextRound > $total) {
                $winnerTeamIds = $this->resolveWinnerTeamIds($session);
                $session->update([
                    'status' => 'finished',
                    'current_round' => $nextRound,
                    'winner_team_ids' => $winnerTeamIds,
                ]);
                $session->room->update(['status' => 'finished']);
                $finished = $session->fresh();
                $this->updatePointsForFinishedSession($finished);
                $this->recordLastRoundStageTail($finished);
                $this->firebaseSync->syncSessionEnd($finished, $winnerTeamIds);
                return ['finished' => true, 'round' => null];
            }
        }

        if ($nextRound > $total) {
            $winnerTeamIds = $this->resolveWinnerTeamIds($session);
            $session->update([
                'status' => 'finished',
                'current_round' => $nextRound,
                'winner_team_ids' => array_values(array_map('strval', $winnerTeamIds)),
            ]);
            $session->room->update(['status' => 'finished']);
            $finished = $session->fresh();
            $this->updatePointsForFinishedSession($finished);
            $this->recordLastRoundStageTail($finished);
            $this->firebaseSync->syncSessionEnd($finished, $winnerTeamIds);

            return ['finished' => true, 'round' => null];
        }

        $session->update([
            'status' => 'playing',
            'current_round' => $nextRound,
            'question_started_at' => now(),
        ]);
        $advanced = $session->fresh();
        $this->customContentUsage->recordCustomQuestionShown($advanced);
        $this->firebaseSync->syncSessionStart($advanced);
        return ['finished' => false, 'round' => $nextRound];
    }

    public function updatePointsForFinishedSession(GameSession $session): void
    {
        $session->load('room.roomPlayers.adventurer', 'room.roomPlayers.user');

        if (empty($session->winner_team_ids)) {
            $session->update(['winner_team_ids' => $this->resolveWinnerTeamIds($session)]);
            $session->refresh();
        }

        $winnerTeamIds = array_map('strval', $session->winner_team_ids ?? []);
        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);
        $isDrawAmongWinners = count($winnerTeamIds) > 1;

        foreach ($session->room->roomPlayers as $rp) {
            $player = $rp->adventurer ?? $rp->user;
            if (!$player) {
                continue;
            }

            $teamId = (string) $rp->team_id;
            if (in_array($teamId, $surrenderedTeamIds, true)) {
                $delta = -1;
            } elseif ($winnerTeamIds !== [] && in_array($teamId, $winnerTeamIds, true)) {
                $delta = $isDrawAmongWinners ? 0 : 1;
            } elseif ($winnerTeamIds !== []) {
                $delta = -1;
            } else {
                $teamScores = $session->room->roomPlayers->groupBy('team_id')
                    ->map(fn ($players) => max(0, (int) $players->sum('score')));
                $maxScore = $teamScores->max();
                $teamsAtMaxScore = $teamScores->filter(fn ($s) => $s === $maxScore);
                $teamScore = $teamScores[$teamId] ?? 0;
                $delta = $teamsAtMaxScore->count() > 1
                    ? ($teamScore === $maxScore ? 0 : -1)
                    : ($teamScore === $maxScore ? 1 : -1);
            }

            $player->increment('points', $delta);
        }
    }

    /**
     * Persist last round's stage type per creator + subcategory so the next session can avoid repeating it on round 1.
     * Only for normal rooms whose subcategory does not pin a single stage.
     */
    public function recordLastRoundStageTail(GameSession $session): void
    {
        $session->loadMissing('room.subcategory');
        $room = $session->room;
        if (! $room || (bool) $room->is_custom) {
            return;
        }

        $subcategory = $room->subcategory;
        if (! $subcategory || ($subcategory->use_stage && $subcategory->stage_id)) {
            return;
        }

        $questionIds = $session->question_ids ?? [];
        if ($questionIds === []) {
            return;
        }

        $ownerType = null;
        $ownerId = null;
        if ($room->created_by_adventurer_id) {
            $ownerType = 'adventurer';
            $ownerId = (int) $room->created_by_adventurer_id;
        } elseif ($room->created_by) {
            $ownerType = 'user';
            $ownerId = (int) $room->created_by;
        }

        if ($ownerType === null || $ownerId === null) {
            return;
        }

        $lastRoundNumber = $this->getLastRoundNumberForTail($session);
        $stageType = $this->getEffectiveStageType($room, $session, $lastRoundNumber);

        CreatorSubcategoryStageTail::query()->updateOrCreate(
            [
                'subcategory_id' => (int) $room->subcategory_id,
                'creator_owner_type' => $ownerType,
                'creator_owner_id' => $ownerId,
            ],
            ['last_round_stage_type' => $stageType]
        );
    }

    /**
     * Round number (1-based) that contains the final question in the schedule.
     */
    private function getLastRoundNumberForTail(GameSession $session): int
    {
        $questionIds = $session->question_ids ?? [];
        if ($questionIds === []) {
            return 1;
        }

        $session->loadMissing('room');
        $mock = new GameSession();
        $mock->forceFill([
            'question_ids' => $questionIds,
            'round_stage_ids' => $session->round_stage_ids,
            'current_round' => count($questionIds),
        ]);
        $mock->setRelation('room', $session->room);

        return $this->getCurrentRoundNumber($mock);
    }

    private function getTailForRoom(Room $room): ?CreatorSubcategoryStageTail
    {
        if (! $room->subcategory_id) {
            return null;
        }

        if ($room->created_by_adventurer_id) {
            return CreatorSubcategoryStageTail::query()
                ->where('subcategory_id', $room->subcategory_id)
                ->where('creator_owner_type', 'adventurer')
                ->where('creator_owner_id', $room->created_by_adventurer_id)
                ->first();
        }

        if ($room->created_by) {
            return CreatorSubcategoryStageTail::query()
                ->where('subcategory_id', $room->subcategory_id)
                ->where('creator_owner_type', 'user')
                ->where('creator_owner_id', $room->created_by)
                ->first();
        }

        return null;
    }
}
