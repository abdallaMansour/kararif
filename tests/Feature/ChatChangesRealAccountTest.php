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
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Uses real rows in your database (no RefreshDatabase, no registration).
 *
 * Defaults (override with .env when passwords differ):
 *   KARARIF_TEST_CREATOR_EMAIL, KARARIF_TEST_CREATOR_PASSWORD
 *   KARARIF_TEST_PLAYER_EMAIL, KARARIF_TEST_PLAYER_PASSWORD
 *
 * Auth: tries POST /api/auth/login (Adventurer). If that returns 401, falls back to
 * verifying password against `adventurers` or `users` and issuing a Sanctum token
 * (covers accounts that exist only as User, or pin-only Adventurer rows).
 */
class ChatChangesRealAccountTest extends TestCase
{
    private const ROOM_CREATOR_EMAIL = 'moamen.hamed33322@gmail.com';

    private const ROOM_CREATOR_PASSWORD = '6789';

    private const OTHER_PLAYER_EMAIL = 'moamen.hamed3334422@gmail.com';

    private const OTHER_PLAYER_PASSWORD = '1234';

    private function creatorEmail(): string
    {
        return (string) env('KARARIF_TEST_CREATOR_EMAIL', self::ROOM_CREATOR_EMAIL);
    }

    private function creatorPassword(): string
    {
        return (string) env('KARARIF_TEST_CREATOR_PASSWORD', self::ROOM_CREATOR_PASSWORD);
    }

    private function playerEmail(): string
    {
        return (string) env('KARARIF_TEST_PLAYER_EMAIL', self::OTHER_PLAYER_EMAIL);
    }

    private function playerPassword(): string
    {
        return (string) env('KARARIF_TEST_PLAYER_PASSWORD', self::OTHER_PLAYER_PASSWORD);
    }

    /**
     * @return array{token: string, kind: 'adventurer'|'user', id: int}
     */
    private function obtainAuth(string $email, string $password): array
    {
        $http = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($http->status() === 200 && $http->json('success')) {
            return [
                'token' => (string) $http->json('data.token'),
                'kind' => 'adventurer',
                'id' => (int) $http->json('data.user.id'),
            ];
        }

        $adv = Adventurer::query()
            ->where(function ($q) use ($email) {
                $q->where('email', $email)->orWhere('username', $email);
            })
            ->first();
        if ($adv && ! empty($adv->password) && Hash::check($password, $adv->password)) {
            return [
                'token' => $adv->createToken('real-account-test')->plainTextToken,
                'kind' => 'adventurer',
                'id' => (int) $adv->id,
            ];
        }

        $user = User::query()
            ->where(function ($q) use ($email) {
                $q->where('email', $email)->orWhere('username', $email);
            })
            ->first();
        if ($user && ! empty($user->password) && Hash::check($password, $user->password)) {
            return [
                'token' => $user->createToken('real-account-test')->plainTextToken,
                'kind' => 'user',
                'id' => (int) $user->id,
            ];
        }

        $this->fail(sprintf(
            'Could not authenticate %s: /api/auth/login HTTP %s. No matching Adventurer/User or password mismatch. Set KARARIF_TEST_* env vars if needed.',
            $email,
            $http->status()
        ));
    }

    /**
     * @return array{kind: 'adventurer'|'user', id: int}|null
     */
    private function findOtherPlayerByEmail(string $email): ?array
    {
        if ($a = Adventurer::query()->where('email', $email)->first()) {
            return ['kind' => 'adventurer', 'id' => (int) $a->id];
        }
        if ($u = User::query()->where('email', $email)->first()) {
            return ['kind' => 'user', 'id' => (int) $u->id];
        }

        return null;
    }

    /**
     * @param  array{kind: 'adventurer'|'user', id: int}  $p
     * @return array<string, mixed>
     */
    private function roomPlayerAttributes(int $roomId, array $p, int $teamId): array
    {
        $row = [
            'room_id' => $roomId,
            'team_id' => $teamId,
            'is_leader' => true,
            'score' => 20,
        ];
        if ($p['kind'] === 'adventurer') {
            $row['adventurer_id'] = $p['id'];
        } else {
            $row['user_id'] = $p['id'];
        }

        return $row;
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
        $a = $this->obtainAuth($this->creatorEmail(), $this->creatorPassword());
        $b = $this->obtainAuth($this->playerEmail(), $this->playerPassword());

        $this->assertNotSame($a['token'], $b['token']);
        $this->assertNotSame($a['id'], $b['id']);
    }

    public function test_get_games_api_returns_custom_draw_row_using_logged_in_creator(): void
    {
        $creator = $this->obtainAuth($this->creatorEmail(), $this->creatorPassword());
        $other = $this->findOtherPlayerByEmail($this->playerEmail());
        $this->assertNotNull($other, 'Other player must exist in DB: '.$this->playerEmail());

        $t = $this->seedFallbackTaxonomy();

        $cat = ['name' => 'My Deck', 'status' => true];
        if ($creator['kind'] === 'adventurer') {
            $cat['owner_adventurer_id'] = $creator['id'];
        } else {
            $cat['owner_user_id'] = $creator['id'];
        }
        $customCategory = CustomCategory::create($cat);

        $code = 'RA'.substr(preg_replace('/\D/', '', (string) microtime(true)), -6);
        $roomData = [
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
        ];
        if ($creator['kind'] === 'adventurer') {
            $roomData['created_by_adventurer_id'] = $creator['id'];
        } else {
            $roomData['created_by'] = $creator['id'];
        }
        $room = Room::create($roomData);

        RoomPlayer::create($this->roomPlayerAttributes($room->id, $creator, 1));
        RoomPlayer::create($this->roomPlayerAttributes($room->id, $other, 2));

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
