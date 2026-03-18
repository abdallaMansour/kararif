<?php

namespace App\Services;

use App\Models\Adventurer;
use App\Models\RoomPlayer;
use App\Models\User;
use App\Utils\ImageUpload;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function changePassword(array $data)
    {
        $user = auth()->guard('sanctum')->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['error' => 'The current password is incorrect.'], 401);
        }
        $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json(['message' => 'password updated successfully']);
    }

    public function changeImage($image)
    {
        try {
            $user = Auth::user();
            if ($user instanceof \App\Models\Adventurer) {
                return response()->json(['message' => 'استخدم تعيين الصورة الشخصية من القائمة المتاحة']);
            }
            $user->clearMediaCollection();
            $user->addMedia($image)->toMediaCollection();
            $user->save();
            return response()->json(['message' => 'user image updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }


    public function changeInfo(array $data)
    {
        try {
            /** @var User $user */
            $user = auth()->guard('sanctum')->user();
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);
            return response()->json(['message' => 'user updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function createAdmin($data)
    {
        $user = User::create($data->allWithHashedPassword());
        $user->type = User::ADMIN_TYPE;
        $user->save();

        if ($data->roles) {
            foreach ($data->roles as $roleId) {
                $user->roles()->attach($roleId, ['user_type' => User::class]);
            }
        }

        if ($data->hasFile('image')) {
            $imagePath = ImageUpload::uploadImage($data->file('image'), 'images/users');
            $user->image = $imagePath;
            $user->save();
        }

        $user->refresh();
        return $user;
    }

    /**
     * Returns wins and losses count for a user/adventurer based on finished game sessions.
     * Win = player's team had the highest score in that session; otherwise loss.
     *
     * @param User|Adventurer $user
     * @return array{wins: int, losses: int}
     */
    public function getWinsLosses(User|Adventurer $user): array
    {
        $query = RoomPlayer::whereHas('room', function ($q) {
            $q->whereHas('gameSessions', fn ($s) => $s->where('status', 'finished'));
        });

        if ($user instanceof Adventurer) {
            $query->where('adventurer_id', $user->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $roomPlayers = $query->with([
            'room.roomPlayers',
            'room.gameSessions' => fn ($q) => $q->where('status', 'finished')->latest()->limit(1),
        ])->get();

        // Safety: avoid counting the same room twice for any reason.
        $roomPlayers = $roomPlayers->unique('room_id')->values();

        $wins = 0;
        $losses = 0;

        foreach ($roomPlayers as $rp) {
            $session = $rp->room->gameSessions->first();
            if (!$session) {
                continue;
            }
            $byTeam = $rp->room->roomPlayers->groupBy('team_id')->map(fn ($players) => $players->sum('score'));
            $maxScore = $byTeam->max();
            $myTeamScore = $byTeam->get((string) $rp->team_id, 0);

            // If it's effectively a solo/one-team session, treat it as a win.
            if ($byTeam->count() <= 1) {
                $wins++;
                continue;
            }

            // Win = my team has the highest score (ties count as win).
            if ($maxScore !== null && $myTeamScore >= $maxScore) {
                $wins++;
            } else {
                $losses++;
            }
        }

        return ['wins' => $wins, 'losses' => $losses];
    }
}
