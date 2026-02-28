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
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
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
        $user = auth()->guard('sanctum')->user()->load('avatarRelation');
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
            'balance' => (int) ($user->balance ?? 0),
            'currencyLabel' => 'لعبة',
        ]);
    }

    public function getGames(): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        $limit = (int) request('limit', 10);
        $page = (int) request('page', 1);

        $roomPlayers = RoomPlayer::where('user_id', $user->id)
            ->whereHas('room', fn ($q) => $q->whereHas('gameSessions', fn ($s) => $s->where('status', 'finished')))
            ->with(['room.roomPlayers.user', 'room.gameSessions' => fn ($q) => $q->where('status', 'finished')])
            ->orderByDesc('joined_at')
            ->paginate($limit, ['*'], 'page', $page);

        $games = collect($roomPlayers->items())->map(function (RoomPlayer $rp) {
            $session = $rp->room->gameSessions->first();
            $myScore = $rp->score;
            $opponent = $rp->room->roomPlayers->where('id', '!=', $rp->id)->first();
            $result = 'draw';
            if ($opponent) {
                $result = $myScore > $opponent->score ? 'win' : ($myScore < $opponent->score ? 'loss' : 'draw');
            }
            return [
                'id' => (string) ($session?->id ?? $rp->room_id),
                'date' => $rp->joined_at?->toIso8601String(),
                'result' => $result,
                'score' => $myScore,
                'opponent' => $opponent?->user?->name,
            ];
        })->values()->all();

        return ApiResponse::success([
            'games' => $games,
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
