<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\CustomQuestionRequest;
use App\Models\Adventurer;
use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use App\Models\GameSession;
use App\Models\SessionAnswer;
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
        $query = CustomQuestion::query()
            ->select('custom_questions.*')
            ->ownedBy($user)
            ->with('category');

        if (request()->has('customCategoryId')) {
            $query->where('custom_category_id', request('customCategoryId'));
        }

        $query->addSelect([
            'usage_count' => SessionAnswer::query()
                ->join('game_sessions', 'game_sessions.id', '=', 'session_answers.game_session_id')
                ->whereColumn('session_answers.custom_question_id', 'custom_questions.id')
                ->where('game_sessions.status', 'finished')
                ->selectRaw('count(distinct session_answers.game_session_id)'),
            'category_usage_count' => GameSession::query()
                ->join('rooms', 'rooms.id', '=', 'game_sessions.room_id')
                ->whereColumn('rooms.custom_category_id', 'custom_questions.custom_category_id')
                ->where('rooms.is_custom', true)
                ->where('game_sessions.status', 'finished')
                ->selectRaw('count(*)'),
        ]);

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
            'answers' => [
                ['text' => $question->answer_1, 'is_correct' => (bool) $question->is_correct_1],
                ['text' => $question->answer_2, 'is_correct' => (bool) $question->is_correct_2],
                ['text' => $question->answer_3, 'is_correct' => (bool) $question->is_correct_3],
                ['text' => $question->answer_4, 'is_correct' => (bool) $question->is_correct_4],
            ],
            'created_at' => $question->created_at?->toIso8601String(),
        ];

        $attrs = $question->getAttributes();
        $data['usage_count'] = array_key_exists('usage_count', $attrs)
            ? (int) $question->usage_count
            : CustomQuestion::finishedSessionUsageCount((int) $question->id);

        if (array_key_exists('category_usage_count', $attrs)) {
            $data['category_usage_count'] = (int) $question->category_usage_count;
        }

        return $data;
    }
}
