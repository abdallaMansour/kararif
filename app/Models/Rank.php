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
    ];

    protected $appends = ['prize_label'];

    public function getPrizeLabelAttribute(): ?string
    {
        if (! $this->prize_type) {
            return null;
        }
        $value = (int) ($this->prize_value ?? 0);
        return match ($this->prize_type) {
            'discount_next_5_purchases' => $value > 0 ? "{$value}% discount on next 5 purchases" : 'Discount on next 5 purchases',
            'free_sessions' => $value > 0 ? "{$value} free game sessions" : 'Free game sessions',
            default => null,
        };
    }
}
