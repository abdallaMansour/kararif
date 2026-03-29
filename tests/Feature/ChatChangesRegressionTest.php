<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use App\Models\GameSession;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Models\Subcategory;
use App\Models\Type;
use App\Models\User;
use App\Services\CustomContentUsageService;
use App\Services\GameService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for recent game-flow changes (custom finish/usage, draw stats, my-games fields, points on tie).
 */
class ChatChangesRegressionTest extends TestCase
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

    public function test_classify_tie_for_first_place_is_draw(): void
    {
        $t = $this->seedFallbackTaxonomy();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $room = Room::create([
            'code' => '222222',
            'is_custom' => false,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'R',
            'rounds' => 1,
            'questions_count' => 1,
            'teams' => 2,
            'players' => 2,
            'status' => 'finished',
            'expires_at' => now()->addHour(),
        ]);

        $p1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $u1->id,
            'team_id' => 1,
            'is_leader' => true,
            'score' => 10,
        ]);
        $p2 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $u2->id,
            'team_id' => 2,
            'is_leader' => true,
            'score' => 10,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'finished',
            'question_ids' => [],
        ]);

        $userService = app(UserService::class);
        $this->assertSame('draw', $userService->classifyFinishedSessionForPlayer($room->fresh(['roomPlayers']), $session, $p1)['result']);
        $this->assertSame('draw', $userService->classifyFinishedSessionForPlayer($room->fresh(['roomPlayers']), $session, $p2)['result']);
    }

    public function test_get_wins_losses_increments_draws_on_score_tie_not_wins(): void
    {
        $t = $this->seedFallbackTaxonomy();
        $u1 = User::factory()->create();

        $room = Room::create([
            'code' => '333333',
            'is_custom' => false,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'R',
            'rounds' => 1,
            'teams' => 2,
            'players' => 2,
            'status' => 'finished',
            'expires_at' => now()->addHour(),
        ]);

        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $u1->id,
            'team_id' => 1,
            'is_leader' => true,
            'score' => 50,
        ]);
        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => User::factory()->create()->id,
            'team_id' => 2,
            'is_leader' => true,
            'score' => 50,
        ]);

        GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'finished',
            'question_ids' => [],
        ]);

        $stats = app(UserService::class)->getWinsLosses($u1->fresh());
        $this->assertSame(0, $stats['wins']);
        $this->assertSame(0, $stats['losses']);
        $this->assertSame(1, $stats['draws']);
    }

    public function test_update_points_tie_for_first_gives_zero_to_leaders_negative_to_others(): void
    {
        $t = $this->seedFallbackTaxonomy();
        $u1 = User::factory()->create(['points' => 100]);
        $u2 = User::factory()->create(['points' => 100]);
        $u3 = User::factory()->create(['points' => 100]);

        $room = Room::create([
            'code' => '444444',
            'is_custom' => false,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'R',
            'teams' => 3,
            'players' => 3,
            'status' => 'finished',
            'expires_at' => now()->addHour(),
        ]);

        RoomPlayer::create(['room_id' => $room->id, 'user_id' => $u1->id, 'team_id' => 1, 'is_leader' => true, 'score' => 10]);
        RoomPlayer::create(['room_id' => $room->id, 'user_id' => $u2->id, 'team_id' => 2, 'is_leader' => true, 'score' => 10]);
        RoomPlayer::create(['room_id' => $room->id, 'user_id' => $u3->id, 'team_id' => 3, 'is_leader' => true, 'score' => 5]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'finished',
            'question_ids' => [],
        ]);

        app(GameService::class)->updatePointsForFinishedSession($session->fresh());

        $this->assertSame(100, (int) $u1->fresh()->points);
        $this->assertSame(100, (int) $u2->fresh()->points);
        $this->assertSame(99, (int) $u3->fresh()->points);
    }

    public function test_custom_category_usage_on_session_start_and_question_on_show(): void
    {
        $t = $this->seedFallbackTaxonomy();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Cat',
            'status' => true,
        ]);
        $question = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Q',
            'question_kind' => 'normal',
            'answer_1' => 'a',
            'is_correct_1' => true,
            'answer_2' => 'b',
            'is_correct_2' => false,
            'answer_3' => 'c',
            'is_correct_3' => false,
            'answer_4' => 'd',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $room = Room::create([
            'code' => '555555',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'Custom',
            'rounds' => 1,
            'questions_count' => 1,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        RoomPlayer::create(['room_id' => $room->id, 'user_id' => $user1->id, 'team_id' => 1, 'is_leader' => true]);
        RoomPlayer::create(['room_id' => $room->id, 'user_id' => $user2->id, 'team_id' => 2, 'is_leader' => true]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'starting',
            'started_at' => now(),
            'start_timer_ends_at' => now()->subSecond(),
            'question_started_at' => null,
            'question_ids' => [$question->id],
            'surrendered_team_ids' => [],
        ]);

        $gameService = app(GameService::class);
        $session = $gameService->ensureSessionPlaying($session->fresh());
        $customCategory->refresh();
        $this->assertSame(1, (int) $customCategory->usage_count);

        $this->assertTrue($gameService->startCurrentQuestion($session->fresh())['ok']);
        $question->refresh();
        $this->assertSame(1, (int) $question->usage_count);

        // Finishing does not bump usage again (no observer on finished).
        $gameService->submitAnswer($session->fresh(), RoomPlayer::where('room_id', $room->id)->where('team_id', 1)->first()->id, 1);
        $gameService->submitAnswer($session->fresh(), RoomPlayer::where('room_id', $room->id)->where('team_id', 2)->first()->id, 1);
        $customCategory->refresh();
        $question->refresh();
        $this->assertSame(1, (int) $customCategory->usage_count);
        $this->assertSame(1, (int) $question->usage_count);
    }

    public function test_custom_content_usage_service_increments_question_on_record_shown(): void
    {
        $t = $this->seedFallbackTaxonomy();
        $customCategory = CustomCategory::create([
            'owner_user_id' => User::factory()->create()->id,
            'name' => 'X',
            'status' => true,
        ]);
        $q = CustomQuestion::create([
            'owner_user_id' => $customCategory->owner_user_id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Q',
            'question_kind' => 'normal',
            'answer_1' => 'a',
            'is_correct_1' => true,
            'answer_2' => 'b',
            'is_correct_2' => false,
            'answer_3' => 'c',
            'is_correct_3' => false,
            'answer_4' => 'd',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $room = Room::create([
            'code' => '666666',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $t['type']->id,
            'category_id' => $t['category']->id,
            'subcategory_id' => $t['subcategory']->id,
            'title' => 'C',
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'question_ids' => [$q->id],
        ]);

        app(CustomContentUsageService::class)->recordCustomQuestionShown($session);
        $q->refresh();
        $this->assertSame(1, (int) $q->usage_count);
    }

}
