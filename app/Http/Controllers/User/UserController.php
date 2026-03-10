<?php

namespace App\Http\Controllers\User;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignAvatarRequest;
use App\Http\Requests\Users\ChangeImageRequest;
use App\Http\Requests\Users\ChangePasswordRequest;
use App\Http\Requests\Users\ChangeUserInfoRequest;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\RoomPlayer;
use App\Models\User;
use App\Services\RankPrizeService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected RankPrizeService $rankPrizeService
    ) {}

    public function changePassword(ChangePasswordRequest $request)
    {
        return $this->userService->changePassword($request->only('current_password', 'password'));
    }

    public function changeImage(ChangeImageRequest $request)
    {
        return $this->userService->changeImage($request->file('image'));
    }

    public function changeInfo(ChangeUserInfoRequest $request)
    {
        return $this->userService->changeInfo($request->validated());
    }

    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->guard('sanctum')->user()->load('avatarRelation');
        $this->rankPrizeService->syncUserRankPrizes($user);
        $user->refresh();
        $data = (new UserResource($user))->toArray(request());
        $data['id'] = (string) $data['id'];
        return ApiResponse::success($data);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->guard('sanctum')->user();
        $data = $request->validated();

        if (!empty($data['fullName'])) {
            $user->name = $data['fullName'];
        }
        if (array_key_exists('phone', $data)) {
            $user->phone = $data['phone'];
        }
        if (!empty($data['newPassword'])) {
            $user->password = Hash::make($data['newPassword']);
        }
        $user->save();

        $out = (new UserResource($user->fresh()->load('avatarRelation')))->toArray(request());
        $out['id'] = (string) $out['id'];
        return ApiResponse::success($out);
    }

    public function assignAvatar(AssignAvatarRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->guard('sanctum')->user();
        $user->avatar_id = $request->input('avatarId');
        $user->save();

        $out = (new UserResource($user->fresh()->load('avatarRelation')))->toArray(request());
        $out['id'] = (string) $out['id'];
        return ApiResponse::success($out, 'تم تحديث الصورة الشخصية', 200);
    }

    public function deleteAccount(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->guard('sanctum')->user();
        $user->tokens()->delete();
        $user->delete();
        return ApiResponse::success(null, 'تم حذف الحساب', 200);
    }

    public function getBalance(): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        return ApiResponse::success([
            'balance' => (int) ($user->available_sessions ?? 0),
            'currencyLabel' => 'جلسة',
        ]);
    }

    public function getGames(): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        $limit = (int) request('limit', 10);
        $page = (int) request('page', 1);

        $roomPlayerQuery = $user instanceof \App\Models\Adventurer
            ? RoomPlayer::where('adventurer_id', $user->id)
            : RoomPlayer::where('user_id', $user->id);
        $roomPlayers = $roomPlayerQuery
            ->whereHas('room', fn ($q) => $q->whereHas('gameSessions', fn ($s) => $s->where('status', 'finished')))
            ->with(['room.roomPlayers.user', 'room.roomPlayers.adventurer', 'room.gameSessions' => fn ($q) => $q->where('status', 'finished')])
            ->orderByDesc('joined_at')
            ->paginate($limit, ['*'], 'page', $page);

        $resultFilter = request('result'); // win|loss
        $rankFilter = request('rank'); // 1|2|3
        $games = collect($roomPlayers->items())->map(function (RoomPlayer $rp) {
            $session = $rp->room->gameSessions->first();
            $roomName = $rp->room->title ?: $rp->room->type?->name ?: ($rp->room->category?->name ?? 'مغامرة');
            $myScore = $rp->score;
            $playersByScore = $rp->room->roomPlayers->sortByDesc('score')->values();
            $myRank = $playersByScore->search(fn ($p) => $p->id === $rp->id) + 1;
            $myTeamId = $rp->team_id;
            $teamScores = $rp->room->roomPlayers->groupBy('team_id')->map(fn ($pls) => $pls->sum('score'));
            $sortedTeams = $teamScores->sortByDesc(fn ($s) => $s)->keys()->values();
            $teamRank = $sortedTeams->search($myTeamId) + 1;
            $userRank = $teamRank;
            $result = 'draw';
            if ($teamScores->count() > 1) {
                $maxScore = $teamScores->max();
                $myTeamScore = $teamScores->get($myTeamId, 0);
                $result = $myTeamScore >= $maxScore ? 'win' : 'loss';
            }
            $opponent = $rp->room->roomPlayers->where('id', '!=', $rp->id)->first();
            $opponentName = ($opponent->adventurer ?? $opponent->user)?->name ?? null;
            $rankLabel = match ($userRank) {
                1 => 'أول',
                2 => 'ثاني',
                3 => 'ثالث',
                default => null,
            };
            return [
                'id' => (string) ($session?->id ?? $rp->room_id),
                'date' => $rp->joined_at?->toIso8601String(),
                'roomName' => $roomName,
                'category' => $rp->room->category?->name ?? $rp->room->type?->name,
                'result' => $result,
                'userRank' => $rankLabel,
                'score' => $myScore,
                'opponent' => $opponentName,
            ];
        })->values();

        if ($resultFilter && in_array($resultFilter, ['win', 'loss'], true)) {
            $games = $games->where('result', $resultFilter)->values();
        }
        if ($rankFilter && in_array($rankFilter, ['1', '2', '3'], true)) {
            $rankLabel = match ($rankFilter) { '1' => 'أول', '2' => 'ثاني', '3' => 'ثالث', default => null };
            $games = $games->where('userRank', $rankLabel)->values();
        }

        return ApiResponse::success([
            'games' => $games->values()->all(),
            'total' => $roomPlayers->total(),
        ]);
    }

    public function getLevels(): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        $level = (int) ($user->level ?? 1);
        $points = (int) ($user->points ?? 0);
        $pointsPerLevel = 100;
        $nextLevelAt = $level * $pointsPerLevel;

        return ApiResponse::success([
            'level' => $level,
            'points' => $points,
            'nextLevelAt' => $nextLevelAt,
            'badge' => null,
        ]);
    }
}
