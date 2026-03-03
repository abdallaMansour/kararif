# Prompt: Add Terms & Conditions and Privacy Policy editing to the dashboard (steps format)

Use this prompt in your **dashboard/frontend** project (or with the dev who owns it) to add UI for editing Terms and Conditions and Privacy Policy as separate steps.

---

## Backend contract (already implemented)

- **Dashboard GET** `GET /api/dashboard/setting` returns per-locale data for terms and privacy in **steps format**:
  - `terms_conditions_ar` / `terms_conditions_en`:  
    `{ "last_updated": "2026-02-10" | null, "steps": [ { "title": "...", "content": "..." }, ... ] }`
  - `privacy_policy_ar` / `privacy_policy_en`: same shape.
- **Dashboard POST** `POST /api/dashboard/setting` accepts (for each language, e.g. `ar`, `en`):
  - **Terms:**  
    `terms_conditions_last_updated_{lang}` (optional, date `Y-m-d`),  
    `terms_conditions_steps_{lang}` (array of `{ title, content }`).
  - **Privacy:**  
    `privacy_policy_last_updated_{lang}` (optional, date `Y-m-d`),  
    `privacy_policy_steps_{lang}` (array of `{ title, content }`).
- **App public endpoints** return the same steps shape (with optional `image`):  
  `GET /api/terms`, `GET /api/privacy-policy` → `{ "data": { "last_updated", "steps", "image" } }`.

---

## Prompt to give to the dashboard / frontend

Copy and paste the following (and adjust stack/framework names if needed):

---

**Add a Settings section for “Terms and Conditions” and “Privacy Policy” so they can be edited as separate steps per language.**

**Requirements:**

1. **Data shape**  
   For each of **Terms and Conditions** and **Privacy Policy**, and for each **language** (e.g. Arabic, English):
   - **Last updated:** optional date (e.g. date input), sent as `Y-m-d` (e.g. `terms_conditions_last_updated_ar`, `privacy_policy_last_updated_ar`).
   - **Steps:** list of steps. Each step has:
     - **Title** (e.g. “1. القبول بالشروط” / “1. Acceptance of Terms”).
     - **Content** (rich text or textarea; may contain paragraphs or bullet points).

2. **Loading**  
   Load settings from `GET /api/dashboard/setting`. Use:
   - `terms_conditions_ar`, `terms_conditions_en` (each is `{ last_updated, steps }`).
   - `privacy_policy_ar`, `privacy_policy_en` (same shape).  
   If the API returns the old single-block format, the backend normalizes it to one step; the UI should support both.

3. **UI**  
   - Tabs or dropdown per language (e.g. Arabic / English).  
   - For the selected type (Terms vs Privacy) and language:
     - An optional “Last updated” date field.
     - A list of steps. Each step: editable title + content. Buttons to add step, remove step, reorder steps (e.g. drag-and-drop or up/down).
   - Separate sections or pages for “Terms and Conditions” and “Privacy Policy” (or two sub-tabs under “Legal / Settings”).

4. **Saving**  
   On submit, send `POST /api/dashboard/setting` with the same fields the backend expects:
   - For each lang: `terms_conditions_last_updated_{lang}`, `terms_conditions_steps_{lang}` (array of `{ title, content }`).
   - For each lang: `privacy_policy_last_updated_{lang}`, `privacy_policy_steps_{lang}` (array of `{ title, content }`).  
   Include other existing setting fields if the form is a single big settings form, so you don’t overwrite them.

5. **Optional**  
   Keep support for the existing “terms/privacy image” upload if your dashboard already has it (keys like `terms_conditions_image`, `privacy_policy_image`); the backend already handles them.

Implement the form so each step is edited separately (title + content per step), with add/remove/reorder, and persist using the API above.

---

You can trim or expand the prompt depending on whether the dashboard is React, Vue, Blade, etc.
