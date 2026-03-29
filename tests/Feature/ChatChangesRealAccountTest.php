<?php

namespace Tests\Feature;

use App\Models\Adventurer;
use App\Models\Category;
use App\Models\CustomCategory;
use App\Models\GameSession;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\Subcategory;
use App\Models\Type;
use Tests\TestCase;

/**
 * Uses real `adventurers` rows in your database (no registration, no RefreshDatabase).
 * Point `.env` / `phpunit.xml` at the same DB where these accounts already exist.
 *
 * Room creator: moamen.hamed33322@gmail.com / 6789
 * Other player:  moamen.hamed3334422@gmail.com / 1234
 */
class ChatChangesRealAccountTest extends TestCase
{
    private const ROOM_CREATOR_EMAIL = 'moamen.hamed33322@gmail.com';

    private const ROOM_CREATOR_PASSWORD = '6789';

    private const OTHER_PLAYER_EMAIL = 'moamen.hamed3334422@gmail.com';

    private const OTHER_PLAYER_PASSWORD = '1234';

    /**
     * @return array{token: string, adventurer_id: int}
     */
    private function loginApp(string $email, string $password): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $token = $response->json('data.token');
        $id = (int) $response->json('data.user.id');
        $this->assertNotEmpty($token);
        $this->assertGreaterThan(0, $id);

        return ['token' => $token, 'adventurer_id' => $id];
    }

    /** @return array{type: Type, category: Category, subcategory: Subcategory} */
    private function seedFallbackTaxonomy(): array
    {
        $type = Type::create(['name' => 'T-RA-'.uniqid(), 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'C-RA-'.uniqid(), 'status' => true]);
        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'S-RA-'.uniqid(),
            'status' => true,
            'use_stage' => false,
        ]);

        return ['type' => $type, 'category' => $category, 'subcategory' => $subcategory];
    }

    public function test_existing_accounts_login_returns_distinct_bearer_tokens(): void
    {
        $a = $this->loginApp(self::ROOM_CREATOR_EMAIL, self::ROOM_CREATOR_PASSWORD);
        $b = $this->loginApp(self::OTHER_PLAYER_EMAIL, self::OTHER_PLAYER_PASSWORD);

        $this->assertNotSame($a['token'], $b['token']);
        $this->assertNotSame($a['adventurer_id'], $b['adventurer_id']);
    }

    public function test_get_games_api_returns_custom_draw_row_using_logged_in_creator(): void
    {
        $creator = $this->loginApp(self::ROOM_CREATOR_EMAIL, self::ROOM_CREATOR_PASSWORD);
        $other = Adventurer::query()->where('email', self::OTHER_PLAYER_EMAIL)->first();
        $this->assertNotNull($other, 'Other player adventurer must exist in DB: '.self::OTHER_PLAYER_EMAIL);

        $t = $this->seedFallbackTaxonomy();

        $customCategory = CustomCategory::create([
            'owner_adventurer_id' => $creator['adventurer_id'],
            'name' => 'My Deck',
            'status' => true,
        ]);

        $code = 'RA'.substr(preg_replace('/\D/', '', (string) microtime(true)), -6);
        $room = Room::create([
            'code' => $code,
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'Titled',
            'teams' => 2,
            'players' => 2,
            'status' => 'finished',
            'expires_at' => now()->addHour(),
            'created_by_adventurer_id' => $creator['adventurer_id'],
        ]);

        RoomPlayer::create([
            'room_id' => $room->id,
            'adventurer_id' => $creator['adventurer_id'],
            'team_id' => 1,
            'is_leader' => true,
            'score' => 20,
        ]);
        RoomPlayer::create([
            'room_id' => $room->id,
            'adventurer_id' => $other->id,
            'team_id' => 2,
            'is_leader' => true,
            'score' => 20,
        ]);

        $finishedSession = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'finished',
            'question_ids' => [],
        ]);

        $res = $this->withHeader('Authorization', 'Bearer '.$creator['token'])->getJson('/api/user/games');
        $res->assertOk();
        $games = $res->json('data.games');
        $this->assertIsArray($games);
        $row = collect($games)->firstWhere('id', (string) $finishedSession->id);
        $this->assertNotNull($row, 'Expected game row for finished session');
        $this->assertTrue($row['isCustom']);
        $this->assertSame((string) $customCategory->id, $row['customCategoryId']);
        $this->assertSame('My Deck', $row['customCategoryName']);
        $this->assertSame('draw', $row['result']);
    }
}
