<?php

namespace App\Observers;

use App\Models\GameSession;
use App\Services\CustomContentUsageService;

class GameSessionObserver
{
    public function __construct(
        protected CustomContentUsageService $customContentUsage
    ) {}

    public function created(GameSession $session): void
    {
        if ($session->status === 'finished') {
            $this->customContentUsage->recordFinishedCustomSession($session);
        }
    }

    public function updated(GameSession $session): void
    {
        if ($session->wasChanged('status') && $session->status === 'finished') {
            $this->customContentUsage->recordFinishedCustomSession($session);
        }
    }
}
