<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\Room;
use App\Models\Stage;
use App\Models\TvDisplay;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;

class FirebaseGameSyncService
{
    private ?Database $database = null;

    private function getDatabase(): ?Database
    {
        if ($this->database !== null) {
            return $this->database;
        }
        $url = config('firebase.projects.app.database.url');
        if (!$url) {
            Log::info('Firebase sync skipped: FIREBASE_DATABASE_URL not set');
            return null;
        }
        try {
            $this->database = app(Database::class);
            return $this->database;
        } catch (\Throwable $e) {
            Log::warning('Firebase database not available', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function syncRoom(Room $room): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        Log::info('Firebase syncRoom starting', ['room_id' => $room->id]);
        try {
            $room->load(['type', 'category', 'subcategory', 'customCategory', 'roomPlayers.user', 'roomPlayers.adventurer']);
            $teams = (int) $room->teams;
            $players = $room->roomPlayers->keyBy('id')->map(fn ($rp) => [
                'userId' => (string) ($rp->adventurer_id ?? $rp->user_id),
                'userName' => ($rp->adventurer ?? $rp->user)?->name ?? 'Player',
                'teamId' => (string) $rp->team_id,
                'teamCode' => 'K' . $rp->team_id,
                'isLeader' => (bool) $rp->is_leader,
            ])->toArray();

            $tvDisplay = TvDisplay::where('room_id', $room->id)->where('status', 'linked')->first();
            $data = [
                'roomId' => (string) $room->id,
                'code' => $room->code,
                'status' => $room->status,
                'isCustom' => (bool) $room->is_custom,
                'tvDisplayId' => $tvDisplay ? (string) $tvDisplay->id : null,
                'type_id' => (int) $room->type_id,
                'category_id' => (int) $room->category_id,
                'subcategory_id' => (int) $room->subcategory_id,
                'custom_category_id' => $room->custom_category_id ? (int) $room->custom_category_id : null,
                'custom_category_name' => $room->customCategory?->name,
                'rounds' => (int) $room->rounds,
                'questionsCount' => (int) ($room->questions_count ?? $room->rounds ?? 0),
                'selectedQuestionsCount' => (int) ($room->questions_count ?? 0),
                'lifePoints' => (int) ($room->life_points ?? 5),
                'teams' => $teams,
                'maxPlayers' => (int) $room->players,
                'joinedCount' => $room->roomPlayers->count(),
                'players' => $players,
                'createdAt' => $room->created_at?->timestamp ?? time(),
            ];

            $db->getReference('rooms/' . $room->id)->set($data);
            Log::info('Firebase syncRoom success', ['room_id' => $room->id]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncRoom failed', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function syncRoomPlayers(Room $room): void
    {
        $this->syncRoom($room);
    }

    public function syncTvDisplay(TvDisplay $display): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $sessionId = null;
            if ($display->room_id) {
                $session = $display->room->gameSessions()
                    ->whereIn('status', ['waiting', 'playing', 'starting', 'paused'])
                    ->latest()
                    ->first();
                $sessionId = $session ? (string) $session->id : null;
            }

            $data = [
                'displayId' => (string) $display->id,
                'linked' => $display->status === TvDisplay::STATUS_LINKED,
                'roomId' => $display->room_id ? (string) $display->room_id : null,
                'sessionId' => $sessionId,
                'status' => $display->status,
                'linkedAt' => $display->status === TvDisplay::STATUS_LINKED ? (int) round(microtime(true) * 1000) : null,
            ];

            $db->getReference('tv_displays/' . $display->id)->set($data);
            Log::info('Firebase syncTvDisplay success', ['display_id' => $display->id]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncTvDisplay failed', [
                'display_id' => $display->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function syncSessionStarting(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups');
            $teams = $this->buildTeamsData($session);
            $stage = $this->buildStageData($session->room, $session);
            $round = $this->buildRoundMeta($session);

            $data = [
                'roomId' => (string) $session->room_id,
                'sessionId' => (string) $session->id,
                'isCustom' => (bool) $session->room->is_custom,
                'selectedQuestionsCount' => (int) ($session->room->questions_count ?? 0),
                'lifePoints' => (int) ($session->room->life_points ?? 5),
                'status' => 'starting',
                'currentRound' => 0,
                'startTimerEndsAt' => $session->start_timer_ends_at
                    ? (int) ($session->start_timer_ends_at->timestamp * 1000)
                    : null,
                'remainingQuestionsCount' => count($session->question_ids ?? []),
                'question' => null,
                'round' => $round,
                'teams' => $teams,
                'stage' => $stage,
            ];

            $db->getReference('sessions/' . $session->id)->set($data);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncSessionStarting failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncSessionStart(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups');
            $question = $this->buildQuestionData($session);
            $teams = $this->buildTeamsData($session);
            $stage = $this->buildStageData($session->room, $session);

            $questionIds = $session->question_ids ?? [];
            $remainingCount = max(0, count($questionIds) - $session->current_round);
            $round = $this->buildRoundMeta($session);

            $data = [
                'roomId' => (string) $session->room_id,
                'sessionId' => (string) $session->id,
                'isCustom' => (bool) $session->room->is_custom,
                'selectedQuestionsCount' => (int) ($session->room->questions_count ?? 0),
                'lifePoints' => (int) ($session->room->life_points ?? 5),
                'status' => $session->status,
                'currentRound' => (int) $session->current_round,
                'remainingQuestionsCount' => $remainingCount,
                'questionStartedAt' => (int) round(microtime(true) * 1000),
                'question' => $question,
                'round' => $round,
                'teams' => $teams,
                'stage' => $stage,
            ];

            $db->getReference('sessions/' . $session->id)->set($data);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncSessionStart failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncQuestion(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.subcategory.stage.questionGroups');
            $question = $this->buildQuestionData($session);
            $teams = $this->buildTeamsData($session);
            $stage = $this->buildStageData($session->room, $session);
            $questionIds = $session->question_ids ?? [];
            $remainingCount = max(0, count($questionIds) - $session->current_round);
            $round = $this->buildRoundMeta($session);

            $db->getReference('sessions/' . $session->id)->update([
                'currentRound' => (int) $session->current_round,
                'remainingQuestionsCount' => $remainingCount,
                'questionStartedAt' => (int) round(microtime(true) * 1000),
                'question' => $question,
                'round' => $round,
                'teams' => $teams,
                'stage' => $stage,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncQuestion failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncScores(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups');
            $teams = $this->buildTeamsData($session);
            $stage = $this->buildStageData($session->room, $session);
            $db->getReference('sessions/' . $session->id)->update(['teams' => $teams, 'stage' => $stage]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncScores failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncSessionEnd(GameSession $session, ?array $winnerIds = null): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups');
            $teams = $this->buildTeamsData($session);
            $stage = $this->buildStageData($session->room, $session);

            $data = [
                'status' => $session->status,
                'isCustom' => (bool) $session->room->is_custom,
                'selectedQuestionsCount' => (int) ($session->room->questions_count ?? 0),
                'lifePoints' => (int) ($session->room->life_points ?? 5),
                'remainingQuestionsCount' => 0,
                'teams' => $teams,
                'stage' => $stage,
                'sessionEndedAt' => (int) round(microtime(true) * 1000),
            ];
            if ($winnerIds !== null) {
                $data['winnerIds'] = $winnerIds;
            }

            $db->getReference('sessions/' . $session->id)->update($data);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncSessionEnd failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function getStageDataForRoom(Room $room, ?GameSession $session = null): ?array
    {
        return $this->buildStageData($room, $session);
    }

    private function buildStageData(Room $room, ?GameSession $session = null): ?array
    {
        if ((bool) $room->is_custom) {
            $gameService = app(GameService::class);
            $currentRoundNumber = $session ? $gameService->getCurrentRoundNumber($session) : 1;
            $stage = $gameService->getEffectiveStageForRound($room, $session, $currentRoundNumber);
            if ($stage) {
                $stage->load('questionGroups');
                $questionGroups = $stage->questionGroups->sortBy('sort_order')->values()->map(function ($g) {
                    return [
                        'id' => (int) $g->id,
                        'sort_order' => (int) $g->sort_order,
                        'start_video' => $g->getFirstMediaUrl('start_video'),
                        'end_video' => $g->getFirstMediaUrl('end_video'),
                        'correct_answer_video' => $g->getFirstMediaUrl('correct_answer_video'),
                        'wrong_answer_video' => $g->getFirstMediaUrl('wrong_answer_video'),
                    ];
                })->all();

                return [
                    'id' => (int) $stage->id,
                    'name' => $stage->name,
                    'stage_type' => Stage::TYPE_LIFE_POINTS,
                    'selected_stage_type' => $stage->stage_type,
                    'question_groups_count' => (int) ($stage->question_groups_count ?? 0),
                    'number_of_questions' => (int) ($room->questions_count ?? 0),
                    'life_points_per_question' => (float) ($room->life_points ?? 5),
                    'start_video' => $stage->getFirstMediaUrl('start_video'),
                    'end_video' => $stage->getFirstMediaUrl('end_video'),
                    'lunch_video' => $stage->getFirstMediaUrl('lunch_video'),
                    'correct_answer_video' => $stage->getFirstMediaUrl('correct_answer_video'),
                    'wrong_answer_video' => $stage->getFirstMediaUrl('wrong_answer_video'),
                    'question_groups' => $questionGroups,
                ];
            }

            return [
                'id' => null,
                'name' => $room->customCategory?->name ?? 'Custom',
                'stage_type' => Stage::TYPE_LIFE_POINTS,
                'selected_stage_type' => null,
                'question_groups_count' => 1,
                'number_of_questions' => (int) ($room->questions_count ?? 0),
                'life_points_per_question' => (float) ($room->life_points ?? 5),
                'start_video' => null,
                'end_video' => null,
                'lunch_video' => null,
                'correct_answer_video' => null,
                'wrong_answer_video' => null,
                'question_groups' => [],
            ];
        }

        $room->loadMissing('subcategory.stage');
        $subcategory = $room->subcategory;
        if (!$subcategory) {
            return null;
        }

        // Case 1: subcategory is explicitly linked to a stage — return stage with video links
        if ($subcategory->use_stage && $subcategory->stage_id && $subcategory->stage) {
            $stage = $subcategory->stage;
            $stage->load('questionGroups');

            $questionGroups = $stage->questionGroups->sortBy('sort_order')->values()->map(function ($g) {
                return [
                    'id' => (int) $g->id,
                    'sort_order' => (int) $g->sort_order,
                    'start_video' => $g->getFirstMediaUrl('start_video'),
                    'end_video' => $g->getFirstMediaUrl('end_video'),
                    'correct_answer_video' => $g->getFirstMediaUrl('correct_answer_video'),
                    'wrong_answer_video' => $g->getFirstMediaUrl('wrong_answer_video'),
                ];
            })->all();

            return [
                'id' => (int) $stage->id,
                'name' => $stage->name,
                'stage_type' => $stage->stage_type,
                'question_groups_count' => (int) ($stage->question_groups_count ?? 0),
                'number_of_questions' => (int) ($stage->number_of_questions ?? 0),
                'life_points_per_question' => $stage->life_points_per_question !== null ? (float) $stage->life_points_per_question : null,
                'start_video' => $stage->getFirstMediaUrl('start_video'),
                'end_video' => $stage->getFirstMediaUrl('end_video'),
                'lunch_video' => $stage->getFirstMediaUrl('lunch_video'),
                'correct_answer_video' => $stage->getFirstMediaUrl('correct_answer_video'),
                'wrong_answer_video' => $stage->getFirstMediaUrl('wrong_answer_video'),
                'question_groups' => $questionGroups,
            ];
        }

        // Case 2: no stage linked -> use random stage per round (from round_stage_ids) if available
        $gameService = app(GameService::class);
        $currentRoundNumber = $session ? $gameService->getCurrentRoundNumber($session) : 1;
        $stage = $gameService->getEffectiveStageForRound($room, $session, $currentRoundNumber);

        if ($stage) {
            $stage->load('questionGroups');
            $questionGroups = $stage->questionGroups->sortBy('sort_order')->values()->map(function ($g) {
                return [
                    'id' => (int) $g->id,
                    'sort_order' => (int) $g->sort_order,
                    'start_video' => $g->getFirstMediaUrl('start_video'),
                    'end_video' => $g->getFirstMediaUrl('end_video'),
                    'correct_answer_video' => $g->getFirstMediaUrl('correct_answer_video'),
                    'wrong_answer_video' => $g->getFirstMediaUrl('wrong_answer_video'),
                ];
            })->all();

            return [
                'id' => (int) $stage->id,
                'name' => $stage->name,
                'stage_type' => $stage->stage_type,
                'question_groups_count' => (int) ($stage->question_groups_count ?? 0),
                'number_of_questions' => (int) ($stage->number_of_questions ?? 0),
                'life_points_per_question' => $stage->life_points_per_question !== null ? (float) $stage->life_points_per_question : null,
                'start_video' => $stage->getFirstMediaUrl('start_video'),
                'end_video' => $stage->getFirstMediaUrl('end_video'),
                'lunch_video' => $stage->getFirstMediaUrl('lunch_video'),
                'correct_answer_video' => $stage->getFirstMediaUrl('correct_answer_video'),
                'wrong_answer_video' => $stage->getFirstMediaUrl('wrong_answer_video'),
                'question_groups' => $questionGroups,
            ];
        }

        // Fallback when no stages exist in DB: virtual stage
        $effectiveStageType = $gameService->getEffectiveStageType($room, $session, $currentRoundNumber);
        $questionsCount = (int) ($room->questions_count ?? $room->rounds ?? 0);

        return [
            'id' => null,
            'name' => $subcategory->name,
            'stage_type' => $effectiveStageType,
            'question_groups_count' => 1,
            'number_of_questions' => $questionsCount,
            'life_points_per_question' => $effectiveStageType === Stage::TYPE_LIFE_POINTS ? 1 : null,
            'start_video' => null,
            'end_video' => null,
            'lunch_video' => null,
            'correct_answer_video' => null,
            'wrong_answer_video' => null,
            'question_groups' => [
                [
                    'id' => 1,
                    'sort_order' => 0,
                    'start_video' => null,
                    'end_video' => null,
                    'correct_answer_video' => null,
                    'wrong_answer_video' => null,
                ],
            ],
        ];
    }

    private function buildRoundMeta(GameSession $session): array
    {
        $gameService = app(GameService::class);
        $room = $session->room;

        $roundNumber = $gameService->getCurrentRoundNumber($session);
        [$startIndex, $endIndex] = $gameService->getRoundQuestionRange($session, $roundNumber);

        $roundQuestionsCount = $endIndex >= $startIndex ? ($endIndex - $startIndex + 1) : 0;
        $currentQuestionIndex = max(0, (int) $session->current_round - 1);
        $currentQuestionInRound = $roundQuestionsCount > 0
            ? ($currentQuestionIndex - $startIndex + 1)
            : 0;

        $roundType = (bool) $room->is_custom
            ? Stage::TYPE_LIFE_POINTS
            : $gameService->getEffectiveStageType($room, $session, $roundNumber);

        return [
            // Round number is 1-based.
            'roundNumber' => (int) $roundNumber,
            // Round type for UI switching (questions_group vs life_points).
            'roundType' => $roundType,
            // Global indices inside `question_ids` (0-based).
            'roundStartQuestionIndex' => (int) $startIndex,
            'roundEndQuestionIndex' => (int) $endIndex,
            // Convenience for rendering (1-based).
            'roundStartQuestionNumber' => (int) $startIndex + 1,
            'roundEndQuestionNumber' => (int) $endIndex + 1,
            'roundQuestionsCount' => (int) $roundQuestionsCount,
            'currentQuestionInRound' => (int) $currentQuestionInRound,
            // Indicators for round boundary.
            'isRoundStart' => $currentQuestionInRound === 1,
            'isRoundEnd' => $roundQuestionsCount > 0 && $currentQuestionInRound === $roundQuestionsCount,
        ];
    }

    private function buildQuestionData(GameSession $session): ?array
    {
        $gameService = app(GameService::class);
        return $gameService->getCurrentQuestion($session);
    }

    private function buildTeamsData(GameSession $session): array
    {
        return $this->buildTeamsDataWithStats($session, false);
    }

    private function buildTeamsDataWithStats(GameSession $session, bool $includeAnswerStats): array
    {
        $room = $session->room;
        $room->load('roomPlayers.user', 'roomPlayers.adventurer', 'subcategory.stage');
        $byTeam = $room->roomPlayers->groupBy('team_id');

        $gameService = app(GameService::class);
        $stageType = $gameService->getEffectiveStageType($room, $session, $gameService->getCurrentRoundNumber($session));
        $isLifePointsStage = $stageType === Stage::TYPE_LIFE_POINTS;

        $correctWrongByRoomPlayer = [];
        if ($includeAnswerStats || $isLifePointsStage) {
            $answers = $session->sessionAnswers;
            foreach ($answers as $a) {
                $id = $a->room_player_id;
                if (!isset($correctWrongByRoomPlayer[$id])) {
                    $correctWrongByRoomPlayer[$id] = ['correct' => 0, 'wrong' => 0];
                }
                if ($a->correct) {
                    $correctWrongByRoomPlayer[$id]['correct']++;
                } else {
                    $correctWrongByRoomPlayer[$id]['wrong']++;
                }
            }
        }

        $teams = [];
        foreach ($byTeam as $teamId => $players) {
            $first = $players->first();
            $playerList = $players->map(fn ($rp) => [
                'userId' => (string) ($rp->adventurer_id ?? $rp->user_id),
                'userName' => ($rp->adventurer ?? $rp->user)?->name ?? 'Player',
                'isLeader' => (bool) $rp->is_leader,
                'tvViewJoined' => $rp->tv_view_joined_at !== null,
            ])->values()->all();

            $correctCount = 0;
            $wrongCount = 0;
            if ($includeAnswerStats || $isLifePointsStage) {
                foreach ($players as $rp) {
                    $stats = $correctWrongByRoomPlayer[$rp->id] ?? ['correct' => 0, 'wrong' => 0];
                    $correctCount += $stats['correct'];
                    $wrongCount += $stats['wrong'];
                }
            }

            $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);
            $isSurrendered = in_array((string) $teamId, $surrenderedTeamIds, true);

            $teamData = [
                'id' => (string) $teamId,
                'name' => ($first->adventurer ?? $first->user)?->name ?? 'الفريق ' . $teamId,
                'score' => (int) $players->sum('score'),
                'teamCode' => 'K' . $teamId,
                'players' => $playerList,
                'surrendered' => $isSurrendered,
            ];
            if ($includeAnswerStats) {
                $teamData['correctCount'] = $correctCount;
                $teamData['wrongCount'] = $wrongCount;
            }
            if ($isLifePointsStage) {
                $initialLives = max(1, (int) ($room->life_points ?? 5));
                $lifePoints = max(0, $initialLives - $wrongCount);
                $teamData['lifePoints'] = $lifePoints;
                $teamData['isEliminated'] = $lifePoints <= 0 || $isSurrendered;
            } else {
                $teamData['isEliminated'] = $isSurrendered;
            }
            $teams[(string) $teamId] = $teamData;
        }
        return $teams;
    }

    public function syncSessionPaused(GameSession $session, bool $lastAnswerCorrect): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups', 'sessionAnswers');
            $question = $this->buildQuestionData($session);
            $teams = $this->buildTeamsDataWithStats($session, true);
            $stage = $this->buildStageData($session->room, $session);
            $round = $this->buildRoundMeta($session);

            $questionIds = $session->question_ids ?? [];
            $remainingCount = max(0, count($questionIds) - $session->current_round);

            $totalCorrect = $session->sessionAnswers->where('correct', true)->count();
            $totalWrong = $session->sessionAnswers->where('correct', false)->count();

            $data = [
                'roomId' => (string) $session->room_id,
                'sessionId' => (string) $session->id,
                'isCustom' => (bool) $session->room->is_custom,
                'selectedQuestionsCount' => (int) ($session->room->questions_count ?? 0),
                'lifePoints' => (int) ($session->room->life_points ?? 5),
                'status' => 'paused',
                'currentRound' => (int) $session->current_round,
                'remainingQuestionsCount' => $remainingCount,
                'question' => $question,
                'lastAnswerCorrect' => $lastAnswerCorrect,
                'round' => $round,
                'teams' => $teams,
                'stats' => [
                    'totalCorrect' => $totalCorrect,
                    'totalWrong' => $totalWrong,
                    'answeredCount' => $totalCorrect + $totalWrong,
                ],
                'stage' => $stage,
                'pausedAt' => (int) round(microtime(true) * 1000),
            ];

            $db->getReference('sessions/' . $session->id)->update($data);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncSessionPaused failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }
}
