<?php

namespace Database\Seeders;

use App\Models\Adventurer;
use App\Models\Avatar;
use App\Models\Category;
use App\Models\GameSession;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\Subcategory;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SeedUserGamesForMoamenSeeder extends Seeder
{
    private const EMAIL = 'moamen.hamed33322@gmail.com';

    private ?int $opponentAdventurerId = null;

    /**
     * Seeds game history for moamen.hamed33322@gmail.com so that
     * GET /api/user/games?page=1&limit=10&rank=1&result=win returns data.
     * Does not delete any existing data.
     */
    public function run(): void
    {
        $adventurer = Adventurer::where('email', self::EMAIL)->first();
        $user = $adventurer ? null : User::where('email', self::EMAIL)->first();

        if (!$adventurer && !$user) {
            $this->command->warn('No adventurer or user found for ' . self::EMAIL . '. Skipping.');
            return;
        }

        $type = Type::first();
        $category = $type ? Category::where('type_id', $type->id)->first() : null;
        $category = $category ?? Category::first();
        $subcategory = $category ? Subcategory::where('category_id', $category->id)->first() : null;
        $subcategory = $subcategory ?? Subcategory::first();

        if (!$type || !$category || !$subcategory) {
            $this->command->warn('Missing type/category/subcategory. Run other seeders first.');
            return;
        }

        $count = 3; // create 3 games: rank 1 win
        for ($i = 0; $i < $count; $i++) {
            $this->createFinishedGame(
                $type,
                $category,
                $subcategory,
                $adventurer,
                $user,
                rank: 1,
                result: 'win',
            );
        }

        $this->command->info('Created ' . $count . ' finished game(s) with rank=1 & result=win for ' . self::EMAIL);
    }

    private function createFinishedGame(
        Type $type,
        Category $category,
        Subcategory $subcategory,
        ?Adventurer $adventurer,
        ?User $user,
        int $rank,
        string $result,
    ): void {
        $code = strtoupper(Str::random(6));
        while (Room::where('code', $code)->exists()) {
            $code = strtoupper(Str::random(6));
        }

        $room = Room::create([
            'code' => $code,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'created_by' => $user?->id,
            'created_by_adventurer_id' => $adventurer?->id,
            'title' => 'تحدي الأبطال - ' . Str::random(4),
            'rounds' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'finished',
        ]);

        GameSession::create([
            'room_id' => $room->id,
            'current_round' => 5,
            'status' => 'finished',
            'started_at' => now()->subHours(rand(1, 48)),
        ]);

        // User's team (team 1) wins with higher score
        $myScore = $result === 'win' ? 100 : 50;
        $opponentScore = $result === 'win' ? 50 : 100;

        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user?->id,
            'adventurer_id' => $adventurer?->id,
            'team_id' => 1,
            'is_leader' => true,
            'score' => $myScore,
            'joined_at' => now()->subHours(rand(1, 48)),
        ]);

        // Opponent (team 2)
        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => null,
            'adventurer_id' => $this->getOrCreateOpponentAdventurer(),
            'team_id' => 2,
            'is_leader' => false,
            'score' => $opponentScore,
            'joined_at' => now()->subHours(rand(1, 48)),
        ]);
    }

    private function getOrCreateOpponentAdventurer(): int
    {
        if ($this->opponentAdventurerId !== null) {
            return $this->opponentAdventurerId;
        }
        $opponent = Adventurer::where('email', '!=', self::EMAIL)->first();
        if ($opponent) {
            $this->opponentAdventurerId = $opponent->id;
            return $this->opponentAdventurerId;
        }
        $avatarId = Avatar::first()?->id;
        $adv = Adventurer::firstOrCreate(
            ['email' => 'opponent-games-seed@seed.test'],
            [
                'name' => 'خصم تجريبي',
                'password' => bcrypt('password'),
                'available_sessions' => 5,
                'avatar_id' => $avatarId,
            ]
        );
        $this->opponentAdventurerId = $adv->id;
        return $this->opponentAdventurerId;
    }
}
