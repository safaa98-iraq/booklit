# Booklit — منصة الملازم التعليمية

An Arabic (RTL) educational platform. Admins and content editors publish structured study booklets (ملازم) with linked multiple-choice questions, and students study and solve them in the Flutter app.

**Phase 1 (this repo):** Laravel REST API, database schema, and the Filament admin/editor panel.
**Phase 2:** Flutter student app (Android, iOS and Web). Waiting on review of Phase 1.

| Layer | Tech |
|---|---|
| API | Laravel 12, Sanctum tokens, MySQL 8 |
| Admin / editor panel | Filament 3 (Arabic, RTL), served at `/admin` |
| Media | Any S3-compatible storage (`MEDIA_DISK=s3`) |

> **Why Laravel 12 instead of 11?** Laravel 11 stopped receiving security fixes in March 2026. `composer audit` reports unpatched advisories in it, including a high-severity CRLF injection in the `email` validation rule. Laravel 12 keeps the same application structure, and Filament 3 supports it.

---

## Quick start

```bash
composer install
cp .env.example .env && php artisan key:generate
# configure DB_* (MySQL) and ADMIN_* in .env
php artisan migrate --seed                         # creates the admin from ADMIN_EMAIL / ADMIN_PASSWORD
php artisan db:seed --class=DemoContentSeeder      # optional sample content
php artisan storage:link                           # when MEDIA_DISK=public
php artisan serve
```

- Panel: `http://localhost:8000/admin`. Demo logins are `admin@example.com` / `password` and `editor@example.com` / `password`.
- Demo student (API): phone `07700000000`, password `password`.
- Tests: `php artisan test` (runs on in-memory SQLite).

### S3-compatible media

```dotenv
MEDIA_DISK=s3
FILAMENT_FILESYSTEM_DISK=s3
MEDIA_SIGNED_URLS=true        # private bucket → short-lived signed URLs
AWS_ACCESS_KEY_ID=… AWS_SECRET_ACCESS_KEY=… AWS_DEFAULT_REGION=… AWS_BUCKET=…
AWS_ENDPOINT=https://<r2|minio|spaces endpoint>   AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

## Domain model

```
Stage (المرحلة) ─┬─ Subject (المادة) ─┬─ Booklet (الملزمة) ── Topic (الموضوع) ── ContentBlock
                 │                    │                                         ▲ uuid (stable)
                 │                    └─ Question ── QuestionOption (2–6, one correct)
                 │                         └─ PassageAnchor ──────────────────────┘ block + [start,end)
                 └─ Subscription (stage- or subject-scoped, expiry)
User (admin | editor | student) ── editor_subject, Device (one active), Attempt ── AttemptAnswer
```

| Table | Notes |
|---|---|
| `stages`, `subjects` | `is_ministerial` on either one locks the subject (paid content). |
| `editor_subject` | Which subjects an editor may author. |
| `booklets` | `quiz_question_count` is set by the editor. `is_published` controls student visibility. |
| `topics`, `content_blocks` | Blocks store `type` + `data` (JSON) + a derived `plain_text`. `uuid` is the stable block ID. |
| `questions` | `source` (`ministerial`/`teacher`), `difficulty`, and `link_type` (`topic` / `booklet` / `none` = standalone bank per subject). |
| `question_options` | 2–6 options, exactly one `is_correct`. |
| `passage_anchors` | `content_block_id` + `start_offset`/`end_offset` + `quoted_text` snapshot (detects stale anchors after edits). |
| `attempts`, `attempt_answers` | Shuffled question order and per-question shuffled `option_order`, stored at start. `topic_id` snapshot feeds the reports. |
| `subscriptions` | `stage_id` **or** `subject_id`, `starts_at`/`expires_at`/`revoked_at`, `activated_by`. |
| `devices` | Binding history. The active binding is the row with `released_at IS NULL`. |
| `settings` | Key/value JSON: Telegram link, locked-subject message, 4th button config. |

### Content blocks and anchor offsets

| type | `data` | `plain_text` |
|---|---|---|
| `paragraph` | `{text}` | `text` |
| `heading` | `{text, level: 2-4}` | `text` |
| `list` | `{style: bullet\|ordered, items: [..]}` | items joined with `\n` |
| `quote` | `{text, variant: note\|tip\|warning\|definition}` | `text` |
| `math` | `{latex, display}` | `latex` |
| `image` | `{url, caption, alt}` | `caption` (not anchorable) |

Anchor offsets are **Unicode code points** into `plain_text`, with the end exclusive. The API returns `plain_text` with every block, so the reader can highlight `[start, end)` directly. Dart strings are UTF-16, so convert through `runes` if the text can contain characters outside the BMP. Arabic text is entirely inside the BMP.

Block UUIDs survive reordering, edits, and moving a block between topics of the same booklet (`BookletContentService::syncBlocks`). Deleting a block deletes its anchors.

---

## Roles and permissions

- **Admin:** everything (`Gate::before`).
- **Editor:** booklets, topics and questions, but only for subjects assigned in *المحررون*. Resource queries are scoped (`manageableBy`), and subject selects are validated with `in:` against the assigned subjects.
- **Student:** API only (`student` middleware). Staff cannot log in to the student API.

Policies live in `app/Policies`. Business logic is in `app/Services`: `SubscriptionService`, `DeviceBindingService`, `AuthService`, `AttemptService`, `BookletContentService`, `ReportService`, `SettingsService`.

## Access rules

- **Locking:** a subject is locked when it or its stage is ministerial, unless the student has a current subscription (`starts_at ≤ now < expires_at`, not revoked) for that subject or its stage. Opening locked content returns:
  ```json
  403 {"code":"subject_locked","message":"…","telegram_url":"https://t.me/…","button_label":"…"}
  ```
- **One device:** the first login (or registration) binds `device_id`, and a login from any other device returns `409 {"code":"device_mismatch"}`. Every authenticated request must send `X-Device-Id`, and it must match both the active binding and the device the token was issued to. Logging in again revokes older tokens. The admin's **إعادة تعيين الجهاز** releases the binding and revokes all tokens.

---

## REST API (`/api/v1`)

Send `Accept: application/json` on every request. Authenticated routes also need `Authorization: Bearer <token>` and `X-Device-Id: <device_id>`.

| Method | Path | Purpose |
|---|---|---|
| GET | `/settings` | Public: Telegram link, locked message, 4th button config |
| POST | `/auth/register` | `name, phone, password, password_confirmation, device_id, device_name?, platform(android\|ios\|web)` → `{token, user}` |
| POST | `/auth/login` | `phone, password, device_id, device_name?, platform` → `{token, user}` |
| POST | `/auth/logout` | Revoke the current token |
| GET | `/me` | Profile + active subscriptions + `watermark` text (name • phone) |
| GET | `/stages` | Home: stages → subjects with `is_locked` |
| GET | `/subjects?stage_id=` | Flat subject list with `is_locked` |
| GET | `/subjects/{id}` | Subject + published booklets (403 `subject_locked` if locked) |
| GET | `/booklets/{id}` | Reader view: topics → blocks, plus `actions` for the 4 bottom buttons (counts, quiz size, `fourth_button`) |
| GET | `/booklets/{id}/questions/review?source=ministerial\|teacher` | **أظهر الحل**: questions with the correct option, explanation and anchors |
| GET | `/subjects/{id}/question-bank/review?source=` | Same, for the subject's standalone bank |
| POST | `/attempts` | `mode(ministerial\|teacher\|quiz)`, `booklet_id` **or** `subject_id`, `difficulty` (required for quiz) |
| GET | `/attempts`, `/attempts/{id}` | History / resume |
| PUT | `/attempts/{id}/answers` | `answers: [{question_id, option_id\|null}]`. Change answers freely before submitting. |
| POST | `/attempts/{id}/submit` | Optional final `answers`. Returns score, `correct_option_id`, `explanation`, and `anchors: [{block_id, topic_id, start, end, text, is_stale}]`. |

Correct answers are never included while an attempt is `in_progress`. Phone numbers are normalised to the Iraqi `07XXXXXXXXX` form, so `+964…`, `00964…` and Arabic-Indic digits all work.

---

## Admin panel (Filament, Arabic RTL)

- **المحتوى:** المراحل · المواد (assign editors, mark as ministerial) · الملازم (topics relation manager with the block editor: paragraph, heading, image, list, LaTeX math, note) · الأسئلة (2–6 options, exactly one correct, link to topic/booklet/bank, **passage-anchor picker**: highlight text with the mouse and the offsets fill in).
- **الطلاب والاشتراكات:** students table with **تفعيل اشتراك** (whole stage or selected subjects, plus expiry date, amount and notes) and **إعادة تعيين الجهاز**. Each student has subscription, device-history and attempt tabs.
- **التقارير:** درجات الطلاب (score per attempt) · الأسئلة الأصعب (per-question error rate) · أداء الطلاب حسب الموضوع (per-topic accuracy per student).
- **الإعدادات:** المحررون · إعدادات التطبيق (Telegram link, locked message, configurable 4th button).

## Phase 2 notes for the Flutter app

- Screenshot protection is client-side: `FLAG_SECURE` on Android, and capture detection plus blur on iOS. On Web, draw the dynamic watermark from `GET /me` → `watermark`.
- Generate `device_id` once per install (and once per browser, kept in local storage on Web) and send it as `X-Device-Id`.
- Show the bottom buttons from `booklet.actions`. The 4th button is driven by `actions.fourth_button` (`enabled`, `label`, `icon`, `action: none|url|route`, `value`).
