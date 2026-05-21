<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameSession;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\Subcategory;
use App\Models\Type;
use App\Models\User;
use App\Services\FirebaseGameSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurrenderMultiTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{type: Type, category: Category, subcategory: Subcategory} */
    private function seedFallbackTaxonomy(): array
    {
        $type = Type::create(['name' => 'T', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'C', 'status' => true]);
        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'S',
            'status' => true,
            'use_stage' => false,
        ]);

        return ['type' => $type, 'category' => $category, 'subcategory' => $subcategory];
    }

    public function test_all_teams_surrender_in_three_team_game_finishes_session(): void
    {
        $this->mock(FirebaseGameSyncService::class, function ($mock) {
            $mock->shouldReceive('syncScores')->once();
            $mock->shouldReceive('syncSessionEnd')->once();
        });

        $t = $this->seedFallbackTaxonomy();
        $users = User::factory()->count(3)->create();
        $room = Room::create([
            'code' => '111111',
            'is_custom' => false,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'Three solo teams',
            'rounds' => 1,
            'questions_count' => 3,
            'teams' => 3,
            'players' => 3,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
        ]);

        foreach ($users as $i => $user) {
            RoomPlayer::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'team_id' => $i + 1,
                'is_leader' => true,
                'score' => 5 - $i,
            ]);
        }

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'question_ids' => [1, 2, 3],
            'surrendered_team_ids' => [],
        ]);

        foreach ([0, 1] as $index) {
            Sanctum::actingAs($users[$index]);
            $response = $this->postJson("/api/game/session/{$session->id}/surrender");
            $response->assertOk()
                ->assertJsonPath('data.endedBySurrender', false);
            $session->refresh();
            $this->assertSame('playing', $session->status);
        }

        Sanctum::actingAs($users[2]);
        $final = $this->postJson("/api/game/session/{$session->id}/surrender");
        $final->assertOk()
            ->assertJsonPath('data.endedBySurrender', true);

        $session->refresh();
        $room->refresh();
        $this->assertSame('finished', $session->status);
        $this->assertSame('finished', $room->status);
        $this->assertCount(3, $session->surrendered_team_ids);
    }

    public function test_joining_solo_player_team_makes_leader_by_default(): void
    {
        $this->mock(FirebaseGameSyncService::class, function ($mock) {
            $mock->shouldReceive('syncRoomPlayers')->once();
        });

        $t = $this->seedFallbackTaxonomy();
        $host = User::factory()->create(['available_sessions' => 5]);
        $guest = User::factory()->create();

        $room = Room::create([
            'code' => '222222',
            'is_custom' => false,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'Solo teams',
            'rounds' => 1,
            'questions_count' => 3,
            'teams' => 3,
            'players' => 3,
            'status' => 'waiting',
            'expires_at' => now()->addHour(),
        ]);

        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $host->id,
            'team_id' => 1,
            'is_leader' => true,
        ]);

        Sanctum::actingAs($guest);
        $response = $this->postJson("/api/game/room/{$room->id}/join", [
            'teamCode' => 'K2',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.isLeader', true);

        $this->assertDatabaseHas('room_players', [
            'room_id' => $room->id,
            'user_id' => $guest->id,
            'team_id' => 2,
            'is_leader' => true,
        ]);
    }
}
