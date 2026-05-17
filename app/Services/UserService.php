<?php

namespace App\Services;

use App\Models\Adventurer;
use App\Models\GameSession;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        $myTeamId = (string) $rp->team_id;

        if (in_array($myTeamId, $surrenderedTeamIds, true)) {
            return ['result' => 'loss', 'rankLabel' => null];
        }

        $winnerTeamIds = array_map('strval', $session->winner_team_ids ?? []);
        if ($winnerTeamIds === []) {
            $winnerTeamIds = app(GameService::class)->resolveWinnerTeamIds($session);
        }

        if ($winnerTeamIds !== []) {
            if (count($winnerTeamIds) > 1 && in_array($myTeamId, $winnerTeamIds, true)) {
                return ['result' => 'draw', 'rankLabel' => null];
            }
            if (in_array($myTeamId, $winnerTeamIds, true)) {
                return ['result' => 'win', 'rankLabel' => 'أول'];
            }

            return ['result' => 'loss', 'rankLabel' => $this->rankLabelByScore($room, $session, $surrenderedTeamIds, $myTeamId)];
        }

        $activeTeamScores = app(GameService::class)->resolveTeamScoresForSession($session, $room);

        if ($activeTeamScores->count() <= 1) {
            return ['result' => 'win', 'rankLabel' => 'أول'];
        }

        $maxScore = $activeTeamScores->max();
        $teamsSharingTopScore = $activeTeamScores->filter(fn ($s) => $s === $maxScore);
        $myTeamScore = $activeTeamScores[$myTeamId] ?? null;

        if ($teamsSharingTopScore->count() > 1 && $myTeamScore === $maxScore) {
            return ['result' => 'draw', 'rankLabel' => null];
        }

        $rankLabel = $this->rankLabelByScore($room, $session, $surrenderedTeamIds, $myTeamId);
        $myRank = match ($rankLabel) {
            'أول' => 1,
            'ثاني' => 2,
            'ثالث' => 3,
            default => 99,
        };

        return [
            'result' => $myRank === 1 ? 'win' : 'loss',
            'rankLabel' => $rankLabel,
        ];
    }

    /**
     * @param list<string> $surrenderedTeamIds
     */
    private function rankLabelByScore(Room $room, GameSession $session, array $surrenderedTeamIds, string $myTeamId): ?string
    {
        $activeTeamScores = app(GameService::class)->resolveTeamScoresForSession($session, $room);

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
        $myRank = $teamRanks[$myTeamId] ?? $rank + 1;

        return match ($myRank) {
            1 => 'أول',
            2 => 'ثاني',
            3 => 'ثالث',
            default => null,
        };
    }

    /**
     * Adventurer + linked User rows (same email) for stats when history spans both tables.
     *
     * @return array{adventurer_ids: list<int>, user_ids: list<int>}
     */
    public function participantIdsForStats(User|Adventurer $user): array
    {
        $adventurerIds = [];
        $userIds = [];

        if ($user instanceof Adventurer) {
            $adventurerIds[] = (int) $user->id;
            if ($user->email) {
                $linkedUser = User::query()->where('email', $user->email)->value('id');
                if ($linkedUser) {
                    $userIds[] = (int) $linkedUser;
                }
            }
        } else {
            $userIds[] = (int) $user->id;
            if ($user->email) {
                $linkedAdventurer = Adventurer::query()->where('email', $user->email)->value('id');
                if ($linkedAdventurer) {
                    $adventurerIds[] = (int) $linkedAdventurer;
                }
            }
        }

        return [
            'adventurer_ids' => array_values(array_unique($adventurerIds)),
            'user_ids' => array_values(array_unique($userIds)),
        ];
    }

    public function scopeRoomPlayersForParticipant(Builder $query, User|Adventurer $user): Builder
    {
        $ids = $this->participantIdsForStats($user);

        return $query->where(function (Builder $q) use ($ids) {
            if ($ids['adventurer_ids'] !== []) {
                $q->whereIn('adventurer_id', $ids['adventurer_ids']);
            }
            if ($ids['user_ids'] !== []) {
                $ids['adventurer_ids'] === []
                    ? $q->whereIn('user_id', $ids['user_ids'])
                    : $q->orWhereIn('user_id', $ids['user_ids']);
            }
        });
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
        $fromSessions = $this->countOutcomesFromFinishedSessions($user);

        if ($user instanceof Adventurer) {
            return [
                'wins' => max($fromSessions['wins'], (int) ($user->number_full_winnings ?? 0)),
                'losses' => max($fromSessions['losses'], (int) ($user->number_game_losses ?? 0)),
                'draws' => $fromSessions['draws'],
            ];
        }

        return $fromSessions;
    }

    /**
     * Backfill permanent win/loss counters from finished sessions (never lowers stored totals).
     */
    public function syncParticipantRecordCounters(User|Adventurer $user): void
    {
        if (! $user instanceof Adventurer) {
            return;
        }

        $fromSessions = $this->countOutcomesFromFinishedSessions($user);

        $user->update([
            'number_full_winnings' => max((int) ($user->number_full_winnings ?? 0), $fromSessions['wins']),
            'number_game_losses' => max((int) ($user->number_game_losses ?? 0), $fromSessions['losses']),
        ]);
    }

    /**
     * @return array{wins: int, losses: int, draws: int}
     */
    public function countOutcomesFromFinishedSessions(User|Adventurer $user): array
    {
        $ids = $this->participantIdsForStats($user);

        $sessions = GameSession::query()
            ->where('status', 'finished')
            ->whereHas('room.roomPlayers', function (Builder $q) use ($ids) {
                $q->where(function (Builder $inner) use ($ids) {
                    if ($ids['adventurer_ids'] !== []) {
                        $inner->whereIn('adventurer_id', $ids['adventurer_ids']);
                    }
                    if ($ids['user_ids'] !== []) {
                        $ids['adventurer_ids'] === []
                            ? $inner->whereIn('user_id', $ids['user_ids'])
                            : $inner->orWhereIn('user_id', $ids['user_ids']);
                    }
                });
            })
            ->with(['room.roomPlayers', 'sessionAnswers.roomPlayer'])
            ->orderByDesc('id')
            ->get()
            ->unique('id');

        $wins = 0;
        $losses = 0;
        $draws = 0;

        foreach ($sessions as $session) {
            $room = $session->room;
            if (! $room) {
                continue;
            }

            $rp = $this->resolveRoomPlayerForParticipant($room, $user, $ids);
            if (! $rp) {
                continue;
            }

            $outcome = $this->classifyFinishedSessionForPlayer($room, $session, $rp);
            match ($outcome['result']) {
                'win' => $wins++,
                'loss' => $losses++,
                'draw' => $draws++,
            };
        }

        return ['wins' => $wins, 'losses' => $losses, 'draws' => $draws];
    }

    /**
     * @param array{adventurer_ids: list<int>, user_ids: list<int>} $ids
     */
    private function resolveRoomPlayerForParticipant(Room $room, User|Adventurer $user, array $ids): ?RoomPlayer
    {
        $players = $room->roomPlayers;

        if ($user instanceof Adventurer) {
            $rp = $players->first(fn (RoomPlayer $p) => in_array((int) $p->adventurer_id, $ids['adventurer_ids'], true));
            if ($rp) {
                return $rp;
            }
        } else {
            $rp = $players->first(fn (RoomPlayer $p) => in_array((int) $p->user_id, $ids['user_ids'], true));
            if ($rp) {
                return $rp;
            }
        }

        if ($ids['adventurer_ids'] !== []) {
            return $players->first(fn (RoomPlayer $p) => in_array((int) $p->adventurer_id, $ids['adventurer_ids'], true));
        }

        if ($ids['user_ids'] !== []) {
            return $players->first(fn (RoomPlayer $p) => in_array((int) $p->user_id, $ids['user_ids'], true));
        }

        return null;
    }
}
