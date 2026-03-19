# Frontend adjustments for game flow (backend spec)

Use this spec to align the frontend app with the latest backend behavior for rooms, sessions, rounds, surrender, and profile.
and check first if anything of this is already implemented

---

## 1. Create room and creator auto-join

- **POST** `/api/game/create-room` (auth required)
- Request body now supports:
    - `questionsCount`: total questions for the session (e.g. 20)
    - `rounds`: number of rounds (e.g. 2) — questions are split evenly across rounds (e.g. 20 questions, 2 rounds ⇒ 10 questions per round)
- **Behavior:** The player who creates the room is **automatically joined as team 1 leader**. No separate join call is needed for the creator. After create-room, the creator can call **GET** `/api/game/room/{roomId}` and will see themselves in `players` as team 1 leader.

---

## 2. Rounds and stage types

- A **session** has a list of questions and a **round** concept: questions are grouped into rounds (e.g. round 1 = first N questions, round 2 = next N, etc.).
- **GET** `/api/game/session/{sessionId}` and Firebase `sessions/{sessionId}` expose:
    - `round` (current question index in the flat list)
    - `settings.rounds` (number of rounds)
    - `remainingQuestionsCount`
- For **subcategories not linked to a stage**, the backend alternates stage type by round and by creator history:
    - Round 1: `questions_group` or `life_points` (depends on creator’s previous finished sessions for that subcategory)
    - Round 2: the other type, then alternating (e.g. R1 → question groups, R2 → life points, R3 → question groups).
- **Frontend:** Use `stage.stage_type` from the API/Firebase to decide UI:
    - `questions_group`: standard score-based round
    - `life_points`: show life bars per team, use `teams[*].lifePoints` and `teams[*].isEliminated`
- Progress can be shown as “Round X” and “Question Y of Z in this round” using `settings.rounds` and `remainingQuestionsCount` (and total questions if exposed).
- Ensure that if the current round type is question groups and the round questionCount is 10 for example so the round will contains 2 groups each group conatins 5 questions and if round questions is 15 = 3 groups ...

---

## 3. Surrender and multi-team games

- **POST** `/api/game/session/{sessionId}/surrender` (auth required)
- **Two teams:** Session ends immediately; non-surrendering team wins. Response: `endedBySurrender: true`, `scores`, `winnerIds`.
- **More than two teams:** The surrendering team is marked eliminated; the session **continues** for the remaining teams. Response: `endedBySurrender: false`, `surrenderingTeamId`, message that the team surrendered and others continue.
- **Firebase:** In `sessions/{sessionId}`, each team in `teams` now has:
    - `surrendered`: boolean — `true` if that team has surrendered
    - `isEliminated`: `true` if the team is out (life points 0 or surrendered)
- **Frontend:**
    - Subscribe to Firebase `sessions/{sessionId}` and, when `teams[*].surrendered === true`, treat that team as out (e.g. grey out, hide from active scoreboard, disable answer submission for that team).
    - For 3+ teams, keep the game UI running until the backend sets the session to finished (e.g. when only one team remains or questions run out).
    - Disable or hide the surrender button for players whose team is already surrendered or eliminated.

---

## 4. Exit room before session starts

- **POST** `/api/game/room/{roomId}/leave` or **POST** `/api/game/room/{roomId}/exit` (auth required)
- Allowed only while the room is in **waiting** state (session not started). If the user tries after the game has started, the API returns an error instructing to use surrender instead.
- Response: `{ left: true, roomId, message }`.
- **Frontend:** Provide an “Exit room” or “Leave” action that calls one of these endpoints when the room is still waiting; on success, navigate back to lobby and stop listening to that room.

---

## 5. Profile stats and surrender count

- **GET** `/api/user/profile` (auth required)
- Response now includes:
    - `stats.wins`: number of finished games where the user’s team had the highest score
    - `stats.losses`: number of finished games where the user’s team did not have the highest score
    - `surrender_count`: total number of times the user has surrendered (unchanged)
- **Frontend:** Use `stats.wins`, `stats.losses`, and `surrender_count` in the profile screen. Optionally compute and display win rate as `wins / max(1, wins + losses)`.

---

## 6. Summary checklist for frontend

- [ ] Create room: send `questionsCount` and `rounds` when applicable; treat creator as already in the room as team 1 leader.
- [ ] Session/rounds: use `stage.stage_type` and `settings.rounds` to show the correct UI (question groups vs life points) and round progress.
- [ ] Firebase: listen to `teams[*].surrendered` and `teams[*].isEliminated`; update scoreboard and disable inputs for eliminated/surrendered teams; keep game running for remaining teams when there are more than two.
- [ ] Pre-session exit: call `POST room/{roomId}/leave` or `POST room/{roomId}/exit` when room is waiting; handle success and navigation.
- [ ] Profile: display `stats.wins`, `stats.losses`, and `surrender_count` from `GET /api/user/profile`.
