<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\CreateCustomRoomRequest;
use App\Http\Requests\Game\CreateRoomRequest;
use App\Http\Requests\Game\JoinRoomRequest;
use App\Http\Requests\Game\LinkTvRequest;
use App\Http\Requests\Game\SubmitAnswerRequest;
use App\Http\Requests\Game\ValidateCodeRequest;
use App\Models\Adventurer;
use App\Models\Category;
use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use App\Models\CustomStage;
use App\Models\GameSession;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\Subcategory;
use App\Models\TvDisplay;
use App\Models\Type;
use App\Models\User;
use App\Services\FirebaseGameSyncService;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(
        protected GameService $gameService,
        protected FirebaseGameSyncService $firebaseSync
    ) {
    }

    public function getQuestionTypes(): JsonResponse
    {
        $types = Type::where('status', true)->withCount('categories')->orderBy('id')->get()->map(fn($t) => [
            'id' => (string) $t->id,
            'name_ar' => $t->name,
            'slug' => \Illuminate\Support\Str::slug($t->name),
            'categories_count' => (int) ($t->categories_count ?? 0),
            'image' => $t->getFirstMediaUrl(),
        ]);

        return ApiResponse::success($types->values()->all());
    }

    public function getCategories(): JsonResponse
    {
        $questionTypeId = request('questionTypeId');
        $query = Category::where('status', true);
        if ($questionTypeId !== null) {
            $query->where('type_id', $questionTypeId);
        }
        $items = $query->with('type')->withCount('questions')->orderBy('id')->get()->map(fn($c) => [
            'id' => (string) $c->id,
            'name_ar' => $c->name,
            'slug' => \Illuminate\Support\Str::slug($c->name),
            'type_name' => $c->type?->name,
            'questions_count' => (int) ($c->questions_count ?? 0),
            'image' => $c->getFirstMediaUrl(),
        ]);

        return ApiResponse::success($items->values()->all());
    }

    public function getSubcategories(): JsonResponse
    {
        $categoryId = request('categoryId');
        $query = Subcategory::where('status', true);
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
        $items = $query->with('category')->withCount('questions')->orderBy('id')->get()->map(fn($s) => [
            'id' => (string) $s->id,
            'name_ar' => $s->name,
            'slug' => \Illuminate\Support\Str::slug($s->name),
            'category_name' => $s->category?->name,
            'questions_count' => (int) ($s->questions_count ?? 0),
            'image' => $s->getFirstMediaUrl(),
        ]);

        return ApiResponse::success($items->values()->all());
    }

    public function validateCode(ValidateCodeRequest $request): JsonResponse
    {
        $room = Room::withCount('roomPlayers')
            ->with(['type', 'category', 'subcategory'])
            ->where('code', $request->input('code'))
            ->first();

        if (!$room || ($room->expires_at && $room->expires_at->isPast())) {
            return ApiResponse::error('رمز المغامرة غير صحيح أو منتهي', 400);
        }

        $teams = (int) $room->teams;
        $data = [
            'valid' => true,
            'roomId' => (string) $room->id,
            'code' => $room->code,
            'gameTitle' => $room->title ?? $room->type?->name ?? '',
            'rounds' => (int) $room->rounds,
            'teams' => $teams,
            'players' => (int) $room->players,
            'playersPerTeam' => $teams > 0 ? (int) ($room->players / $teams) : 0,
            'questionType' => $room->type?->name ?? '',
            'questionCategory' => $room->category?->name ?? '',
            'questionSubCategory' => $room->subcategory?->name ?? '',
        ];

        return ApiResponse::success($data);
    }

    public function createRoom(CreateRoomRequest $request): JsonResponse
    {
        $user = auth()->user();

        // IMPORTANT:
        // - `rounds` is the number of rounds (split of the full questions list)
        // - `questionsCount` is the total number of questions in the session
        $questionsCountInput = $request->input('questionsCount');
        $roundsCountInput = $request->input('rounds');

        if ($questionsCountInput !== null) {
            $questionsCount = (int) $questionsCountInput;
            $rounds = (int) ($roundsCountInput ?? 1);
        } else {
            // Legacy fallback: previous code used `rounds` as total questions.
            $questionsCount = (int) ($roundsCountInput ?? 5);
            $rounds = 1;
        }

        // 1 round = 1 consumed available session (minimum 1 session per room).
        $sessionCost = max(1, $rounds);
        if (($user->available_sessions ?? 0) < $sessionCost) {
            return ApiResponse::error('لا توجد جلسات لعبة متاحة. كل جولة إضافية تحتاج جلسة واحدة. يرجى شراء حزمة للاستمرار.', 403);
        }

        $code = $this->gameService->generateRoomCode();
        $teams = (int) $request->input('teams', 2);
        $playersPerTeam = (int) $request->input('players', 2);
        $totalPlayers = $playersPerTeam * $teams;

        $roomData = [
            'code' => $code,
            'type_id' => $request->input('questionType'),
            'category_id' => $request->input('mainCategoryId'),
            'subcategory_id' => $request->input('subCategoryId'),
            'title' => $request->input('title'),
            'rounds' => $rounds,
            'questions_count' => $questionsCount,
            'teams' => $teams,
            'players' => $totalPlayers,
            'expires_at' => now()->addHours(24),
        ];
        if ($user instanceof Adventurer) {
            $roomData['created_by_adventurer_id'] = $user->id;
        } else {
            $roomData['created_by'] = $user->id;
        }
        $room = Room::create($roomData);

        $user->decrement('available_sessions', $sessionCost);

        $creatorPlayer = $this->attachCreatorAsK1Leader($room, $user);

        $this->firebaseSync->syncRoom($room);
        $this->firebaseSync->syncRoomPlayers($room->fresh());

        $teamsData = [];
        for ($i = 1; $i <= $teams; $i++) {
            $teamsData[] = [
                'teamId' => (string) $i,
                'teamCode' => 'K' . $i,
            ];
        }

        return ApiResponse::success(array_merge([
            'roomId' => (string) $room->id,
            'code' => $room->code,
            'expiresAt' => $room->expires_at?->toIso8601String(),
            'teams' => $teamsData,
        ], $this->creatorJoinPayload($creatorPlayer)), null, 201);
    }

    public function createCustomRoom(CreateCustomRoomRequest $request): JsonResponse
    {
        $user = auth()->user();
        $category = CustomCategory::ownedBy($user)->find((int) $request->input('customCategoryId'));
        if (!$category) {
            return ApiResponse::error('Custom category not found for this owner.', 422);
        }

        $customStage = CustomStage::query()
            ->where('id', (int) $request->input('customStageId'))
            ->where('status', true)
            ->first();
        if (!$customStage) {
            return ApiResponse::error('Custom stage not found or inactive.', 422);
        }

        $availableQuestions = CustomQuestion::ownedBy($user)
            ->where('custom_category_id', $category->id)
            ->where('status', true)
            ->count();
        if ($availableQuestions <= 0) {
            return ApiResponse::error('This custom category has no active questions.', 422);
        }

        $questionsCountInput = $request->input('questionsCount');
        $roundsCountInput = $request->input('rounds');
        if ($questionsCountInput !== null) {
            $questionsCount = (int) $questionsCountInput;
            $rounds = (int) ($roundsCountInput ?? 1);
        } else {
            $questionsCount = (int) ($roundsCountInput ?? 5);
            $rounds = 1;
        }
        $questionsCount = max(1, min($questionsCount, $availableQuestions));

        // 1 round = 1 consumed available session (minimum 1 session per room).
        $sessionCost = max(1, $rounds);
        if (($user->available_sessions ?? 0) < $sessionCost) {
            return ApiResponse::error('لا توجد جلسات لعبة متاحة. كل جولة إضافية تحتاج جلسة واحدة. يرجى شراء حزمة للاستمرار.', 403);
        }

        $code = $this->gameService->generateRoomCode();
        $teams = (int) $request->input('teams', 2);
        $playersPerTeam = (int) $request->input('players', 2);
        $totalPlayers = $playersPerTeam * $teams;
        $fallbackTypeId = Type::where('status', true)->value('id');
        $fallbackCategoryId = Category::where('status', true)->value('id');
        $fallbackSubcategoryId = Subcategory::where('status', true)->value('id');
        if (!$fallbackTypeId || !$fallbackCategoryId || !$fallbackSubcategoryId) {
            return ApiResponse::error('Base game setup is incomplete for room creation.', 500);
        }

        $roomData = [
            'code' => $code,
            'is_custom' => true,
            'custom_category_id' => $category->id,
            'custom_stage_id' => $customStage->id,
            'type_id' => $fallbackTypeId,
            'category_id' => $fallbackCategoryId,
            'subcategory_id' => $fallbackSubcategoryId,
            'title' => $request->input('title', $category->name),
            'rounds' => $rounds,
            'questions_count' => $questionsCount,
            'life_points' => max(1, intdiv($questionsCount, 2)),
            'teams' => $teams,
            'players' => $totalPlayers,
            'expires_at' => now()->addHours(24),
        ];
        if ($user instanceof Adventurer) {
            $roomData['created_by_adventurer_id'] = $user->id;
        } else {
            $roomData['created_by'] = $user->id;
        }
        $room = Room::create($roomData);
        $user->decrement('available_sessions', $sessionCost);

        $creatorPlayer = $this->attachCreatorAsK1Leader($room, $user);

        $this->firebaseSync->syncRoom($room->fresh());
        $this->firebaseSync->syncRoomPlayers($room->fresh());

        $teamsData = [];
        for ($i = 1; $i <= $teams; $i++) {
            $teamsData[] = [
                'teamId' => (string) $i,
                'teamCode' => 'K' . $i,
            ];
        }

        return ApiResponse::success(array_merge([
            'roomId' => (string) $room->id,
            'code' => $room->code,
            'isCustom' => true,
            'customCategoryId' => (string) $category->id,
            'customStageId' => (string) $customStage->id,
            'customStageName' => $customStage->name,
            'lifePoints' => $this->gameService->resolveLifePointsPoolForRoom($room),
            'selectedQuestionsCount' => (int) $room->questions_count,
            'expiresAt' => $room->expires_at?->toIso8601String(),
            'teams' => $teamsData,
        ], $this->creatorJoinPayload($creatorPlayer)), null, 201);
    }

    public function getRoom(int $roomId): JsonResponse
    {
        $room = Room::withCount('roomPlayers')
            ->with(['type', 'category', 'subcategory.stage.questionGroups', 'roomPlayers.user', 'roomPlayers.adventurer', 'gameSessions' => fn($q) => $q->whereIn('status', ['waiting', 'playing', 'starting', 'paused'])->latest()->limit(1)])
            ->find($roomId);

        if (!$room) {
            return ApiResponse::error('الغرفة غير موجودة', 404);
        }

        $teams = (int) $room->teams;
        $teamCodes = [];
        for ($i = 1; $i <= $teams; $i++) {
            $teamCodes[] = [
                'teamId' => (string) $i,
                'teamCode' => 'K' . $i,
            ];
        }
        $activeSession = $room->gameSessions->first();
        $data = [
            'roomId' => (string) $room->id,
            'code' => $room->code,
            'status' => $room->status,
            'sessionId' => $activeSession ? (string) $activeSession->id : null,
            'joinedCount' => $room->room_players_count ?? $room->roomPlayers()->count(),
            'settings' => [
                'gameTitle' => $room->title ?? $room->type?->name ?? '',
                'rounds' => (int) $room->rounds,
                'teams' => $teams,
                'players' => (int) $room->players,
                'playersPerTeam' => $teams > 0 ? (int) ($room->players / $teams) : 0,
                'questionType' => $room->type?->name ?? '',
                'questionCategory' => $room->category?->name ?? '',
                'questionSubCategory' => $room->subcategory?->name ?? '',
            ],
            'teams' => $teamCodes,
            'stage' => $this->firebaseSync->getStageDataForRoom($room),
            'players' => $room->roomPlayers->map(function ($rp) {
                $entity = $rp->adventurer ?? $rp->user;

                return [
                    'playerId' => (string) $rp->id,
                    'userId' => (string) ($rp->adventurer_id ?? $rp->user_id),
                    'userName' => $entity?->name ?? 'Player',
                    'country' => $entity ? [
                        'label' => $entity->country_label ?? null,
                        'code' => $entity->country_code ?? null,
                    ] : null,
                    'teamId' => (string) $rp->team_id,
                    'teamCode' => 'K' . $rp->team_id,
                    'isLeader' => (bool) $rp->is_leader,
                ];
            })->values()->all(),
        ];

        return ApiResponse::success($data);
    }

    public function getCustomRoom(int $roomId): JsonResponse
    {
        $room = Room::withCount('roomPlayers')
            ->with([
                'customCategory',
                'customStage',
                'roomPlayers.user',
                'roomPlayers.adventurer',
                'gameSessions' => fn($q) => $q->whereIn('status', ['waiting', 'playing', 'starting', 'paused'])->latest()->limit(1),
            ])
            ->where('is_custom', true)
            ->find($roomId);

        if (!$room) {
            return ApiResponse::error('الغرفة غير موجودة', 404);
        }

        $activeSession = $room->gameSessions->first();
        $selectedQuestionsCount = $activeSession
            ? count($activeSession->question_ids ?? [])
            : (int) ($room->questions_count ?? 0);
        $teams = (int) $room->teams;
        $teamCodes = [];
        for ($i = 1; $i <= $teams; $i++) {
            $teamCodes[] = [
                'teamId' => (string) $i,
                'teamCode' => 'K' . $i,
            ];
        }

        return ApiResponse::success([
            'roomId' => (string) $room->id,
            'code' => $room->code,
            'status' => $room->status,
            'isCustom' => true,
            'customCategoryId' => (string) $room->custom_category_id,
            'customCategoryName' => $room->customCategory?->name,
            'customStageId' => $room->custom_stage_id !== null ? (string) $room->custom_stage_id : null,
            'customStageName' => $room->customStage?->name,
            'sessionId' => $activeSession ? (string) $activeSession->id : null,
            'joinedCount' => $room->room_players_count ?? $room->roomPlayers()->count(),
            'selectedQuestionsCount' => (int) $selectedQuestionsCount,
            'settings' => [
                'gameTitle' => $room->title ?? $room->customCategory?->name ?? '',
                'rounds' => (int) $room->rounds,
                'teams' => $teams,
                'players' => (int) $room->players,
                'playersPerTeam' => $teams > 0 ? (int) ($room->players / $teams) : 0,
                'lifePoints' => $this->gameService->resolveLifePointsPoolForRoom($room, $activeSession),
            ],
            'teams' => $teamCodes,
            'stage' => $this->firebaseSync->getStageDataForRoom($room, $activeSession),
            'players' => $room->roomPlayers->map(function ($rp) {
                $entity = $rp->adventurer ?? $rp->user;

                return [
                    'playerId' => (string) $rp->id,
                    'userId' => (string) ($rp->adventurer_id ?? $rp->user_id),
                    'userName' => $entity?->name ?? 'Player',
                    'teamId' => (string) $rp->team_id,
                    'teamCode' => 'K' . $rp->team_id,
                    'isLeader' => (bool) $rp->is_leader,
                ];
            })->values()->all(),
        ]);
    }

    public function linkTv(LinkTvRequest $request, int $roomId): JsonResponse
    {
        $room = Room::find($roomId);
        if (!$room) {
            return ApiResponse::error('الغرفة غير موجودة', 404);
        }

        $user = auth()->user();
        $roomPlayer = $user instanceof Adventurer
            ? RoomPlayer::where('room_id', $roomId)->where('adventurer_id', $user->id)->first()
            : RoomPlayer::where('room_id', $roomId)->where('user_id', $user->id)->first();

        if (!$roomPlayer) {
            return ApiResponse::error('أنت غير مشارك في هذه الغرفة', 403);
        }

        $tvCode = $request->input('tvCode');
        $display = TvDisplay::where('code', $tvCode)->first();

        if (!$display) {
            return ApiResponse::error('رمز التلفزيون غير صحيح', 400);
        }

        $wasLinked = false;
        if ($display->isWaiting()) {
            $display->update([
                'room_id' => $roomId,
                'status' => TvDisplay::STATUS_LINKED,
                'expires_at' => $room->expires_at ?? now()->addHours(24),
            ]);
            $this->firebaseSync->syncTvDisplay($display->fresh());
            $this->firebaseSync->syncRoom($room->fresh());
            $wasLinked = true;
        } elseif ($display->status === TvDisplay::STATUS_LINKED && (int) $display->room_id === (int) $roomId) {
            // TV already linked to this room – just join current user
        } else {
            return ApiResponse::error('رمز التلفزيون منتهي أو مربوط بغرفة أخرى', 400);
        }

        if ($roomPlayer->tv_view_joined_at === null) {
            $roomPlayer->update(['tv_view_joined_at' => now()]);
            $this->firebaseSync->syncRoomPlayers($room->fresh());

            $activeSession = $room->gameSessions()->whereIn('status', ['starting', 'playing', 'paused'])->latest()->first();
            if ($activeSession) {
                if ($activeSession->status === 'starting') {
                    $startedSession = $this->gameService->maybeStartSessionWhenAllJoined($room->fresh());
                    if (!$startedSession) {
                        $this->firebaseSync->syncSessionStarting($activeSession->fresh());
                    }
                } else {
                    $this->firebaseSync->syncScores($activeSession->fresh());
                }
            }
        }

        $session = $room->gameSessions()->whereIn('status', ['starting', 'playing', 'paused'])->latest()->first();

        return ApiResponse::success([
            'linked' => $wasLinked,
            'viewingTv' => true,
            'roomId' => (string) $room->id,
            'sessionId' => $session ? (string) $session->id : null,
        ]);
    }

    public function joinRoom(JoinRoomRequest $request, int $roomId): JsonResponse
    {
        $room = Room::find($roomId);
        if (!$room) {
            return ApiResponse::error('الغرفة ممتلئة أو الرمز خاطئ', 404);
        }
        if ($room->status !== 'waiting') {
            return ApiResponse::error('الغرفة ممتلئة أو الرمز خاطئ', 400);
        }
        if ($request->has('code') && $request->input('code') !== $room->code) {
            return ApiResponse::error('الغرفة ممتلئة أو الرمز خاطئ', 400);
        }

        $user = auth()->user();
        $adventurerId = $user instanceof Adventurer ? $user->id : null;
        $userId = $user instanceof User ? $user->id : null;
        $existing = $adventurerId
            ? RoomPlayer::where('room_id', $roomId)->where('adventurer_id', $adventurerId)->first()
            : RoomPlayer::where('room_id', $roomId)->where('user_id', $userId)->first();
        if ($existing) {
            return ApiResponse::success([
                'joined' => true,
                'teamId' => (string) $existing->team_id,
                'playerId' => (string) $existing->id,
                'teamCode' => 'K' . $existing->team_id,
                'isLeader' => (bool) $existing->is_leader,
            ]);
        }

        // Join room is free; only create room costs a session

        $teamCode = $request->input('teamCode');
        $teamId = (int) ltrim($teamCode, 'K');
        if ($teamId < 1 || $teamId > (int) $room->teams) {
            return ApiResponse::error('رمز الفريق غير صالح لهذه المغامرة', 400);
        }

        $teams = (int) $room->teams;
        $playersPerTeam = $teams > 0 ? (int) ($room->players / $teams) : 0;
        if ($playersPerTeam > 0) {
            $teamPlayersCount = $room->roomPlayers()->where('team_id', $teamId)->count();
            if ($teamPlayersCount >= $playersPerTeam) {
                return ApiResponse::error('هذا الفريق ممتلئ، يرجى اختيار فريق آخر', 400);
            }
        }

        $isLeader = (bool) $request->boolean('isLeader');
        if ($isLeader) {
            $hasLeader = $room->roomPlayers()
                ->where('team_id', $teamId)
                ->where('is_leader', true)
                ->exists();
            if ($hasLeader) {
                return ApiResponse::error('تم تعيين قائد لهذا الفريق بالفعل', 400);
            }
        }

        $playerData = ['room_id' => $roomId, 'team_id' => $teamId, 'is_leader' => $isLeader];
        if ($adventurerId) {
            $playerData['adventurer_id'] = $adventurerId;
        } else {
            $playerData['user_id'] = $userId;
        }
        $player = RoomPlayer::create($playerData);

        // No session cost for joining; only create room deducts

        $this->firebaseSync->syncRoomPlayers($room->fresh());

        if ($room->roomPlayers()->count() >= (int) $room->players) {
            $this->gameService->getOrCreateSession($room->fresh());
        }

        return ApiResponse::success([
            'joined' => true,
            'teamId' => (string) $player->team_id,
            'playerId' => (string) $player->id,
            'teamCode' => 'K' . $player->team_id,
            'isLeader' => (bool) $player->is_leader,
        ]);
    }

    public function leaveRoom(int $roomId): JsonResponse
    {
        $room = Room::find($roomId);
        if (!$room) {
            return ApiResponse::error('الغرفة غير موجودة', 404);
        }

        $user = auth()->user();
        $roomPlayer = $user instanceof Adventurer
            ? RoomPlayer::where('room_id', $roomId)->where('adventurer_id', $user->id)->first()
            : RoomPlayer::where('room_id', $roomId)->where('user_id', $user->id)->first();

        if (!$roomPlayer) {
            return ApiResponse::error('أنت غير مشارك في هذه الغرفة', 403);
        }

        // Allow "exit & kick all" only before the session is actually playing on TV.
        // This covers the case where a session row exists (status=starting) but not all players joined TV yet.
        $activeSession = $room->gameSessions()
            ->whereIn('status', ['waiting', 'starting', 'playing', 'paused'])
            ->latest()
            ->first();

        $allPlayersJoinedTv = $room->roomPlayers()->count() > 0
            && $room->roomPlayers()->whereNull('tv_view_joined_at')->count() === 0;

        $creatorId = $room->created_by ?? null;
        $creatorAdventurerId = $room->created_by_adventurer_id ?? null;
        $isCreator = ($user instanceof Adventurer)
            ? ($creatorAdventurerId !== null && (int) $creatorAdventurerId === (int) $user->id)
            : ($creatorId !== null && (int) $creatorId === (int) $user->id);

        $canKickAll = $isCreator
            && $activeSession
            && in_array($activeSession->status, ['waiting', 'starting'], true)
            && !$allPlayersJoinedTv;

        // Default behavior: allow leaving only while room is waiting.
        if ($room->status !== 'waiting' && !$canKickAll) {
            return ApiResponse::error('لا يمكن المغادرة بعد بدء اللعبة. استخدم الاستسلام لإنهاء المغامرة.', 400);
        }

        if ($canKickAll) {
            // Kick everyone and invalidate the room since the session hasn't started on TV yet.
            $room->roomPlayers()->delete();

            if ($activeSession) {
                $activeSession->update(['status' => 'finished']);
                $this->firebaseSync->syncSessionEnd($activeSession->fresh());
            }

            $room->update([
                'status' => 'finished',
                'expires_at' => now(),
            ]);

            // Unlink any linked TV display to avoid leaving it attached to a dead room.
            $display = TvDisplay::where('room_id', $room->id)->where('status', TvDisplay::STATUS_LINKED)->first();
            if ($display) {
                $display->update([
                    'room_id' => null,
                    'status' => TvDisplay::STATUS_WAITING,
                    'expires_at' => now()->addMinutes(15),
                ]);
                $this->firebaseSync->syncTvDisplay($display->fresh());
            }

            $this->firebaseSync->syncRoom($room->fresh());
            $this->firebaseSync->syncRoomPlayers($room->fresh());

            return ApiResponse::success([
                'left' => true,
                'roomId' => (string) $room->id,
                'kickedAll' => true,
                'message' => 'تم إنهاء الغرفة وإخراج جميع اللاعبين قبل بدء الجلسة على التلفزيون',
            ]);
        }

        $roomPlayer->delete();
        $this->firebaseSync->syncRoomPlayers($room->fresh());

        return ApiResponse::success([
            'left' => true,
            'roomId' => (string) $room->id,
            'message' => 'تم مغادرة الغرفة بنجاح',
        ]);
    }

    public function getSession(int $sessionId): JsonResponse
    {
        $session = GameSession::with('room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        $session = $this->gameService->ensureSessionPlaying($session);

        $this->gameService->refreshStaleQuestionTimer($session);
        $session = $session->fresh(['room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups']);

        $questionIds = $session->question_ids ?? [];
        $remainingCount = max(0, count($questionIds) - $session->current_round);
        // Read-only recovery: if the final question is paused, close the session. Do not auto-timeout via GET.
        if ($session->status === 'paused' && $this->gameService->isOnLastScheduledQuestion($session)) {
            $this->gameService->advanceFinishedSessionIfLastQuestion($session);
            $session = $session->fresh(['room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.subcategory.stage.questionGroups']);
        }

        $questionData = in_array($session->status, ['playing', 'paused'], true)
            ? $this->gameService->getCurrentQuestion($session)
            : null;
        $teams = $session->room->roomPlayers->groupBy('team_id')->map(function ($players, $teamId) {
            $first = $players->first();
            $name = ($first->adventurer ?? $first->user)?->name ?? 'الفريق ' . $teamId;
            $score = $players->sum('score');

            return [
                'id' => (string) $teamId,
                'name' => $name,
                'score' => $score,
                'teamCode' => 'K' . $teamId,
            ];
        })->values()->all();

        $questionLimit = GameService::QUESTION_TIME_LIMIT_SECONDS;
        $timeLeft = $session->question_started_at
            ? max(0, $questionLimit - (int) $session->question_started_at->diffInSeconds(now()))
            : $questionLimit;

        $data = [
            'sessionId' => (string) $session->id,
            'status' => $session->status,
            'round' => $session->current_round,
            'remainingQuestionsCount' => $remainingCount,
            'question' => $questionData,
            'timeLeft' => $timeLeft,
            'questionTimeLimitSeconds' => $questionLimit,
            'questionStartedAt' => $session->question_started_at?->timestamp ? (int) ($session->question_started_at->timestamp * 1000) : null,
            'startTimerEndsAt' => $session->start_timer_ends_at?->timestamp ? (int) ($session->start_timer_ends_at->timestamp * 1000) : null,
            'teams' => $teams,
            'stage' => $this->firebaseSync->getStageDataForRoom($session->room, $session),
        ];
        if ($session->status === 'paused') {
            $lastAnswer = $session->sessionAnswers()->latest('id')->first();
            $data['lastAnswerCorrect'] = $lastAnswer ? $lastAnswer->correct : null;
        }

        return ApiResponse::success($data);
    }

    public function submitAnswer(SubmitAnswerRequest $request, int $sessionId): JsonResponse
    {
        $session = GameSession::find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }
        if ($session->status === 'finished') {
            return ApiResponse::error('انتهت الجلسة', 400);
        }
        if ($session->status === 'paused') {
            return ApiResponse::error('اللعبة متوقفة؛ انتظر السؤال التالي', 400);
        }

        $flowCooldown = $this->gameService->questionFlowCooldownRemainingSeconds($session);
        if ($flowCooldown > 0) {
            return ApiResponse::error(
                'يرجى الانتظار ' . $flowCooldown . ' ثانية قبل إرسال إجابة أو طلب إنهاء الوقت / السؤال التالي',
                429
            );
        }

        $session = $this->gameService->ensureSessionPlaying($session);
        if ($session->status === 'starting') {
            return ApiResponse::error('اللعبة لم تبدأ بعد', 400);
        }

        $optionIndex = $this->gameService->normalizeAnswerOptionIndex(
            $request->input('optionIndex'),
            $request->input('shape'),
        );
        if ($optionIndex === null) {
            return ApiResponse::error(
                'optionIndex يجب أن يكون بين 1 و 4 (مثلث=1، دائرة=2، x=3، مربع=4)',
                422
            );
        }
        $user = auth()->user();
        $roomPlayer = $user instanceof Adventurer
            ? RoomPlayer::where('room_id', $session->room_id)->where('adventurer_id', $user->id)->first()
            : RoomPlayer::where('room_id', $session->room_id)->where('user_id', $user->id)->first();
        if (!$roomPlayer) {
            return ApiResponse::error('أنت غير مشارك في هذه المغامرة', 403);
        }
        if (!$roomPlayer->is_leader) {
            return ApiResponse::error('فقط قائد الفريق يمكنه الإجابة على الأسئلة', 403);
        }

        $answerCooldown = $this->gameService->submitAnswerCooldownRemainingSeconds($session, $roomPlayer->id);
        if ($answerCooldown > 0) {
            return ApiResponse::error(
                'يرجى الانتظار ' . $answerCooldown . ' ثانية قبل إرسال إجابة أخرى',
                429
            );
        }

        $this->gameService->recordSubmitAnswerAction($session, $roomPlayer->id);

        $result = $this->gameService->submitAnswer($session, $roomPlayer->id, $optionIndex);
        $data = [
            'correct' => $result['correct'],
            'scoreDelta' => $result['scoreDelta'],
            'nextQuestionAvailable' => $result['nextQuestionAvailable'],
            'answeredOptionIndex' => $optionIndex,
        ];
        if (!empty($result['sessionFinished'])) {
            $data['sessionFinished'] = true;
        }

        return ApiResponse::success($data);
    }

    public function nextQuestion(Request $request, int $sessionId): JsonResponse
    {
        $session = GameSession::with('room')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        if (! $this->canControlNextQuestionFromTvOrAuth($request, $session)) {
            return ApiResponse::error('غير مصرح: أرسل displayId أو deviceId لشاشة التلفزيون المربوطة بهذه الغرفة، أو سجّل الدخول كمشارك في الغرفة.', 403);
        }

        $cooldown = $this->gameService->questionFlowCooldownRemainingSeconds($session);
        if ($cooldown > 0) {
            return ApiResponse::error(
                'يرجى الانتظار ' . $cooldown . ' ثانية قبل طلب السؤال التالي أو إنهاء الوقت أو إرسال إجابة مرة أخرى',
                429
            );
        }

        $this->gameService->recordQuestionFlowAction($session);

        // Close an expired question only when the server timer was actually started (do not infer on a fresh question).
        if ($session->status === 'playing') {
            $this->gameService->applyPlayingQuestionTimeout($session->fresh(), false);
            $session = GameSession::with('room')->find($sessionId);
        }

        if ($session->status === 'finished') {
            $this->gameService->syncFirebaseForSession($session->fresh(['room']));

            return ApiResponse::success([
                'finished' => true,
                'sessionId' => (string) $session->id,
                'round' => null,
            ]);
        }

        if ($session->status !== 'paused') {
            return ApiResponse::error(
                'لا يزال السؤال نشطاً: انتظر انتهاء وقت السؤال أو إجابة جميع الفرق، أو استدعِ POST .../timeout بعد انتهاء المؤقت.',
                400
            );
        }

        $result = $this->gameService->advanceToNextQuestion($session);
        if (!empty($result['invalid'])) {
            return ApiResponse::error('لا يمكن الانتقال للسؤال التالي', 400);
        }

        return ApiResponse::success([
            'finished' => $result['finished'],
            'sessionId' => (string) $session->id,
            'round' => $result['round'],
        ]);
    }

    public function pause(int $sessionId): JsonResponse
    {
        $session = GameSession::find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }
        if ($session->status !== 'playing') {
            return ApiResponse::error('لا يمكن إيقاف الجلسة مؤقتاً في هذه الحالة', 400);
        }

        $session->update(['status' => 'paused']);
        $this->firebaseSync->syncSessionPaused($session->fresh(), false);

        return ApiResponse::success([
            'paused' => true,
            'sessionId' => (string) $session->id,
        ]);
    }

    public function resume(int $sessionId): JsonResponse
    {
        $session = GameSession::find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }
        if ($session->status !== 'paused') {
            return ApiResponse::error('الجلسة ليست في حالة الإيقاف المؤقت', 400);
        }

        $session->update([
            'status' => 'playing',
            'question_started_at' => now(),
        ]);

        $this->firebaseSync->syncSessionStart($session->fresh());

        return ApiResponse::success([
            'resumed' => true,
            'sessionId' => (string) $session->id,
            'round' => $session->current_round,
        ]);
    }

    public function startQuestion(int $sessionId): JsonResponse
    {
        $session = GameSession::find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        if ($session->status !== 'playing') {
            return ApiResponse::error('لا يمكن بدء السؤال في هذه الحالة', 400);
        }

        $result = $this->gameService->startCurrentQuestion($session);

        if (!$result['ok']) {
            return ApiResponse::error('لا يمكن بدء السؤال', 400);
        }

        return ApiResponse::success([
            'started' => true,
            'reason' => $result['reason'],
            'sessionId' => (string) $session->id,
            'round' => (int) $session->current_round,
        ]);
    }

    public function timeout(Request $request, int $sessionId): JsonResponse
    {
        $session = GameSession::with('room')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        if (! $this->canControlNextQuestionFromTvOrAuth($request, $session)) {
            return ApiResponse::error('غير مصرح: أرسل displayId أو deviceId لشاشة التلفزيون المربوطة بهذه الغرفة، أو سجّل الدخول كمشارك في الغرفة.', 403);
        }

        if (!in_array($session->status, ['playing', 'paused'], true)) {
            return ApiResponse::error('لا يمكن إنهاء الوقت في هذه الحالة', 400);
        }

        $cooldown = $this->gameService->questionFlowCooldownRemainingSeconds($session);
        if ($cooldown > 0) {
            return ApiResponse::error(
                'يرجى الانتظار ' . $cooldown . ' ثانية قبل طلب إنهاء الوقت أو السؤال التالي أو إرسال إجابة مرة أخرى',
                429
            );
        }

        $this->gameService->recordQuestionFlowAction($session);

        $result = $this->gameService->applyPlayingQuestionTimeout($session, true);
        if (!$result['applied']) {
            if (($result['reason'] ?? '') === 'timer_not_elapsed') {
                return ApiResponse::error('لم ينته الوقت بعد', 400);
            }
            if (($result['reason'] ?? '') === 'question_not_started') {
                return ApiResponse::error('لم يبدأ مؤقت السؤال على الخادم؛ استدعِ POST /api/game/session/{id}/start-question بعد عرض السؤال.', 400);
            }
            if (($result['reason'] ?? '') === 'no_room') {
                return ApiResponse::error('الغرفة غير متاحة', 400);
            }
            if (($result['reason'] ?? '') === 'already_handled') {
                return ApiResponse::success([
                    'paused' => $session->status === 'paused',
                    'finished' => $session->status === 'finished',
                    'sessionId' => (string) $session->id,
                    'reason' => 'already_handled',
                ]);
            }

            return ApiResponse::error('لا يمكن إنهاء الوقت في هذه الحالة', 400);
        }

        $session = $result['session'];
        $allLeadersAnswered = (bool) ($result['all_leaders_answered'] ?? false);
        $reason = ($result['session_finished'] ?? false)
            ? 'life_points_finished'
            : ($allLeadersAnswered ? 'all_leaders_answered' : 'timeout');

        return ApiResponse::success([
            'paused' => $session->status === 'paused',
            'finished' => $session->status === 'finished',
            'sessionId' => (string) $session->id,
            'reason' => $reason,
        ]);
    }

    public function surrender(int $sessionId): JsonResponse
    {
        $session = GameSession::with('room.roomPlayers')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }
        if ($session->status === 'finished') {
            return ApiResponse::error('انتهت الجلسة', 400);
        }

        $user = auth()->user();
        $roomPlayer = $user instanceof Adventurer
            ? RoomPlayer::where('room_id', $session->room_id)->where('adventurer_id', $user->id)->first()
            : RoomPlayer::where('room_id', $session->room_id)->where('user_id', $user->id)->first();
        if (!$roomPlayer) {
            return ApiResponse::error('أنت غير مشارك في هذه المغامرة', 403);
        }

        $user->increment('surrender_count');
        if ($user instanceof Adventurer) {
            $user->increment('number_surrender_times');
        }

        $surrenderingTeamId = $roomPlayer->team_id;
        $distinctTeamCount = $session->room->roomPlayers->pluck('team_id')->unique()->filter()->count();

        RoomPlayer::where('room_id', $session->room_id)
            ->where('team_id', $surrenderingTeamId)
            ->update(['score' => 0]);

        if ($distinctTeamCount > 2) {
            // Multi-team: mark this team as surrendered; others continue
            $surrenderedIds = $session->surrendered_team_ids ?? [];
            $surrenderedIds[] = (string) $surrenderingTeamId;
            $surrenderedIds = array_values(array_unique($surrenderedIds));
            $session->update(['surrendered_team_ids' => $surrenderedIds]);
            $this->firebaseSync->syncScores($session->fresh(['room.roomPlayers']));

            return ApiResponse::success([
                'sessionId' => (string) $session->id,
                'endedBySurrender' => false,
                'surrenderingTeamId' => (string) $surrenderingTeamId,
                'message' => 'تم استسلام فريقك. تستمر الفرق الأخرى في اللعب.',
            ], null, 200);
        }

        // Two teams or fewer: session ends, non-surrendering team wins
        // Persist surrendered team id as well, so Firebase `teams[*].surrendered` / `isEliminated` can be shown.
        $winnerIds = collect($session->room->roomPlayers->pluck('team_id')->unique()->filter())
            ->reject(fn ($id) => (int) $id === (int) $surrenderingTeamId)
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $session->update([
            'status' => 'finished',
            'surrendered_team_ids' => array_values(array_unique([
                ...(is_array($session->surrendered_team_ids) ? $session->surrendered_team_ids : []),
                (string) $surrenderingTeamId,
            ])),
            'winner_team_ids' => $winnerIds,
        ]);
        $session->room?->update(['status' => 'finished']);

        $finished = $session->fresh();
        $this->gameService->updatePointsForFinishedSession($finished);
        $this->gameService->recordLastRoundStageTail($finished);

        $finished->load('room.roomPlayers.user', 'room.roomPlayers.adventurer');
        $surrenderedTeamIds = array_map('strval', $finished->surrendered_team_ids ?? []);
        $byTeam = $finished->room->roomPlayers->groupBy('team_id')->map(function ($players, $teamId) use ($surrenderedTeamIds) {
            $first = $players->first();
            $name = ($first->adventurer ?? $first->user)?->name ?? 'الفريق ' . $teamId;
            $isSurrendered = in_array((string) $teamId, $surrenderedTeamIds, true);
            $score = $isSurrendered ? 0 : (int) $players->sum('score');

            return [
                'teamId' => (string) $teamId,
                'name' => $name,
                'score' => $score,
                'teamCode' => 'K' . $teamId,
            ];
        })->values()->all();

        $winnerIds = collect($byTeam)
            ->pluck('teamId')
            ->reject(fn($id) => (int) $id === (int) $surrenderingTeamId)
            ->values()
            ->all();

        $this->firebaseSync->syncSessionEnd($finished, $winnerIds);

        $data = [
            'sessionId' => (string) $session->id,
            'endedBySurrender' => true,
            'surrenderingTeamId' => (string) $surrenderingTeamId,
            'scores' => $byTeam,
            'winnerIds' => $winnerIds,
            'message' => 'تم إنهاء المغامرة بسبب استسلام أحد الفرق',
        ];

        return ApiResponse::success($data, null, 200);
    }

    public function endSession(int $sessionId): JsonResponse
    {
        $session = GameSession::with('room')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }
        if ($session->status === 'finished') {
            return ApiResponse::error('انتهت الجلسة بالفعل', 400);
        }

        $winnerTeamIds = $this->gameService->resolveWinnerTeamIds($session);
        $session->update([
            'status' => 'finished',
            'winner_team_ids' => $winnerTeamIds,
        ]);
        $session->room?->update(['status' => 'finished']);

        $finished = $session->fresh();
        $this->gameService->updatePointsForFinishedSession($finished);
        $this->gameService->recordLastRoundStageTail($finished);
        $this->firebaseSync->syncSessionEnd($finished, $winnerTeamIds);

        return ApiResponse::success([
            'ended' => true,
            'sessionId' => (string) $session->id,
        ]);
    }

    public function getResult(int $sessionId): JsonResponse
    {
        $session = GameSession::with('room.roomPlayers.user', 'room.roomPlayers.adventurer')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        $byTeam = $session->room->roomPlayers->groupBy('team_id')->map(function ($players, $teamId) {
            $first = $players->first();
            $name = ($first->adventurer ?? $first->user)?->name ?? 'الفريق ' . $teamId;
            $score = $players->sum('score');

            return [
                'teamId' => (string) $teamId,
                'name' => $name,
                'score' => $score,
                'teamCode' => 'K' . $teamId,
            ];
        })->values()->all();

        $winner = collect($byTeam)->sortByDesc('score')->first();
        $roundsPlayed = count($session->question_ids ?? []);

        return ApiResponse::success([
            'scores' => $byTeam,
            'winnerId' => $winner['teamId'] ?? null,
            'roundsPlayed' => $roundsPlayed,
        ]);
    }

    /**
     * TV (no Bearer): displayId or deviceId must match a display linked to this session's room.
     * With Bearer: any authenticated user who is a room player may call next-question or timeout.
     */
    private function canControlNextQuestionFromTvOrAuth(Request $request, GameSession $session): bool
    {
        $room = $session->room;
        if (! $room) {
            return false;
        }

        $roomId = (int) $session->room_id;

        $displayId = $request->input('displayId', $request->input('tvDisplayId'));
        if ($displayId !== null && $displayId !== '') {
            $display = TvDisplay::query()->whereKey((int) $displayId)->first();

            return $display !== null && $display->canControlRoomSession($roomId);
        }

        $deviceId = $request->input('deviceId');
        if (is_string($deviceId) && $deviceId !== '') {
            $display = TvDisplay::query()
                ->where('device_id', $deviceId)
                ->first();

            return $display !== null && $display->canControlRoomSession($roomId);
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $roomPlayer = $user instanceof Adventurer
            ? RoomPlayer::where('room_id', $roomId)->where('adventurer_id', $user->id)->first()
            : RoomPlayer::where('room_id', $roomId)->where('user_id', $user->id)->first();

        return $roomPlayer !== null;
    }

    /**
     * Same as normal create-room: creator is a room player on team 1 (K1) as leader.
     */
    private function attachCreatorAsK1Leader(Room $room, Adventurer|User $user): RoomPlayer
    {
        $data = [
            'room_id' => $room->id,
            'team_id' => 1,
            'is_leader' => true,
        ];
        if ($user instanceof Adventurer) {
            $data['adventurer_id'] = $user->id;
        } else {
            $data['user_id'] = $user->id;
        }

        return RoomPlayer::create($data);
    }

    /**
     * @return array{joined: true, playerId: string, teamId: string, teamCode: string, isLeader: true}
     */
    private function creatorJoinPayload(RoomPlayer $creatorPlayer): array
    {
        return [
            'joined' => true,
            'playerId' => (string) $creatorPlayer->id,
            'teamId' => (string) $creatorPlayer->team_id,
            'teamCode' => 'K' . $creatorPlayer->team_id,
            'isLeader' => (bool) $creatorPlayer->is_leader,
        ];
    }
}
