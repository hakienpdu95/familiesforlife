Tóm tắt Task 5:

  SurveyAnswerData — DTO một câu trả lời:
  - field_key (string), value (mixed — scalar hoặc int[] cho checkbox), other_text (?string)
  
  SurveyResponseData — DTO toàn bộ submit payload:
  - answers (DataCollection<SurveyAnswerData>), respondent_ref (?string), respondent_ip (?string binary)

  SubmitSurveyRequest — static validation (HTTP layer):

  ┌────────────────────────────────────────────────┬──────────────────────────────────────────────────────┐
  │                      Rule                      │                       Mục đích                       │
  ├────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │ answers required, array, min:1                 │ Phải có ít nhất 1 câu trả lời                        │
  ├────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │ answers.*.field_key required, string, max:100  │ Cấu trúc cơ bản                                      │
  ├────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │ answers.*.value required                       │ Phải có giá trị (kiểu cụ thể validate động ở Task 6) │
  ├────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │ answers.*.other_text nullable, string, max:500 │ Text cho option "Khác"                               │
  └────────────────────────────────────────────────┴──────────────────────────────────────────────────────┘

  Có thêm respondentIpBinary() — chuyển IPv4/IPv6 thành binary 16 bytes để lưu vào respondent_ip (VARBINARY(16)), và toResponseData() — bridge sang Action.

  Ranh giới rõ: static = cấu trúc payload đúng format → dynamic (Task 6) = field_key tồn tại, option hợp lệ, required, rule_min/max.


Tóm tắt Task 6 — SubmitSurveyAction:

  ---
  Flow tổng thể:
  loadFieldMap (1 query) → runValidation (5 lớp, collect ALL errors) → DB::transaction (insert)
  
  5 lớp validation — thứ tự và logic:

  ┌─────┬───────────────────────────────────────────────────────────────────────────────────┬─────────────────────────────────────────┐
  │ Lớp │                                     Mục đích                                      │          Dừng field nếu fail?           │
  ├─────┼───────────────────────────────────────────────────────────────────────────────────┼─────────────────────────────────────────┤
  │ 1   │ field_key tồn tại + is_active = 1                                                 │ Có — layers 2–5 không thể chạy          │
  ├─────┼───────────────────────────────────────────────────────────────────────────────────┼─────────────────────────────────────────┤
  │ 2   │ Kiểu dữ liệu khớp field_type (checkbox=array, number=numeric, date=Y-m-d...)      │ Không throw, nhưng skip layer 3 nếu lỗi │
  ├─────┼───────────────────────────────────────────────────────────────────────────────────┼─────────────────────────────────────────┤
  │ 3   │ option_value phải tồn tại trong survey_field_options của đúng field               │ Skip nếu layer 2 lỗi                    │
  ├─────┼───────────────────────────────────────────────────────────────────────────────────┼─────────────────────────────────────────┤
  │ 4   │ Required fields phải có mặt và không rỗng — chạy sau khi scan xong tất cả answers │ —                                       │
  ├─────┼───────────────────────────────────────────────────────────────────────────────────┼─────────────────────────────────────────┤
  │ 5   │ rule_min/rule_max (number: giá trị, text: độ dài), rule_max_select (checkbox)     │ Skip nếu có lỗi trước                   │
  └─────┴───────────────────────────────────────────────────────────────────────────────────┴─────────────────────────────────────────┘

  Quyết định thiết kế quan trọng:
  - Thu thập ALL errors trước khi throw — user thấy tất cả field cần sửa cùng lúc
  - Duplicate field_key trong cùng submit → reject ngay 
  - fieldMap load 1 lần với eager options — không có N+1 dù survey có 100 fields
  - SurveyAnswer::insert($rows) bulk insert — không gọi create() từng row
  - buildAnswerRows() tự tìm otherOptionId trong submitted options — đúng spec "is_other + text → row có cả option_id + value_string"
  - Lỗi trả về keyed by field_key — đúng contract { errors: { field_key: [...] } }

Tóm tắt Task 7:

  ---
  Kiến trúc cốt lõi — Batch query pattern:
  
  Thay vì N queries (1 per field), service chạy tối đa 5 queries — một per stat type, tất cả index-backed:

  forSurvey()
    ├── countCompleteResponses()   → INDEX (survey_id, status, submitted_at)
    ├── batchChoiceStats()         → INDEX (field_id, option_id)
    ├── batchNumberStats()         → INDEX (field_id, value_number)
    ├── batchBooleanStats()        → INDEX (field_id, value_bool)
    ├── batchTextStats()           → INDEX (field_id, value_string)
    └── batchTextareaStats()       → field_id prefix của INDEX (field_id, option_id)

  Output shape per field_type:

  ┌───────────────────────────┬────────────────────────────────────────────────────────────────────────────────────────────┐
  │           Type            │                                        Stats trả về                                        │
  ├───────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ select / radio / checkbox │ distribution[] → {option_value, label, count, percent} — percent tính trên total_responses │
  ├───────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ number / rating           │ {count, avg, min, max} — avg round 2 chữ số thập phân                                      │
  ├───────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ boolean                   │ {yes_count, no_count, total} — cast TINYINT 1/0 khi so sánh                                │
  ├───────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ text / textarea           │ {count} — không aggregate nội dung per spec                                                │
  └───────────────────────────┴────────────────────────────────────────────────────────────────────────────────────────────┘

  Quyết định quan trọng:
  - options cho choice fields được eager load 1 lần cùng fieldMap — không join SQL, không N+1
  - batchBooleanStats cast value_bool sang int khi so sánh vì MySQL trả về TINYINT string 
  - textarea không có index riêng trên value_text — dùng COUNT(*) WHERE field_id IN (...) thông qua prefix của index (field_id, option_id) — đúng spec "index-backed, không full table
  scan"
  - Service là pure read — không có side effect, không write DB



Giai đoạn 1 hoàn thành. Tóm tắt những gì đã implement:

  ┌──────┬───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
  │ Task │                                                                                  Kết quả                                                                                  │
  ├──────┼───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ 1.1  │ SurveyToken model — relationship survey(), scopes active()/forSurvey(), helpers isExpired()/isValid(). Quan hệ tokens() thêm vào Survey model.                            │
  ├──────┼───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ 1.2  │ SurveyPermissionSeeder — tạo 7 permissions survey.* trong DB, gán cho system_admin, ceo, marketing, ops. Chạy thành công.                                                 │
  ├──────┼───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ 1.3  │ CreateSurveyAction + UpdateSurveyAction (slug lock khi active, unique check) + SurveyFormData DTO + SurveyRequest (validate title/slug/version) + Admin\SurveyController  │
  │      │ (index/create/store/edit/update/destroy + activity log).                                                                                                                  │
  ├──────┼───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ 1.4  │ ActivateSurveyAction — validate ≥1 active field, đổi status → Active, lock slug.                                                                                          │
  ├──────┼───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ 1.5  │ Sidebar thêm menu "Khảo sát" với @can('survey.view') guard, submenu Danh sách + Tạo mới.                                                                                  │
  ├──────┼───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ 1.6  │ admin/surveys/index.blade.php — table với filter (q + status), status badge, responses count, actions (edit/delete), empty state.                                         │
  ├──────┼───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ 1.7  │ admin/surveys/create.blade.php — form tạo mới + Alpine.js auto-generate slug từ title. admin/surveys/edit.blade.php — 2 cột: form thông tin + builder placeholder + stats │
  │      │  card + nút activate.                                                                                                                                                     │
  └──────┴───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘

  7 routes backend.surveys.* đã đăng ký. 17/17 unit tests pass.

Tổng kết GIAI ĐOẠN 3 đã implement:

  ---
  Task 3.1 — GenerateSurveyTokenAction
  
  - Sinh Str::random(64) plaintext → lưu hash('sha256', $plain) vào DB
  - Trả ['token' => $model, 'plain' => $plain] — plaintext chỉ xuất hiện 1 lần duy nhất
  - Ghi activity log token.created

  Task 3.2 — RevokeSurveyTokenAction + TokenController

  - 3 routes: GET /tokens (list), POST /tokens (generate JSON), DELETE /tokens/{token} (revoke JSON)
  - TokenController check quyền survey.manage_tokens, validate input

  Task 3.3 — ValidateSurveyToken middleware

  - Đọc Authorization: Bearer <plain> → hash SHA-256 → lookup DB
  - Kiểm tra is_active, expires_at, đúng survey (theo slug)
  - updateQuietly(['last_used_at' => now()]) — không trigger events
  - Gắn vào /schema và /submit routes

  Task 3.4 — Rate limit

  - Named limiter survey-submit: 60 req/min per token hash (fallback IP)
  - Đăng ký trong RouteServiceProvider::boot(), áp dụng qua throttle:survey-submit trên /submit

  Task 3.5 — UI views/tokens/index.blade.php

  - List tất cả tokens: name, status badge (Active/Revoked/Hết hạn), last_used_at, expires_at
  - Modal tạo token: name + optional expires_at
  - Modal reveal plaintext: hiển thị token 1 lần với copy button, cảnh báo "lưu ngay"
  - Thu hồi token với confirm dialog
  - Fix migration: unique(['survey_id', 'is_active']) → index (MySQL-safe, tạo index thay thế trước khi drop)

  Index migration fix đúng
  Migration trước set UNIQUE(survey_id, is_active) — sai spec và giới hạn 1 active/1 inactive per survey. Migration fix đổi về non-unique composite index. Comment giải thích rõ lý do.

┌───────────┬
  ❯ làm thế nào để file job có thể chạy được nhỉ. bạn hướng dẫn nhanh giúp tôi nhé                                                                                                        
Bảng jobs đã tồn tại sẵn. Mọi thứ đã sẵn sàng, chỉ cần chạy worker.

  ---
  Hướng dẫn nhanh
  
  .env đang dùng QUEUE_CONNECTION=database — job sẽ lưu vào bảng jobs trong MySQL. Bảng jobs, failed_jobs, job_batches đều đã có.

  Chạy worker (trong terminal riêng, song song với php artisan serve):

  php artisan queue:work

  Hoặc nếu muốn auto-detect file mới khi dev:

  php artisan queue:listen

  ┌───────────┬───────────────────────┬─────────────────────────────┐
  │           │      queue:work       │        queue:listen         │
  ├───────────┼───────────────────────┼─────────────────────────────┤
  │ Hiệu suất │ Cao hơn (giữ process) │ Thấp hơn (restart mỗi job)  │
  ├───────────┼───────────────────────┼─────────────────────────────┤
  │ Dùng khi  │ Production            │ Dev (pick up code thay đổi) │
  └───────────┴───────────────────────┴─────────────────────────────┘

  Test nhanh: Gọi một API có Bearer token hợp lệ → xem last_used_at trong DB được cập nhật sau vài giây.

  Nếu job lỗi, xem trong bảng failed_jobs:
  php artisan queue:failed

  Retry:
  php artisan queue:retry all

  ---
  Tóm lại: chỉ cần chạy php artisan queue:listen là job UpdateTokenLastUsedJob sẽ tự xử lý. Không cần config thêm gì.

  ┌───────────┬

  Phân quyền tương lai: Hiện tại cả 3 dùng survey.manage_tokens. Sau này chỉ cần thêm permission mới (VD: survey.view_token_secret) và thay vào $this->authorize(...) trong reveal() —
  không cần đụng vào logic nào khác.


---
GIAI ĐOẠN 3 — Review & Fixes (Bổ sung sau review kỹ thuật)

  Các issues được phát hiện và fix:

  1. UpdateTokenLastUsedJob (NEW)
     - Trước: updateQuietly() đồng bộ trong middleware — block hot path mỗi request
     - Sau: dispatch async UpdateTokenLastUsedJob (ShouldQueue, $tries=3) → queue:work xử lý nền
     - whereKey($id)->update(['last_used_at' => now()]) — bypass Eloquent events, O(1) index write

  2. ValidateSurveyToken middleware — eager load + slug check
     - Trước: 2 queries (load token, load survey riêng)
     - Sau: eager load survey qua with('survey') trong 1 query; check slug qua $token->survey->slug
     - Bảo vệ thêm /stats và /responses routes (move vào middleware group)

  3. TokenFormData — validation responsibility
     - Thêm rules() static method vào Data class
     - Controller dùng TokenFormData::from(request()) thay vì $request->validate() thủ công

  4. Token redesign — "xem lại được" + hard delete
     - Thêm column token_encrypted (TEXT NULLABLE) — lưu Crypt::encryptString($plain) AES-256
     - GenerateSurveyTokenAction lưu cả 2: token (SHA-256 hash, dùng để lookup) + token_encrypted
     - TokenController::reveal() — Crypt::decryptString($token->token_encrypted), trả plaintext
     - DeleteSurveyTokenAction — log trước khi hard delete (giữ audit trail)
     - Routes mới: GET /{survey}/tokens/{token}/reveal, PATCH /{survey}/tokens/{token}/revoke
     - UI redesign: 3 actions/token (Xem, Thu hồi, Xóa) với Alpine.js reveal modal + delete confirm modal

  ┌──────────────────────┬──────────────────────────────────────────────────────────────────┐
  │       Pattern        │                            Mục đích                              │
  ├──────────────────────┼──────────────────────────────────────────────────────────────────┤
  │ token (SHA-256)      │ Index lookup, không thể reverse — dùng trong middleware           │
  │ token_encrypted      │ AES-256 via Crypt — admin có thể xem lại qua reveal endpoint     │
  │ is_active flag       │ Revoke mà không mất audit history                                │
  │ Hard delete          │ GDPR / cleanup — log trước khi xóa để giữ trail                 │
  └──────────────────────┴──────────────────────────────────────────────────────────────────┘


---
GIAI ĐOẠN 4 — Responses & Statistics

  Task 4.1 — ResponseController::index() + filter
  - Paginate(30) với 4 filters: respondent_ref (LIKE), status (enum cast), from/to date
  - Summary counts: totalAll + totalComplete (2 queries cache-friendly, trên index)
  - Export giữ nguyên filters hiện tại qua array_merge(['survey' => $survey], array_filter($filters))
  - Permission guard: survey.view_responses

  Task 4.2 — ResponseViewerService + show()
  - 3 queries cố định, không N+1 dù survey có bao nhiêu sections/fields:
    1. sections ordered()
    2. fields+options eager load per section (with(['fields' => fn => ordered()->with('options')]))
    3. answers+option eager load (with('option')->get()->groupBy('field_id'))
  - Format per FieldType: Checkbox → array of labels, Rating → star count (string), Boolean → "Có"/"Không", Text/Textarea → raw string
  - Output: array of sections → fields → {label, type, answer}

  Task 4.3 — Soft delete SurveyResponse
  - Migration thêm deleted_at column vào survey_responses
  - SurveyResponse::use SoftDeletes — global scope auto-exclude deleted records
  - ResponseController::destroy() soft delete + redirect flash "Đã xóa response"
  - Mọi query (index, stats, export) tự động bỏ qua deleted records

  Task 4.4 — SurveyStatsService::totalByDay()
  - Dùng Eloquent (SoftDeletes scope auto) + selectRaw DATE(submitted_at) as day, COUNT(*) as count
  - Fill missing days với count=0 để chart continuous (không có khoảng trống)
  - Output: array<{day: string, count: int}> — 30 phần tử dù không có data

  Task 4.5 — StatsController + Apache ECharts dashboard
  - StatsController::index() — permission survey.view_responses, inject SurveyStatsService
  - View stats/index.blade.php:
    • 4 summary cards: total responses, active fields, 30d count, avg/day
    • ECharts v5 line chart (CDN) — smooth area, ResizeObserver responsive, dark theme auto-detect
    • Per-field cards per type:
      - choice → progress bars (percent label)
      - number/rating → 2×2 grid (avg, count, min, max)
      - boolean → split bar (Có/Không với %)
      - text/textarea → count badge

  Task 4.6 — Export Excel wired đúng
  - ExportSurveyResponsesAction dùng rap2hpoutre/fast-excel + Generator — O(1) memory streaming
  - Permission guard: survey.export
  - Route /export đặt TRƯỚC /{response} wildcard để tránh route conflict
  - Export giữ filters (respondent_ref, status, from, to) qua query string

  Navigation bổ sung:
  - surveys/index: cột Responses count → clickable link + 2 icon buttons (Responses, Thống kê)
  - surveys/edit sidebar: fix href="#" → real routes (Responses, Thống kê, Export Excel)

  ┌──────┬──────────────────────────────────────────────────────────────────────────────┐
  │ Task │ File chính                                                                   │
  ├──────┼──────────────────────────────────────────────────────────────────────────────┤
  │ 4.1  │ Http/Controllers/Admin/ResponseController.php (index, export, destroy)       │
  │ 4.2  │ Services/ResponseViewerService.php + ResponseController::show()             │
  │ 4.3  │ Models/SurveyResponse.php (SoftDeletes) + migration deleted_at             │
  │ 4.4  │ Services/SurveyStatsService::totalByDay()                                  │
  │ 4.5  │ Http/Controllers/Admin/StatsController.php + views/stats/index.blade.php   │
  │ 4.6  │ Actions/ExportSurveyResponsesAction.php + route /export (trước /{response})│
  └──────┴──────────────────────────────────────────────────────────────────────────────┘

  Blade quirk gặp phải và fix:
  - ParseError "expecting elseif or else or endif" — Blade regex dùng \B (non-word boundary)
  - @else/@endif phải đứng sau ký tự non-ASCII-word (space, newline), không được dính sát text
  - Rule: luôn đặt @else/@endif trên dòng riêng biệt


---
NGUYÊN TẮC KỸ THUẬT ÁP DỤNG XUYÊN SUỐT MODULE SURVEY

  Kiến trúc:
  - AVSA + CQRS-lite: Actions (write), Services (read), Controllers (điều phối)
  - lorisleiva/laravel-actions: AsAction trait — mỗi business operation là 1 class
  - spatie/laravel-data: DTO với rules() — validation là trách nhiệm của Data class, không phải Controller
  - spatie/laravel-permission: $this->authorize() trong Controller, @can trong Blade

  Hiệu suất:
  - Mọi aggregate query dùng composite index — không full table scan
  - Batch queries: SurveyStatsService chạy ≤5 queries cho toàn bộ stats, không N+1
  - Eager loading: with() trong mọi query có relation để tránh lazy load
  - Async jobs: ShouldQueue cho side effects không cần blocking (last_used_at update)
  - fast-excel + Generator: O(1) memory khi export bất kể số rows

  Bảo mật:
  - SHA-256 hash để lookup token (không reversible, safe khi DB bị dump)
  - AES-256 (Crypt::encryptString) để admin xem lại plaintext — key trong APP_KEY
  - SoftDeletes: không xóa vật lý response, giữ audit trail
  - Activity log: mọi create/update/delete đều ghi log với context
  - Permission guard: mọi route đều có authorize() check

  Code conventions:
  - Route literal (/export) phải đăng ký TRƯỚC wildcard (/{response})
  - Blade @else/@endif luôn trên dòng riêng (tránh \B regex bug)
  - $hidden = ['token', 'token_encrypted'] trên Model — không serialize secret ra API