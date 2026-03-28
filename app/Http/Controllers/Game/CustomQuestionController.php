<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\CustomQuestionRequest;
use App\Models\Adventurer;
use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use Illuminate\Http\JsonResponse;

class CustomQuestionController extends Controller
{
    /**
     * List questions owned by the authenticated player (same as index; explicit route GET /game/my-custom-questions).
     */
    public function getMine(): JsonResponse
    {
        return $this->index();
    }

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $query = CustomQuestion::ownedBy($user)->with('category');

        if (request()->has('customCategoryId')) {
            $query->where('custom_category_id', request('customCategoryId'));
        }

        $items = $query->orderByDesc('id')
            ->get()
            ->map(fn (CustomQuestion $question) => $this->serializeQuestion($question))
            ->values()
            ->all();

        return ApiResponse::success($items);
    }

    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        $question = CustomQuestion::ownedBy($user)
            ->with('category')
            ->find($id);

        if (! $question) {
            return ApiResponse::error('Custom question not found.', 404);
        }

        return ApiResponse::success($this->serializeQuestion($question));
    }

    public function store(CustomQuestionRequest $request): JsonResponse
    {
        $user = auth()->user();
        $payload = $request->validated();
        $payload['question_kind'] = CustomQuestion::KIND_NORMAL;
        $payload['status'] = (bool) ($payload['status'] ?? true);

        $category = CustomCategory::ownedBy($user)->find($payload['custom_category_id']);
        if (! $category) {
            return ApiResponse::error('Custom category not found for this owner.', 422);
        }

        if ($user instanceof Adventurer) {
            $payload['owner_adventurer_id'] = $user->id;
        } else {
            $payload['owner_user_id'] = $user->id;
        }

        $question = CustomQuestion::create($payload);
        $question->load('category');

        return ApiResponse::success($this->serializeQuestion($question), null, 201);
    }

    public function update(CustomQuestionRequest $request, int $id): JsonResponse
    {
        $user = auth()->user();
        $question = CustomQuestion::ownedBy($user)->find($id);
        if (! $question) {
            return ApiResponse::error('Custom question not found.', 404);
        }

        $payload = $request->validated();
        $payload['question_kind'] = CustomQuestion::KIND_NORMAL;

        if (array_key_exists('custom_category_id', $payload)) {
            $category = CustomCategory::ownedBy($user)->find($payload['custom_category_id']);
            if (! $category) {
                return ApiResponse::error('Custom category not found for this owner.', 422);
            }
        }

        $question->update($payload);
        $question->load('category');

        return ApiResponse::success($this->serializeQuestion($question));
    }

    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        $question = CustomQuestion::ownedBy($user)->find($id);
        if (! $question) {
            return ApiResponse::error('Custom question not found.', 404);
        }

        $question->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    private function serializeQuestion(CustomQuestion $question): array
    {
        $data = [
            'id' => (string) $question->id,
            'custom_category_id' => $question->custom_category_id ? (string) $question->custom_category_id : null,
            'custom_category_name' => $question->category?->name,
            'name' => $question->name,
            'question_kind' => $question->question_kind,
            'status' => (bool) $question->status,
            'usage_count' => (int) ($question->usage_count ?? 0),
            'answers' => [
                ['text' => $question->answer_1, 'is_correct' => (bool) $question->is_correct_1],
                ['text' => $question->answer_2, 'is_correct' => (bool) $question->is_correct_2],
                ['text' => $question->answer_3, 'is_correct' => (bool) $question->is_correct_3],
                ['text' => $question->answer_4, 'is_correct' => (bool) $question->is_correct_4],
            ],
            'created_at' => $question->created_at?->toIso8601String(),
        ];

        if ($question->relationLoaded('category') && $question->category) {
            $data['category_usage_count'] = (int) ($question->category->usage_count ?? 0);
        }

        return $data;
    }
}
