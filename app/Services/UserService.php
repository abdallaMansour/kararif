<?php

namespace App\Services;

use App\Models\Adventurer;
use App\Models\GameSession;
use App\Models\Room;
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
     * Score-based outcome for a player after a finished session.
     *
     * @return array{result: 'win'|'loss'|'draw', rankLabel: string|null}
     */
    public function classifyFinishedSessionForPlayer(Room $room, GameSession $session, RoomPlayer $rp): array
    {
        $surrenderedTeamIds = array_map('strval', $session->surrendered_team_ids ?? []);

        $activeTeamScores = $room->roomPlayers
            ->groupBy('team_id')
            ->reject(fn ($_, $teamId) => in_array((string) $teamId, $surrenderedTeamIds, true))
            ->map(fn ($players) => $players->sum('score'));

        if (in_array((string) $rp->team_id, $surrenderedTeamIds, true)) {
            return ['result' => 'loss', 'rankLabel' => null];
        }

        if ($activeTeamScores->count() <= 1) {
            return ['result' => 'win', 'rankLabel' => 'أول'];
        }

        $maxScore = $activeTeamScores->max();
        $teamsSharingTopScore = $activeTeamScores->filter(fn ($s) => $s === $maxScore);
        $myTeamScore = $activeTeamScores[(string) $rp->team_id] ?? null;

        if ($teamsSharingTopScore->count() > 1 && $myTeamScore === $maxScore) {
            return ['result' => 'draw', 'rankLabel' => null];
        }

        $sortedByScore = $activeTeamScores->sortByDesc(fn ($s) => $s);
        $prevScore = null;
        $rank = 0;
        $teamRanks = [];
        foreach ($sortedByScore as $tid => $score) {
            if ($prevScore === null || $score < $prevScore) {
                $rank++;
            }
            $teamRanks[(string) $tid] = $rank;
            $prevScore = $score;
        }
        $myRank = $teamRanks[(string) $rp->team_id] ?? $rank + 1;
        $rankLabel = match ($myRank) {
            1 => 'أول',
            2 => 'ثاني',
            3 => 'ثالث',
            default => null,
        };

        return [
            'result' => $myRank === 1 ? 'win' : 'loss',
            'rankLabel' => $rankLabel,
        ];
    }

    /**
     * Returns wins, losses, and draws for a user/adventurer based on finished game sessions.
     * Draw = tie for first place (same top team score among active teams).
     *
     * @param User|Adventurer $user
     * @return array{wins: int, losses: int, draws: int}
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
            'room.gameSessions' => fn ($q) => $q->where('status', 'finished')->latest('id')->limit(1),
        ])->get();

        // Safety: avoid counting the same room twice for any reason.
        $roomPlayers = $roomPlayers->unique('room_id')->values();

        $wins = 0;
        $losses = 0;
        $draws = 0;

        foreach ($roomPlayers as $rp) {
            $session = $rp->room->gameSessions->first();
            if (! $session) {
                continue;
            }

            $outcome = $this->classifyFinishedSessionForPlayer($rp->room, $session, $rp);
            match ($outcome['result']) {
                'win' => $wins++,
                'loss' => $losses++,
                'draw' => $draws++,
            };
        }

        return ['wins' => $wins, 'losses' => $losses, 'draws' => $draws];
    }
}
