# Auth Split, Game Meta, and Dashboard CRUD Plan

## 1. Auth: Use Adventurers Table (Separate from Dashboard Users)

### Current State
- App auth uses `users` table via [app/Services/AuthService.php](app/Services/AuthService.php) and [app/Http/Controllers/Auth/AppLoginController.php](app/Http/Controllers/Auth/AppLoginController.php)
- Dashboard uses `users` with Laratrust (roles/permissions)
- `adventurers` table has: `pin_code` (4-digit), no `password`, no `username`, `phone`, `avatar_id`, `available_sessions`, etc.

### Approach
- Extend `adventurers` with columns needed for app auth and game flow; keep API response shape unchanged
- Add `adventurers` guard/provider; use it for app API routes
- Keep `users` for dashboard admins only
- Add `adventurer_id` to `room_players`, `payments`, `rooms` (created_by), `coupon_usages`; backfill; switch models

### Adventurers Migration Additions
Columns: `password`, `username`, `phone`, `avatar_id`, `available_sessions`, `points`, `surrender_count`, `country_label`, `country_code`, `remember_token`, rank-related columns as needed

### Key Changes
- [config/auth.php](config/auth.php): Add `adventurers` guard and provider
- Make `Adventurer` extend `Authenticatable`, use `HasApiTokens`
- [AuthService](app/Services/AuthService.php): Use `Adventurer` for login/register
- [AppLoginController](app/Http/Controllers/Auth/AppLoginController.php), [AuthController](app/Http/Controllers/Auth/AuthController.php): Operate on `Adventurer`
- Migration: add `adventurer_id` to `room_players`, `payments`, `rooms`, `coupon_usages`; backfill; drop `user_id` for app tables
- Password reset flow: use `adventurers`
- Postman: no URL/body changes

---

## 2. Token Expiry: Allow Multi-Device Sessions

### Issue
[AuthService::loginUser](app/Services/AuthService.php) calls `$user->tokens()->delete()` on login, invalidating all previous tokens.

### Fix
- Remove `$user->tokens()->delete()` on login so other sessions remain valid

---

## 3. Balance vs Available Sessions

### Change
- `getBalance` (or equivalent): return `available_sessions` as the effective balance
- Ensure payment flow grants `available_sessions`, not `balance`

---

## 4. Points = Score, Rank (Win +1, Lose -1)

### Change
- On session end (win/loss/surrender): `points += 1` for winners, `points -= 1` for losers
- Apply in GameService and surrender flow

---

## 5. Game Metadata Endpoints: Add Images

### Endpoints
- `/api/game/question-types`
- `/api/game/categories?questionTypeId=3`
- `/api/game/subcategories?categoryId=2`

### Change
- Add `image` (e.g. `getFirstMediaUrl()`) to Type, Category, Subcategory responses

---

## 6. `/api/user/games` Enhancements

### Changes
- Add `roomName` (from `room.title` or type/category)
- Add `userRank`: 1st, 2nd, 3rd (or "لا ثاني" etc.) based on score order
- Add query filters: `rank=1|2|3`, `result=win|loss`
- Match screenshot: room name "تحدي الأبطال الأسطوري", result "فوز", rank "الأول"

---

## 7. `/api/ranks`: Prizes in Arabic Only

### Change
- Add `prize_label_ar` or switch `prize_label` to Arabic
- Examples: "خصم X% على 5 مشتريات قادمة", "X جلسات لعبة مجانية"

---

## 8. Dashboard CRUD Prompts

### 1) Content How-To-Play

> Create dashboard CRUD for "How to Play" content. The public API `GET /api/content/how-to-play` returns `{ sections: [{ title, content }] }`. Add a `how_to_play_sections` table with `title`, `content`, `order`. Dashboard: list, create, edit, delete sections. Reorder with `order`. The API must return sections ordered by `order`.

### 2) News

> Create dashboard CRUD for News. The public API `GET /api/news?limit=10` returns items with `id`, `title`, `summary`, `date`, `thumbnail`, `url`. Use existing `news` table. Dashboard: list (paginated), create, edit, delete. Support image upload for thumbnail. Form: title, summary, body (richtext), thumbnail (file), url (optional), published_at (datetime).
