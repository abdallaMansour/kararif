<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\Room;
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
        if (!config('firebase.projects.app.database.url')) {
            return null;
        }
        try {
            $this->database = app(Database::class);
            return $this->database;
        } catch (\Throwable $e) {
            Log::warning('Firebase database not available', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function syncRoom(Room $room): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $room->load(['type', 'category', 'subcategory', 'roomPlayers.user']);
            $teams = (int) $room->teams;
            $players = $room->roomPlayers->keyBy('id')->map(fn ($rp) => [
                'userId' => (string) $rp->user_id,
                'userName' => $rp->user?->name ?? 'Player',
                'teamId' => (string) $rp->team_id,
                'teamCode' => 'K' . $rp->team_id,
                'isLeader' => (bool) $rp->is_leader,
            ])->toArray();

            $data = [
                'roomId' => (string) $room->id,
                'code' => $room->code,
                'status' => $room->status,
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
        } catch (\Throwable $e) {
            Log::warning('Firebase syncRoom failed', ['room_id' => $room->id, 'error' => $e->getMessage()]);
        }
    }

    public function syncRoomPlayers(Room $room): void
    {
        $this->syncRoom($room);
    }

    public function syncSessionStart(GameSession $session): void
    {
        $db = $this->getDatabase();
        if (!$db) {
            return;
        }
        try {
            $session->load('room.roomPlayers.user');
            $question = $this->buildQuestionData($session);
            $teams = $this->buildTeamsData($session);

            $data = [
                'roomId' => (string) $session->room_id,
                'status' => $session->status,
                'currentRound' => (int) $session->current_round,
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

            $db->getReference('sessions/' . $session->id)->update([
                'currentRound' => (int) $session->current_round,
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
            $session->load('room.roomPlayers.user');
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
            $session->load('room.roomPlayers.user');
            $teams = $this->buildTeamsData($session);

            $data = [
                'status' => $session->status,
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
        $byTeam = $session->room->roomPlayers->groupBy('team_id');
        $teams = [];
        foreach ($byTeam as $teamId => $players) {
            $first = $players->first();
            $teams[(string) $teamId] = [
                'id' => (string) $teamId,
                'name' => $first?->user?->name ?? 'الفريق ' . $teamId,
                'score' => (int) $players->sum('score'),
                'teamCode' => 'K' . $teamId,
            ];
        }
        return $teams;
    }
}
