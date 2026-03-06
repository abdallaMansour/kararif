<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ValidateCodeRequest;
use App\Http\Requests\Game\CreateRoomRequest;
use App\Http\Requests\Game\JoinRoomRequest;
use App\Http\Requests\Game\SubmitAnswerRequest;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\GameSession;
use App\Models\Type;
use App\Models\User;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\FirebaseGameSyncService;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;

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
        /** @var User $user */
        $user = auth()->user();
        if (($user->available_sessions ?? 0) < 1) {
            return ApiResponse::error('لا توجد جلسات لعبة متاحة. يرجى شراء حزمة للاستمرار.', 403);
        }

        $code = $this->gameService->generateRoomCode();
        $teams = (int) $request->input('teams', 2);
        $playersPerTeam = (int) $request->input('players', 2);
        $totalPlayers = $playersPerTeam * $teams;
        $rounds = (int) ($request->input('questionsCount') ?? $request->input('rounds', 5));

        $room = Room::create([
            'code' => $code,
            'type_id' => $request->input('questionType'),
            'category_id' => $request->input('mainCategoryId'),
            'subcategory_id' => $request->input('subCategoryId'),
            'created_by' => auth()->id(),
            'title' => $request->input('title'),
            'rounds' => $rounds,
            'teams' => $teams,
            'players' => $totalPlayers,
            'expires_at' => now()->addHours(24),
        ]);

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
            ->with(['type', 'category', 'subcategory', 'roomPlayers.user'])
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
        $data = [
            'roomId' => (string) $room->id,
            'code' => $room->code,
            'status' => $room->status,
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
            'players' => $room->roomPlayers->map(fn ($rp) => [
                'playerId' => (string) $rp->id,
                'userId' => (string) $rp->user_id,
                'userName' => $rp->user?->name ?? 'Player',
                'teamId' => (string) $rp->team_id,
                'teamCode' => 'K' . $rp->team_id,
                'isLeader' => (bool) $rp->is_leader,
            ])->values()->all(),
        ];
        return ApiResponse::success($data);
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

        $userId = auth()->id();
        /** @var User $user */
        $user = User::find($userId);
        $existing = RoomPlayer::where('room_id', $roomId)->where('user_id', $userId)->first();
        if ($existing) {
            return ApiResponse::success([
                'joined' => true,
                'teamId' => (string) $existing->team_id,
                'playerId' => (string) $existing->id,
                'teamCode' => 'K' . $existing->team_id,
            ]);
        }

        if (($user->available_sessions ?? 0) < 1) {
            return ApiResponse::error('لا توجد جلسات لعبة متاحة. يرجى شراء حزمة للاستمرار.', 403);
        }

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

        $player = RoomPlayer::create([
            'room_id' => $roomId,
            'user_id' => $userId,
            'team_id' => $teamId,
            'is_leader' => $isLeader,
        ]);

        $user->decrement('available_sessions');

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

    public function getSession(int $sessionId): JsonResponse
    {
        $session = GameSession::with('room.roomPlayers.user')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        $questionData = $this->gameService->getCurrentQuestion($session);
        $teams = $session->room->roomPlayers->groupBy('team_id')->map(function ($players, $teamId) {
            $first = $players->first();
            $name = $first?->user?->name ?? 'الفريق ' . $teamId;
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

        $data = [
            'sessionId' => (string) $session->id,
            'round' => $session->current_round,
            'question' => $questionData,
            'timeLeft' => $timeLeft,
            'questionStartedAt' => $session->question_started_at?->timestamp ? (int) ($session->question_started_at->timestamp * 1000) : null,
            'teams' => $teams,
        ];
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

        $optionIndex = (int) $request->input('optionIndex', $request->input('answerId', 1));
        $user = auth()->user();
        $roomPlayer = RoomPlayer::where('room_id', $session->room_id)->where('user_id', $user->id)->first();
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
            'nextQuestion' => $result['nextQuestion'] !== null,
        ];
        return ApiResponse::success($data);
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

        /** @var User $user */
        $user = auth()->user();
        $roomPlayer = RoomPlayer::where('room_id', $session->room_id)
            ->where('user_id', $user->id)
            ->first();
        if (!$roomPlayer) {
            return ApiResponse::error('أنت غير مشارك في هذه المغامرة', 403);
        }

        $user->increment('surrender_count');

        // When any player surrenders, the whole team loses and the session ends.
        $surrenderingTeamId = $roomPlayer->team_id;

        // Mark session and room as finished
        $session->update(['status' => 'finished']);
        $session->room?->update(['status' => 'finished']);

        // Build scores similar to getResult()
        $session->load('room.roomPlayers.user');
        $byTeam = $session->room->roomPlayers->groupBy('team_id')->map(function ($players, $teamId) {
            $first = $players->first();
            $name = $first?->user?->name ?? 'الفريق ' . $teamId;
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

    public function getResult(int $sessionId): JsonResponse
    {
        $session = GameSession::with('room.roomPlayers.user')->find($sessionId);
        if (!$session) {
            return ApiResponse::error('الجلسة غير موجودة', 404);
        }

        $byTeam = $session->room->roomPlayers->groupBy('team_id')->map(function ($players, $teamId) {
            $first = $players->first();
            $name = $first?->user?->name ?? 'الفريق ' . $teamId;
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
