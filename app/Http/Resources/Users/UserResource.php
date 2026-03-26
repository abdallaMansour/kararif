<?php

namespace App\Http\Resources\Users;

use App\Helpers\RankHelper;
use App\Models\Adventurer;
use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarRelation = $this->avatarRelation;
        $avatarPayload = $avatarRelation ? [
            'id' => (string) $avatarRelation->id,
            'name' => $avatarRelation->name,
            'image' => $avatarRelation->image_url,
        ] : null;

        $customCategoriesCountQuery = CustomCategory::query();
        $customQuestionsCountQuery = CustomQuestion::query();
        if ($this->resource instanceof Adventurer) {
            $customCategoriesCountQuery->where('owner_adventurer_id', $this->id);
            $customQuestionsCountQuery->where('owner_adventurer_id', $this->id);
        } else {
            $customCategoriesCountQuery->where('owner_user_id', $this->id);
            $customQuestionsCountQuery->where('owner_user_id', $this->id);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullName' => $this->name,
            'username' => $this->username ?? null,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $avatarPayload,
            'badge' => null,
            'rank' => RankHelper::getRankForScore((float) ($this->points ?? 0)),
            'country' => [
                'label' => $this->country_label,
                'code' => $this->country_code,
            ],
            'surrender_count' => (int) ($this->surrender_count ?? 0),
            'available_sessions' => (int) ($this->available_sessions ?? 0),
            'rank_prize_discount' => [
                'has_discount' => ($this->rank_discount_uses_left ?? 0) > 0 && ($this->rank_discount_percent ?? 0) > 0,
                'discount_percent' => ($this->rank_discount_uses_left ?? 0) > 0 ? (int) ($this->rank_discount_percent ?? 0) : null,
                'uses_left' => (int) ($this->rank_discount_uses_left ?? 0),
            ],
            'custom_categories_count' => (int) $customCategoriesCountQuery->count(),
            'custom_questions_count' => (int) $customQuestionsCountQuery->count(),
            'stats' => app(UserService::class)->getWinsLosses($this->resource),
        ];
    }
}
