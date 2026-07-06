# MinHan — Lộ trình Tích hợp AI/ML

> Phiên bản: 2.0 | Ngày: 2026-06-25
> Ràng buộc: **Không có AI API ngoài, không có Python** — 100% Laravel stack thuần
> Stack: Laravel 13, PHP 8.4, Redis, MySQL/SQLite, Reverb WebSocket, php-ai/php-ml
> Nguyên tắc: Mọi xử lý ML/NLP đều là Laravel Job chạy trong queue — không có service ngoài

---

## Tổng quan Lộ trình

```
Phase 1 │ Data Streams         │ Tháng 1–2  │ 100% Laravel + Redis + Echo, zero dependency mới
Phase 2 │ Recommendation       │ Tháng 2–4  │ SQL aggregation + php-ml TF-IDF, thuần Laravel
Phase 3 │ Sparse Learning      │ Tháng 3–5  │ PHP math thuần, fix Assessment & Lead scoring
Phase 4 │ Topic Modeling       │ Tháng 5–8  │ php-ml TF-IDF+KMeans, Laravel Job batch
Phase 5 │ NLP                  │ Tháng 8–12 │ Lexicon-based + TF-IDF vector, thuần PHP
Phase 6 │ Continual Learning   │ Tháng 12+  │ php-ml GradientBoosting, cần labeled data đủ lớn
```

### Dependency duy nhất cần thêm

```bash
composer require php-ai/php-ml
# MIT license, không cần extension PHP đặc biệt
# Cung cấp: TF-IDF, KMeans, GradientBoosting, cosine similarity, tokenizer
# Tất cả chạy trong PHP process — không cần service ngoài
```

---

## Khảo sát Nền tảng Đã có (quan trọng — không làm lại)

### Các bảng đã scaffold sẵn
```
recommendation_rules          → Rule-based từ assessment score (threshold_score per domain)
workforce_recommendations     → Bản ghi kết quả gợi ý (provider, model, context_hash)
workforce_recommendation_items→ Chi tiết từng recommendation item (domain_code, resource_type)
snapshot_recommendations      → Gợi ý từ assessment snapshots
result_recommendations        → Gợi ý từ kết quả đánh giá
score_rules                   → Mapping survey_field → domain_score (condition_type: boolean/choice)
score_rule_options            → Tùy chọn cho single/multi choice
score_rule_numeric_ranges     → Thang điểm số
ai_impact_snapshots           → ROI tracker (baseline_value, achieved_value, improvement_pct)
ai_monthly_usages             → Token tracking (đang trống vì chưa có API)
ai_requests                   → Request/response log
```

### Events đã có (dùng làm trigger cho Phase 1 & 2)
```
Lead:       LeadCreated, LeadAssigned, LeadStageChanged
Task:       TaskCreated, TaskUpdated
Assessment: AssessmentCompleted, MaturityLevelChanged, CertificationIssued, LowKpiAlert
KcItem:     KcItemCreated, KcItemUpdated
Survey:     (qua CalculateSurveyScoreJob)
Employee:   EmployeeCreated, EmployeeUpdated
Project:    ProjectCreated, ProjectUpdated
```

### Jobs đã có (extend thay vì tạo mới)
```
CalculateSurveyScoreJob  → Phase 3: thêm sparse scoring
UpdateKcViewCountJob     → Phase 2: thêm similarity trigger
UpdateProjectProgressJob → Phase 1: thêm broadcast
```

---

## Phase 1 — Data Streams

> **Mục tiêu:** Dashboard và KPI thay đổi real-time không cần reload. Alert chạy ngay khi event xảy ra thay vì batch.
> **Stack:** Redis + Laravel Broadcasting (Reverb) + Echo.js
> **Chi phí bổ sung:** 0đ

### Hiện trạng vs Mục tiêu

```
Hiện tại                              → Sau Phase 1
─────────────────────────────────────────────────────
Dashboard charts: AJAX poll mỗi 30s  → Echo push khi data thay đổi
Task done: cập nhật DB, page reload  → project.progress_pct broadcast live
Lead stage change: form submit        → Lead funnel chart tự cập nhật
Alert rules: chạy batch (scheduled)  → Fire ngay khi event xảy ra
Workflow execution: xem lịch sử F5   → Live status badge không reload
Survey submit: job queue im lặng     → Real-time notification kết quả
```

---

### Task 1.1 — Broadcasting Events cho Dashboard Charts

**Mục đích:** Các chart `/api/dashboard/charts/*` hiện là pull (client hỏi server). Chuyển sang push qua Echo.

**File cần tạo/sửa:**

```
app/Events/Dashboard/
├── TaskThroughputUpdated.php     (new)
├── LeadFunnelUpdated.php         (new)
├── WorkflowHealthUpdated.php     (new)
└── HeadcountUpdated.php          (new)
```

**Nội dung TaskThroughputUpdated.php:**
```php
namespace App\Events\Dashboard;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TaskThroughputUpdated implements ShouldBroadcast
{
    public function __construct(
        public readonly int $organizationId,
        public readonly array $payload  // { labels[], completed[], created[] }
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("org.{$this->organizationId}.dashboard")];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.task-throughput';
    }
}
```

**Listeners cần tạo** (`app/Listeners/Dashboard/`):
```
BroadcastTaskThroughput.php   → lắng nghe TaskCreated, TaskUpdated (status=done)
BroadcastLeadFunnel.php       → lắng nghe LeadCreated, LeadStageChanged
BroadcastWorkflowHealth.php   → lắng nghe WorkflowExecution events
BroadcastHeadcount.php        → lắng nghe EmployeeCreated, EmployeeUpdated
```

**Đăng ký trong `app/Providers/EventServiceProvider.php`:**
```php
TaskCreated::class  => [BroadcastTaskThroughput::class],
TaskUpdated::class  => [BroadcastTaskThroughput::class],
LeadCreated::class  => [BroadcastLeadFunnel::class],
LeadStageChanged::class => [BroadcastLeadFunnel::class],
```

**routes/channels.php — thêm channel org:**
```php
Broadcast::channel('org.{orgId}.dashboard', function ($user, $orgId) {
    return (int) $user->organization_id === (int) $orgId;
});
```

**Frontend — sửa `resources/js/admin-shell.js`:**
```javascript
// Thay vì setInterval polling, subscribe channel:
if (window.Echo && window.userId) {
    Echo.private(`org.${window.orgId}.dashboard`)
        .listen('.dashboard.task-throughput', (e) => {
            window.dispatchEvent(new CustomEvent('chart:task-throughput', { detail: e.payload }));
        })
        .listen('.dashboard.lead-funnel', (e) => {
            window.dispatchEvent(new CustomEvent('chart:lead-funnel', { detail: e.payload }));
        });
}
```

**Thêm vào `resources/views/layouts/backend.blade.php`:**
```blade
<meta name="org-id" content="{{ \App\Shared\Tenancy\TenantContext::getOrganizationId() }}">
```

**Queue:** `default` (không dùng `high` — không khẩn cấp)

---

### Task 1.2 — Live Project Progress Broadcast

**Mục đích:** `project.progress_pct` cập nhật real-time khi sub-task thay đổi.

**Sửa** `Modules/Task/app/Jobs/UpdateProjectProgressJob.php`:
```php
// Sau khi cập nhật progress:
broadcast(new ProjectProgressUpdated(
    organizationId: $project->organization_id,
    projectId: $project->id,
    progressPct: $project->progress_pct,
    taskDone: $project->task_done,
    taskTotal: $project->task_total,
))->toOthers();
```

**Tạo** `Modules/Project/app/Events/ProjectProgressUpdated.php` (ShouldBroadcast):
```
Channel: org.{orgId}.projects
Event:   project.progress-updated
Payload: { project_id, progress_pct, task_done, task_total }
```

**Frontend** (trong project show view):
```javascript
// Alpine x-data trên progress bar element:
Echo.private(`org.${orgId}.projects`)
    .listen('.project.progress-updated', (e) => {
        if (e.project_id === currentProjectId) {
            this.progressPct = e.progress_pct;
        }
    });
```

---

### Task 1.3 — Real-time Alert Rules Engine

**Mục đích:** `activity_log_alert_rules` hiện chưa có trigger mechanism. Thêm real-time check.

**Tạo** `app/Listeners/ActivityLog/EvaluateAlertRules.php`:
```php
// Lắng nghe Spatie ActivityLog event (mọi model change)
// Sau mỗi activity log entry:
//   1. Query alert_rules khớp với log_name + description_pattern
//   2. Đếm trong time_window_minutes
//   3. Nếu count >= threshold → fire AlertTriggered event → gửi notification
```

**Tạo** `Modules/ActivityLog/app/Jobs/CheckAlertRuleJob.php` (queue: `actlog`):
```
Input: log_name, description, organization_id
Logic:
  - Load rules matching log_name
  - Count recent entries trong time_window
  - Threshold exceeded → NotifyAdminOfAlert (email + in-app)
  - Ghi vào activity_log_alert_rule_triggers (table mới cần migration)
```

**Migration cần thêm:**
```php
// database/migrations/extensions/add_alert_rule_triggers_table.php
Schema::create('activity_log_alert_rule_triggers', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('alert_rule_id');
    $table->unsignedBigInteger('organization_id');
    $table->unsignedInteger('trigger_count');
    $table->timestamp('triggered_at');
    $table->boolean('notified')->default(false);
    $table->timestamps();
    $table->index(['alert_rule_id', 'triggered_at']);
});
```

---

### Task 1.4 — Live Workflow Execution Status

**Mục đích:** Trang workflow detail hiển thị execution progress real-time.

**Sửa** `Modules/WorkflowAutomation/app/Jobs/` (các job step):
```
Sau mỗi step hoàn thành:
  broadcast(new WorkflowStepCompleted(
      orgId, executionId, stepKey, status, outputData
  ))
  Channel: org.{orgId}.workflow.{executionId}
```

**Frontend** (workflow execution detail view):
```javascript
Echo.private(`org.${orgId}.workflow.${executionId}`)
    .listen('.workflow.step-completed', (e) => {
        // Cập nhật badge status cho step
        document.querySelector(`[data-step="${e.step_key}"]`)
                .classList.add(`status-${e.status}`);
    });
```

---

### Task 1.5 — Redis Streams cho High-volume Events

**Mục đích:** Thay Redis List (queue mặc định) bằng Redis Streams cho `actlog` queue — hỗ trợ consumer groups, replay, monitoring.

**Sửa** `config/queue.php`:
```php
'connections' => [
    'redis-streams' => [
        'driver'     => 'redis',
        'connection' => 'default',
        'queue'      => 'actlog',
        'block'      => 5,
    ],
    // Giữ nguyên redis connection thông thường cho các queue khác
]
```

**Thêm Redis Stream consumer** trong Supervisor config:
```ini
[program:minhan-actlog-stream]
command=php artisan queue:work redis-streams --queue=actlog --sleep=1 --tries=5
numprocs=1
```

**Checklist Phase 1:**
- [ ] Task 1.1: Broadcasting events + Echo subscribe trên dashboard
- [ ] Task 1.2: Project progress live update
- [ ] Task 1.3: Alert rules engine real-time
- [ ] Task 1.4: Workflow execution live status
- [ ] Task 1.5: Redis Streams cho actlog queue
- [ ] Task 1.x: Viết test cho broadcasting (Laravel TestCase + assertBroadcast)

---

## Phase 2 — Recommendation

> **Mục tiêu:** Gợi ý thông minh tại các điểm chạm quan trọng: KcItem, Lead assignment, Career Pathway, SOP.
> **Stack:** PHP thuần + SQL aggregation + TF-IDF đơn giản (không cần Python ở phase này)
> **Chi phí bổ sung:** 0đ
> **Lưu ý:** Các bảng `recommendation_rules`, `workforce_recommendations`, `workforce_recommendation_items` đã có sẵn — chỉ cần implement logic.

---

### Task 2.1 — KcItem Content-Based Recommendation

**Mục đích:** Hiển thị "Tài liệu liên quan" dưới mỗi KcItem. Dùng TF-IDF trên title + tags, không cần ML library.

**Tạo** `Modules/KcItem/app/Services/KcItemSimilarityService.php`:
```php
class KcItemSimilarityService
{
    // TF-IDF thuần PHP:
    // 1. Tokenize title + tag_names → bag of words (chuẩn hóa lowercase, bỏ stop words)
    // 2. Tính TF-IDF vector cho mỗi item → lưu cache Redis (key: kc:tfidf:{item_id})
    // 3. Cosine similarity giữa target item và tất cả items cùng org
    // 4. Top 5 items có score cao nhất → return

    public function getSimilarItems(KcItem $item, int $limit = 5): Collection
    {
        $cacheKey = "kc:similar:{$item->id}";
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($item, $limit) {
            $allItems = KcItem::where('organization_id', $item->organization_id)
                              ->where('id', '!=', $item->id)
                              ->where('visibility', '!=', 'restricted')
                              ->get(['id', 'title', 'tags']);
            return $this->computeCosineSimilarity($item, $allItems, $limit);
        });
    }

    private function tokenize(string $text): array
    {
        // Lowercase, bỏ ký tự đặc biệt, split by space
        // Áp dụng stop words tiếng Việt (danh sách tĩnh ~200 từ)
        $stopWords = config('recommendation.stopwords_vi', []);
        $tokens = preg_split('/\s+/', strtolower(strip_tags($text)));
        return array_diff($tokens, $stopWords);
    }
}
```

**Tạo** `config/recommendation.php`:
```php
return [
    'stopwords_vi' => ['và', 'của', 'là', 'có', 'cho', 'trong', 'với', 'được', ...],
    'kc_cache_ttl_hours' => 6,
    'lead_assignment_lookback_days' => 90,
    'similarity_min_score' => 0.15,
];
```

**Tạo** `Modules/KcItem/app/Jobs/RebuildKcSimilarityJob.php` (queue: `low`):
```
Trigger: KcItemCreated, KcItemUpdated events
Logic: Xóa cache kc:similar:* của org → rebuild ngầm cho top 20 items phổ biến nhất
TTL cache: 6 giờ
```

**Sửa** `Modules/KcItem/resources/views/show.blade.php`:
```blade
{{-- Thêm section "Tài liệu liên quan" --}}
@if($similarItems->isNotEmpty())
<section class="mt-8">
    <h3>Tài liệu liên quan</h3>
    @foreach($similarItems as $item)
        <a href="{{ route('backend.kc-items.show', $item) }}">{{ $item->title }}</a>
    @endforeach
</section>
@endif
```

---

### Task 2.2 — Lead Assignment Recommendation

**Mục đích:** Khi tạo/sửa Lead, hệ thống gợi ý "Sales rep phù hợp nhất" dựa trên win-rate lịch sử theo source + stage.

**Tạo** `Modules/Lead/app/Queries/LeadAssignmentRecommendationQuery.php`:
```php
class LeadAssignmentRecommendationQuery
{
    // Tính win-rate per (assigned_to, source_id, first_stage) trong 90 ngày qua:
    // SELECT assigned_to,
    //        COUNT(*) FILTER (WHERE status = 'won') * 100.0 / COUNT(*) as win_rate,
    //        AVG(DATEDIFF(actual_close_date, created_at)) as avg_days_to_close,
    //        COUNT(*) as total_leads
    // FROM leads
    // WHERE organization_id = ? AND source_id = ? AND created_at > NOW() - 90 days
    //   AND assigned_to IS NOT NULL AND status IN ('won','lost')
    // GROUP BY assigned_to
    // HAVING total_leads >= 3  -- đủ sample
    // ORDER BY win_rate DESC
    // LIMIT 3

    public function recommend(int $orgId, int $sourceId): Collection
    {
        return Cache::remember(
            "lead:assign:rec:{$orgId}:{$sourceId}",
            now()->addMinutes(30),
            fn() => $this->runQuery($orgId, $sourceId)
        );
    }
}
```

**Thêm API endpoint** vào `Modules/Lead/routes/web.php`:
```php
Route::get('/leads/recommend-assignee', [LeadAssigneeRecommendController::class, 'index'])
     ->middleware(['auth', 'tenant'])
     ->name('lead.recommend-assignee');
// Response: JSON { users: [{ id, name, win_rate, avg_days }] }
```

**Frontend** (form tạo Lead — tom-select cho assigned_to):
```javascript
// Khi user chọn source, AJAX gọi recommend-assignee:
document.querySelector('[name=source_id]').addEventListener('change', async (e) => {
    const resp = await fetch(`/leads/recommend-assignee?source_id=${e.target.value}`);
    const data = await resp.json();
    // Thêm badge "⭐ Gợi ý" vào đầu danh sách dropdown
    data.users.forEach(u => tomSelectInstance.addOption({
        value: u.id, text: `${u.name} — ${u.win_rate}% win rate`
    }));
});
```

---

### Task 2.3 — Career Pathway Recommendation từ Assessment Score

**Mục đích:** Sau khi Assessment hoàn thành, hệ thống tự động tạo `workforce_recommendation_items` dựa trên `recommendation_rules` (đã có DB) — **không cần AI API**, dùng rule-based logic thuần.

**Tạo** `Modules/Assessment/app/Actions/GenerateRuleBasedRecommendationsAction.php`:
```php
class GenerateRuleBasedRecommendationsAction
{
    public function execute(AssessmentResult $result): WorkforceRecommendation
    {
        // 1. Load recommendation_rules cho assessment_code này
        $rules = RecommendationRule::where('assessment_code', $result->assessment_code)
                                   ->where('is_active', true)
                                   ->orderBy('priority')
                                   ->get();

        // 2. Với mỗi rule: kiểm tra domain_score < threshold_score không?
        $items = [];
        foreach ($rules as $rule) {
            $domainScore = $result->getDomainScore($rule->trigger_domain);
            if ($domainScore < $rule->threshold_score) {
                $items[] = [
                    'domain_code'       => $rule->trigger_domain,
                    'priority'          => $rule->priority,
                    'action_description'=> $rule->description,
                    'resource_type'     => 'practice',  // default
                    'resource_name'     => $rule->label,
                ];
            }
        }

        // 3. Lưu vào workforce_recommendations + workforce_recommendation_items
        $recommendation = WorkforceRecommendation::updateOrCreate(
            ['workforce_profile_id' => $result->workforce_profile_id,
             'organization_id'      => $result->organization_id],
            ['provider'        => 'rule_based',
             'model'           => null,
             'generated_at'    => now(),
             'context_hash'    => md5($result->id . $result->updated_at),
             'recommendations' => $items,
             'is_stale'        => false]
        );

        WorkforceRecommendationItem::where('workforce_recommendation_id', $recommendation->id)->delete();
        foreach ($items as $item) {
            WorkforceRecommendationItem::create(
                array_merge($item, ['workforce_recommendation_id' => $recommendation->id])
            );
        }

        return $recommendation;
    }
}
```

**Listener** `Modules/Assessment/app/Listeners/GenerateRecommendationsOnComplete.php`:
```php
// Lắng nghe AssessmentCompleted event
// → dispatch(new GenerateRecommendationsJob($result))  // queue: default
```

**Hiển thị** trong `backend.workforce.me`:
```blade
{{-- Sau khi có kết quả assessment --}}
@foreach($recommendations as $rec)
    <div class="recommendation-card priority-{{ $rec->priority }}">
        <span class="badge">{{ $rec->domain_code }}</span>
        <p>{{ $rec->action_description }}</p>
        @if($rec->resource_url)
            <a href="{{ $rec->resource_url }}">Xem tài nguyên →</a>
        @endif
    </div>
@endforeach
```

---

### Task 2.4 — SOP Recommendation khi Tạo Task

**Mục đích:** Khi tạo Task mới, gợi ý SOPs liên quan dựa trên keyword match title.

**Tạo** `Modules/Sop/app/Queries/SopRecommendationQuery.php`:
```php
// Full-text search LIKE trên sop_processes.title + sop_processes.description
// Filter: organization_id, status = approved, visibility = public | internal
// Score: số từ khớp trong title (weight 2) + description (weight 1)
// Limit: 3 kết quả

// MySQL: dùng MATCH...AGAINST nếu có FULLTEXT index
// SQLite: dùng LIKE fallback
```

**Thêm vào Task create form** (AJAX gợi ý khi user dừng gõ title):
```javascript
// Debounce 500ms sau khi user ngừng gõ title:
const suggestSOPs = debounce(async (title) => {
    if (title.length < 5) return;
    const resp = await fetch(`/api/sop/suggest?q=${encodeURIComponent(title)}`);
    const sops = await resp.json();
    renderSopSuggestions(sops);
}, 500);
```

**API endpoint** `Modules/Sop/routes/web.php`:
```php
Route::get('/api/sop/suggest', [SopSuggestionController::class, 'index'])
     ->middleware(['auth', 'tenant']);
// Response: [{ id, title, url }]
```

---

### Task 2.5 — Workflow Template Recommendation

**Mục đích:** Khi tạo Workflow mới, gợi ý template dựa trên trigger_type đã chọn + lịch sử org khác dùng.

**Tạo** `Modules/WorkflowAutomation/app/Queries/WorkflowTemplateRecommendQuery.php`:
```php
// Tìm workflows có:
//   visibility = 'public' (shared templates)
//   trigger_type = ? (giống nhau)
//   organization_id != current org (cross-org templates)
//   execution_count cao nhất (proxy for quality)
// → Top 5
```

**Thêm column vào workflows table** (migration mới):
```php
$table->unsignedInteger('execution_count')->default(0)->after('visibility');
$table->unsignedInteger('clone_count')->default(0)->after('execution_count');
```

**Checklist Phase 2:**
- [ ] Task 2.1: KcItem TF-IDF similarity + show.blade.php
- [ ] Task 2.2: Lead assignment recommendation API + frontend
- [ ] Task 2.3: Rule-based career pathway recommendation (dùng recommendation_rules đã có)
- [ ] Task 2.4: SOP suggestion khi tạo Task
- [ ] Task 2.5: Workflow template recommendation
- [ ] Task 2.x: Cache strategy (Redis) cho tất cả recommendation endpoints
- [ ] Task 2.x: Unit tests cho SimilarityService, RecommendationQuery

---

## Phase 3 — Sparse Learning

> **Mục tiêu:** Assessment scoring chính xác khi user chưa làm đủ sections. Lead scoring khi thiếu dữ liệu.
> **Stack:** PHP thuần, toán học thống kê cơ bản
> **Chi phí bổ sung:** 0đ
> **Vấn đề cụ thể:** `CalculateSurveyScoreJob` hiện tính điểm 0 cho section chưa làm → workforce profile bị méo.

---

### Task 3.1 — Sparse Assessment Scoring (Weighted Normalization)

**Vấn đề hiện tại** trong `Modules/Survey/app/Jobs/CalculateSurveyScoreJob.php`:
```
User làm 4/10 sections → 6 sections còn lại = 0 điểm
→ Total score thấp giả tạo → lead_score sai → recommendation sai
```

**Giải pháp — Weighted Completion Scoring:**

**Tạo** `Modules/Survey/app/Services/SparseScoreCalculator.php`:
```php
class SparseScoreCalculator
{
    /**
     * Tính điểm có trọng số cho sections đã làm.
     * Sections chưa làm được impute bằng group average (cùng role).
     */
    public function calculate(SurveyResponse $response): array
    {
        $answeredSections = $response->answers
            ->groupBy(fn($a) => $a->question->section->section_code);

        $allSections = $response->survey->sections;
        $completionRate = $answeredSections->count() / $allSections->count();

        $scores = [];
        foreach ($allSections as $section) {
            if ($answeredSections->has($section->section_code)) {
                // Tính điểm thực từ câu trả lời
                $scores[$section->section_code] = [
                    'score'      => $this->scoreSection($answeredSections[$section->section_code], $section),
                    'source'     => 'answered',
                    'weight'     => 1.0,
                ];
            } else {
                // Impute bằng median của cùng role trong org
                $scores[$section->section_code] = [
                    'score'  => $this->getGroupMedian($section->section_code, $response),
                    'source' => 'imputed',
                    'weight' => 0.6,  // Trọng số thấp hơn cho giá trị imputed
                ];
            }
        }

        // Weighted average: answered * 1.0 + imputed * 0.6
        $totalWeight = collect($scores)->sum('weight');
        $weightedScore = collect($scores)->sum(fn($s) => $s['score'] * $s['weight']) / $totalWeight;

        return [
            'weighted_score'   => round($weightedScore, 2),
            'completion_rate'  => round($completionRate * 100, 1),
            'section_scores'   => $scores,
            'is_sparse'        => $completionRate < 1.0,
            'confidence'       => $this->computeConfidence($completionRate, count($scores)),
        ];
    }

    private function getGroupMedian(string $sectionCode, SurveyResponse $response): float
    {
        // Query: median score của users cùng role đã hoàn thành section này
        // Cache: "sparse:median:{sectionCode}:{role}:{orgId}" TTL 1 ngày
        $cacheKey = "sparse:median:{$sectionCode}:{$response->respondent->getRoleNames()->first()}:{$response->survey->organization_id}";
        return Cache::remember($cacheKey, now()->addDay(), fn() =>
            SurveyAnswer::join('survey_responses', ...)
                        ->where('section_code', $sectionCode)
                        ->whereHas('respondent', fn($q) => $q->role($role))
                        ->median('calculated_score') ?? 50.0  // fallback = 50 nếu chưa có data
        );
    }

    private function computeConfidence(float $completionRate, int $totalSections): float
    {
        // Confidence = completionRate * (1 + ln(totalSections)/10)
        // Range: 0.0 - 1.0
        return min(1.0, $completionRate * (1 + log(max(1, $totalSections)) / 10));
    }
}
```

**Sửa** `CalculateSurveyScoreJob.php`:
```php
// Inject SparseScoreCalculator
$result = $this->calculator->calculate($response);

// Lưu thêm:
$response->update([
    'weighted_score'  => $result['weighted_score'],
    'completion_rate' => $result['completion_rate'],
    'score_confidence'=> $result['confidence'],
    'is_sparse'       => $result['is_sparse'],
]);
```

**Migration cần thêm:**
```php
// extensions: add sparse scoring columns to survey_responses
$table->decimal('weighted_score', 5, 2)->nullable()->after('total_score');
$table->decimal('completion_rate', 5, 2)->nullable()->after('weighted_score');
$table->decimal('score_confidence', 4, 3)->nullable()->after('completion_rate');
$table->boolean('is_sparse')->default(false)->after('score_confidence');
```

**Hiển thị confidence** trong kết quả assessment:
```blade
@if($response->is_sparse)
<div class="alert alert-warning">
    Hoàn thành {{ $response->completion_rate }}% — điểm được ước tính.
    Confidence: {{ round($response->score_confidence * 100) }}%
    <a href="{{ route('backend.sandbox.index') }}">Hoàn thiện để tăng độ chính xác →</a>
</div>
@endif
```

---

### Task 3.2 — Sparse Lead Scoring

**Vấn đề:** `leads.lead_score` = 0 khi `survey_response_id` = null (lead chưa qua assessment). Hầu hết leads sẽ thiếu trường này.

**Tạo** `Modules/Lead/app/Services/LeadScoringService.php`:
```php
class LeadScoringService
{
    /**
     * Tính lead_score từ nhiều nguồn dữ liệu có sẵn,
     * không phụ thuộc survey_response.
     */
    public function score(Lead $lead): int
    {
        $score = 0;
        $maxScore = 0;

        // Nguồn 1: Survey score (nếu có) — weight 40
        if ($lead->survey_response_id) {
            $score += ($lead->survey_score ?? 0) * 0.4;
            $maxScore += 40;
        }

        // Nguồn 2: Completeness score (các fields đã điền) — weight 20
        $filledFields = collect([
            $lead->contact_phone,
            $lead->contact_company,
            $lead->expected_value,
            $lead->expected_close_date,
            $lead->source_id,
        ])->filter()->count();
        $score += ($filledFields / 5) * 20;
        $maxScore += 20;

        // Nguồn 3: Recency score (last_activity_at) — weight 20
        if ($lead->last_activity_at) {
            $daysSince = now()->diffInDays($lead->last_activity_at);
            $recencyScore = max(0, 20 - ($daysSince * 0.5));
            $score += $recencyScore;
        }
        $maxScore += 20;

        // Nguồn 4: Source historical win-rate — weight 20
        if ($lead->source_id) {
            $winRate = $this->getSourceWinRate($lead->source_id, $lead->organization_id);
            $score += $winRate * 0.2;
        }
        $maxScore += 20;

        // Normalize về 0-100
        return $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;
    }

    private function getSourceWinRate(int $sourceId, int $orgId): float
    {
        return Cache::remember("lead:winrate:{$orgId}:{$sourceId}", now()->addHours(2), fn() =>
            Lead::where('organization_id', $orgId)
                ->where('source_id', $sourceId)
                ->whereIn('status', ['won', 'lost'])
                ->selectRaw('SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as rate')
                ->value('rate') ?? 50.0
        );
    }
}
```

**Hook vào Lead lifecycle:**
```php
// Modules/Lead/app/Listeners/RecalculateLeadScore.php
// Lắng nghe: LeadCreated, LeadUpdated, LeadAssigned, LeadStageChanged
// → dispatch(new RecalculateLeadScoreJob($lead))  // queue: default
```

---

### Task 3.3 — Sparse Workforce Profile (Partial Domain Scores)

**Vấn đề:** User chỉ có assessment ở 2/6 domains → profile bị "trống" nhiều chỗ.

**Tạo** `Modules/Assessment/app/Services/WorkforceProfileImputer.php`:
```php
class WorkforceProfileImputer
{
    // Với domains chưa có score:
    //   → Dùng cohort average: users cùng job_title + organization
    //   → Nếu không có cohort → dùng global average của domain đó trong org
    //   → Đánh dấu is_imputed = true cho domains được điền tự động

    public function fillMissingDomains(WorkforceProfile $profile): array
    {
        $existingDomains = $profile->domainScores->pluck('domain_code')->toArray();
        $allDomains = ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'];
        $missingDomains = array_diff($allDomains, $existingDomains);

        $result = [];
        foreach ($missingDomains as $domain) {
            $result[$domain] = [
                'score'       => $this->getCohortAverage($domain, $profile),
                'is_imputed'  => true,
                'source'      => 'cohort_average',
            ];
        }
        return $result;
    }
}
```

**Checklist Phase 3:**
- [ ] Task 3.1: SparseScoreCalculator + migration thêm columns vào survey_responses
- [ ] Task 3.2: LeadScoringService + RecalculateLeadScoreJob
- [ ] Task 3.3: WorkforceProfileImputer
- [ ] Task 3.x: Confidence badge trên UI assessment results
- [ ] Task 3.x: Unit tests cho scoring logic (các edge cases: 0 sections, all sections)
- [ ] Task 3.x: Artisan command `php artisan leads:rescore` — rescore tất cả leads theo model mới

---

## Phase 4 — Topic Modeling

> **Mục tiêu:** Auto-tag KcItem, cluster survey responses, phát hiện SOP overlap.
> **Điều kiện:** KcItem > 200 bài viết trong org
> **Stack:** `php-ai/php-ml` — TF-IDF + KMeans + cosine similarity, chạy trong Laravel Job
> **Chi phí bổ sung:** 0đ, không service ngoài, không Python

---

### Task 4.1 — Text Processor cho Tiếng Việt (PHP)

**Tạo** `app/Services/TextProcessor/VietnameseTextProcessor.php`:

```php
namespace App\Services\TextProcessor;

class VietnameseTextProcessor
{
    // Tiếng Việt đã phân cách bằng khoảng trắng → không cần tokenizer phức tạp
    // WhitespaceTokenizer của php-ml dùng được trực tiếp

    private array $stopWords;

    public function __construct()
    {
        $this->stopWords = config('recommendation.stopwords_vi', []);
    }

    public function tokenize(string $text): array
    {
        // Lowercase, bỏ HTML tags, bỏ dấu câu, split by whitespace
        $clean = strtolower(strip_tags($text));
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);
        $tokens = preg_split('/\s+/', trim($clean), -1, PREG_SPLIT_NO_EMPTY);

        // Lọc stop words và từ quá ngắn (< 2 ký tự)
        return array_values(array_filter($tokens, fn($t) =>
            mb_strlen($t) >= 2 && !in_array($t, $this->stopWords)
        ));
    }

    public function buildDocument(string ...$parts): string
    {
        // Ghép title (weight x3) + tags + content
        return implode(' ', $parts);
    }
}
```

**Tạo** `config/recommendation.php` (nếu chưa có):
```php
return [
    'stopwords_vi' => [
        'và', 'của', 'là', 'có', 'cho', 'trong', 'với', 'được', 'này',
        'các', 'một', 'để', 'đã', 'không', 'khi', 'từ', 'tại', 'về',
        'theo', 'như', 'bằng', 'hoặc', 'nếu', 'thì', 'vì', 'mà', 'tôi',
        'bạn', 'họ', 'chúng', 'ta', 'hay', 'rằng', 'đây', 'đó', 'nào',
        // ~200 stop words tiếng Việt phổ biến
    ],
    'tfidf_cache_hours'          => 6,
    'similarity_min_score'       => 0.12,
    'lead_assignment_lookback'   => 90,   // days
    'cluster_min_documents'      => 20,   // min docs để cluster có nghĩa
    'topic_retrain_threshold'    => 50,   // số doc mới trước khi retrain
];
```

---

### Task 4.2 — TF-IDF Service (php-ml)

**Tạo** `app/Services/ML/TfIdfService.php`:

```php
namespace App\Services\ML;

use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use App\Services\TextProcessor\VietnameseTextProcessor;

class TfIdfService
{
    public function __construct(
        private VietnameseTextProcessor $processor
    ) {}

    /**
     * Tính TF-IDF vectors cho một tập documents.
     * Trả về: array of float vectors (một vector per document).
     */
    public function vectorize(array $documents): array
    {
        $tokenized = array_map(
            fn($doc) => implode(' ', $this->processor->tokenize($doc)),
            $documents
        );

        $vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
        $vectorizer->fit($tokenized);
        $countMatrix = $vectorizer->transform($tokenized);

        $transformer = new TfIdfTransformer();
        $transformer->fit($countMatrix);
        return $transformer->transform($countMatrix);
    }

    /**
     * Cosine similarity giữa 2 vectors.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $dot   = array_sum(array_map(fn($x, $y) => $x * $y, $a, $b));
        $normA = sqrt(array_sum(array_map(fn($x) => $x * $x, $a)));
        $normB = sqrt(array_sum(array_map(fn($x) => $x * $x, $b)));
        return ($normA && $normB) ? $dot / ($normA * $normB) : 0.0;
    }

    /**
     * Tìm top-K items gần nhất với target vector.
     */
    public function findTopK(array $targetVector, array $corpus, int $k = 5): array
    {
        $scores = [];
        foreach ($corpus as $id => $vector) {
            $scores[$id] = $this->cosineSimilarity($targetVector, $vector);
        }
        arsort($scores);
        return array_slice($scores, 0, $k, true);
    }
}
```

---

### Task 4.3 — Auto-Tag KcItem (TF-IDF Keywords)

**Mục đích:** Trích xuất từ khóa quan trọng nhất từ nội dung KcItem → gợi ý tags tự động.

**Tạo** `Modules/KcItem/app/Jobs/AutoTagKcItemJob.php` (queue: `low`):

```php
class AutoTagKcItemJob implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(private readonly int $kcItemId) {}

    public function handle(TfIdfService $tfidf, VietnameseTextProcessor $processor): void
    {
        $item = KcItem::find($this->kcItemId);
        if (!$item) return;

        // Lấy tất cả KcItems cùng org để tính IDF (corpus)
        $allItems = KcItem::where('organization_id', $item->organization_id)
                          ->where('id', '!=', $item->id)
                          ->where('visibility', '!=', 'restricted')
                          ->get(['id', 'title', 'content']);

        if ($allItems->count() < 5) return; // Corpus quá nhỏ → skip

        $documents = $allItems->map(fn($i) =>
            $processor->buildDocument($i->title, $i->title, strip_tags($i->content))
        )->toArray();

        // Tính TF-IDF cho toàn corpus
        $vectors   = $tfidf->vectorize($documents);
        $targetVec = $tfidf->vectorize([
            $processor->buildDocument($item->title, $item->title, strip_tags($item->content))
        ])[0];

        // Tìm top similar items → lấy tags của chúng (tag propagation)
        $topIds = $tfidf->findTopK($targetVec, $vectors, k: 3);
        $suggestedTags = KcItem::whereIn('id', array_keys($topIds))
                               ->with('tags')
                               ->get()
                               ->flatMap(fn($i) => $i->tags->pluck('name'))
                               ->countBy()
                               ->sortDesc()
                               ->take(5)
                               ->keys()
                               ->toArray();

        // Sync chỉ auto-generated tags (giữ nguyên tags người dùng đặt thủ công)
        $item->syncAutoGeneratedTags($suggestedTags);
    }
}
```

**Listener** `Modules/KcItem/app/Listeners/TriggerAutoTagging.php`:
```php
// Lắng nghe: KcItemCreated, KcItemUpdated
// Chỉ dispatch khi title hoặc content thay đổi:
$contentHash = md5($event->item->title . strip_tags($event->item->content));
if ($event->item->content_hash !== $contentHash) {
    dispatch(new AutoTagKcItemJob($event->item->id));
    $event->item->updateQuietly(['content_hash' => $contentHash]);
}
```

**Migration cần thêm:**
```php
// extensions: add content_hash to kc_items
$table->char('content_hash', 32)->nullable()->after('content');
// Dùng để detect thay đổi, tránh re-tag khi save không đổi content
```

---

### Task 4.4 — KMeans Clustering cho Survey Responses

**Mục đích:** Nhóm các câu trả lời tự luận của Survey thành clusters để phân tích themes.

**Tạo** `Modules/Survey/app/Jobs/ClusterSurveyResponsesJob.php` (queue: `low`):

```php
use Phpml\Clustering\KMeans;

class ClusterSurveyResponsesJob implements ShouldQueue
{
    public function handle(TfIdfService $tfidf): void
    {
        $survey    = Survey::find($this->surveyId);
        $responses = SurveyAnswer::whereHas('question', fn($q) => $q->where('field_type', 'textarea'))
                                  ->where('survey_id', $survey->id)
                                  ->get();

        if ($responses->count() < config('recommendation.cluster_min_documents')) return;

        $texts   = $responses->pluck('answer_value')->toArray();
        $vectors = $tfidf->vectorize($texts);

        // Số clusters = sqrt(n/2) — heuristic đơn giản
        $k = max(2, (int) round(sqrt($responses->count() / 2)));
        $kmeans   = new KMeans($k);
        $clusters = $kmeans->cluster($vectors);

        // Lưu kết quả
        SurveyResponseCluster::where('survey_id', $survey->id)->delete();
        foreach ($clusters as $clusterId => $members) {
            // Lấy sample responses tiêu biểu (gần centroid nhất)
            SurveyResponseCluster::create([
                'survey_id'        => $survey->id,
                'cluster_id'       => $clusterId,
                'item_count'       => count($members),
                'sample_responses' => collect($members)->take(3)
                                        ->map(fn($idx) => $texts[$idx])->toArray(),
            ]);
        }
    }
}
```

**Migration:**
```php
// survey_response_clusters
$table->id();
$table->unsignedBigInteger('survey_id');
$table->unsignedTinyInteger('cluster_id');
$table->unsignedInteger('item_count')->default(0);
$table->json('sample_responses')->nullable();
$table->timestamps();
$table->index(['survey_id', 'cluster_id']);
```

---

### Task 4.5 — SOP Overlap Detection (Cosine Similarity)

**Tạo** `Modules/Sop/app/Console/Commands/DetectSopOverlapCommand.php`:
```php
// php artisan sop:detect-overlap [--org=1] [--threshold=0.7]
// Logic:
//   1. Load tất cả approved SOPs
//   2. Vectorize title + steps content bằng TF-IDF
//   3. Tính pairwise cosine similarity
//   4. Pairs có similarity > threshold → tạo notification cho system_admin
//   5. In bảng kết quả ra console

// Lưu kết quả vào cache (Redis) 24h để tránh tính lại liên tục
```

**Chạy định kỳ** (schedule trong `routes/console.php`):
```php
Schedule::command('sop:detect-overlap')->weekly()->onSunday();
```

**Checklist Phase 4:**
- [ ] Task 4.1: `VietnameseTextProcessor` + `config/recommendation.php` (stop words)
- [ ] Task 4.2: `TfIdfService` (dùng `php-ai/php-ml`)
- [ ] Task 4.3: `AutoTagKcItemJob` + `TriggerAutoTagging` listener + migration `content_hash`
- [ ] Task 4.4: `ClusterSurveyResponsesJob` + migration `survey_response_clusters`
- [ ] Task 4.5: `DetectSopOverlapCommand` + schedule weekly
- [ ] Task 4.x: Admin UI review auto-generated tags (confirm/reject)
- [ ] Task 4.x: Unit tests cho `TfIdfService::cosineSimilarity`, `findTopK`
- [ ] Task 4.x: Artisan command `kc:retag-all` để retag toàn bộ KcItems khi stop words thay đổi

---

## Phase 5 — NLP

> **Mục tiêu:** Semantic search KcItem/SOP cải tiến, lead note sentiment, keyword extraction.
> **Điều kiện:** Phase 4 (TfIdfService) đã hoạt động
> **Stack:** Mở rộng `TfIdfService` từ Phase 4 + lexicon-based sentiment (PHP array)
> **Không cần:** Model ngoài, Python, embedding server — TF-IDF vector đã là "embedding" đủ tốt
> **Chi phí bổ sung:** 0đ

---

### Task 5.1 — Enhanced KcItem Search (TF-IDF Vector Search)

**Vấn đề hiện tại:** KcItem search dùng `LIKE '%query%'` → bỏ sót tài liệu liên quan nhưng khác từ.

**Giải pháp:** Lưu TF-IDF vector của mỗi KcItem vào DB → search bằng cosine similarity — đây là "semantic search" không cần model ngoài.

**Migration:**
```php
// kc_item_vectors table
$table->id();
$table->unsignedBigInteger('kc_item_id')->unique();
$table->unsignedBigInteger('organization_id');
$table->mediumText('tfidf_vector');  // JSON float array — ~2-5KB per item
$table->char('content_hash', 32);   // Để detect khi cần rebuild
$table->timestamp('vectorized_at');
$table->index('organization_id');
```

**Tạo** `Modules/KcItem/app/Jobs/BuildKcItemVectorJob.php` (queue: `low`):
```php
class BuildKcItemVectorJob implements ShouldQueue
{
    public function handle(TfIdfService $tfidf, VietnameseTextProcessor $processor): void
    {
        $item     = KcItem::find($this->kcItemId);
        $orgItems = KcItem::where('organization_id', $item->organization_id)->get();

        // Cần toàn bộ corpus để tính IDF chính xác
        $documents = $orgItems->map(fn($i) =>
            $processor->buildDocument($i->title, $i->title, strip_tags($i->content))
        )->toArray();

        $vectors   = $tfidf->vectorize($documents);
        $itemIndex = $orgItems->search(fn($i) => $i->id === $item->id);

        KcItemVector::updateOrCreate(
            ['kc_item_id' => $item->id],
            [
                'organization_id' => $item->organization_id,
                'tfidf_vector'    => json_encode($vectors[$itemIndex]),
                'content_hash'    => md5($documents[$itemIndex]),
                'vectorized_at'   => now(),
            ]
        );
    }
}
```

**Tạo** `Modules/KcItem/app/Services/KcSearchService.php`:
```php
class KcSearchService
{
    public function search(string $query, int $orgId, int $topK = 10): Collection
    {
        $queryDoc    = $this->processor->buildDocument($query);
        $queryTokens = implode(' ', $this->processor->tokenize($queryDoc));

        // Load tất cả vectors trong org (cached 10 phút)
        $vectors = Cache::remember("kc:vectors:{$orgId}", 600, fn() =>
            KcItemVector::where('organization_id', $orgId)
                        ->get(['kc_item_id', 'tfidf_vector'])
                        ->mapWithKeys(fn($v) => [$v->kc_item_id => json_decode($v->tfidf_vector, true)])
                        ->toArray()
        );

        if (empty($vectors)) {
            // Fallback: LIKE search nếu chưa có vectors
            return KcItem::where('organization_id', $orgId)
                         ->where('title', 'like', "%{$query}%")
                         ->limit($topK)->get();
        }

        // Tính query vector từ corpus hiện tại (approximation)
        $queryVector  = $this->approximateQueryVector($queryTokens, $vectors);
        $similarities = $this->tfidf->findTopK($queryVector, $vectors, $topK);

        return KcItem::whereIn('id', array_keys($similarities))
                     ->get()
                     ->sortByDesc(fn($item) => $similarities[$item->id] ?? 0)
                     ->values();
    }
}
```

**Sửa** `KcItemController::index()`:
```php
if ($request->filled('q')) {
    $items = $this->searchService->search($request->q, $orgId);
} else {
    $items = KcItem::where('organization_id', $orgId)->latest()->paginate(20);
}
```

**Artisan command:**
```bash
php artisan kc:build-vectors [--org=all]
# → Dispatch BuildKcItemVectorJob cho tất cả KcItems
# → Chạy 1 lần sau khi deploy Phase 5
```

---

### Task 5.2 — Sentiment Analysis cho Lead Notes (Lexicon-based)

**Không cần model ML** — dùng từ điển cảm xúc tiếng Việt (static PHP array).

**Tạo** `app/Services/Sentiment/VietnameseSentimentAnalyzer.php`:
```php
class VietnameseSentimentAnalyzer
{
    // Từ điển ~500 từ tích cực + ~500 từ tiêu cực tiếng Việt thông dụng trong bán hàng
    private array $positiveWords = [
        'quan tâm', 'thích', 'đồng ý', 'hài lòng', 'tốt', 'tuyệt', 'sẵn sàng',
        'muốn mua', 'cần gấp', 'tiến hành', 'xác nhận', 'chốt', 'ký', 'thanh toán',
        'hợp tác', 'tin tưởng', 'ủng hộ', 'phù hợp', 'hiệu quả', 'chuyên nghiệp',
        // ...
    ];
    private array $negativeWords = [
        'từ chối', 'không quan tâm', 'đắt', 'chưa cần', 'đang xem xét',
        'để sau', 'bận', 'không có tiền', 'ngừng', 'hủy', 'thất vọng',
        'chậm trễ', 'không ổn', 'vấn đề', 'khiếu nại', 'không phù hợp',
        // ...
    ];

    public function analyze(string $text): array
    {
        $lower   = mb_strtolower($text);
        $posHits = array_filter($this->positiveWords, fn($w) => str_contains($lower, $w));
        $negHits = array_filter($this->negativeWords, fn($w) => str_contains($lower, $w));

        $posScore = count($posHits);
        $negScore = count($negHits);
        $total    = max(1, $posScore + $negScore);

        $score = ($posScore - $negScore) / $total;  // -1.0 to 1.0

        return [
            'label'    => $score > 0.1 ? 'positive' : ($score < -0.1 ? 'negative' : 'neutral'),
            'score'    => round(($score + 1) / 2, 3),  // normalize 0-1
            'pos_hits' => array_values($posHits),
            'neg_hits' => array_values($negHits),
        ];
    }
}
```

**Tạo** `Modules/Lead/app/Jobs/AnalyzeLeadNoteSentimentJob.php` (queue: `low`):
```php
public function handle(VietnameseSentimentAnalyzer $analyzer): void
{
    $note   = LeadNote::find($this->noteId);
    $result = $analyzer->analyze($note->content);

    $note->update([
        'sentiment_label' => $result['label'],
        'sentiment_score' => $result['score'],
    ]);

    // Kiểm tra 3 notes liên tiếp negative → alert
    $recentNegative = LeadNote::where('lead_id', $note->lead_id)
                              ->where('sentiment_label', 'negative')
                              ->latest()
                              ->take(3)
                              ->count();

    if ($recentNegative >= 3) {
        event(new LeadAtRiskDetected($note->lead_id));
        // → Listener giảm lead_score, gắn tag "at-risk", notify assignee
    }
}
```

**Migration:**
```php
// extensions: add sentiment to lead_notes
$table->string('sentiment_label', 10)->nullable();  // positive|negative|neutral
$table->decimal('sentiment_score', 4, 3)->nullable();
```

---

### Task 5.3 — Keyword Extraction cho SOP & Assessment

**Mục đích:** Tóm tắt nhanh nội dung dài (SOP steps, assessment description) thành keywords.

**Tạo** `app/Services/TextProcessor\KeywordExtractor.php`:
```php
class KeywordExtractor
{
    // TF-IDF based: từ có TF-IDF score cao nhất = từ khóa quan trọng nhất
    // Không cần corpus ngoài — self-contained (TF từ document, IDF từ collection nhỏ)

    public function extract(string $text, int $topN = 10): array
    {
        $tokens    = $this->processor->tokenize($text);
        $frequency = array_count_values($tokens);
        arsort($frequency);
        return array_slice(array_keys($frequency), 0, $topN);
    }
}
```

**Ứng dụng:**
- SOP show page: auto-generate "Từ khóa" section từ step content
- Assessment result: highlight domain keywords yếu
- KcItem preview: extract 5 keywords cho card list view

---

### Task 5.4 — SOP Full-Text Search Cải tiến

**Tạo** `Modules/Sop/app/Services/SopSearchService.php`:
```php
// Tương tự KcSearchService nhưng cho SOPs
// Vectorize: sop_processes.title + concat(sop_steps.label + sop_steps.description)
// Bảng: sop_vectors (id, sop_id, org_id, tfidf_vector, content_hash)
```

**Checklist Phase 5:**
- [ ] Task 5.1: `BuildKcItemVectorJob` + `KcItemVector` migration + `KcSearchService`
- [ ] Task 5.2: `VietnameseSentimentAnalyzer` + `AnalyzeLeadNoteSentimentJob` + migration
- [ ] Task 5.3: `KeywordExtractor` + tích hợp vào SOP show page
- [ ] Task 5.4: `SopSearchService` + sop_vectors migration
- [ ] Task 5.x: Artisan `kc:build-vectors` và `sop:build-vectors`
- [ ] Task 5.x: Unit tests cho `VietnameseSentimentAnalyzer` (test cases tiếng Việt)
- [ ] Task 5.x: Mở rộng từ điển sentiment từ lead notes thực tế (feedback loop)

---

## Phase 6 — Continual Learning

> **Điều kiện bắt buộc trước khi bắt đầu:**
> - Lead won/lost records ≥ 500 (có đủ labeled data)
> - Phase 2 (Recommendation) + Phase 3 (Sparse Learning) đang chạy ổn định ≥ 3 tháng
> - Feedback loop rõ ràng (ai confirm label đúng/sai)
> - Model versioning infrastructure đã có
> **Mục tiêu:** Lead scoring model tự cải thiện theo thời gian. Assessment scoring calibration.

---

### Task 6.1 — Lead Scoring Model Training (php-ml GradientBoosting)

**Tạo** `Modules/Lead/app/Services/LeadScoringModelService.php`:
```php
use Phpml\Classification\GradientBoosting;  // php-ml
use Phpml\CrossValidation\StratifiedRandomSplit;
use Phpml\Metric\ClassificationReport;

class LeadScoringModelService
{
    // Features từ leads table (tất cả đã có sau Phase 3):
    private array $featureKeys = [
        'source_win_rate',           // LeadScoringService::getSourceWinRate()
        'completion_score',          // Số fields không null / tổng fields
        'recency_score',             // Days since last_activity_at
        'survey_score',              // leads.survey_score (nullable → imputed Phase 3)
        'expected_value_log',        // log(expected_value + 1) → normalize
        'days_in_current_stage',     // now() - stage_changed_at
        'activity_count',            // leads.activity_count (đã có)
    ];

    public function train(): array
    {
        // Lấy leads đã có outcome (won=1, lost=0) trong 1 năm gần nhất
        $leads = Lead::whereIn('status', ['won', 'lost'])
                     ->where('created_at', '>=', now()->subYear())
                     ->with('source')
                     ->get();

        if ($leads->count() < 100) {
            throw new InsufficientTrainingDataException(
                "Cần ít nhất 100 leads đã đóng. Hiện có: {$leads->count()}"
            );
        }

        $samples = $leads->map(fn($l) => $this->extractFeatures($l))->toArray();
        $labels  = $leads->map(fn($l) => $l->status === 'won' ? 1 : 0)->toArray();

        // Train/test split 80/20
        $split      = new StratifiedRandomSplit($samples, $labels, 0.2, 42);
        $classifier = new GradientBoosting(100, 0.1, 3);  // estimators, learning_rate, max_depth
        $classifier->train($split->getTrainSamples(), $split->getTrainLabels());

        // Đánh giá
        $predicted = $classifier->predict($split->getTestSamples());
        $report    = ClassificationReport::score($split->getTestLabels(), $predicted);

        // Lưu model + metrics
        $version = $this->saveModel($classifier, $report);
        return ['version' => $version, 'metrics' => $report];
    }

    public function predict(Lead $lead): int
    {
        $model   = $this->loadActiveModel();
        if (!$model) return app(LeadScoringService::class)->score($lead); // fallback Phase 3

        $features   = $this->extractFeatures($lead);
        $prediction = $model->predict([$features])[0];
        $proba      = $model->predictProbability([$features])[0][1] ?? 0.5;
        return (int) round($proba * 100);
    }

    private function saveModel(GradientBoosting $model, array $metrics): string
    {
        $version = 'v' . date('Ymd_His');
        $path    = storage_path("ml-models/lead_scorer_{$version}.model");

        // Serialize model (php-ml supports this natively)
        file_put_contents($path, serialize($model));

        MlModelVersion::create([
            'model_type'      => 'lead_scorer',
            'version'         => $version,
            'storage_path'    => $path,
            'metrics'         => $metrics,
            'training_config' => ['estimators' => 100, 'learning_rate' => 0.1],
            'trained_at'      => now(),
            'is_active'       => false,  // Manual activate sau khi review metrics
        ]);

        return $version;
    }
}
```

**Artisan command training:**
```bash
php artisan lead-scorer:train
# → Train model mới, lưu vào storage/ml-models/
# → In metrics (precision, recall, F1)
# → KHÔNG tự động activate — cần manual review

php artisan lead-scorer:activate v20260901_120000
# → Set is_active = true cho version này
# → Các requests tiếp theo dùng model mới

php artisan lead-scorer:compare v1 v2
# → So sánh metrics 2 versions
```

**Schedule** (trong `routes/console.php`):
```php
// Retrain mỗi 2 tuần — chỉ khi có đủ data mới
Schedule::command('lead-scorer:train')
         ->biweekly()
         ->when(fn() => Lead::whereIn('status', ['won','lost'])
                             ->where('updated_at', '>=', now()->subWeeks(2))
                             ->count() >= 50);
```

---

### Task 6.2 — Assessment Score Calibration

**Mục đích:** Điều chỉnh `score_rules.threshold_score` tự động khi có đủ kết quả thực tế.

**Logic:**
```
Sau 6 tháng có data:
  1. Phân tích: users có domain_score < threshold có thực sự kém ở domain đó không?
     (cross-validate với performance_reviews, kpi_goals)
  2. Nếu false positive rate > 30% → tăng threshold
  3. Nếu false negative rate > 20% → giảm threshold
  4. Tạo "calibration report" cho AI Operator review trước khi apply
  5. AI Operator approve → update score_rules.threshold_score
```

**Tạo** `Modules/Assessment/app/Console/Commands/GenerateCalibrationReportCommand.php`:
```bash
php artisan assessment:calibrate-report --assessment=AI_READINESS
# → Tạo report: "Domain D3 hiện có threshold 60 nhưng users dưới 60 vẫn perform tốt (FP=35%)"
# → Đề xuất: "Giảm threshold D3 về 50"
# → Lưu vào assessment_calibration_reports table
# → Notification cho AI Operator
```

---

### Task 6.3 — Model Versioning & Rollback

**Tạo bảng:**
```php
// ml_model_versions:
$table->id();
$table->string('model_type', 50);      // lead_scorer | assessment_calibration
$table->string('version', 20);         // v1.0, v1.1, ...
$table->unsignedBigInteger('organization_id')->nullable();
$table->string('storage_path', 500);   // storage/ml-models/...
$table->json('metrics');               // { accuracy, precision, recall, f1 }
$table->json('training_config');
$table->unsignedBigInteger('trained_by')->nullable();
$table->timestamp('trained_at');
$table->boolean('is_active')->default(false);
$table->timestamps();
```

**Artisan commands:**
```bash
php artisan ml:deploy lead_scorer v1.2    # Activate version
php artisan ml:rollback lead_scorer       # Rollback to previous version
php artisan ml:compare lead_scorer v1.1 v1.2  # Compare metrics
```

**Checklist Phase 6:**
- [ ] Task 6.1: LeadScoringModelService (php-ml GradientBoosting) + artisan train/activate commands
- [ ] Task 6.2: Assessment calibration report command
- [ ] Task 6.3: Model versioning table + deploy/rollback commands
- [ ] Task 6.x: A/B testing framework (50% leads dùng rule-based, 50% dùng ML model)
- [ ] Task 6.x: Model monitoring (accuracy drift detection)
- [ ] Task 6.x: Feedback collection UI (người dùng confirm lead score đúng/sai)

---

## Tổng hợp Dependencies giữa các Phase

```
Phase 1 ────────────────────────────────────────────────────────►
  └─► Phase 2 (dùng Events từ Phase 1 làm trigger)
        └─► Phase 3 (Sparse scoring cải thiện input cho Recommendation)
              └─► Phase 4 (cần corpus đủ lớn từ Phase 2 recommendation data)
                    └─► Phase 5 (TF-IDF vectors từ Phase 4 làm nền tảng)
                          └─► Phase 6 (cần data quality từ Phase 3 + 5)
```

---

## Cấu trúc File Mới Cần Tạo (tổng hợp)

```
app/
├── Events/Dashboard/
│   ├── TaskThroughputUpdated.php         [Phase 1]
│   ├── LeadFunnelUpdated.php             [Phase 1]
│   ├── WorkflowHealthUpdated.php         [Phase 1]
│   └── HeadcountUpdated.php              [Phase 1]
├── Listeners/
│   ├── Dashboard/
│   │   ├── BroadcastTaskThroughput.php   [Phase 1]
│   │   ├── BroadcastLeadFunnel.php       [Phase 1]
│   │   └── BroadcastHeadcount.php        [Phase 1]
│   └── ActivityLog/
│       └── EvaluateAlertRules.php        [Phase 1]
└── Services/
    ├── TextProcessor/
    │   ├── VietnameseTextProcessor.php   [Phase 4 — tokenizer + stop words]
    │   └── KeywordExtractor.php          [Phase 5]
    ├── ML/
    │   └── TfIdfService.php              [Phase 4 — dùng php-ai/php-ml]
    └── Sentiment/
        └── VietnameseSentimentAnalyzer.php [Phase 5 — lexicon-based, không cần model]

Modules/
├── Survey/app/
│   ├── Services/SparseScoreCalculator.php         [Phase 3]
│   └── Jobs/ClusterSurveyResponsesJob.php         [Phase 4 — php-ml KMeans]
├── Lead/app/
│   ├── Queries/LeadAssignmentRecommendationQuery.php [Phase 2]
│   ├── Services/
│   │   ├── LeadScoringService.php                 [Phase 3]
│   │   └── LeadScoringModelService.php            [Phase 6 — php-ml GradientBoosting]
│   └── Jobs/
│       ├── RecalculateLeadScoreJob.php            [Phase 3]
│       └── AnalyzeLeadNoteSentimentJob.php        [Phase 5]
├── Assessment/app/
│   ├── Actions/GenerateRuleBasedRecommendationsAction.php [Phase 2]
│   ├── Services/WorkforceProfileImputer.php               [Phase 3]
│   └── Console/Commands/GenerateCalibrationReportCommand.php [Phase 6]
├── KcItem/app/
│   ├── Services/
│   │   ├── KcItemSimilarityService.php  [Phase 2]
│   │   └── KcSearchService.php          [Phase 5 — TF-IDF vector search]
│   ├── Listeners/TriggerAutoTagging.php [Phase 4]
│   └── Jobs/
│       ├── RebuildKcSimilarityJob.php   [Phase 2]
│       ├── AutoTagKcItemJob.php         [Phase 4 — TF-IDF tag propagation]
│       └── BuildKcItemVectorJob.php     [Phase 5]
├── Sop/app/
│   ├── Queries/SopRecommendationQuery.php             [Phase 2]
│   ├── Services/SopSearchService.php                  [Phase 5]
│   └── Console/Commands/DetectSopOverlapCommand.php   [Phase 4 — cosine similarity]
└── WorkflowAutomation/app/
    └── Queries/WorkflowTemplateRecommendQuery.php      [Phase 2]

config/
└── recommendation.php                  [Phase 2+4: stop words, thresholds, cache TTL]

storage/
└── ml-models/                          [Phase 6: serialized php-ml models — không Python]
    └── lead_scorer_v{date}.model

database/migrations/extensions/
├── add_alert_rule_triggers_table.php          [Phase 1]
├── add_sparse_scoring_to_survey_responses.php [Phase 3]
├── add_content_hash_to_kc_items.php           [Phase 4]
├── add_kc_item_vectors_table.php              [Phase 5]
├── add_sentiment_to_lead_notes.php            [Phase 5]
├── add_sop_vectors_table.php                  [Phase 5]
├── add_execution_count_to_workflows.php       [Phase 2]
├── add_survey_response_clusters_table.php     [Phase 4]
└── add_ml_model_versions_table.php            [Phase 6]
```

---

## Metrics Đánh giá Thành công theo Phase

| Phase | Metric chính | Target |
|-------|-------------|--------|
| Phase 1 | Latency dashboard update | < 500ms từ event → UI |
| Phase 1 | Alert response time | < 5s từ trigger → notification |
| Phase 2 | KcItem recommendation CTR | > 15% (user click vào related items) |
| Phase 2 | Lead assignment accept rate | > 60% (user chọn gợi ý thay vì tự chọn) |
| Phase 3 | Assessment score accuracy | Confidence > 0.7 với completion > 60% |
| Phase 3 | Lead score correlation | r > 0.5 với actual win/loss |
| Phase 4 | Auto-tag precision | > 70% (human review confirm) |
| Phase 4 | Survey cluster coherence | Coherence score > 0.4 (gensim metric) |
| Phase 5 | Semantic search relevance | NDCG@5 > 0.65 (so với keyword search) |
| Phase 5 | Lead sentiment accuracy | > 80% so với human label |
| Phase 6 | Lead score model precision | > 72% trên test set |
| Phase 6 | Model stability | Accuracy drift < 5% sau 1 tháng deploy |

---

## Nguyên tắc Kỹ thuật Xuyên suốt

```
1. GRACEFUL DEGRADATION
   Mọi AI/ML feature đều có fallback về logic cũ nếu job thất bại.
   → Job ML timeout → dùng keyword search / rule-based fallback
   → Recommendation lỗi → không show suggestion, không crash page

2. TENANT ISOLATION
   Mọi model, cache, embedding đều scope theo organization_id.
   → TF-IDF trained per org (không cross-org)
   → Recommendation không leak data giữa orgs
   → Cache key format: "{feature}:{orgId}:{entityId}"

3. QUEUE PRIORITY
   → Recommendation: queue=low (không blocking user)
   → Scoring: queue=default (ảnh hưởng UX)
   → Embedding generation: queue=low
   → Alert evaluation: queue=actlog

4. ZERO REGRESSION
   → Mỗi Phase có feature flag riêng (config/features.php)
   → Tắt được từng feature không ảnh hưởng đến phần còn lại
   → Code review bắt buộc trước khi merge Phase mới

5. DATA PRIVACY
   → Embedding và model không được export ra ngoài org
   → Mọi xử lý chạy trong Laravel process — không gửi data ra ngoài
   → Sensitive fields (phone, email) bị mask trước khi đưa vào NLP
```

---

*Roadmap này được xây dựng dựa trên scan thực tế codebase ngày 2026-06-25.*
*Cập nhật khi có thay đổi kiến trúc hoặc điều chỉnh scope từ team.*
