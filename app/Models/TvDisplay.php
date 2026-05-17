<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvDisplay extends Model
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_LINKED = 'linked';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'device_id',
        'code',
        'room_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        // Waiting codes expire after TTL; linked displays stay valid until unlinked.
        if ($this->status === self::STATUS_LINKED && $this->room_id) {
            return false;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING && ! $this->isExpired();
    }

    /**
     * Whether this display may control an active session (e.g. next-question) for the given room.
     */
    public function canControlRoomSession(int $roomId): bool
    {
        return $this->status === self::STATUS_LINKED
            && (int) $this->room_id === $roomId
            && ! $this->isExpired();
    }
}
