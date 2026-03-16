<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ValidateCodeRequest;
use App\Http\Requests\Game\CreateRoomRequest;
use App\Http\Requests\Game\JoinRoomRequest;
use App\Http\Requests\Game\SubmitAnswerRequest;
use App\Http\Requests\Game\LinkTvRequest;
use App\Models\Adventurer;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\GameSession;
use App\Models\TvDisplay;
use App\Models\Type;
use App\Models\User;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\FirebaseGameSyncService;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use App\Models\SessionAnswer;

class GameController extends Controller
{
    public function __construct(
        protected GameService $gameService,
        protected FirebaseGameSyncService $firebaseSync
    ) {}

    public function getQuestionTypes(): JsonResponse
    {
        $types = Type::where('status', true)->withCount('categories')->orderBy('id')->get()->map(fn ($t) => [
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
        $items = $query->with('type')->withCount('questions')->orderBy('id')->get()->map(fn ($c) => [
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
        $items = $query->with('category')->withCount('questions')->orderBy('id')->get()->map(fn ($s) => [
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
        if (($user->available_sessions ?? 0) < 1) {
            return ApiResponse::error('لا توجد جلسات لعبة متاحة. يرجى شراء حزمة للاستمرار.', 403);
        }

        $code = $this->gameService->generateRoomCode();
        $teams = (int) $request->input('teams', 2);
        $playersPerTeam = (int) $request->input('players', 2);
        $totalPlayers = $playersPerTeam * $teams;
        $rounds = (int) ($request->input('questionsCount') ?? $request->input('rounds', 5));

        $roomData = [
            'code' => $code,
            'type_id' => $request->input('questionType'),
            'category_id' => $request->input('mainCategoryId'),
            'subcategory_id' => $request->input('subCategoryId'),
            'title' => $request->input('title'),
            'rounds' => $rounds,
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

        $user->decrement('available_sessions');

        $this->firebaseSync->syncRoom($room);

        $teamsData = [];
        for ($i = 1; $i <= $teams; $i++) {
            $teamsData[] = [
                'teamId' => (string) $i,
                'teamCode' => 'K' . $i,
            ];
        }

        return ApiResponse::success([
            'roomId' => (string) $room->id,
            'code' => $room->code,
            'expiresAt' => $room->expires_at?->toIso8601String(),
            'teams' => $teamsData,
        ], null, 201);
    }

    public function getRoom(int $roomId): JsonResponse
    {
        $room = Room::withCount('roomPlayers')
            ->with(['type', 'category', 'subcategory.stage.questionGroups', 'roomPlayers.user', 'roomPlayers.adventurer', 'gameSessions' => fn ($q) => $q->whereIn('status', ['waiting', 'playing', 'starting', 'paused'])->latest()->limit(1)])
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
            'players' => $room->roomPlayers->map(fn ($rp) => [
                'playerId' => (string) $rp->id,
                'userId' => (string) ($rp->adventurer_id ?? $rp->user_id),
                'userName' => ($rp->adventurer ?? $rp->user)?->name ?? 'Player',
                'teamId' => (string) $rp->team_id,
                'teamCode' => 'K' . $rp->team_id,
                'isLeader' => (bool) $rp->is_leader,
            ])->values()->all(),
        ];
        return ApiResponse::success($data);
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
        if ($room->status !== 'waiting') {
            return ApiResponse::error('لا يمكن المغادرة بعد بدء اللعبة. استخدم الاستسلام لإنهاء المغامرة.', 400);
        }

        $user = auth()->user();
        $roomPlayer = $user instanceof Adventurer
            ? RoomPlayer::where('room_id', $roomId)->where('adventurer_id', $user->id)->first()
            : RoomPlayer::where('room_id', $roomId)->where('user_id', $user->id)->first();

        if (!$roomPlayer) {
            return ApiResponse::error('أنت غير مشارك في هذه الغرفة', 403);
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

        $timeLeft = 120;
        if ($session->question_started_at) {
            $elapsed = (int) $session->question_started_at->diffInSeconds(now());
            $timeLeft = max(0, 120 - $elapsed);
        }

        $questionIds = $session->question_ids ?? [];
        $remainingCount = max(0, count($questionIds) - $session->current_round);

        $data = [
            'sessionId' => (string) $session->id,
            'status' => $session->status,
            'round' => $session->current_round,
            'remainingQuestionsCount' => $remainingCount,
            'question' => $questionData,
            'timeLeft' => $timeLeft,
            'questionStartedAt' => $session->question_started_at?->timestamp ? (int) ($session->question_started_at->timestamp * 1000) : null,
            'startTimerEndsAt' => $session->start_timer_ends_at?->timestamp ? (int) ($session->start_timer_ends_at->timestamp * 1000) : null,
            'teams' => $teams,
            'stage' => $this->firebaseSync->getStageDataForRoom($session->room),
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

        $session = $this->gameService->ensureSessionPlaying($session);
        if ($session->status === 'starting') {
            return ApiResponse::error('اللعبة لم تبدأ بعد', 400);
        }

        $optionIndex = (int) $request->input('optionIndex', $request->input('answerId', 1));
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

        $result = $this->gameService->submitAnswer($session, $roomPlayer->id, $optionIndex);
        $data = [
            'correct' => $result['correct'],
            'scoreDelta' => $result['scoreDelta'],
            'nextQuestionAvailable' => $result['nextQuestionAvailable'],
        ];
        return ApiResponse::success($data);
    }

    public function nextQuestion(int $sessionId): JsonResponse
    {
        $session = GameSession::find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }
        if ($session->status !== 'paused') {
            return ApiResponse::error('الجلسة ليست في حالة الإيقاف المؤقت', 400);
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

    public function timeout(int $sessionId): JsonResponse
    {
        $session = GameSession::with('room')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        if ($session->status !== 'playing') {
            return ApiResponse::error('لا يمكن إنهاء الوقت في هذه الحالة', 400);
        }

        $timeLimitSeconds = 30;
        if (!$session->question_started_at ||
            $session->question_started_at->diffInSeconds(now()) < $timeLimitSeconds) {
            return ApiResponse::error('لم ينته الوقت بعد', 400);
        }

        $room = $session->room()->with('roomPlayers')->first();
        $leaders = $room->roomPlayers->where('is_leader', true);
        $leaderIds = $leaders->pluck('id');

        $questionIds = $session->question_ids ?? [];
        $roundIndex = $session->current_round - 1;
        $questionId = $questionIds[$roundIndex] ?? null;

        $allLeadersAnswered = false;
        if ($questionId && $leaderIds->count() > 0) {
            $answeredLeaderIds = SessionAnswer::where('game_session_id', $session->id)
                ->where('question_id', $questionId)
                ->whereIn('room_player_id', $leaderIds)
                ->pluck('room_player_id')
                ->unique();

            $allLeadersAnswered = $answeredLeaderIds->count() >= $leaderIds->count();
        }

        // For life-points stages, treat missing answers as wrong (lose 1 life on timeout)
        $stageType = $room->subcategory?->stage?->stage_type;
        if ($questionId && $stageType === \App\Models\Stage::TYPE_LIFE_POINTS) {
            foreach ($leaders as $leader) {
                $alreadyAnswered = SessionAnswer::where('game_session_id', $session->id)
                    ->where('question_id', $questionId)
                    ->where('room_player_id', $leader->id)
                    ->exists();
                if (!$alreadyAnswered) {
                    SessionAnswer::create([
                        'game_session_id' => $session->id,
                        'question_id' => $questionId,
                        'room_player_id' => $leader->id,
                        'answer_index' => 0,
                        'correct' => false,
                        'score_delta' => 0,
                    ]);
                }
            }
        }

        $session->update(['status' => 'paused']);
        $this->firebaseSync->syncSessionPaused($session->fresh(), false);

        return ApiResponse::success([
            'paused' => true,
            'sessionId' => (string) $session->id,
            'reason' => $allLeadersAnswered ? 'all_leaders_answered' : 'timeout',
        ]);
    }

    public function surrender(int $sessionId): JsonResponse
    {
        $session = GameSession::find($sessionId);
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

        // When any player surrenders, the whole team loses and the session ends.
        $surrenderingTeamId = $roomPlayer->team_id;

        // Mark session and room as finished
        $session->update(['status' => 'finished']);
        $session->room?->update(['status' => 'finished']);

        $this->gameService->updatePointsForFinishedSession($session->fresh());

        // Build scores similar to getResult()
        $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer');
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

        // All teams except the surrendering team are considered winners
        $winnerIds = collect($byTeam)
            ->pluck('teamId')
            ->reject(fn ($id) => (int) $id === (int) $surrenderingTeamId)
            ->values()
            ->all();

        $this->firebaseSync->syncSessionEnd($session->fresh(), $winnerIds);

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

        $session->update(['status' => 'finished']);
        $session->room?->update(['status' => 'finished']);

        // Let existing logic handle points/winners if needed
        $this->gameService->updatePointsForFinishedSession($session->fresh());
        $this->firebaseSync->syncSessionEnd($session->fresh());

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
}
