<?php

namespace App\Http\Resources\ContactUs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactUsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sourceLabels = [
            'mobile' => 'تطبيق الجوال',
            'tv' => 'تطبيق التلفزيون',
            'other' => 'أخرى',
        ];

        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'email' => $this->email ?? null,
            'category' => $this->category ?? null,
            'subject' => $this->subject ?? null,
            'message' => $this->message ?? null,
            'source' => $this->source ?? null,
            'sourceLabel' => $sourceLabels[$this->source ?? ''] ?? ($this->source ?? '—'),
            'is_read' => $this->is_read ?? false,
        ];
    }
}
