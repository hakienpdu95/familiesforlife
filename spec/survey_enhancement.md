# Survey Module — Enhancement & Optimization Spec

> Tài liệu đặc tả các tính năng **cần bổ sung, cải tiến và tối ưu** cho `Modules/Survey/`.
> Đây là phần tiếp nối của `survey_feature_inventory.md` (mô tả hiện trạng).
> Dùng tài liệu này để Claude (hoặc dev) đọc và **implement tiếp** — mỗi mục có: bối cảnh, schema/migration, model/action, code mẫu, acceptance criteria.
>
> **Stack**: Laravel 13 (modular — `nwidart/laravel-modules`), Alpine.js, Tabulator, Redis cache, Queue jobs.
> **Cập nhật**: 2026-05-25

---

## Quy ước chung khi implement

1. **Module path**: tất cả file nằm trong `Modules/Survey/` theo cấu trúc hiện có (`app/Http/Controllers`, `app/Models`, `app/Actions`, `app/Jobs`, `app/Services`, `database/migrations`, `resources/views`, `routes`).
2. **Action pattern**: business logic đặt trong `Actions/`, controller chỉ orchestrate. Tuân theo các Action hiện có (`CreateSurveyAction`, `SubmitSurveyAction`, ...).
3. **Migration naming**: tiếp tục đánh số tăng dần từ `000054_*` (số cuối hiện tại là `000053`).
4. **Authorization**: dùng Gate/Policy hiện có (`survey.view`, `survey.update`, `survey.export`, `survey.manage_tokens`, `survey.view_responses`, `survey.delete`). Thêm permission mới nếu cần và khai báo trong Policy.
5. **Cache**: schema cache key hiện là dạng `survey:schema:{slug}`, TTL 30 phút. Mọi thay đổi schema phải purge cache.
6. **Queue**: phân tách queue theo độ ưu tiên (xem mục 9).
7. **Backward compatibility**: không phá vỡ API public hiện có (`/v1/surveys/*`). Thêm field mới phải nullable/optional.

---

## Mục lục

1. [UX làm khảo sát (draft, progress, conditional logic)](#1-ux-làm-khảo-sát)
2. [Field types mở rộng (matrix, ranking, NPS)](#2-field-types-mở-rộng)
3. [Token & Access control (usage limit, dedup, webhook)](#3-token--access-control)
4. [Scoring Engine (versioned config, bulk reprocess, A/B dry-run)](#4-scoring-engine)
5. [Reporting & Analytics (per-field, segment, scheduled export)](#5-reporting--analytics)
6. [Audit & Governance (activity log, GDPR erasure)](#6-audit--governance)
7. [DevX & Ops (OpenAPI docs, feature flags)](#7-devx--ops)
8. [Tối ưu hiệu suất (performance optimization)](#8-tối-ưu-hiệu-suất)
9. [Queue & Job tối ưu](#9-queue--job-tối-ưu)
10. [Thứ tự triển khai đề xuất](#10-thứ-tự-triển-khai-đề-xuất)

---

## 1. UX làm khảo sát

**Ưu tiên**: 🔴 Cao — ảnh hưởng trực tiếp completion rate.

### 1.1 Lưu nháp / Auto-save

**Bối cảnh**: Hiện submit toàn bộ một lần. Nếu người dùng đóng tab giữa chừng (đặc biệt survey dài như AI Readiness) thì mất toàn bộ câu trả lời → drop-off cao.

**Giải pháp 2 lớp**:

**Lớp client (Alpine.js)** — lưu vào `localStorage`:
```js
// Trong Alpine component survey form
const draftKey = `survey_draft_${slug}_${respondentRef || 'anon'}`;

// Auto-save khi answers thay đổi (debounce 800ms)
saveDraft() {
    clearTimeout(this._draftTimer);
    this._draftTimer = setTimeout(() => {
        localStorage.setItem(draftKey, JSON.stringify({
            answers: this.answers,
            currentSection: this.currentSection,
            savedAt: Date.now(),
        }));
    }, 800);
},

// Restore khi init
restoreDraft() {
    const raw = localStorage.getItem(draftKey);
    if (!raw) return;
    const draft = JSON.parse(raw);
    // Chỉ restore nếu < 7 ngày
    if (Date.now() - draft.savedAt < 7 * 86400_000) {
        this.answers = draft.answers;
        this.currentSection = draft.currentSection;
        this.showDraftBanner = true; // "Đã khôi phục bản nháp"
    }
},

// Clear sau khi submit thành công
clearDraft() { localStorage.removeItem(draftKey); }
```

**Lớp server (tùy chọn, cho cross-device)** — endpoint backup nháp:
- Route: `POST /v1/surveys/{slug}/draft` (middleware `ValidateSurveyToken`)
- Controller: `SurveyApiController@saveDraft` → lưu vào bảng `survey_drafts` hoặc Redis với key `survey:draft:{slug}:{respondent_ref}` TTL 7 ngày.
- Route: `GET /v1/surveys/{slug}/draft?ref=xxx` → trả nháp.

**Migration (nếu chọn lưu DB)** — `000054_create_survey_drafts_table`:
```php
Schema::create('survey_drafts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
    $table->string('respondent_ref')->nullable()->index();
    $table->json('answers');
    $table->unsignedInteger('current_section')->default(0);
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
    $table->unique(['survey_id', 'respondent_ref']);
});
```

**Acceptance criteria**:
- Đóng tab giữa chừng → mở lại thấy banner "Khôi phục bản nháp?" và dữ liệu được điền lại.
- Submit thành công → nháp bị xóa cả client lẫn server.
- Nháp tự hết hạn sau 7 ngày.

### 1.2 Progress bar theo section

**Bối cảnh**: Schema đã có `survey_sections` với `sort_order`. Người làm khảo sát không biết còn bao nhiêu phần.

**Giải pháp** — Alpine.js state + render:
```html
<div class="progress-wrap">
    <div class="progress-bar" :style="`width: ${((currentSection + 1) / totalSections) * 100}%`"></div>
    <span x-text="`Phần ${currentSection + 1} / ${totalSections}`"></span>
</div>
```
- `totalSections` lấy từ schema (`sections.length`).
- Tính % theo số câu đã trả lời thay vì số section nếu muốn chính xác hơn: `answeredFields / totalRequiredFields * 100`.

**Acceptance criteria**: Thanh tiến trình cập nhật realtime khi chuyển section / trả lời câu hỏi.

### 1.3 Conditional logic (show/hide field theo điều kiện)

**Bối cảnh**: Hiện `survey_fields.parent_field_id` cho self-ref nhưng **chưa có điều kiện kích hoạt** (hiện field con khi cha = giá trị gì). Cần làm đầy đủ.

**Migration** — `000055_create_survey_field_conditions_table`:
```php
Schema::create('survey_field_conditions', function (Blueprint $table) {
    $table->id();
    // Field sẽ được hiện/ẩn
    $table->foreignId('field_id')->constrained('survey_fields')->cascadeOnDelete();
    // Field điều kiện (field cha)
    $table->foreignId('depends_on_field_id')->constrained('survey_fields')->cascadeOnDelete();
    $table->string('operator');     // '=', '!=', 'in', 'not_in', '>', '<', 'contains', 'answered'
    $table->json('trigger_value');  // giá trị / mảng giá trị kích hoạt
    $table->string('action')->default('show'); // 'show' | 'hide' | 'require'
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
    $table->index('field_id');
});
```

**Model** — `SurveyFieldCondition`:
```php
class SurveyFieldCondition extends Model
{
    protected $casts = ['trigger_value' => 'array'];
    public function field()        { return $this->belongsTo(SurveyField::class, 'field_id'); }
    public function dependsOn()     { return $this->belongsTo(SurveyField::class, 'depends_on_field_id'); }
}
```

**Đưa vào schema** (`BuildSurveySchemaAction`): mỗi field có thêm key `conditions: [{depends_on_field_key, operator, trigger_value, action}]`.

**Đánh giá ở client (Alpine.js)**:
```js
isFieldVisible(field) {
    if (!field.conditions?.length) return true;
    return field.conditions.every(c => {
        const depVal = this.answers[c.depends_on_field_key];
        const ok = this.evalCondition(depVal, c.operator, c.trigger_value);
        return c.action === 'show' ? ok : !ok;
    });
},
evalCondition(val, op, target) {
    switch (op) {
        case '=':       return val == target;
        case '!=':      return val != target;
        case 'in':      return Array.isArray(target) && target.includes(val);
        case 'answered':return val !== null && val !== undefined && val !== '';
        case '>':       return Number(val) > Number(target);
        case '<':       return Number(val) < Number(target);
        case 'contains':return Array.isArray(val) && val.includes(target);
        default:        return true;
    }
}
```

**Quan trọng — validate phía server**: `SubmitSurveyRequest` phải bỏ qua validate `is_required` cho field đang bị ẩn (không thì submit fail). Tính lại visibility trên server từ conditions.

**Acceptance criteria**:
- Builder UI cho phép thêm điều kiện "Hiện field này KHI field X = giá trị Y".
- Field ẩn không được validate required và không lưu answer.
- Logic conditional khớp giữa client và server.

---

## 2. Field types mở rộng

**Ưu tiên**: 🟡 Trung.

`survey_fields.field_type` hiện là enum. Thêm 3 type mới: `matrix`, `ranking`, `nps`.

### 2.1 Matrix / Likert scale

**Mô tả**: Nhiều câu hỏi (rows) × một thang điểm chung (columns). Ví dụ: "Đánh giá mức độ đồng ý" với 5 phát biểu × thang 1–5.

**Migration** — `000056_create_survey_field_rows_table`:
```php
Schema::create('survey_field_rows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('field_id')->constrained('survey_fields')->cascadeOnDelete();
    $table->string('row_key');   // machine key cho row
    $table->string('label');
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
```
- Columns dùng lại `survey_field_options` (mỗi option = 1 cột của thang điểm).
- Answer: với matrix, lưu nhiều `survey_answers` — mỗi row một bản ghi. Thêm cột `row_key` (nullable) vào `survey_answers`:

**Migration** — `000057_add_row_key_to_survey_answers`:
```php
Schema::table('survey_answers', function (Blueprint $table) {
    $table->string('row_key')->nullable()->after('field_id')->index();
});
```

**Scoring**: với matrix, `ScoreRule` áp dụng per-row hoặc tổng. Mở rộng `FeatureExtractor` để xử lý answers có `row_key` (sum hoặc average các row scores).

### 2.2 Ranking (kéo thả thứ tự ưu tiên)

**Mô tả**: Người dùng kéo thả N options theo thứ tự ưu tiên.

**Không cần bảng mới** — lưu vào `survey_answers.answer_text` dạng CSV thứ tự: `"opt_c,opt_a,opt_b"`.

**Client (Alpine.js + SortableJS)**:
```html
<ul x-ref="rankList">
    <template x-for="opt in field.options" :key="opt.option_value">
        <li :data-value="opt.option_value" x-text="opt.label"></li>
    </template>
</ul>
<script>
// init Sortable, onEnd → cập nhật answers[field_key] = thứ tự mới
</script>
```

**Scoring**: thêm `question_scoring_type = 'ranking'` trong `ScoreRule`. Điểm = vị trí ngược (option ở vị trí 1 được điểm cao nhất). Lưu mapping trong `ScoreRuleOption.score`.

### 2.3 NPS (Net Promoter Score)

**Mô tả**: Thang 0–10 + câu hỏi follow-up lý do, phân nhóm Detractor (0–6) / Passive (7–8) / Promoter (9–10).

**Không cần migration** — `field_type = 'nps'`, value lưu vào `answer_numeric`. Follow-up là một field con (conditional, dùng mục 1.3).

**Stats riêng cho NPS** (thêm vào `SurveyStatsService`):
```php
public function npsScore(int $surveyId, int $fieldId): array
{
    $counts = SurveyAnswer::where('field_id', $fieldId)
        ->whereNotNull('answer_numeric')
        ->selectRaw('
            SUM(CASE WHEN answer_numeric >= 9 THEN 1 ELSE 0 END) as promoters,
            SUM(CASE WHEN answer_numeric BETWEEN 7 AND 8 THEN 1 ELSE 0 END) as passives,
            SUM(CASE WHEN answer_numeric <= 6 THEN 1 ELSE 0 END) as detractors,
            COUNT(*) as total
        ')->first();

    $nps = $counts->total > 0
        ? round((($counts->promoters - $counts->detractors) / $counts->total) * 100)
        : 0;
    return compact('nps') + $counts->toArray();
}
```

**Acceptance criteria** (cả 3 type):
- Builder UI cho phép tạo field type mới với cấu hình tương ứng.
- Render đúng ở public form, submit lưu đúng dữ liệu.
- Scoring & stats xử lý đúng từng loại.
- Export bao gồm dữ liệu của các type mới.

---

## 3. Token & Access control

**Ưu tiên**: 🟡 Trung.

### 3.1 Giới hạn usage_limit cho token

**Migration** — `000058_add_usage_limit_to_survey_tokens`:
```php
Schema::table('survey_tokens', function (Blueprint $table) {
    $table->unsignedInteger('usage_limit')->nullable()->after('expires_at'); // null = không giới hạn
    $table->unsignedInteger('usage_count')->default(0)->after('usage_limit');
});
```

**Logic**: trong `ValidateSurveyToken` middleware, sau khi xác thực:
```php
if ($token->usage_limit !== null && $token->usage_count >= $token->usage_limit) {
    abort(429, 'Token đã đạt giới hạn sử dụng.');
}
```
- Tăng `usage_count` khi submit thành công (trong `SubmitSurveyAction`), không phải mỗi request (để GET schema không tốn lượt). Dùng atomic increment: `$token->increment('usage_count')`.

### 3.2 Dedup theo respondent_ref

**Bối cảnh**: Cùng một email/ID có thể submit nhiều lần. Cần option chặn.

**Migration** — `000059_add_dedup_settings_to_surveys`:
```php
Schema::table('surveys', function (Blueprint $table) {
    $table->boolean('allow_multiple_responses')->default(true)->after('status');
});
```

**Logic trong `SubmitSurveyAction`**:
```php
if (! $survey->allow_multiple_responses && $request->filled('respondent_ref')) {
    $exists = SurveyResponse::where('survey_id', $survey->id)
        ->where('respondent_ref', $request->respondent_ref)
        ->where('status', 1) // complete
        ->exists();
    if ($exists) {
        abort(409, 'Bạn đã hoàn thành khảo sát này rồi.');
    }
}
```
- Thêm composite index `(survey_id, respondent_ref)` trên `survey_responses` để query nhanh.

### 3.3 Webhook khi có response mới

**Bối cảnh**: Tích hợp CRM / Slack / Zapier khi có submission.

**Migration** — `000060_create_survey_webhooks_table`:
```php
Schema::create('survey_webhooks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
    $table->string('url');
    $table->string('secret')->nullable();      // để ký HMAC
    $table->json('events')->nullable();         // ['response.created', 'result.calculated']
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Job** — `SurveyWebhookJob`:
```php
class SurveyWebhookJob implements ShouldQueue
{
    public int $tries = 3;
    public array $backoff = [10, 60, 300]; // exponential

    public function __construct(
        public int $webhookId,
        public string $event,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $webhook = SurveyWebhook::find($this->webhookId);
        if (! $webhook?->is_active) return;

        $body = json_encode($this->payload);
        $signature = $webhook->secret
            ? hash_hmac('sha256', $body, $webhook->secret)
            : null;

        Http::withHeaders(array_filter([
            'Content-Type'        => 'application/json',
            'X-Survey-Signature'  => $signature,
            'X-Survey-Event'      => $this->event,
        ]))->timeout(15)->post($webhook->url, $this->payload);
    }
}
```

**Dispatch**: sau `SubmitSurveyAction` (event `response.created`) và sau `CalculateSurveyScoreJob` (event `result.calculated`). Đặt vào queue riêng `webhooks` (xem mục 9).

**Acceptance criteria**:
- Admin UI quản lý webhook (CRUD) trong survey settings.
- Webhook gửi đúng payload, ký HMAC, retry khi fail.
- Token hết lượt → 429; survey không cho lặp → 409.

---

## 4. Scoring Engine

**Ưu tiên**: 🟡 Trung.

### 4.1 Versioned scoring config (snapshot + rollback)

**Bối cảnh**: `survey_results.weight_version` chỉ là integer, không lưu config thực tế tại thời điểm chấm. Khi sửa config rồi muốn rollback hoặc audit "kết quả này được chấm bằng config nào" thì không truy được.

**Migration** — `000061_create_assessment_config_snapshots_table`:
```php
Schema::create('assessment_config_snapshots', function (Blueprint $table) {
    $table->id();
    $table->string('assessment_code')->index();
    $table->unsignedInteger('version');
    $table->json('config');         // snapshot toàn bộ: domains, rules, bands, personas, pain_points, recommendations, roadmap
    $table->string('created_by')->nullable();
    $table->text('change_note')->nullable();
    $table->timestamps();
    $table->unique(['assessment_code', 'version']);
});
```

**Logic**: Mỗi lần `ScoringAdminController@saveConfig` chạy thành công → tạo snapshot mới với `version = max + 1`. Lưu `version` này vào `survey_results.weight_version` khi chấm điểm.

**Rollback**: endpoint `POST /dashboard/surveys/{survey}/scoring/rollback/{version}` → đọc snapshot, ghi đè config hiện tại trong transaction.

### 4.2 Bulk reprocess

**Bối cảnh**: Sau khi sửa scoring config, các result cũ vẫn dùng điểm cũ. Cần chấm lại hàng loạt.

**Route**: `POST /dashboard/surveys/{survey}/scoring/reprocess-all` (auth `survey.update`).

**Controller** → dispatch theo batch (Laravel Bus::batch):
```php
public function reprocessAll(Survey $survey)
{
    $responseIds = SurveyResponse::where('survey_id', $survey->id)
        ->where('status', 1)
        ->pluck('id');

    $jobs = $responseIds->map(fn ($id) =>
        new CalculateSurveyScoreJob($id, force: true)
    )->all();

    $batch = Bus::batch($jobs)
        ->name("reprocess-survey-{$survey->id}")
        ->onQueue('low')           // không chiếm queue submit
        ->allowFailures()
        ->dispatch();

    return response()->json(['batch_id' => $batch->id, 'total' => count($jobs)]);
}
```
- UI hiển thị progress bar dựa trên `Bus::findBatch($batchId)->progress()`.

### 4.3 A/B dry-run (so sánh 2 config)

**Bối cảnh**: Wizard tab ⑦ dry-run với config hiện tại. Cần test cùng bộ answers trên config khác (ví dụ: config nháp vs config đang chạy) để xem điểm thay đổi thế nào.

**Mở rộng** `ScoringAdminController@dryRun`:
- Input thêm `compare_with_version` (optional).
- Chạy `ScoringEngineService::calculate()` 2 lần với 2 config (config request + config snapshot version), trả về diff:
```json
{
  "current":  { "overall_score": 65, "band": "AI_READY", "domain_scores": {...} },
  "compared": { "overall_score": 58, "band": "DIGITAL_FOUNDATION", "domain_scores": {...} },
  "diff":     { "overall_score": -7, "band_changed": true }
}
```

**Acceptance criteria**:
- Mỗi lần save config tạo snapshot version mới.
- Rollback khôi phục đúng config cũ.
- Bulk reprocess chạy nền, có progress, không làm chậm submit.
- Dry-run so sánh hiển thị diff rõ ràng.

---

## 5. Reporting & Analytics

**Ưu tiên**: 🟢 Thấp–Trung.

### 5.1 Per-field breakdown

**Mở rộng** `SurveyStatsService` — thêm method trả phân phối answers từng field:
```php
public function fieldBreakdown(int $surveyId, int $fieldId): array
{
    $field = SurveyField::with('options')->findOrFail($fieldId);

    if (in_array($field->field_type, ['radio', 'select', 'checkbox', 'yes_no'])) {
        // Đếm theo option
        return SurveyAnswer::where('field_id', $fieldId)
            ->whereNotNull('answer_choice_id')
            ->selectRaw('answer_choice_id, COUNT(*) as count')
            ->groupBy('answer_choice_id')
            ->get()
            ->map(fn ($r) => [
                'label' => $field->options->firstWhere('id', $r->answer_choice_id)?->label,
                'count' => $r->count,
            ])->all();
    }

    if (in_array($field->field_type, ['number', 'scale', 'nps'])) {
        // Phân phối numeric + thống kê
        return SurveyAnswer::where('field_id', $fieldId)
            ->whereNotNull('answer_numeric')
            ->selectRaw('answer_numeric as value, COUNT(*) as count')
            ->groupBy('answer_numeric')->orderBy('answer_numeric')
            ->get()->toArray();
    }

    return []; // text → không chart, hiển thị danh sách / word cloud
}
```
- View `stats/index.blade.php`: render bar/pie chart per-field (Chart.js đã có sẵn trong Tabulator stack, hoặc thêm).

### 5.2 Segment filter / so sánh cohort

**Mục tiêu**: Lọc stats theo `band_code`, `token_id`, khoảng thời gian → so sánh các nhóm.

**Endpoint**: mở rộng `StatsController@index` nhận query params `?band=&token=&from=&to=`. Filter ở tầng query trước khi aggregate.

**UI**: dropdown chọn segment, render 2 dataset cạnh nhau để so sánh.

### 5.3 Scheduled export (weekly digest)

**Job** — `SurveyScheduledExportJob`:
- Chạy theo lịch (Laravel scheduler trong `routes/console.php` hoặc module's `Console/Kernel`).
- Export responses tuần qua → Excel → gửi email admin kèm summary (số submission, NPS, band distribution).

**Migration** — `000062_add_export_schedule_to_surveys` (nếu cấu hình per-survey):
```php
Schema::table('surveys', function (Blueprint $table) {
    $table->string('export_schedule')->nullable();      // 'daily' | 'weekly' | null
    $table->string('export_recipients')->nullable();    // CSV emails
});
```

**Scheduler**:
```php
Schedule::command('survey:scheduled-export')->weeklyOn(1, '08:00'); // Thứ 2 8h
```

**Acceptance criteria**:
- Mỗi field choice/numeric có chart phân phối.
- Lọc segment trả về stats đúng cho từng nhóm.
- Scheduled export gửi email đúng lịch với file đính kèm.

---

## 6. Audit & Governance

**Ưu tiên**: 🟡 Trung (cao nếu có yêu cầu compliance/GDPR).

### 6.1 Activity log cho scoring config

**Bối cảnh**: Không truy được ai sửa scoring config lúc nào.

**Cách 1 — dùng package** `spatie/laravel-activitylog` (khuyến nghị):
```bash
composer require spatie/laravel-activitylog
```
- Thêm trait `LogsActivity` vào `Assessment`, `ScoreRule`, `ScoreBand`, `Persona`, `RecommendationRule`, `PainPointRule`.
- Config `getActivitylogOptions()` để log `created/updated/deleted` với attributes thay đổi.

**Cách 2 — bảng tự build** `000063_create_scoring_audit_logs_table`:
```php
Schema::create('scoring_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('assessment_code')->index();
    $table->string('action');        // 'config_saved', 'rollback', 'reprocess'
    $table->string('actor')->nullable();
    $table->json('changes')->nullable();
    $table->json('snapshot_before')->nullable();
    $table->json('snapshot_after')->nullable();
    $table->timestamps();
});
```
- Ghi log trong `ScoringAdminController@saveConfig` (kết hợp với snapshot ở mục 4.1).

### 6.2 GDPR erasure (soft-delete + self-service)

**Bối cảnh**: `ResponseController@destroy` xóa cứng. Cần soft-delete + cho respondent tự xóa data.

**Migration** — `000064_add_soft_deletes_to_survey_responses`:
```php
Schema::table('survey_responses', function (Blueprint $table) {
    $table->softDeletes();
});
```
- Thêm `use SoftDeletes;` vào model `SurveyResponse`.

**Self-service erasure endpoint**:
- Route: `DELETE /v1/surveys/{slug}/my-data?ref=xxx` (middleware `ValidateSurveyToken`).
- Xóa (soft) tất cả response + answers + result của `respondent_ref` đó.
- Job dọn dẹp cứng sau N ngày: `PurgeDeletedResponsesJob` chạy hàng ngày, xóa vĩnh viễn record `deleted_at < now()->subDays(30)`.

**Lưu ý**: result/answer cascade phải xóa theo. Cân nhắc anonymize thay vì xóa nếu cần giữ data thống kê (set `respondent_ref = null`, `respondent_ip = null`).

**Acceptance criteria**:
- Mọi thay đổi scoring config được ghi log với actor + diff.
- Response xóa mềm, vẫn ẩn khỏi list/stats.
- Respondent tự xóa được data của mình qua API.
- Job dọn record cũ chạy đúng lịch.

---

## 7. DevX & Ops

**Ưu tiên**: 🟢 Thấp.

### 7.1 OpenAPI / JSON schema docs tự sinh

- Dùng `dedoc/scramble` (auto-gen OpenAPI từ Laravel routes) hoặc viết spec thủ công cho `/v1/surveys/*`.
- Public docs tại `/docs/api/survey` cho bên tích hợp.
- Endpoint `GET /v1/surveys/{slug}/schema` đã trả JSON schema — bổ sung JSON Schema Draft chuẩn để client validate trước khi submit.

### 7.2 Feature flags để A/B test scoring config

- Dùng `laravel/pennant`.
- Flag ví dụ: `scoring-v2` → một % responses dùng config version mới, so sánh band distribution giữa 2 nhóm.
- Kết hợp với snapshot (4.1) và feedback loop (`scoring_feedback`) hiện có để đo predicted vs actual.

---

## 8. Tối ưu hiệu suất

**Ưu tiên**: 🔴 Cao — áp dụng xuyên suốt.

### 8.1 N+1 queries

- **Schema build** (`BuildSurveySchemaAction`): eager-load `sections.fields.options` trong 1 query tree. Hiện đã cache 30 phút — đảm bảo build dùng `with(['sections.fields.options'])`, tránh lazy load.
- **Response viewer** (`ResponseController@show`): eager-load `answers.field`, `answers.choice` để tránh N+1 khi group by section.
- **Stats / Summary**: dùng aggregate query (`selectRaw` + `groupBy`) thay vì load toàn bộ rồi tính trong PHP.

### 8.2 Indexing

Kiểm tra và thêm index cho các query nóng:
```php
// survey_answers — query nhiều theo response + field
$table->index(['response_id', 'field_id']);
// survey_responses — filter list/stats
$table->index(['survey_id', 'status', 'submitted_at']);
$table->index(['survey_id', 'respondent_ref']);
// result_domain_scores — join khi summary
$table->index(['result_id', 'domain_code']);
// submission_behavior_logs — query theo response
$table->index(['response_id', 'occurred_at']);
```

### 8.3 Caching

- **Schema cache**: đã có (Redis TTL 30 phút). **Bổ sung**: purge cache khi `ActivateSurveyAction` chạy (hiện chỉ purge khi sửa section/field — activate đổi trạng thái cũng cần purge).
- **Scoring config cache**: cache `ScoringConfig` DTO theo `assessment_code` (config ít đổi, đọc nhiều khi chấm điểm hàng loạt). Purge khi `saveConfig`.
- **Stats cache**: cache kết quả `StatsController@index` ngắn hạn (TTL 5 phút) cho survey có nhiều response — dùng tag để purge khi có submission mới.

### 8.4 Batch insert trong ResultPersister

`ResultPersister` đã batch insert (tốt). Đảm bảo:
- Dùng `DB::table()->insert($rows)` cho child tables thay vì `Model::create()` trong loop.
- Wrap toàn bộ trong 1 transaction (đã có).
- Với bulk reprocess hàng nghìn response → cân nhắc `insertOrIgnore` + chunk.

### 8.5 Export streaming

- `ExportSurveyResponsesAction` đã stream ≤10k và queue >10k (tốt).
- Đảm bảo dùng `LazyCollection` + `cursor()` để không load hết vào RAM.
- File export cleanup: job xóa file `storage/app/exports/*.xlsx` cũ hơn TTL Redis (1h) để tránh đầy disk.

### 8.6 Behavior log volume

`submission_behavior_logs` có thể phình rất nhanh (mỗi response sinh nhiều event). Đề xuất:
- Partition theo tháng hoặc archive định kỳ sang bảng lạnh / S3.
- Job `ArchiveBehaviorLogsJob` chạy hàng tháng, nén log cũ > 90 ngày.
- Hoặc lưu aggregate (time_spent per section) thay vì raw events nếu không cần chi tiết.

---

## 9. Queue & Job tối ưu

**Ưu tiên**: 🔴 Cao.

**Vấn đề hiện tại**: `CalculateSurveyScoreJob`, `ExportSurveyResponsesJob`, `UpdateTokenLastUsedJob` đều ở queue `default` → export nặng có thể chặn scoring, làm respondent chờ kết quả lâu.

**Phân tách queue**:

| Job | Queue đề xuất | Lý do |
|-----|---------------|-------|
| `CalculateSurveyScoreJob` | `high` | Respondent đang chờ kết quả |
| `SurveyWebhookJob` | `webhooks` | Cô lập lỗi mạng bên thứ 3 |
| `UpdateTokenLastUsedJob` | `low` | Không khẩn cấp |
| `ExportSurveyResponsesJob` | `low` | Nặng, không cần realtime |
| Bulk reprocess batch | `low` | Chạy nền |
| `PurgeDeletedResponsesJob` | `low` | Bảo trì |
| `ArchiveBehaviorLogsJob` | `low` | Bảo trì |

**Config worker** (`supervisor` / `horizon`):
```php
// Nếu dùng Laravel Horizon
'production' => [
    'high'     => ['connection' => 'redis', 'queue' => ['high'], 'processes' => 3],
    'default'  => ['connection' => 'redis', 'queue' => ['default'], 'processes' => 2],
    'webhooks' => ['connection' => 'redis', 'queue' => ['webhooks'], 'processes' => 2],
    'low'      => ['connection' => 'redis', 'queue' => ['low'], 'processes' => 1],
],
```

**Cải tiến job**:
- `CalculateSurveyScoreJob`: thêm `ShouldBeUnique` theo `responseId` để tránh chấm trùng khi double-submit.
- Tất cả webhook/export job: dùng exponential backoff `[10, 60, 300]`.
- Thêm `failed()` method log lỗi vào audit/Sentry.

---

## 10. Thứ tự triển khai đề xuất

Theo thứ tự impact / effort (làm trước những thứ ROI cao):

| # | Hạng mục | Ưu tiên | Effort | Ghi chú |
|---|----------|---------|--------|---------|
| 1 | Queue phân tách (mục 9) | 🔴 Cao | Thấp | Quick win, cải thiện ngay độ trễ |
| 2 | Indexing + N+1 fix (8.1–8.2) | 🔴 Cao | Thấp | Quick win hiệu suất |
| 3 | Auto-save nháp (1.1) | 🔴 Cao | Trung | Tăng completion rate |
| 4 | Progress bar (1.2) | 🔴 Cao | Thấp | UX quick win |
| 5 | Conditional logic (1.3) | 🔴 Cao | Cao | Cần đồng bộ client/server |
| 6 | Schema/config cache purge (8.3) | 🔴 Cao | Thấp | Tránh bug cache stale |
| 7 | Dedup + usage_limit token (3.1–3.2) | 🟡 Trung | Thấp | |
| 8 | Versioned config + snapshot (4.1) | 🟡 Trung | Trung | Nền tảng cho audit + rollback |
| 9 | Bulk reprocess (4.2) | 🟡 Trung | Trung | Cần sau khi có versioning |
| 10 | Activity log (6.1) | 🟡 Trung | Thấp | Dùng package |
| 11 | Webhook (3.3) | 🟡 Trung | Trung | |
| 12 | GDPR soft-delete (6.2) | 🟡 Trung | Trung | Nếu có yêu cầu compliance |
| 13 | Field types mới (mục 2) | 🟡 Trung | Cao | Matrix phức tạp nhất |
| 14 | Per-field stats + charts (5.1) | 🟢 Thấp | Trung | |
| 15 | A/B dry-run (4.3) | 🟢 Thấp | Trung | |
| 16 | Segment filter (5.2) | 🟢 Thấp | Trung | |
| 17 | Scheduled export (5.3) | 🟢 Thấp | Thấp | |
| 18 | Behavior log archive (8.6) | 🟢 Thấp | Trung | Khi data lớn |
| 19 | OpenAPI docs (7.1) | 🟢 Thấp | Thấp | |
| 20 | Feature flags (7.2) | 🟢 Thấp | Trung | |

**Migration numbering** dùng cho spec này: `000054` → `000064` (xem từng mục). Điều chỉnh số thực tế theo migration cuối cùng trong repo.

---

## Tổng kết

Module Survey hiện tại đã hoàn chỉnh ở 4 lớp (Builder / Submission / Scoring / Reporting). Spec này bổ sung **3 trục cải tiến**:

1. **Trải nghiệm & độ tin cậy** (auto-save, progress, conditional, dedup) → tăng completion rate.
2. **Vận hành & governance** (versioned config, audit, GDPR, webhook) → sẵn sàng production/compliance.
3. **Hiệu suất** (queue split, indexing, caching, archive) → scale tốt khi data lớn.

Khi implement, ưu tiên các **quick win hiệu suất** (mục 1–6 trong bảng 10) trước vì effort thấp mà tác động lớn.