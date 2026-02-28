# Dashboard Updates Prompt

Use the following prompt in your dashboard app's chat to implement all backend-related UI and API integration:

---

Implement the following dashboard API integration and UI changes. The backend is ready; the dashboard app should consume these APIs and expose the corresponding screens.

## 1. User data (if you show users)

When listing or showing app users, include these fields in the payload/display:
- **surrender_count** (or number_surrender_times): number of times the user surrendered in games
- **country_label**: nullable string
- **country_code**: nullable string (e.g. SA, AE)
- Any other new user fields returned by the backend

If the backend adds a dedicated dashboard users list/show endpoint later, use the same field names.

## 2. Avatars CRUD

Add a new **Avatars** section in the dashboard.

- **List:** `GET /api/dashboard/avatars` (auth: Bearer token)
- **Create:** `POST /api/dashboard/avatars` — body: `name` (optional), `image` (optional string URL)
- **Show:** `GET /api/dashboard/avatars/{id}`
- **Update:** `PUT /api/dashboard/avatars/{id}` — body: same as create
- **Delete:** `DELETE /api/dashboard/avatars/{id}`

Response shape per avatar: `id`, `name`, `image`, `created_at`, `updated_at`.

UI: List avatars in a table/grid; add form to create (name, image URL); edit and delete actions.

## 3. Packages CRUD (game-sessions / payment packages)

Add a new **Packages** or **Game packages** section (under e.g. Payment or Settings).

- **List:** `GET /api/dashboard/packages` (auth: Bearer token)
- **Create:** `POST /api/dashboard/packages` — body: `name` (required), `sessions_count` (required, integer, min 1), `price` (required, number), `currency` (optional, default AED), `points` (optional), `active` (optional boolean)
- **Show:** `GET /api/dashboard/packages/{id}`
- **Update:** `PUT /api/dashboard/packages/{id}` — body: same as create
- **Delete:** `DELETE /api/dashboard/packages/{id}`

Response shape per package: `id`, `name`, `points`, `sessions_count`, `price`, `currency`, `active`, `created_at`, `updated_at`.

UI: List packages with name, number of sessions, price (AED), active status; add/edit form; delete with confirmation.

## 4. Coupons CRUD

Add a new **Coupons** section.

- **List:** `GET /api/dashboard/coupons` (auth: Bearer token)
- **Create:** `POST /api/dashboard/coupons` — body: `title` (required), `code` (required, unique), `usage_per_user` (required, integer, min 1), `discount_percentage` (required, 0–100), `expires_at` (optional, date/datetime), `status` (optional: `active` or `inactive`)
- **Show:** `GET /api/dashboard/coupons/{id}`
- **Update:** `PUT /api/dashboard/coupons/{id}` — body: same as create (code must be unique except for this coupon)
- **Delete:** `DELETE /api/dashboard/coupons/{id}`

Response shape per coupon: `id`, `title`, `code`, `usage_per_user`, `discount_percentage`, `expires_at`, `status`, `created_at`, `updated_at`.

UI: List coupons with title, code, usage per user, discount %, expiration, status; add/edit form; delete with confirmation.

Note: The app applies a coupon via `POST /api/coupons/apply` (app route) with `code` and optional `packageId`; no dashboard UI needed for that.

## 5. Email (forgot-password OTP)

No dashboard UI change required. Document that forgot-password OTP delivery depends on SMTP env vars (`MAIL_*`) in the backend; optionally add a “Test email” or “Send test OTP” button that calls the backend forgot-password endpoint with a test email if you want to verify delivery from the dashboard.

---

Summary of new dashboard routes (all under `GET/POST/PUT/DELETE` with base URL `/api/dashboard`, auth: Bearer token):

| Resource  | List          | Create | Show | Update | Delete |
|----------|---------------|--------|------|--------|--------|
| Avatars  | GET /avatars  | POST /avatars | GET /avatars/{id}  | PUT /avatars/{id}  | DELETE /avatars/{id}  |
| Packages | GET /packages | POST /packages | GET /packages/{id} | PUT /packages/{id} | DELETE /packages/{id} |
| Coupons  | GET /coupons  | POST /coupons | GET /coupons/{id}  | PUT /coupons/{id}  | DELETE /coupons/{id}  |

User data: when/if you display app users, include surrender_count, country_label, country_code.
