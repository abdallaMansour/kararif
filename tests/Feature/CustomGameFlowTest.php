<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use App\Models\Subcategory;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomGameFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_assign_custom_question_to_owned_category(): void
    {
        $user = User::factory()->create(['available_sessions' => 10]);
        Sanctum::actingAs($user);

        $categoryResponse = $this->postJson('/api/game/custom-categories', [
            'name' => 'Family Night',
        ]);
        $categoryResponse->assertCreated();
        $categoryId = (int) $categoryResponse->json('data.id');

        $questionResponse = $this->postJson('/api/game/custom-questions', [
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
        $questionResponse->assertCreated();
        $questionId = (int) $questionResponse->json('data.id');

        $assignResponse = $this->patchJson("/api/game/custom-questions/{$questionId}/assign-category", [
            'custom_category_id' => $categoryId,
        ]);
        $assignResponse->assertOk()->assertJsonPath('data.custom_category_id', (string) $categoryId);
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
        $createResponse->assertCreated();
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

        $qs = $this->getJson('/api/game/my-custom-questions');
        $qs->assertOk();
        $this->assertCount(1, $qs->json('data'));
    }
}
