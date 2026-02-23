# Dashboard Prompt: Stages & Questions Restructure + Adventurers + Ranks

Use this prompt in your dashboard app so the AI can add or adjust pages, forms, and API calls to match the backend.

---

## 1. Taxonomy (Type → Category → Subcategory → Question)

- **Questions are shared across all stages.** There is no `stage_id` on questions. The hierarchy is: **Type (top)** → **Category** → **Subcategory** → **Question**.
- **Types** are top-level. List and CRUD types with: `name`, `image` (optional), `status`. No stage, category, subcategory, or price fields.
- **Categories** belong to a type. Use `type_id` (required) instead of `stage_id`. Fields: `type_id`, `name`, `image` (optional), `status`. No monthly/yearly price.
- **Subcategories** belong to a category. Fields: `category_id`, `name`, `image` (optional), `status`. No `stage_id`, no monthly/yearly price.
- **Questions** belong to type, category, and subcategory. Fields: `type_id`, `category_id`, `subcategory_id`, `name`, 4 answers with one correct, `status`, plus the 5 question videos (start, lunch, question, correct_answer, wrong_answer). No `stage_id`.
- **API:**  
  - Dashboard: `GET/POST .../dashboard/types`, `.../dashboard/types/{id}`; same for `categories` (filter by `type_id`), `subcategories` (filter by `category_id`), `questions` (filter by `type_id`, `category_id`, `subcategory_id`).  
  - Remove all monthly/yearly price inputs and columns from UI.

---

## 2. Stages: type selector and conditional fields

- **Stage type** is required: either **"Questions Group Stage"** (`questions_group`) or **"Life Points Stage"** (`life_points`).
- **If "Questions Group Stage":**
  - Show field: **Question groups count** (required, integer ≥ 1).
  - After save, the backend creates that many **question groups**. Each group has 4 videos: **Start video**, **End video**, **Correct answer video**, **Wrong answer video**.
  - Provide a way to assign these 4 videos **per group** (e.g. a page or section per group).  
  - API to update a group’s videos: `POST .../dashboard/stages/{stage_id}/groups/{group_id}/videos` with `multipart/form-data`: `start_video`, `end_video`, `correct_answer_video`, `wrong_answer_video` (each optional file).
- **If "Life Points Stage":**
  - Show fields: **Number of questions** (required), **Life points per question** (required).
  - Show **4 video uploads for the stage** (not per group): **Start video**, **End video**, **Correct answer video**, **Wrong answer video**.
- **Common stage fields (both types):** name, back icon, home icon, exit icon, status.
- **API:**  
  - `GET .../dashboard/stages` and `GET .../dashboard/stages/{id}` return `stage_type`, `question_groups_count`, `number_of_questions`, `life_points_per_question`, the 4 life-point videos (when applicable), and `question_groups` (array of groups, each with id, sort_order, and 4 video URLs).  
  - Create/update: `POST .../dashboard/stages` and `POST .../dashboard/stages/{id}` with the appropriate fields and, for Life Points, the 4 video files.

---

## 3. Adventurers (app users)

- **Dashboard CRUD** under permission **`adventurers`**.
- **Fields:** name, country (optional), email, **PIN code (4 digits, acts as password)**; lifetime_score; number_correct_answers, number_wrong_answers, number_full_winnings, number_surrender_times; created_at.
- **List:** optional filters by name, email, country, score range. Order by `lifetime_score` DESC.
- **Create from dashboard:** include PIN (4 digits); backend stores it hashed. No `pin_code` in list/show responses.
- **API:**  
  - `GET/POST .../dashboard/adventurers`, `GET/POST/DELETE .../dashboard/adventurers/{id}`.  
  - Permission: `adventurers`.

---

## 4. Ranks

- **Dashboard CRUD** under permission **`ranks`**.
- **Fields:** name, **start_score** (integer), icon (optional image).
- **End score** is not stored: it is computed as **(next rank’s start_score − 1)**. The top rank has no end (null). Show in UI as "Start score" and "End score (auto)" or similar.
- **API:**  
  - Dashboard: `GET/POST .../dashboard/ranks`, `GET/POST/DELETE .../dashboard/ranks/{id}`.  
  - Response includes computed `end_score` per rank.  
  - Permission: `ranks`.

---

## 5. App endpoints (for mobile/web app, not dashboard)

- **Scoreboard:** `GET .../scoreboard?per_page=50` — returns adventurers ordered by `lifetime_score` DESC, each with id, name, country, lifetime_score, **rank** (object: id, name, start_score, end_score, icon), and the count fields. Paginated.
- **Ranks list:** `GET .../ranks` — returns all ranks with id, name, start_score, end_score (computed), icon. Ordered by start_score ASC.

---

## 6. Permissions

- **questions_and_stages** — stages (including type, groups, life points, videos), types, categories, subcategories.
- **questions** — questions CRUD and question videos (assign 5 videos per question).
- **adventurers** — adventurers CRUD.
- **ranks** — ranks CRUD.

---

## 7. Summary for the dashboard

1. **Taxonomy:** Type → Category → Subcategory → Question; no stage on question; remove all price fields from category, subcategory, type.
2. **Stages:** Dropdown "Questions Group Stage" | "Life Points Stage"; conditional fields and 4 videos (per group or per stage); assign group videos via `POST .../stages/{stage}/groups/{group}/videos`.
3. **Adventurers:** List/create/edit/delete with name, country, email, 4-digit PIN, stats; permission `adventurers`.
4. **Ranks:** List/create/edit/delete with name, start_score, icon; show computed end_score; permission `ranks`.
5. **App:** Use `GET .../scoreboard` and `GET .../ranks` for the app; no dashboard UI required for these.

Implement or adjust dashboard pages and forms to match these APIs and permission names. Keep the same patterns as the rest of your app (routing, API client, forms, tables).
