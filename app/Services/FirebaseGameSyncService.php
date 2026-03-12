<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\Room;
use App\Models\TvDisplay;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;

class FirebaseGameSyncService
{
    private ?Database $database = null;

    private function getDatabase(): ?Database
    {
        if ($this->database !== null) {
            return $this->database;
        }
        $url = config('firebase.projects.app.database.url');
        if (!$url) {
            Log::info('Firebase sync skipped: FIREBASE_DATABASE_URL not set');
            return null;
        }
        try {
            $this->database = app(Database::class);
            return $this->database;
        } catch (\Throwable $e) {
            Log::warning('Firebase database not available', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function syncRoom(Room $room): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        Log::info('Firebase syncRoom starting', ['room_id' => $room->id]);
        try {
            $room->load(['type', 'category', 'subcategory', 'roomPlayers.user', 'roomPlayers.adventurer']);
            $teams = (int) $room->teams;
            $players = $room->roomPlayers->keyBy('id')->map(fn ($rp) => [
                'userId' => (string) ($rp->adventurer_id ?? $rp->user_id),
                'userName' => ($rp->adventurer ?? $rp->user)?->name ?? 'Player',
                'teamId' => (string) $rp->team_id,
                'teamCode' => 'K' . $rp->team_id,
                'isLeader' => (bool) $rp->is_leader,
            ])->toArray();

            $tvDisplay = TvDisplay::where('room_id', $room->id)->where('status', 'linked')->first();
            $data = [
                'roomId' => (string) $room->id,
                'code' => $room->code,
                'status' => $room->status,
                'tvDisplayId' => $tvDisplay ? (string) $tvDisplay->id : null,
                'type_id' => (int) $room->type_id,
                'category_id' => (int) $room->category_id,
                'subcategory_id' => (int) $room->subcategory_id,
                'rounds' => (int) $room->rounds,
                'teams' => $teams,
                'maxPlayers' => (int) $room->players,
                'joinedCount' => $room->roomPlayers->count(),
                'players' => $players,
                'createdAt' => $room->created_at?->timestamp ?? time(),
            ];

            $db->getReference('rooms/' . $room->id)->set($data);
            Log::info('Firebase syncRoom success', ['room_id' => $room->id]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncRoom failed', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function syncRoomPlayers(Room $room): void
    {
        $this->syncRoom($room);
    }

    public function syncTvDisplay(TvDisplay $display): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $sessionId = null;
            if ($display->room_id) {
                $session = $display->room->gameSessions()
                    ->whereIn('status', ['waiting', 'playing', 'starting'])
                    ->latest()
                    ->first();
                $sessionId = $session ? (string) $session->id : null;
            }

            $data = [
                'displayId' => (string) $display->id,
                'linked' => $display->status === TvDisplay::STATUS_LINKED,
                'roomId' => $display->room_id ? (string) $display->room_id : null,
                'sessionId' => $sessionId,
                'status' => $display->status,
                'linkedAt' => $display->status === TvDisplay::STATUS_LINKED ? (int) round(microtime(true) * 1000) : null,
            ];

            $db->getReference('tv_displays/' . $display->id)->set($data);
            Log::info('Firebase syncTvDisplay success', ['display_id' => $display->id]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncTvDisplay failed', [
                'display_id' => $display->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function syncSessionStarting(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer');
            $teams = $this->buildTeamsData($session);

            $data = [
                'roomId' => (string) $session->room_id,
                'status' => 'starting',
                'currentRound' => 0,
                'startTimerEndsAt' => $session->start_timer_ends_at
                    ? (int) ($session->start_timer_ends_at->timestamp * 1000)
                    : null,
                'remainingQuestionsCount' => count($session->question_ids ?? []),
                'question' => null,
                'teams' => $teams,
                'sessionId' => (string) $session->id,
            ];

            $db->getReference('sessions/' . $session->id)->set($data);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncSessionStarting failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncSessionStart(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer');
            $question = $this->buildQuestionData($session);
            $teams = $this->buildTeamsData($session);

            $questionIds = $session->question_ids ?? [];
            $remainingCount = count($questionIds) - max(0, $session->current_round - 1);
            if ($remainingCount < 0) {
                $remainingCount = 0;
            }

            $data = [
                'roomId' => (string) $session->room_id,
                'sessionId' => (string) $session->id,
                'status' => $session->status,
                'currentRound' => (int) $session->current_round,
                'remainingQuestionsCount' => $remainingCount,
                'questionStartedAt' => (int) round(microtime(true) * 1000),
                'question' => $question,
                'teams' => $teams,
            ];

            $db->getReference('sessions/' . $session->id)->set($data);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncSessionStart failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncQuestion(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $question = $this->buildQuestionData($session);
            $teams = $this->buildTeamsData($session);
            $questionIds = $session->question_ids ?? [];
            $remainingCount = count($questionIds) - max(0, $session->current_round - 1);
            if ($remainingCount < 0) {
                $remainingCount = 0;
            }

            $db->getReference('sessions/' . $session->id)->update([
                'currentRound' => (int) $session->current_round,
                'remainingQuestionsCount' => $remainingCount,
                'questionStartedAt' => (int) round(microtime(true) * 1000),
                'question' => $question,
                'teams' => $teams,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncQuestion failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncScores(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer');
            $teams = $this->buildTeamsData($session);
            $db->getReference('sessions/' . $session->id)->update(['teams' => $teams]);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncScores failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncSessionEnd(GameSession $session, ?array $winnerIds = null): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user', 'room.roomPlayers.adventurer');
            $teams = $this->buildTeamsData($session);

            $data = [
                'status' => $session->status,
                'remainingQuestionsCount' => 0,
                'teams' => $teams,
                'sessionEndedAt' => (int) round(microtime(true) * 1000),
            ];
            if ($winnerIds !== null) {
                $data['winnerIds'] = $winnerIds;
            }

            $db->getReference('sessions/' . $session->id)->update($data);
        } catch (\Throwable $e) {
            Log::warning('Firebase syncSessionEnd failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
        }
    }

    private function buildQuestionData(GameSession $session): ?array
    {
        $gameService = app(GameService::class);
        return $gameService->getCurrentQuestion($session);
    }

    private function buildTeamsData(GameSession $session): array
    {
        $room = $session->room;
        $room->load('roomPlayers.user', 'roomPlayers.adventurer');
        $byTeam = $room->roomPlayers->groupBy('team_id');
        $teams = [];
        foreach ($byTeam as $teamId => $players) {
            $first = $players->first();
            $playerList = $players->map(fn ($rp) => [
                'userId' => (string) ($rp->adventurer_id ?? $rp->user_id),
                'userName' => ($rp->adventurer ?? $rp->user)?->name ?? 'Player',
                'isLeader' => (bool) $rp->is_leader,
                'tvViewJoined' => $rp->tv_view_joined_at !== null,
            ])->values()->all();

            $teams[(string) $teamId] = [
                'id' => (string) $teamId,
                'name' => ($first->adventurer ?? $first->user)?->name ?? 'الفريق ' . $teamId,
                'score' => (int) $players->sum('score'),
                'teamCode' => 'K' . $teamId,
                'players' => $playerList,
            ];
        }
        return $teams;
    }
}
