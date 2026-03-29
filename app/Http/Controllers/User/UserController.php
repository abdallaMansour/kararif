<?php

namespace App\Http\Controllers\User;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignAvatarRequest;
use Illuminate\Support\Facades\DB;
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
        $countryCodeFilter = request('country_code');

        $roomPlayerQuery = $user instanceof \App\Models\Adventurer
            ? RoomPlayer::where('adventurer_id', $user->id)
            : RoomPlayer::where('user_id', $user->id);
        $roomPlayerQuery
            ->whereHas('room', fn ($q) => $q->whereHas('gameSessions', fn ($s) => $s->where('status', 'finished')));

        if ($countryCodeFilter) {
            $roomPlayerQuery->whereExists(function ($q) use ($countryCodeFilter) {
                $q->select(DB::raw(1))
                    ->from('room_players as opp')
                    ->whereColumn('opp.room_id', 'room_players.room_id')
                    ->whereColumn('opp.id', '!=', 'room_players.id')
                    ->where(function ($sub) use ($countryCodeFilter) {
                        $sub->whereExists(function ($ue) use ($countryCodeFilter) {
                            $ue->select(DB::raw(1))
                                ->from('users')
                                ->whereColumn('users.id', 'opp.user_id')
                                ->where('users.country_code', $countryCodeFilter);
                        })->orWhereExists(function ($ae) use ($countryCodeFilter) {
                            $ae->select(DB::raw(1))
                                ->from('adventurers')
                                ->whereColumn('adventurers.id', 'opp.adventurer_id')
                                ->where('adventurers.country_code', $countryCodeFilter);
                        });
                    });
            });
        }

        $roomPlayers = $roomPlayerQuery
            ->with([
                'room.type',
                'room.category',
                'room.customCategory',
                'room.roomPlayers.user',
                'room.roomPlayers.adventurer',
                'room.gameSessions' => fn ($q) => $q->where('status', 'finished')->latest('id'),
            ])
            ->orderByDesc('joined_at')
            ->paginate($limit, ['*'], 'page', $page);

        $resultFilter = request('result'); // win|loss|draw
        $rankFilter = request('rank'); // 1|2|3
        $games = collect($roomPlayers->items())->map(function (RoomPlayer $rp) {
            $session = $rp->room->gameSessions->sortByDesc('id')->first();

            $isCustom = (bool) $rp->room->is_custom;
            $roomName = $rp->room->title ?: $rp->room->type?->name ?: ($rp->room->category?->name ?? 'مغامرة');
            $categoryLabel = $isCustom
                ? ($rp->room->customCategory?->name ?? $rp->room->title ?? $rp->room->type?->name)
                : ($rp->room->category?->name ?? $rp->room->type?->name);
            $myScore = $rp->score;

            if (! $session) {
                return [
                    'id' => (string) $rp->room_id,
                    'date' => $rp->joined_at?->toIso8601String(),
                    'roomName' => $roomName,
                    'category' => $categoryLabel,
                    'isCustom' => $isCustom,
                    'customCategoryId' => $isCustom && $rp->room->custom_category_id
                        ? (string) $rp->room->custom_category_id
                        : null,
                    'customCategoryName' => $isCustom ? ($rp->room->customCategory?->name) : null,
                    'result' => 'loss',
                    'userRank' => null,
                    'score' => $myScore,
                    'opponent' => null,
                    'opponentCountry' => null,
                ];
            }

            $outcome = $this->userService->classifyFinishedSessionForPlayer($rp->room, $session, $rp);
            $opponent = $rp->room->roomPlayers->where('id', '!=', $rp->id)->first();
            $opponentEntity = $opponent?->adventurer ?? $opponent?->user;
            $opponentName = $opponentEntity?->name ?? null;
            $opponentCountry = $opponentEntity ? [
                'label' => $opponentEntity->country_label ?? null,
                'code' => $opponentEntity->country_code ?? null,
            ] : null;

            return [
                'id' => (string) ($session->id ?? $rp->room_id),
                'date' => $rp->joined_at?->toIso8601String(),
                'roomName' => $roomName,
                'category' => $categoryLabel,
                'isCustom' => $isCustom,
                'customCategoryId' => $isCustom && $rp->room->custom_category_id
                    ? (string) $rp->room->custom_category_id
                    : null,
                'customCategoryName' => $isCustom ? ($rp->room->customCategory?->name) : null,
                'result' => $outcome['result'],
                'userRank' => $outcome['rankLabel'],
                'score' => $myScore,
                'opponent' => $opponentName,
                'opponentCountry' => $opponentCountry,
            ];
        })->values();

        if ($resultFilter && in_array($resultFilter, ['win', 'loss', 'draw'], true)) {
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
