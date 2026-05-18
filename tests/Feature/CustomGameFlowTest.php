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
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomGameFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_custom_question_with_owned_category(): void
    {
        $user = User::factory()->create(['available_sessions' => 10]);
        Sanctum::actingAs($user);

        $categoryResponse = $this->postJson('/api/game/custom-categories', [
            'name' => 'Family Night',
        ]);
        $categoryResponse->assertCreated();
        $categoryId = (int) $categoryResponse->json('data.id');

        $questionResponse = $this->postJson('/api/game/custom-questions', [
            'custom_category_id' => $categoryId,
            'name' => '2 + 2 = ?',
            'answer_1' => '3',
            'is_correct_1' => false,
            'answer_2' => '4',
            'is_correct_2' => true,
            'answer_3' => '5',
            'is_correct_3' => false,
            'answer_4' => '6',
            'is_correct_4' => false,
        ]);
        $questionResponse->assertCreated()
            ->assertJsonPath('data.custom_category_id', (string) $categoryId);
        $this->assertArrayHasKey('usage_count', $questionResponse->json('data'));
        $this->assertSame(0, $questionResponse->json('data.usage_count'));

        $questionId = (int) $questionResponse->json('data.id');
        $showResponse = $this->getJson("/api/game/custom-questions/{$questionId}");
        $showResponse->assertOk()
            ->assertJsonPath('data.usage_count', 0);
    }

    public function test_create_custom_question_without_category_returns_unprocessable(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/game/custom-questions', [
            'name' => 'No category',
            'answer_1' => 'a',
            'is_correct_1' => true,
            'answer_2' => 'b',
            'is_correct_2' => false,
            'answer_3' => 'c',
            'is_correct_3' => false,
            'answer_4' => 'd',
            'is_correct_4' => false,
        ]);

        $response->assertUnprocessable();
    }

    public function test_custom_room_details_returns_selected_questions_count(): void
    {
        $user = User::factory()->create(['available_sessions' => 10]);
        Sanctum::actingAs($user);

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user->id,
            'name' => 'My Custom Category',
            'status' => true,
        ]);

        CustomQuestion::create([
            'owner_user_id' => $user->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Capital of France?',
            'question_kind' => 'normal',
            'answer_1' => 'Paris',
            'is_correct_1' => true,
            'answer_2' => 'London',
            'is_correct_2' => false,
            'answer_3' => 'Berlin',
            'is_correct_3' => false,
            'answer_4' => 'Rome',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $createResponse = $this->postJson('/api/game/create-custom-room', [
            'customCategoryId' => $customCategory->id,
            'questionsCount' => 1,
            'rounds' => 1,
            'teams' => 2,
            'players' => 2,
        ]);
        $createResponse->assertCreated()
            ->assertJsonPath('data.joined', true)
            ->assertJsonPath('data.teamCode', 'K1')
            ->assertJsonPath('data.isLeader', true);
        $roomId = (int) $createResponse->json('data.roomId');

        $detailsResponse = $this->getJson("/api/game/custom-room/{$roomId}");
        $detailsResponse->assertOk()->assertJsonPath('data.selectedQuestionsCount', 1);
    }

    public function test_my_custom_getters_require_auth_and_return_only_own_data(): void
    {
        $this->getJson('/api/game/my-custom-categories')->assertUnauthorized();
        $this->getJson('/api/game/my-custom-questions')->assertUnauthorized();

        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        CustomCategory::create([
            'owner_user_id' => $user->id,
            'name' => 'Mine',
            'status' => true,
        ]);
        CustomCategory::create([
            'owner_user_id' => $other->id,
            'name' => 'Theirs',
            'status' => true,
        ]);

        $mineCat = CustomCategory::where('owner_user_id', $user->id)->first();
        CustomQuestion::create([
            'owner_user_id' => $user->id,
            'custom_category_id' => $mineCat->id,
            'name' => 'Q1',
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

        $cats = $this->getJson('/api/game/my-custom-categories');
        $cats->assertOk();
        $this->assertCount(1, $cats->json('data'));
        $this->assertSame('Mine', $cats->json('data.0.name'));
        $this->assertArrayHasKey('usage_count', $cats->json('data.0'));
        $this->assertSame(0, $cats->json('data.0.usage_count'));

        $qs = $this->getJson('/api/game/my-custom-questions');
        $qs->assertOk();
        $this->assertCount(1, $qs->json('data'));
        $this->assertArrayHasKey('usage_count', $qs->json('data.0'));
        $this->assertArrayHasKey('category_usage_count', $qs->json('data.0'));
        $this->assertSame(0, $qs->json('data.0.usage_count'));
        $this->assertSame(0, $qs->json('data.0.category_usage_count'));
    }

    public function test_custom_session_records_category_usage_on_start_and_question_usage_when_shown_then_finishes_without_next_question(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Usage Cat',
            'status' => true,
        ]);

        $question = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'One question',
            'question_kind' => 'normal',
            'answer_1' => 'Yes',
            'is_correct_1' => true,
            'answer_2' => 'No',
            'is_correct_2' => false,
            'answer_3' => 'Maybe',
            'is_correct_3' => false,
            'answer_4' => 'Skip',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $room = Room::create([
            'code' => '111111',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
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

        $leader1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
        ]);
        $leader2 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
        ]);

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
        $this->assertSame('playing', $session->status);
        $this->assertTrue($gameService->startCurrentQuestion($session->fresh())['ok']);

        $customCategory->refresh();
        $question->refresh();
        $this->assertSame(1, (int) $customCategory->usage_count);
        $this->assertSame(1, (int) $question->usage_count);

        $gameService->submitAnswer($session->fresh(), $leader1->id, 1);
        $this->assertSame('playing', $session->fresh()->status);

        $gameService->submitAnswer($session->fresh(), $leader2->id, 1);
        $this->assertSame('finished', $session->fresh()->status);
        $this->assertSame('finished', $room->fresh()->status);

        $customCategory->refresh();
        $question->refresh();
        $this->assertSame(1, (int) $customCategory->usage_count);
        $this->assertSame(1, (int) $question->usage_count);
    }

    public function test_life_points_timeout_applies_score_and_life_penalties(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Timeout Cat',
            'status' => true,
        ]);

        $question = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Timeout question',
            'question_kind' => 'normal',
            'answer_1' => 'Yes',
            'is_correct_1' => true,
            'answer_2' => 'No',
            'is_correct_2' => false,
            'answer_3' => 'Maybe',
            'is_correct_3' => false,
            'answer_4' => 'Skip',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $room = Room::create([
            'code' => '222222',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => 'Custom Timeout',
            'rounds' => 1,
            'questions_count' => 1,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
            'score' => 0,
        ]);
        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
            'score' => 0,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now()->subSeconds(GameService::QUESTION_TIME_LIMIT_SECONDS + 5),
            'question_ids' => [$question->id],
            'surrendered_team_ids' => [],
        ]);

        $gameService = app(GameService::class);
        $result = $gameService->applyPlayingQuestionTimeout($session->fresh(), true);

        $this->assertTrue($result['applied']);
        $this->assertSame('finished', $result['session']->status);
        $this->assertTrue($result['session_finished'] ?? false);

        $room = $room->fresh()->load('roomPlayers');
        foreach ($room->roomPlayers as $player) {
            $this->assertSame(-10, (int) $player->score);
        }

        foreach ([1, 2] as $teamId) {
            $lives = $gameService->getRemainingLivesForTeamInGameRound($result['session']->fresh(['sessionAnswers']), $room, $teamId, 1);
            $this->assertSame(4, $lives);
        }
    }

    public function test_life_points_submit_after_timer_still_applies_timeout_penalties(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Late Submit Cat',
            'status' => true,
        ]);

        $question = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Late submit question',
            'question_kind' => 'normal',
            'answer_1' => 'Yes',
            'is_correct_1' => true,
            'answer_2' => 'No',
            'is_correct_2' => false,
            'answer_3' => 'Maybe',
            'is_correct_3' => false,
            'answer_4' => 'Skip',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $room = Room::create([
            'code' => '333333',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => 'Custom Late',
            'rounds' => 1,
            'questions_count' => 1,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        $leader1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
            'score' => 0,
        ]);
        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
            'score' => 0,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now()->subSeconds(GameService::QUESTION_TIME_LIMIT_SECONDS + 5),
            'question_ids' => [$question->id],
            'surrendered_team_ids' => [],
        ]);

        $gameService = app(GameService::class);
        $gameService->submitAnswer($session->fresh(), $leader1->id, 2);

        $session = $session->fresh();
        $this->assertSame('paused', $session->status);

        $room = $room->fresh()->load('roomPlayers');
        $leader1Score = (int) $room->roomPlayers->firstWhere('id', $leader1->id)->score;
        $leader2Score = (int) $room->roomPlayers->firstWhere('team_id', 2)->score;

        $this->assertSame(-10, $leader1Score);
        $this->assertSame(-10, $leader2Score);

        $livesTeam2 = $gameService->getRemainingLivesForTeamInGameRound($session->fresh(['sessionAnswers']), $room, 2, 1);
        $this->assertSame(4, $livesTeam2);
    }

    public function test_zero_based_option_index_counts_as_correct_for_custom_game(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Index Cat',
            'status' => true,
        ]);

        $question = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Pick first',
            'question_kind' => 'normal',
            'answer_1' => 'Yes',
            'is_correct_1' => true,
            'answer_2' => 'No',
            'is_correct_2' => false,
            'answer_3' => 'Maybe',
            'is_correct_3' => false,
            'answer_4' => 'Skip',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $room = Room::create([
            'code' => '888888',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => 'Index',
            'rounds' => 1,
            'questions_count' => 1,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        $leader1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
            'score' => 0,
        ]);
        RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
            'score' => 0,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now(),
            'question_ids' => [$question->id],
            'surrendered_team_ids' => [],
        ]);

        $gameService = app(GameService::class);
        $this->assertSame(1, $gameService->normalizeAnswerOptionIndex(1));
        $this->assertSame(1, $gameService->normalizeAnswerOptionIndex('triangle'));

        $result = $gameService->submitAnswer($session->fresh(), $leader1->id, 1);
        $this->assertTrue($result['correct']);
        $this->assertSame(10, $result['scoreDelta']);

        $lives = $gameService->getRemainingLivesForTeamInGameRound($session->fresh(), $room, 1, 1);
        $this->assertSame(5, $lives);
        $this->assertSame(10, (int) $leader1->fresh()->score);
    }

    public function test_stale_answers_for_other_question_ids_do_not_auto_finish_session(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Stale Cat',
            'status' => true,
        ]);

        $oldQuestion = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Old',
            'question_kind' => 'normal',
            'answer_1' => 'Yes',
            'is_correct_1' => true,
            'answer_2' => 'No',
            'is_correct_2' => false,
            'answer_3' => 'Maybe',
            'is_correct_3' => false,
            'answer_4' => 'Skip',
            'is_correct_4' => false,
            'status' => true,
        ]);
        $newQuestion = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'New',
            'question_kind' => 'normal',
            'answer_1' => 'Yes',
            'is_correct_1' => true,
            'answer_2' => 'No',
            'is_correct_2' => false,
            'answer_3' => 'Maybe',
            'is_correct_3' => false,
            'answer_4' => 'Skip',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $room = Room::create([
            'code' => '666666',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => 'Stale',
            'rounds' => 1,
            'questions_count' => 2,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        $leader1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
        ]);
        $leader2 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
        ]);

        $anotherNew = CustomQuestion::create([
            'owner_user_id' => $user1->id,
            'custom_category_id' => $customCategory->id,
            'name' => 'Another new',
            'question_kind' => 'normal',
            'answer_1' => 'Yes',
            'is_correct_1' => true,
            'answer_2' => 'No',
            'is_correct_2' => false,
            'answer_3' => 'Maybe',
            'is_correct_3' => false,
            'answer_4' => 'Skip',
            'is_correct_4' => false,
            'status' => true,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now(),
            'question_ids' => [$newQuestion->id, $anotherNew->id],
            'surrendered_team_ids' => [],
        ]);

        // Stale row: round 2 answered for a question no longer in slot 2 of this schedule.
        \App\Models\SessionAnswer::create([
            'game_session_id' => $session->id,
            'custom_question_id' => $oldQuestion->id,
            'question_round' => 2,
            'room_player_id' => $leader1->id,
            'answer_index' => 1,
            'correct' => true,
            'score_delta' => 10,
        ]);

        $gameService = app(GameService::class);
        $this->assertFalse($gameService->allQuestionsHaveBeenPlayed($session->fresh()));

        $gameService->submitAnswer($session->fresh(), $leader1->id, 1);
        $gameService->submitAnswer($session->fresh(), $leader2->id, 1);

        $this->assertSame('paused', $session->fresh()->status);
        $this->assertNotSame('finished', $session->fresh()->status);
    }

    public function test_life_points_does_not_finish_entire_session_when_one_team_loses_all_lives_on_first_question(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'LP Cat',
            'status' => true,
        ]);

        $questions = [];
        foreach (range(1, 3) as $i) {
            $questions[] = CustomQuestion::create([
                'owner_user_id' => $user1->id,
                'custom_category_id' => $customCategory->id,
                'name' => 'Q' . $i,
                'question_kind' => 'normal',
                'answer_1' => 'Yes',
                'is_correct_1' => true,
                'answer_2' => 'No',
                'is_correct_2' => false,
                'answer_3' => 'Maybe',
                'is_correct_3' => false,
                'answer_4' => 'Skip',
                'is_correct_4' => false,
                'status' => true,
            ]);
        }

        $room = Room::create([
            'code' => '777777',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => 'LP',
            'rounds' => 1,
            'questions_count' => 3,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        $leader1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
            'score' => 0,
        ]);
        $leader2 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
            'score' => 0,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now(),
            'question_ids' => collect($questions)->pluck('id')->all(),
            'surrendered_team_ids' => [],
        ]);

        $gameService = app(GameService::class);
        $gameService->submitAnswer($session->fresh(), $leader1->id, 2);
        $gameService->submitAnswer($session->fresh(), $leader2->id, 1);

        $session = $session->fresh();
        $this->assertSame('paused', $session->status);
        $this->assertNotSame('finished', $session->status);
        $this->assertSame(0, $gameService->getRemainingLivesForTeamInGameRound($session, $room, 1, 1));

        $advance = $gameService->advanceToNextQuestion($session);
        $this->assertFalse($advance['finished']);
        $this->assertSame(2, $advance['round']);
        $this->assertSame('playing', $session->fresh()->status);
    }

    public function test_custom_game_advances_to_next_question_slot_after_pause(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Advance Cat',
            'status' => true,
        ]);

        $questions = [];
        foreach (['First', 'Second'] as $label) {
            $questions[] = CustomQuestion::create([
                'owner_user_id' => $user1->id,
                'custom_category_id' => $customCategory->id,
                'name' => $label,
                'question_kind' => 'normal',
                'answer_1' => 'Yes',
                'is_correct_1' => true,
                'answer_2' => 'No',
                'is_correct_2' => false,
                'answer_3' => 'Maybe',
                'is_correct_3' => false,
                'answer_4' => 'Skip',
                'is_correct_4' => false,
                'status' => true,
            ]);
        }

        $room = Room::create([
            'code' => '555555',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => 'Advance',
            'rounds' => 1,
            'questions_count' => 2,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        $leader1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
        ]);
        $leader2 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now(),
            'question_ids' => collect($questions)->pluck('id')->all(),
            'surrendered_team_ids' => [],
        ]);

        $gameService = app(GameService::class);
        $gameService->submitAnswer($session->fresh(), $leader1->id, 1);
        $gameService->submitAnswer($session->fresh(), $leader2->id, 1);
        $this->assertSame('paused', $session->fresh()->status);

        $advance = $gameService->advanceToNextQuestion($session->fresh());
        $this->assertFalse($advance['finished']);
        $this->assertSame(2, $advance['round']);

        $session = $session->fresh();
        $this->assertSame(2, $session->current_round);
        $this->assertSame('playing', $session->status);
        $this->assertNull($session->question_started_at);

        $current = $gameService->getCurrentQuestion($session);
        $this->assertSame((string) $questions[1]->id, $current['id']);
        $this->assertSame(2, $current['questionRound']);
    }

    public function test_profile_finalize_finishes_when_all_questions_answered_but_round_stuck(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $type = Type::create(['name' => 'Fallback Type', 'status' => true]);
        $category = Category::create(['type_id' => $type->id, 'name' => 'Fallback Category', 'status' => true]);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Fallback Subcategory', 'status' => true, 'use_stage' => false]);

        $customCategory = CustomCategory::create([
            'owner_user_id' => $user1->id,
            'name' => 'Stuck Round Cat',
            'status' => true,
        ]);

        $questions = [];
        foreach (['Q1', 'Q2'] as $label) {
            $questions[] = CustomQuestion::create([
                'owner_user_id' => $user1->id,
                'custom_category_id' => $customCategory->id,
                'name' => $label,
                'question_kind' => 'normal',
                'answer_1' => 'Yes',
                'is_correct_1' => true,
                'answer_2' => 'No',
                'is_correct_2' => false,
                'answer_3' => 'Maybe',
                'is_correct_3' => false,
                'answer_4' => 'Skip',
                'is_correct_4' => false,
                'status' => true,
            ]);
        }

        $room = Room::create([
            'code' => '444444',
            'is_custom' => true,
            'custom_category_id' => $customCategory->id,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => 'Stuck',
            'rounds' => 1,
            'questions_count' => 2,
            'life_points' => 5,
            'teams' => 2,
            'players' => 2,
            'status' => 'playing',
            'expires_at' => now()->addHour(),
            'created_by' => $user1->id,
        ]);

        $leader1 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user1->id,
            'team_id' => 1,
            'is_leader' => true,
        ]);
        $leader2 = RoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $user2->id,
            'team_id' => 2,
            'is_leader' => true,
        ]);

        $session = GameSession::create([
            'room_id' => $room->id,
            'current_round' => 1,
            'status' => 'playing',
            'started_at' => now(),
            'question_started_at' => now(),
            'question_ids' => collect($questions)->pluck('id')->all(),
            'surrendered_team_ids' => [],
        ]);

        $gameService = app(GameService::class);
        foreach ($questions as $index => $question) {
            $session->update([
                'current_round' => $index + 1,
                'status' => 'playing',
                'question_started_at' => now(),
            ]);
            $fresh = $session->fresh();
            $gameService->submitAnswer($fresh, $leader1->id, 1);
            $gameService->submitAnswer($session->fresh(), $leader2->id, 2);
        }

        $session = $session->fresh();
        $this->assertTrue($gameService->allQuestionsHaveBeenPlayed($session));
        $this->assertSame('finished', $session->status);

        $stats = app(\App\Services\UserService::class)->getWinsLosses($user1->fresh());
        $this->assertSame(1, $stats['wins']);
        $this->assertSame(1, $stats['losses']);
    }
}
