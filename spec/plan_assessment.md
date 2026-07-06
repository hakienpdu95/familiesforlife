# Kế hoạch Consolidation: Assessment làm trung tâm chấm điểm

> **Mục tiêu:** Một Assessment module duy nhất chứa toàn bộ engine scoring + config.
> Mọi module trong hệ thống (Survey, Lead, Customer, …) đều là **consumer** — chỉ implement
> interface và gọi Assessment. Không có logic scoring nào được đặt ngoài Assessment module.

---

## Nguyên tắc kiến trúc

```
┌─────────────────────────────────────────────────────────┐
│                   Assessment Module                     │
│  Engine · Config · Results · Events · Snapshots         │
│  (core — không import bất kỳ domain module nào)         │
└──────────┬──────────────────────────────────────────────┘
           │  ScoringSubjectInterface  +  AssessmentCompleted event
    ┌──────┴──────┬──────────────┬──────────────┐
    ▼             ▼              ▼              ▼
 Survey        Lead          Customer        ... (future)
 (consumer)  (consumer)     (consumer)      (consumer)
```

**Luật bất biến:**
- Assessment module **không được** import model của bất kỳ domain module nào
- Domain module muốn tham gia scoring → implement `ScoringSubjectInterface`
- Domain module muốn phản ứng sau scoring → lắng nghe `AssessmentCompleted` event
- Config scoring của mọi entity → thực hiện tại `/dashboard/assessments/{code}/config`
- Một Assessment record (`assessment_code`) có thể được dùng cho nhiều entity khác nhau

---

## ScoringSubjectInterface — Contract cho mọi entity có thể chấm điểm

```php
// Modules/Assessment/app/Contracts/ScoringSubjectInterface.php
interface ScoringSubjectInterface
{
    public function getScoringSubjectId(): int;
    public function getScoringSubjectType(): string;  // FQCN của model
    public function getAssessmentCode(): string;       // rỗng = bỏ qua
    public function getScoringAnswers(): array;        // field_key → ['type', 'value']
}
```

**Các entity đã implement:**
| Entity | Module | Nguồn assessment_code |
|---|---|---|
| `SurveyResponse` | Survey | `surveys.assessment_code` (linked khi enable scoring) |
| `Lead` | Lead | `organizations.lead_assessment_code` (per-org config) |

**Pattern cho entity mới (ví dụ Customer):**
1. Model implement `ScoringSubjectInterface`
2. Tạo `EnsureAssessmentLinkedAction` trong module đó
3. Tạo Listener trigger Assessment khi entity thay đổi
4. Đăng ký listener trong module's `EventServiceProvider`

---

## Luồng dữ liệu đầy đủ

```
[Entity created/updated]
    │
    ▼
[Module Listener] (vd: TriggerLeadAssessment, SubmitSurveyAction)
    │
    ▼
RunAssessmentAction::handle(ScoringSubjectInterface $subject)
    │  Modules/Assessment/app/Actions/
    ▼
ScoringEngineService::calculate()
    │  Modules/Assessment/app/Engine/
    │  10 giai đoạn: Config → Answers → Features → Weights →
    │  Aggregation → Classification → PainPoints →
    │  Recommendations → Roadmap → Persist
    ▼
assessment_results (polymorphic: subject_type + subject_id)
    │
    ▼
AssessmentCompleted event
    │
    ├── Survey\Listeners\DispatchSurveyWebhookOnAssessmentCompleted
    ├── WorkflowAutomation\Listeners\FireWorkflowOnAssessmentCompleted
    └── [future: Customer, Lead result handlers...]
```

---

## Trạng thái hiện tại (trước consolidation)

### Dead code cần xóa
| Vị trí | LOC | Lý do |
|---|---|---|
| `Modules/Survey/app/Scoring/*` | 1,703 | Engine duplicate — không có caller |
| `Modules/Survey/app/Actions/CreateConfigSnapshotAction.php` | ~120 | Duplicate của Assessment action |
| `Modules/Survey/app/Actions/RestoreConfigFromSnapshotAction.php` | ~130 | Duplicate của Assessment action |

### Duplicate models (cùng bảng DB, khác namespace)
Survey namespace có bản sao của tất cả Assessment config models:
`Assessment`, `AssessmentDomain`, `ScoreRule`, `ScoreRuleOption`, `ScoreRuleNumericRange`,
`ScoreBand`, `PassFailConfig`, `Persona`, `PersonaCondition`, `PainPointRule`,
`RecommendationRule`, `RoadmapPhase`, `RoadmapMilestone`, `MaturityLevel`, `ScoringFeedback`,
`AssessmentConfigSnapshot` + toàn bộ `Snapshot*` models

### Duplicate controller
`Survey\Http\Controllers\ScoringAdminController` (694 LOC) ≈ `Assessment\Http\Controllers\AssessmentConfigController` (697 LOC)
→ Cùng logic, khác namespace, cùng ghi xuống cùng bảng DB

### Result models — đặc biệt
`Survey\Models\SurveyResult` dùng `$table = 'survey_results'` — sau migration `000063`,
bảng này đã được thay bằng **VIEW** trỏ về `assessment_results`. Hoạt động được nhưng là
tech debt cần dọn theo lộ trình.

---

## Phase 1 — Xóa dead code engine

**Mục tiêu:** Loại bỏ 30 files / ~1,950 LOC không được gọi trong luồng thực tế.

**Căn cứ:** `CalculateSurveyScoreAction` đã delegate hoàn toàn sang `RunAssessmentAction`.
`Survey/app/Scoring/*` không có caller nào trong codebase.

**Việc cần làm:**
```
XÓA Modules/Survey/app/Scoring/               (toàn bộ directory, 30 files)
XÓA Modules/Survey/app/Actions/CreateConfigSnapshotAction.php
XÓA Modules/Survey/app/Actions/RestoreConfigFromSnapshotAction.php
```

**Verify trước khi xóa:**
```bash
grep -rn "Survey\\Actions\\CreateConfigSnapshotAction" Modules/
grep -rn "Survey\\Actions\\RestoreConfigFromSnapshotAction" Modules/
grep -rn "use Modules\\Survey\\Scoring" Modules/
```
Kết quả phải là rỗng.

**Rủi ro:** Không có — dead code hoàn toàn, không ảnh hưởng runtime.

---

## Phase 2 — EnsureAssessmentLinkedAction (per-module bridge)

**Vấn đề cần giải quyết:**
Assessment module không biết Survey hay Lead tồn tại. Nhưng khi user lần đầu click
"Cấu hình Scoring" cho một Survey chưa được link với Assessment nào, cần tự động:
1. Tạo Assessment record với code derive từ entity
2. Ghi code đó vào entity
3. Redirect về Assessment config UI

**Vị trí đặt:** Trong **module của từng entity** — không phải trong Assessment module.
(Assessment module không được import Survey hay Lead model.)

### Survey: EnsureAssessmentLinkedAction

**Đặt tại:** `Modules/Survey/app/Actions/EnsureAssessmentLinkedAction.php`

```php
// Logic:
public function handle(Survey $survey): string
{
    if ($survey->assessment_code) {
        return $survey->assessment_code;  // đã có → no-op
    }

    // Derive code từ slug — PHẢI dùng đúng quy ước này để backward compatible
    $code = str_replace('-', '_', $survey->slug);

    // Tạo Assessment record nếu chưa tồn tại
    Assessment::firstOrCreate(
        ['assessment_code' => $code],
        [
            'name'                => $survey->title,
            'aggregation_model'   => 'weighted_domain',  // default
            'classification_type' => 'score_band',        // default
            'has_scoring'         => true,
            'is_active'           => true,
        ]
    );

    // Link survey → assessment
    $survey->update(['assessment_code' => $code]);

    return $code;
}
```

**Lưu ý quan trọng:**
- `deriveCode()` dùng `str_replace('-', '_', slug)` — **không dùng** random hash như
  `AssessmentController::generateUniqueCode()`. Các survey đã có data dùng code này,
  thay đổi sẽ mất link.
- Import `Assessment` model từ **Assessment namespace**:
  `use Modules\Assessment\Models\Assessment;`

### Lead: pattern tương tự (nếu cần)

Lead dùng `organizations.lead_assessment_code` — code này được admin set thủ công,
không cần auto-derive. Không cần `EnsureAssessmentLinkedAction` cho Lead.

### Pattern cho entity mới (Customer, v.v.)

```php
// Modules/{Module}/app/Actions/EnsureAssessmentLinkedAction.php
// Derive code từ entity slug/id, firstOrCreate Assessment, update entity
// Đặt trong module của entity — không phải Assessment module
```

---

## Phase 3 — Redirect Survey scoring UI → Assessment

**Mục tiêu:** Xóa `ScoringAdminController` (694 LOC) và Survey's scoring wizard.
Mọi config scoring thực hiện tại Assessment config UI.

### Route name đúng

Assessment config route đầy đủ tên là `assessments.config.index` (không phải `assessments.config`):

```php
// Assessment/routes/web.php
Route::prefix('/{code}/config')->name('config.')->group(function () {
    Route::get('/', ...)->name('index');  // → assessments.config.index
    ...
});
```

### Thay thế scoring routes trong Survey

```php
// TRƯỚC — Survey/routes/web.php (8 routes, tất cả → ScoringAdminController):
Route::prefix('/{survey}/scoring')->name('scoring.')->group(function () {
    Route::get('/',                  [ScoringAdminController::class, 'index']);
    Route::get('/config',            [ScoringAdminController::class, 'getConfig']);
    Route::put('/config',            [ScoringAdminController::class, 'saveConfig']);
    Route::get('/fields',            [ScoringAdminController::class, 'getFields']);
    Route::get('/flags',             [ScoringAdminController::class, 'getFlags']);
    Route::post('/validate',         [ScoringAdminController::class, 'validateConfig']);
    Route::get('/snapshots',         [ScoringAdminController::class, 'getSnapshots']);
    Route::post('/rollback/{ver}',   [ScoringAdminController::class, 'rollbackConfig']);
    Route::post('/reprocess-all',    [ScoringAdminController::class, 'reprocessAll']);
    Route::get('/batch/{batchId}',   [ScoringAdminController::class, 'getBatchStatus']);
});

// SAU — thay bằng 1 redirect route:
Route::get('/{survey}/scoring', [SurveyScoringRedirectController::class, 'redirect'])
     ->name('scoring.index');
```

### SurveyScoringRedirectController (mới, nhỏ)

```php
// Modules/Survey/app/Http/Controllers/SurveyScoringRedirectController.php
public function redirect(Survey $survey)
{
    $this->authorize('survey.update');
    $code = EnsureAssessmentLinkedAction::run($survey);
    return redirect()->route('assessments.config.index', $code);
}
```

### Việc cần làm — file by file

```
XÓA  Modules/Survey/app/Http/Controllers/ScoringAdminController.php
XÓA  Modules/Survey/resources/views/scoring/          (toàn bộ directory — wizard UI deprecated)
TẠO  Modules/Survey/app/Http/Controllers/SurveyScoringRedirectController.php
SỬA  Modules/Survey/routes/web.php                    (thay 8 routes → 1 redirect route)
SỬA  Modules/Survey/resources/views/surveys/edit.blade.php:149
     route('backend.surveys.scoring.index') → route('backend.surveys.scoring', $survey)
     (hoặc giữ tên route cũ scoring.index nếu muốn backward compat)
```

**Lưu ý:** Assessment config UI (`/dashboard/assessments/{code}/config`) đã có đầy đủ
các tính năng tương đương: getFields, getFlags, validate, snapshots, rollback, reprocess,
batch-status. Không cần port JS wizard của Survey.

---

## Phase 4 — Unify config models (xóa bản sao Survey namespace)

**Xác nhận:** Tất cả Survey config models dùng **cùng bảng vật lý** với Assessment models.
Chúng là alias namespace — xóa Survey copies là an toàn.

### Bảng mapping

| Survey model (xóa) | Assessment model (giữ) | Bảng DB |
|---|---|---|
| `Survey\Models\Assessment` | `Assessment\Models\Assessment` | `assessments` |
| `Survey\Models\AssessmentDomain` | `Assessment\Models\AssessmentDomain` | `assessment_domains` |
| `Survey\Models\ScoreRule` | `Assessment\Models\ScoreRule` | `score_rules` |
| `Survey\Models\ScoreRuleOption` | `Assessment\Models\ScoreRuleOption` | `score_rule_options` |
| `Survey\Models\ScoreRuleNumericRange` | `Assessment\Models\ScoreRuleNumericRange` | `score_rule_numeric_ranges` |
| `Survey\Models\ScoreBand` | `Assessment\Models\ScoreBand` | `score_bands` |
| `Survey\Models\PassFailConfig` | `Assessment\Models\PassFailConfig` | `pass_fail_configs` |
| `Survey\Models\Persona` | `Assessment\Models\Persona` | `personas` |
| `Survey\Models\PersonaCondition` | `Assessment\Models\PersonaCondition` | `persona_conditions` |
| `Survey\Models\PainPointRule` | `Assessment\Models\PainPointRule` | `pain_point_rules` |
| `Survey\Models\RecommendationRule` | `Assessment\Models\RecommendationRule` | `recommendation_rules` |
| `Survey\Models\RoadmapPhase` | `Assessment\Models\RoadmapPhase` | `roadmap_phases` |
| `Survey\Models\RoadmapMilestone` | `Assessment\Models\RoadmapMilestone` | `roadmap_milestones` |
| `Survey\Models\MaturityLevel` | `Assessment\Models\MaturityLevel` | `maturity_levels` |
| `Survey\Models\ScoringFeedback` | `Assessment\Models\ScoringFeedback` | `scoring_feedback` |
| `Survey\Models\AssessmentConfigSnapshot` | `Assessment\Models\AssessmentConfigSnapshot` | `assessment_config_snapshots` |
| `Survey\Models\Snapshot*` (12 files) | `Assessment\Models\Snapshot*` | `snapshot_*` |

### Consumer quan trọng cần update TRƯỚC khi xóa

**`SurveyResultController.php` (296 LOC)** — file này import nhiều Survey config models nhất:

```php
// TRƯỚC (Survey namespace):
use Modules\Survey\Models\AssessmentDomain;
use Modules\Survey\Models\MaturityLevel;
use Modules\Survey\Models\PainPointRule;
use Modules\Survey\Models\RecommendationRule;
use Modules\Survey\Models\ScoreBand;
use Modules\Survey\Models\ScoringFeedback;

// SAU (Assessment namespace):
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\PainPointRule;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoringFeedback;
```

**Scan đầy đủ trước khi xóa:**
```bash
grep -rn "use Modules\\Survey\\Models\\Assessment\b" Modules/ --include="*.php"
grep -rn "use Modules\\Survey\\Models\\ScoreRule" Modules/ --include="*.php"
grep -rn "use Modules\\Survey\\Models\\ScoreBand" Modules/ --include="*.php"
# ... repeat cho từng model
```

**Thứ tự thực hiện Phase 4:**
1. Update `SurveyResultController` imports → Assessment namespace
2. Scan và update tất cả file còn lại
3. Xóa model files trong Survey namespace
4. Chạy `php artisan test` để verify

---

## Phase 5a — Result models: giữ VIEW-backed SurveyResult (ngắn hạn)

**Quyết định:** Giữ `Survey\Models\SurveyResult` (trỏ VIEW `survey_results`) trong giai đoạn này.

**Lý do:** `SurveyResultController` dùng `SurveyResult` với nhiều scope phức tạp
(`forResponse()`, `whereHas()`, aggregate queries). VIEW hoạt động đúng — đây là tech debt
có kiểm soát, không phải bug.

**Điều kiện để giữ:**
- VIEW `survey_results` phải được tạo trong migration `000063` (đã confirm ✓)
- Các scope query vẫn hoạt động đúng qua VIEW (cần test ✓)

**Việc cần làm Phase 5a:**
```
GIỮ  Modules/Survey/app/Models/SurveyResult.php       (VIEW-backed, hoạt động)
GIỮ  Modules/Survey/app/Models/ResultDomainScore.php
GIỮ  Modules/Survey/app/Models/ResultSignalFlag.php
GIỮ  Modules/Survey/app/Models/ResultPainPoint.php
GIỮ  Modules/Survey/app/Models/ResultRecommendation.php
GIỮ  Modules/Survey/app/Models/ResultRoadmapPhase.php
GIỮ  Modules/Survey/app/Models/ResultClassification.php
GIỮ  Modules/Survey/app/Models/ResultQuestionScore.php
```

---

## Phase 5b — Refactor SurveyResultController → AssessmentResult (dài hạn)

> **Đây là ticket riêng, không block Phase 1–5a.**

**Mục tiêu:** Xóa phụ thuộc vào VIEW, dùng trực tiếp `AssessmentResult` model.

**Thay đổi query pattern:**

```php
// TRƯỚC (SurveyResult — VIEW-backed):
$result = SurveyResult::forResponse($response->id)->first();

// SAU (AssessmentResult — polymorphic):
$result = AssessmentResult::where('subject_type', SurveyResponse::class)
                          ->where('subject_id', $response->id)
                          ->first();
// hoặc nếu thêm scope vào AssessmentResult:
$result = AssessmentResult::forSubject($response)->first();
```

**Việc cần làm Phase 5b:**
1. Thêm `scopeForSubject()` vào `Assessment\Models\AssessmentResult`
2. Refactor toàn bộ `SurveyResultController` (296 LOC) sang `AssessmentResult`
3. Xóa `Survey\Models\SurveyResult` và toàn bộ `Survey\Models\Result*.php`
4. Xóa VIEW `survey_results` (viết migration drop view)

---

## Phase 6 — Giữ nguyên behavior đặc thù từng module

### Survey — giữ các file sau

| File | Lý do |
|---|---|
| `Actions/CalculateSurveyScoreAction.php` | Entry point từ survey submission flow — đơn giản hóa thêm (bỏ `WebhookDispatcher` inject vì webhook đã handle qua event) nhưng giữ |
| `Jobs/CalculateSurveyScoreJob.php` | Queue job wrapper — giữ |
| `Listeners/DispatchSurveyWebhookOnAssessmentCompleted.php` | Survey-specific webhook dispatch — đúng chỗ |
| `Providers/EventServiceProvider.php` | Đăng ký webhook listener — giữ |
| `Observers/SurveyObserver.php` | Token deactivation khi soft-delete — không liên quan scoring |

### WorkflowAutomation — không thay đổi

```php
// WorkflowAutomation/Providers/EventServiceProvider.php
AssessmentCompleted::class => [
    FireWorkflowOnAssessmentCompleted::class,  // trigger workflow khi có kết quả
]
```
Đã đúng vị trí. Document để tránh tưởng bỏ sót.

### Lead — không thay đổi

```php
// Lead/Providers/EventServiceProvider.php
LeadCreated::class => [TriggerLeadAssessment::class],
LeadUpdated::class => [TriggerLeadAssessment::class],
```

---

## Phase 7 — Verify và quyết định Lead integration

### Đã confirm hoạt động đúng ✅

- `Lead` implements `ScoringSubjectInterface` ✓
- `getAssessmentCode()` trả về `organization->lead_assessment_code` ✓
- `getScoringAnswers()` map đủ các field (has_phone, expected_value, stage_probability, ...) ✓
- `TriggerLeadAssessment` listener đăng ký đúng trong `EventServiceProvider` ✓

### Cần quyết định: throttle LeadUpdated

**Vấn đề:** `TriggerLeadAssessment` fire trên cả `LeadUpdated` — mỗi lần gán owner,
đổi stage, cập nhật note đều trigger Assessment job. Với lead volume lớn, đây là
nguồn tải queue đáng kể.

**Các lựa chọn:**

| Lựa chọn | Ưu | Nhược |
|---|---|---|
| A. Giữ nguyên (fire mọi update) | Score luôn mới nhất | Queue tải cao với lead active |
| B. Chỉ fire khi field scoring thay đổi | Tiết kiệm queue | Phải maintain danh sách field |
| C. Rate-limit: không re-score nếu score < 24h | Đơn giản | Score có thể stale |

**Cần confirm** lựa chọn trước khi go-live. Đây là **design decision**, không phải bug.

---

## Phase 8 — Pattern tích hợp entity mới (Customer, v.v.)

Khi muốn chấm điểm một entity mới (ví dụ `Customer`):

### Checklist tích hợp

**Bước 1 — Model implement ScoringSubjectInterface:**
```php
class Customer extends Model implements ScoringSubjectInterface
{
    public function getScoringSubjectId(): int   { return $this->id; }
    public function getScoringSubjectType(): string { return static::class; }
    public function getAssessmentCode(): string  { return $this->organization?->customer_assessment_code ?? ''; }
    public function getScoringAnswers(): array   { return [...]; }
}
```

**Bước 2 — Thêm assessment_code config vào organizations:**
```bash
php artisan make:migration add_customer_assessment_code_to_organizations_table
# thêm: $table->string('customer_assessment_code', 64)->nullable();
```

**Bước 3 — Tạo EnsureAssessmentLinkedAction trong Customer module:**
```php
// Modules/Customer/app/Actions/EnsureAssessmentLinkedAction.php
// Derive code + Assessment::firstOrCreate + entity.update
```

**Bước 4 — Tạo Trigger Listener:**
```php
// Modules/Customer/app/Listeners/TriggerCustomerAssessment.php
public function handle(CustomerCreated|CustomerUpdated $event): void
{
    RunAssessmentAction::dispatch($event->customer);
}
```

**Bước 5 — Đăng ký listener trong EventServiceProvider của module.**

**Bước 6 — Tạo Assessment record qua UI `/dashboard/assessments/create`**,
gán code vào `organizations.customer_assessment_code`.

**Bước 7 — Cấu hình scoring tại `/dashboard/assessments/{code}/config`.**

---

## Tổng kết thay đổi

| Hạng mục | Trước | Sau |
|---|---|---|
| Scoring engine | 2 bản (3,419 LOC) | 1 bản trong Assessment (1,716 LOC) |
| Config models | 2 namespace, cùng bảng | 1 namespace (Assessment) |
| Config UI controller | ScoringAdminController (694 LOC) | AssessmentConfigController (697 LOC) |
| Config UI routes | `/survey/{id}/scoring/*` (8 routes) | `/dashboard/assessments/{code}/config` |
| Survey scoring wizard view | `views/scoring/` (SPA riêng) | Xóa — dùng Assessment UI |
| Bridge action | `deriveCode()` rải rác trong controller | `EnsureAssessmentLinkedAction` (trong Survey module) |
| Result models | SurveyResult (VIEW) + Assessment models | Phase 5a: giữ VIEW / Phase 5b: dùng AssessmentResult |
| Entity mới muốn scoring | Phải fork Survey scoring | Implement interface + 6 bước checklist |

## Thứ tự thực hiện

```
Phase 1  → Xóa dead code                        (độc lập, không dependency)
Phase 2  → Tạo EnsureAssessmentLinkedAction      (cần xong trước Phase 3)
Phase 3  → Redirect Survey UI → Assessment       (cần Phase 2 xong)
Phase 4  → Unify config models                   (cần Phase 3 xong — ScoringAdminController đã xóa)
Phase 5a → Xác nhận VIEW-backed models OK        (song song với Phase 4)
Phase 6  → Verify behavior giữ nguyên            (verify sau mỗi phase)
Phase 7  → Confirm Lead throttle decision        (quyết định trước khi go-live)
Phase 5b → Refactor SurveyResultController       (ticket riêng, không block release)
Phase 8  → Tích hợp entity mới (Customer...)    (sau khi hệ thống ổn định)
```

> **Xem thêm:** `spec/assessment-registry.md` — kế hoạch riêng cho Assessment Registry
> (fix listing bug + source_type + UX index). Các phase này ưu tiên cao hơn và độc lập
> với lộ trình consolidation ở đây.

---

*Spec version 2 — cập nhật corrections từ code audit ngày 2026-05-29*
