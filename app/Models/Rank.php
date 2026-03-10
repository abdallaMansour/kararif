<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Rank extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'start_score',
        'prize_type',
        'prize_value',
        'prize_label_ar',
    ];

    public function getPrizeLabelArAttribute(): ?string
    {
        if (! empty($this->attributes['prize_label_ar'] ?? null)) {
            return $this->attributes['prize_label_ar'];
        }
        if (! $this->prize_type) {
            return null;
        }
        $value = (int) ($this->prize_value ?? 0);
        return match ($this->prize_type) {
            'discount_next_5_purchases' => $value > 0 ? "خصم {$value}% على 5 مشتريات قادمة" : 'خصم على 5 مشتريات قادمة',
            'free_sessions' => $value > 0 ? "{$value} جلسات لعبة مجانية" : 'جلسات لعبة مجانية',
            default => null,
        };
    }
}
