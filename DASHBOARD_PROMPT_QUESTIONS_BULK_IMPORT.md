# Dashboard Prompt: Questions bulk import (Excel)

Use this prompt in the **dashboard app** so the AI can add UI and API integration for bulk-uploading questions from Excel.

---

## Backend (already implemented)

Base path: `{API_BASE}/api/dashboard`  
Auth: `Authorization: Bearer {sanctum_token}`  
Permission: `questions` (same as existing questions CRUD)

### 1. Download import template (empty structure + example row)

- **GET** `/questions/bulk-import/template`
- **Response:** binary `.xlsx` file (`questions_import_template.xlsx`)
- **Sheets:**
  - `Instructions` — short steps
  - `Questions` — columns to fill (see below)

### 2. Download lookup reference (live IDs from database)

- **GET** `/questions/bulk-import/lookups`
- **Response:** binary `.xlsx` file (`questions_import_lookups.xlsx`)
- **Sheets:**
  - **`Lookups_All`** (default tab) — **all** lookup data in one table:
    - `section`: `Types` | `Categories` | `Subcategories` | `Question kinds`
    - `id`, `name`, `type_id` (categories), `category_id` (subcategories), `value` + `label_ar` (question kinds), `display`
  - `Lookups_Types` — `id`, `name`, `display`
  - `Lookups_Categories` — `id`, `name`, `type_id`, `display`
  - `Lookups_Subcategories` — `id`, `name`, `category_id`, `display`
  - `Lookups_QuestionKinds` — `value`, `label_en`, `label_ar`, `display` (`normal`, `words`, `voice`, `video`, `image`)

Admins use this file to copy the correct IDs into the import template. Open **`Lookups_All`** first for everything in one view.

### 3. Bulk upload

- **POST** `/questions/bulk-import`
- **Content-Type:** `multipart/form-data`
- **Body:** `file` — `.xlsx` or `.xls` (max 10 MB)
- **Success (200):**

```json
{
  "success": true,
  "data": {
    "created": 12,
    "failed": 2,
    "errors": [
      { "row": 5, "message": "category_id 99 does not exist." }
    ]
  },
  "message": "..."
}
```

- **Validation error (422):** missing `Questions` sheet, missing columns, or no data rows
- Import is **row-by-row**: valid rows are created; invalid rows are listed in `errors` without rolling back successful rows.

---

## `Questions` sheet columns (import reads by header name)

| Column | Required | Notes |
|--------|----------|--------|
| `type_id` | Yes | Must exist |
| `category_id` | Yes | Must belong to `type_id` |
| `subcategory_id` | Yes | Must belong to `category_id` |
| `name` | Yes | Question text, max 255 chars |
| `question_kind` | Yes | `normal`, `words`, `voice`, `video`, `image` |
| `word` | If `words` | Letters separated by spaces (e.g. `ك ر ة`) |
| `answer_1` … `answer_4` | Yes | All four required |
| `is_correct_1` … `is_correct_4` | Yes | Exactly **one** `TRUE` / `1` / `yes` |
| `status` | No | Default `1` / active |
| `type_display`, `category_display`, … | No | Ignored on import (for human reference only) |

**Not imported via Excel:** question image/voice/video files and the 5 stage videos (`start_video`, `lunch_video`, etc.). After bulk import, use the existing **Assign videos** screen per question.

---

## UI to build in the dashboard

### Questions list page (or dedicated “Bulk import” section)

1. **Download template** button → `GET .../bulk-import/template` (save as file; use `responseType: 'blob'` in axios/fetch).
2. **Download IDs reference** button → `GET .../bulk-import/lookups` (blob download).
3. **Upload Excel** area:
   - File input accepting `.xlsx`, `.xls`
   - Submit → `POST .../bulk-import` with `FormData`: `formData.append('file', file)`
4. **Results panel** after upload:
   - Show `created` and `failed` counts
   - Table of `errors` with `row` + `message` (Excel row numbers match the file, header = row 1)
5. On success with `created > 0`, refresh the questions list.

### UX copy (suggested)

- Explain: download lookups first to get valid IDs, fill the template, then upload.
- Note: media and TV videos must be added manually after import for `voice` / `video` / `image` kinds.

### Example: download (axios)

```javascript
const downloadTemplate = async () => {
  const res = await api.get('/dashboard/questions/bulk-import/template', {
    responseType: 'blob',
    headers: { Authorization: `Bearer ${token}` },
  });
  const url = window.URL.createObjectURL(new Blob([res.data]));
  const a = document.createElement('a');
  a.href = url;
  a.download = 'questions_import_template.xlsx';
  a.click();
  window.URL.revokeObjectURL(url);
};
```

### Example: upload

```javascript
const uploadBulk = async (file) => {
  const form = new FormData();
  form.append('file', file);
  const res = await api.post('/dashboard/questions/bulk-import', form, {
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'multipart/form-data',
    },
  });
  return res.data; // { success, data: { created, failed, errors }, message }
};
```

---

## Permissions & errors

- Same permission as questions CRUD: user needs `questions` permission.
- `401` — not logged in or missing permission.
- Show Arabic API `message` / `error` strings in toast or alert when upload fails.

---

## Checklist

- [ ] Two download buttons (template + lookups) with blob handling
- [ ] File picker + upload with loading state
- [ ] Results summary (`created`, `failed`, error table)
- [ ] Refresh questions list after successful import
- [ ] Help text about lookups file and post-import media/videos
