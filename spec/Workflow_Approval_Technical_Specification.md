# Approval Module (trước đây gọi "Workflow & Approval")
**Đặc tả Kỹ thuật Chi tiết – Sẵn sàng Triển khai**

**Phiên bản:** 4.3 (cập nhật ngày 13/07/2026 — thay thế toàn bộ v4.2; đối chiếu lại toàn bộ đặc tả với codebase thực tế, sửa §17.2 — hook dọn `ApprovalSubject` mồ côi vẫn CHƯA được áp dụng vào `Product::booted()`, chỉ là khuyến nghị)
**Ngày:** 13/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Hệ thống:** Nền tảng multi-tenant (Organization-scoped)
**Module tham chiếu để đối chiếu codebase:** `Modules/Post` (Publishing Engine v2.0 — `spec/PublishingEngine_Technical_Specification.md`), `Modules/WorkflowAutomation` (automation engine hiện có), `Modules/Product`

**Đổi so với v4.1 → v4.2:** thêm §18.11 — dashboard "Bài viết chờ duyệt" xuyên tổ chức + sửa bug Gate name mismatch ở sidebar.

**Đổi so với v3.2 (làm chặt hơn để dễ implement, không đổi hành vi cốt lõi):** thêm xác nhận idempotent tường minh cho `approval:backfill-subjects` (§4.1) + ghi chú cân nhắc index cho `public_snapshot` (không cam kết ngay, theo nguyên tắc §17.3); helper `approvalLogs()` mới trong `HasApproval` (§7.1); `ApprovalDashboardService::pendingFor()` dùng `Gate::forUser()->allows()` tường minh + thêm `ApprovalDashboardController`/route mẫu đầy đủ (§12); ghi chú rõ vì sao KHÔNG cache `publicContent()` ở mức entity (§7.1) và khi nào mới cần cache/materialize kết quả `publiclyVisible()` ở quy mô lớn (§17.3); làm rõ trong comment `ReviseContentAction` rằng chỉ 2 nhánh Approved/Published tạo `ApprovalLog`, Draft/Pending/Archived không bao giờ tạo log rác (§8.4); comment `initial_status_resolver` là optional, mặc định `Draft` (§5).

**Đổi so với v3.1 → v3.2 (đã chốt, không đổi lại):** sửa lại đúng hành vi hiển thị công khai — cổng thông tin phải tiếp tục hiển thị bản nội dung đã duyệt gần nhất, không gián đoạn khi có bản sửa mới đang chờ duyệt (mô hình "đóng băng bản đã duyệt" qua cột `public_snapshot`, không phải "ẩn tạm"). Xem §1, §7.1, §8.2, §8.4.

**Đổi so với v3.3 → v3.4 (hoàn thiện thêm, không đổi hành vi):** code mẫu đầy đủ cho observer dọn `ApprovalSubject` mồ côi khi `Product::forceDelete()`, gắn trực tiếp vào `booted()` sẵn có của `Product` (§17.2); nói rõ hơn 1 câu về ưu tiên cache response/fragment/CDN thay vì cache từng entity khi catalog public lớn (§17.3); thêm ví dụ Blade đầy đủ cho dashboard — group theo `subject_type`, link "Xem & duyệt" qua accessor tuỳ chọn `approvalDashboardUrl` (§12).

**Đổi so với v3.4 → v3.5 (sửa bug thật phát hiện khi seed demo data, §17.8):** `LogsApprovalActions::transition()` (§8.1) nhận thêm tham số `$parent` và gọi `$parent->setRelation('approvalSubject', $locked)` sau mỗi transition — trước đó, gọi nhiều Action liên tiếp trên CÙNG 1 object (vd Submit→Approve→Publish→sửa nội dung trong 1 request/job, không `refresh()` giữa chừng) khiến `$product->approvalSubject` giữ mãi cache CŨ từ lúc tạo, làm `ReviseContentAction` đọc nhầm status và âm thầm không chuyển `Pending`. Đã cập nhật cả 6 Action (§8.2–8.4) truyền `$subject` gốc thay vì chỉ `$subject->approvalSubject`.

**Đổi so với v3.7 → v3.8 (sửa bug nghiêm trọng, §17.11):** phát hiện khi giải thích vì sao sửa nội dung sản phẩm Archived báo lỗi — hoá ra nội dung mới **đã bị ghi vào DB** trước khi exception ném ra (thiết kế cũ dùng sự kiện `updated`, sau khi UPDATE đã tự commit). Đổi `HasApproval::bootHasApproval()` (§7.1) từ `static::updated`+`wasChanged()` sang `static::updating`+`isDirty()` — chặn TRƯỚC khi query chạy, không có trạng thái nửa vời nào. Thêm xử lý lỗi thân thiện ở `ProductAdminController::update()` (§9.4, §17.11) thay vì để lộ lỗi 500.

**Đổi so với v3.6 → v3.7 (thiếu sót phát hiện khi người dùng hỏi "sao không thấy menu"):** route dashboard (§12) tồn tại từ Phase 5 nhưng CHƯA có mục menu sidebar nào trỏ tới — chỉ truy cập được nếu gõ thẳng URL. Đã thêm 2 mục menu vào `resources/views/layouts/partials/sidebar.blade.php` (§13): "Chờ duyệt của tôi" (`approval.view_dashboard`) và "Lịch sử duyệt" (`approval.view_history`, permission MỚI). Trang "Lịch sử duyệt" (§12.1) là tính năng mới — dashboard §12 chỉ cho user thấy việc CỦA RIÊNG họ (pending item họ tự duyệt được), không phải nơi xem toàn cảnh; trang mới liệt kê MỌI `ApprovalLog` (mọi entity/trạng thái/hành động) cho vai trò giám sát (`ceo`, `system_admin` — `ops` cố ý không có, §11).

**Đổi so với v3.5 → v3.6 (2 bug thật phát hiện khi thêm badge duyệt vào danh sách Product, §17.9, §17.10):**
1. `approval:backfill-subjects` (§4.1) trước đó LUÔN báo "0 bản ghi" bất kể DB có bao nhiêu entity thật, vì chạy từ console không có `TenantContext` → `OrganizationScope` (global scope trên mọi model `BelongsToOrganization`) tự áp `whereRaw('0=1')` failsafe. Đã sửa: command giờ loop qua **mọi Organization**, `TenantContext::set()` từng cái trước khi query (§4.1).
2. Hiển thị badge duyệt nội dung trên trang danh sách Product (`index.blade.php`, nhiều dòng cùng lúc) đòi hỏi eager-load `approvalSubject` — nếu không, Eloquent strict mode (`Model::shouldBeStrict()`, bật ở non-production) ném `LazyLoadingViolationException` ngay khi collection có ≥ 2 phần tử (không xảy ra với trang edit vì chỉ có 1 Product/request). Đã thêm `->with('approvalSubject')` vào `ListProductsForAdminHandler` (§9.8 mới).

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định | Lý do |
|---|---|---|
| **Tên module** | `Modules/Approval` (không phải `Modules/Workflow`) | `Modules/WorkflowAutomation` đã sở hữu khái niệm "Workflow" + permission prefix `workflow.*` (`workflow.monitor/edit/view_limited/ai_config/full_config` — `app/Enums/PermissionEnum.php`). Đổi tên loại bỏ xung đột khái niệm/permission. |
| Phạm vi áp dụng | Polymorphic qua trait `HasApproval` | Dùng chung cho nhiều entity (`Product`, và các entity tương lai) mà không duplicate code. |
| Cơ chế polymorphic | Eloquent `morphs()` thật (đổi tên cột `subject`) + `Relation::morphMap()` (không `enforce`, xem §5) | Khác cách `WorkflowAutomation` dùng `entity_type` string thô + `SubjectRegistry` (họ cần resolve động theo cấu hình per-org); ở đây model được biết trước tại compile-time qua trait nên `morphTo` chuẩn là đúng, cho quan hệ Eloquent thật + eager-load được. `enforceMorphMap()` KHÔNG dùng vì đó là cờ toàn cục ảnh hưởng mọi polymorphic relation khác trong app (Activitylog, Permission, Notifications…), vượt quá thẩm quyền của 1 module con. |
| State machine | 5 trạng thái cố định: Draft → Pending → Approved → Published → Archived | Khớp tinh thần `TranslationStatus` của Post, rút gọn còn 5 case chung (bỏ `Scheduled`/`Unpublished` — đặc thù publishing). |
| Quan hệ với `WorkflowAutomation` | Không phụ thuộc, không thay thế | `WorkflowAutomation` phục vụ quy trình cấu hình được theo tổ chức + automation; `Approval` phục vụ phê duyệt nội dung chuẩn, hard-code, không cấu hình qua UI. Xem §2. |
| Quan hệ với Post | Không đổi gì ở Post | `PostArticleTranslation` giữ nguyên `TranslationStatus` riêng (đã hoàn thiện). `Approval` nhắm tới entity **chưa có** cơ chế duyệt — ứng viên đầu tiên: `Product`. |
| Approval flow | Single-step, không có `current_approver_id` ở MVP | Post không "chỉ định người duyệt cụ thể" — chỉ dùng Policy + permission domain. Nếu sau này cần routing theo người cụ thể, dùng `WorkflowUserTask` của `WorkflowAutomation` (Mô hình C), không tái tạo trong `Approval`. |
| Permissions | **Không có permission riêng của `Approval`** | Mỗi module tiêu thụ tự định nghĩa permission theo domain của nó (`product.publish`…), tự viết Policy — đúng convention hiện tại (`post_article.*`, `product.*`). Ngoại lệ: 1 permission duy nhất cho dashboard xuyên-entity (§10). |
| Migration location | `Modules/Approval/database/migrations/` (module-local, giống Post) | `database/migrations/generated/` chỉ là nơi NWIDART/`migration:generate --fresh` dump schema tập trung cho `WorkflowAutomation`, không phải quy ước bắt buộc cho module mới. |
| Cấu hình entity hỗ trợ | `config/approval.php` (§14) | Cần 1 nơi khai báo model + label + (tuỳ chọn) resolver trạng thái ban đầu cho backfill — dùng để build morph map & dashboard, tránh hard-code rải rác. |

---

## 1. Giới thiệu & Mục tiêu

**Bản chất nghiệp vụ (chốt lại, quan trọng nhất):** đây là cơ chế **kiểm soát thay đổi nội dung trước khi công khai** — mỗi khi nội dung của một entity bị tạo mới hoặc chỉnh sửa, cấp quản lý (CEO/Ops…) cần xem và duyệt lại **trước khi** bản thay đổi đó được đẩy ra cổng thông tin công khai. Đây thuần là governance/biên tập nội dung — **không liên quan** đến các thuộc tính vận hành/kinh doanh (giá, tồn kho, trạng thái bán hàng…), dù các thuộc tính đó nằm trên cùng 1 entity.

Hệ quả trực tiếp của bản chất này (khác định nghĩa hẹp "chỉ duyệt lần đầu" của v1.0–v3.0): **thay đổi nội dung SAU KHI đã Published cũng phải chờ duyệt lại trước khi thay thế bản đang hiển thị công khai.** Điểm mấu chốt (chốt lại ở v3.2, sửa hiểu sai của v3.1): **sửa nội dung KHÔNG đồng nghĩa với việc ẩn thông tin khỏi cổng thông tin.** Cổng thông tin vẫn tiếp tục hiển thị bản nội dung đã duyệt gần nhất (không gián đoạn); bản sửa mới nằm chờ duyệt song song, và chỉ THAY THẾ nội dung công khai tại đúng thời điểm nó được duyệt + publish. Đây là lý do:
- State machine ở §6 có 2 transition `Approved → Pending`, `Published → Pending` (nội bộ biết "đang có 1 bản sửa chờ duyệt"), nhưng transition này **không ảnh hưởng** tới cái gì đang hiển thị công khai.
- `approval_subjects` có thêm cột `public_snapshot` (§4) — lưu đúng bản nội dung đã duyệt/đang publish, tách khỏi bản nội dung "đang chỉnh sửa" nằm ngay trên entity. Cổng thông tin đọc từ `public_snapshot`, KHÔNG đọc trực tiếp cột nội dung hiện tại của entity. Xem §7.1, §8.2, §8.4, §9.6.

Module **Approval** cung cấp cơ chế trạng thái + phê duyệt **dùng chung, nhẹ, không cấu hình qua UI**, gắn vào bất kỳ Eloquent model nào qua trait `HasApproval`.

Mục tiêu:
- State machine 5 trạng thái tái sử dụng được, không viết lại `canTransitionTo()` cho từng entity.
- Tự động đưa nội dung về `Pending` và ẩn khỏi cổng thông tin ngay khi có thay đổi ở các trường nội dung đã theo dõi (§7.1) — không cần module tiêu thụ tự nhớ gọi tay (rủi ro quên gọi = rò rỉ nội dung chưa duyệt ra công khai).
- Audit log append-only + notification, đúng pattern đã có (`PostPublishingLog`, `RespectsNotificationPreferences`).
- Không xung đột, không phụ thuộc `Modules/WorkflowAutomation`.
- Không migrate lại entity đã có cơ chế trạng thái riêng (Post).

**Ngoài phạm vi (cố ý):**
- Multi-step approval / định tuyến theo người duyệt cụ thể → `WorkflowUserTask` (`WorkflowAutomation`).
- Cấu hình trạng thái/transition qua UI theo từng organization → `workflow_entity_states`/`transitions`.
- Migrate `PostArticleTranslation` hay `Product.status` (`ProductStatus`) hiện tại.

---

## 2. So sánh & đánh giá với hạ tầng hiện có

### 2.1 `Modules/WorkflowAutomation` — Mô hình B/C (đã tồn tại, cấu hình được)

- `workflow_entity_states` / `workflow_entity_transitions` / `workflow_entity_state_logs` — state machine cấu hình qua DB per-`organization_id` + `entity_type` (string tự do), `allowed_roles` JSON, có thể kích hoạt automation (`triggers_workflow_id`).
- `WorkflowUserTask` + `UserTaskExecutor` — human-in-the-loop, tạm dừng automation chờ người quyết định (`assignee_id`/`assignee_role`, `due_at`, `on_timeout`).

**Đánh giá:** đủ mạnh nhưng nặng hơn nhu cầu MVP (phải khai báo state/transition qua bảng cấu hình trước khi dùng được). Phù hợp cho quy trình khác nhau theo từng tổ chức + cần automation kèm theo — **không phải nhu cầu ở đây**.

### 2.2 `Modules/Post` — Publishing Engine v2.0 (mẫu tham chiếu)

`TranslationStatus` inline trên entity + Action riêng từng transition (`AsAction`, gọi `$action->handle(...)`) + `PostPublishingLog` (append-only, FK trực tiếp không polymorphic vì chỉ 1 entity dùng) + `PostArticlePolicy` (permission domain riêng, Action không tự check quyền) + notification qua `RespectsNotificationPreferences`/`NotificationData::make()`.

**Đánh giá:** pattern đúng để mô phỏng, nhưng Post không cần bảng phụ polymorphic. `Approval` cần polymorphic thật vì nhiều entity dùng chung → vẫn cần `approval_subjects`.

### 2.3 `Modules/Product` — chưa có approval, và có 1 trục trạng thái khác cần phân biệt rõ

`products.status` (`ProductStatus`: `active`/`inactive`/`discontinued`/`out_of_stock`) là **trạng thái vận hành/tồn kho** (hiển thị trên storefront, có nút "Mua ngay" hay không) — đổi qua `ChangeProductStatusAction`, gate bằng `product.edit`, **không phải** approval workflow. Hiện Product **chưa có bước "chờ duyệt nội dung"** nào cả — ai có `product.edit` là sửa và có hiệu lực ngay.

→ Khi tích hợp `HasApproval` vào `Product` (§8), đây là **2 trục độc lập, song song**, không thay thế nhau:
- `products.status` (`ProductStatus`) — sản phẩm còn bán được không (tồn kho/kinh doanh).
- `approval_subjects.status` (`ApprovalStatus`) — nội dung sản phẩm đã được duyệt để hiển thị công khai chưa (workflow biên tập).

Đây là **tính năng mới** đối với Product (business đang không yêu cầu), không phải sửa lỗi — cần xác nhận với PO trước khi implement Phase 4 rằng: có thật sự cần bước duyệt nội dung Product hay không, hay `Approval` chỉ minh hoạ cách áp dụng `HasApproval` cho một entity thực tế đầu tiên.

### 2.4 Kết luận

| | `WorkflowAutomation` | `Approval` (module mới) | `Post` |
|---|---|---|---|
| Trạng thái | Cấu hình qua DB per-org | Hard-code 5 trạng thái | Hard-code 7 trạng thái, inline |
| Transition rule | Cấu hình qua DB | Hard-code trong enum | Hard-code trong enum |
| Polymorphic | `entity_type` string + registry | Eloquent `morphTo` thật | Không cần |
| Automation trigger | Có | Không | Không |

---

## 3. Kiến trúc dữ liệu — ERD

```
AnyEntity (Product, ...) ── use HasApproval
    │ morphOne
    ▼
ApprovalSubject
  ├─ id
  ├─ subject_type, subject_id   (morphs 'subject', alias qua Relation::morphMap())
  ├─ organization_id
  ├─ status (ApprovalStatus: draft|pending|approved|published|archived)
  ├─ approved_by, approved_at  (nullable — set khi status → approved)
  ├─ public_snapshot (JSON, nullable — bản nội dung đã duyệt/đang hiển thị công khai,
  │                    chỉ ghi lại trong PublishAction, KHÔNG đổi khi nội dung bị sửa sau đó)
  └─ timestamps, softDeletes

ApprovalLog (audit, append-only)
  ├─ approval_subject_id
  ├─ organization_id
  ├─ action (submit|approve|reject|publish|archive)
  ├─ from_status, to_status
  ├─ reason (bắt buộc khi action=reject)
  ├─ performed_by (nullable — null khi chạy từ job/command hệ thống)
  └─ created_at (không có updated_at)
```

---

## 4. Migrations

Đặt tại `Modules/Approval/database/migrations/` (module-local, giống `Modules/Post`).

**Migration #1 — `create_approval_subjects_table`**
```php
Schema::create('approval_subjects', function (Blueprint $table) {
    $table->id();
    $table->morphs('subject'); // subject_type, subject_id + index tự động
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->string('status', 20)->default('draft');
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    // Bản nội dung đã duyệt/đang hiển thị công khai — chỉ PublishAction (§8.2) được ghi cột
    // này. ReviseContentAction (§8.4) KHÔNG đụng vào, để cổng thông tin không bị gián đoạn khi
    // nội dung đang chờ duyệt lại (§1). NULL = entity chưa từng được publish lần nào.
    $table->json('public_snapshot')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['subject_type', 'subject_id'], 'uq_approval_subject');
    $table->index(['organization_id', 'status'], 'idx_approval_org_status');
    // Phục vụ dashboard lọc theo loại entity (§10) — tránh full scan khi nhiều subject_type.
    $table->index(['organization_id', 'subject_type', 'status'], 'idx_approval_org_type_status');
});
```

**Migration #2 — `create_approval_logs_table`**
```php
Schema::create('approval_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('approval_subject_id')->constrained()->cascadeOnDelete();
    $table->string('action', 20);       // submit|approve|reject|publish|archive
    $table->string('from_status', 20)->nullable();
    $table->string('to_status', 20);
    $table->string('reason', 500)->nullable();
    $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['approval_subject_id', 'created_at'], 'idx_approval_log_subject');
});
```

### 4.1 Command backfill — `approval:backfill-subjects`

Theo đúng mẫu `post:backfill-translations` (`Modules/Post/app/Console/Commands/BackfillPostTranslationsCommand.php`): idempotent (bỏ qua entity đã có `ApprovalSubject`), `chunkById`, `--dry-run`.

```php
// Modules/Approval/app/Console/Commands/BackfillApprovalSubjectsCommand.php
class BackfillApprovalSubjectsCommand extends Command
{
    protected $signature = 'approval:backfill-subjects {type} {--dry-run}';
    protected $description = 'Tạo ApprovalSubject còn thiếu cho 1 subject_type khai báo trong config/approval.php, trên TẤT CẢ organization';

    /**
     * BẮT BUỘC loop qua từng Organization và TenantContext::set() trước khi query — Product
     * (và mọi model dùng BelongsToOrganization) có global scope OrganizationScope, mà khi
     * TenantContext CHƯA set (đúng trạng thái mặc định của 1 tiến trình artisan console, không
     * đi qua middleware IdentifyOrganization) sẽ tự áp `whereRaw('0=1')` — failsafe chống rò rỉ
     * dữ liệu chéo tổ chức. Thiếu bước này, lệnh LUÔN báo "0 bản ghi" bất kể DB có bao nhiêu
     * entity thật — bug thật phát hiện khi kiểm tra vì sao dữ liệu demo có sẵn chưa từng được
     * backfill dù chạy lệnh "thành công" nhiều lần trước đó (§17.9).
     */
    public function handle(): void
    {
        $type = $this->argument('type');
        $config = config("approval.subjects.{$type}") ?? throw new InvalidArgumentException("Subject type \"{$type}\" chưa khai báo trong config/approval.php");

        $modelClass = $config['model'];
        $resolver   = $config['initial_status_resolver'] ?? null; // FQCN implements ResolvesInitialApprovalStatus, tuỳ chọn
        $dryRun     = (bool) $this->option('dry-run');
        $totalCount = 0;

        foreach (Organization::all() as $organization) {
            TenantContext::set($organization);
            $orgCount = 0;

            $modelClass::query()->whereDoesntHave('approvalSubject')
                ->chunkById(200, function ($entities) use (&$orgCount, $dryRun, $type, $resolver, $organization) {
                    foreach ($entities as $entity) {
                        $status = $resolver ? $resolver::resolve($entity) : ApprovalStatus::Draft;

                        // Nếu coi entity cũ là "đã publish", PHẢI set luôn public_snapshot ngay
                        // bằng nội dung hiện tại — thiếu bước này, isPubliclyVisible()/
                        // scopePubliclyVisible() (§7.1) sẽ loại toàn bộ dữ liệu cũ khỏi cổng
                        // thông tin dù status=published, vì tiêu chí hiển thị dựa vào
                        // public_snapshot chứ không phải status (§1).
                        $snapshot = $status === ApprovalStatus::Published
                            ? collect($entity->approvalWatchedAttributes())->mapWithKeys(fn ($a) => [$a => $entity->getAttribute($a)])->all()
                            : null;

                        if ($dryRun) {
                            $this->line("[dry-run] org #{$organization->id} {$type} #{$entity->id} → status={$status->value}, snapshot=" . ($snapshot ? 'có' : 'không'));
                            $orgCount++;
                            continue;
                        }

                        ApprovalSubject::create([
                            'subject_type'     => $entity->getMorphClass(),
                            'subject_id'       => $entity->id,
                            'organization_id'  => $entity->organization_id,
                            'status'           => $status,
                            'public_snapshot'  => $snapshot,
                        ]);
                        $orgCount++;
                    }
                });

            $totalCount += $orgCount;
        }

        TenantContext::flush();

        $this->info(($dryRun ? '[dry-run] ' : '') . "Đã xử lý {$totalCount} bản ghi \"{$type}\" trên tất cả organization.");
    }
}
```

`ResolvesInitialApprovalStatus` là interface tối giản (`public static function resolve(Model $entity): ApprovalStatus;`) để module tiêu thụ tự map trạng thái cũ sang `ApprovalStatus` ban đầu (vd Product: `ProductStatus::Active/OutOfStock/Discontinued` → `Published`, `Inactive` → `Draft` — logic này thuộc về `Modules/Product`, không hard-code trong `Approval`). Dùng FQCN class-string (không phải Closure) trong config để an toàn khi `config:cache`.

> **Xác nhận idempotent sau khi backfill:** sau khi chạy `php artisan approval:backfill-subjects product` thật, chạy lại đúng lệnh đó (không `--dry-run`) một lần nữa — nhờ `whereDoesntHave('approvalSubject')`, lần chạy thứ 2 phải in ra **`Đã xử lý 0 bản ghi`**. Nếu ra số khác 0, có bug ở điều kiện lọc hoặc có race condition giữa các lần chạy (vd chạy song song 2 tiến trình) — không nên coi backfill là "chạy 1 lần cho chắc" mà bỏ qua bước xác nhận này, vì đây là cách rẻ nhất để phát hiện sai sót trước khi đưa dữ liệu thật vào production.

> **Index cho `public_snapshot` (JSON) — cân nhắc, không làm ngay:** cột JSON không tự có index hữu ích cho `whereNotNull('public_snapshot')`, và cách index JSON khác nhau giữa SQLite (dev) và engine production (MySQL/Postgres tuỳ cấu hình — CLAUDE.md: "SQLite (dev) / configurable for production"), nên **không cam kết 1 cách index cụ thể ở đây**. Áp dụng đúng nguyên tắc "chỉ tối ưu khi đo được nghẽn thật" (§17.3): nếu sau này `whereNotNull('public_snapshot')` (dùng trong `scopePubliclyVisible()`, §7.1) chậm ở quy mô lớn, giải pháp ưu tiên là thêm 1 cột boolean denormalize (vd `has_snapshot`, set `true` cùng lúc `PublishAction` ghi `public_snapshot`) và index cột boolean đó — portable trên mọi DB engine, thay vì cố index trực tiếp lên JSON.

---

## 5. Config

```php
// Modules/Approval/config/approval.php
return [
    'subjects' => [
        'product' => [
            'model' => \Modules\Product\Models\Product::class,
            'label' => 'Sản phẩm',
            // Optional — mặc định null nghĩa là mọi bản ghi cũ được backfill với
            // status=Draft, public_snapshot=null (an toàn nhất: coi như CHƯA từng công khai,
            // không tự ý coi dữ liệu cũ là "đã duyệt"). Chỉ khai báo FQCN ở đây (§4.1) khi
            // thật sự cần map trạng thái cũ sang Published để không làm gián đoạn dữ liệu đang
            // hiển thị (vd Product đã bán bình thường trước khi có Approval).
            'initial_status_resolver' => null,
        ],
        // thêm entity mới tại đây khi áp dụng HasApproval
    ],
];
```

Dùng để:
1. Build morph map trong `ApprovalServiceProvider::boot()` (không hard-code danh sách trong 2 nơi):
   ```php
   Relation::morphMap(
       collect(config('approval.subjects'))->map(fn ($s) => $s['model'])->all(),
       merge: true,
   );
   ```
   **Sửa lại so với v3.4 (phát hiện khi triển khai Phase 1, quan trọng):** dùng `morphMap()`, **KHÔNG** dùng `enforceMorphMap()`/`requireMorphMap()` như các bản trước ghi. `enforceMorphMap()` bật cờ **toàn cục** buộc MỌI model dùng trong BẤT KỲ quan hệ polymorphic nào của cả ứng dụng phải nằm trong morph map, nếu không `Model::getMorphClass()` ném `ClassMorphViolationException` — áp dụng cho cả Spatie Activitylog (`subject`/`causer` trên `TenantAwareModel`), Spatie Permission (`model_has_roles`/`model_has_permissions`), Laravel Notifications (`notifiable_type`)… không chỉ riêng `ApprovalSubject.subject`. Một module con không có thẩm quyền bật cờ toàn app đó. `morphMap()` (không `enforce`) chỉ đăng ký alias đẹp cho các model khai báo trong `config/approval.php`, không ảnh hưởng gì tới model khác — đúng nhu cầu thực tế (tránh lưu FQCN thô trong cột `subject_type`) mà không có tác dụng phụ toàn cục.
2. `ApprovalDashboardService` lặp qua danh sách này để gom pending items theo từng loại (§10).
3. `approval:backfill-subjects {type}` đọc `model`/`initial_status_resolver`.

---

## 6. Enum

```php
// Modules/Approval/app/Enums/ApprovalStatus.php
namespace Modules\Approval\Enums;

enum ApprovalStatus: string
{
    case Draft     = 'draft';
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Pending   => 'Chờ duyệt',
            self::Approved  => 'Đã duyệt',
            self::Published => 'Đã xuất bản',
            self::Archived  => 'Lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-ghost',
            self::Pending   => 'badge-warning',
            self::Approved  => 'badge-info',
            self::Published => 'badge-success',
            self::Archived  => 'badge-neutral',
        };
    }

    /**
     * Transition hợp lệ — validate ở tầng Action, KHÔNG chỉ ở UI (theo TranslationStatus).
     * `Approved → Pending` và `Published → Pending` KHÔNG phải do người dùng bấm nút — đây là
     * transition tự động khi nội dung bị sửa sau khi đã duyệt/đã lên cổng thông tin (§7.1,
     * §8.4): nội dung đã qua duyệt/đã live mà bị đổi thì phải chờ duyệt lại, không được coi là
     * còn hiệu lực nữa. Draft/Pending sửa nội dung thì không cần transition gì (chưa từng qua
     * duyệt/đang chờ duyệt sẵn rồi).
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft     => $target === self::Pending,
            self::Pending   => in_array($target, [self::Approved, self::Draft], true), // reject → Draft
            self::Approved  => in_array($target, [self::Published, self::Pending], true),
            self::Published => in_array($target, [self::Archived, self::Pending], true),
            self::Archived  => false,
        };
    }
}
```

---

## 7. Models & Trait

```php
// Modules/Approval/app/Models/ApprovalSubject.php
class ApprovalSubject extends Model
{
    use SoftDeletes, BelongsToOrganization;

    protected $casts = [
        'status'          => ApprovalStatus::class,
        'approved_at'     => 'datetime',
        'public_snapshot' => 'array',
    ];

    public function subject(): MorphTo { return $this->morphTo(); }
    public function logs(): HasMany { return $this->hasMany(ApprovalLog::class); }
}

// Modules/Approval/app/Models/ApprovalLog.php — append-only, giống PostPublishingLog
class ApprovalLog extends Model
{
    use BelongsToOrganization;
    const UPDATED_AT = null;
    protected $fillable = ['organization_id', 'approval_subject_id', 'action', 'from_status', 'to_status', 'reason', 'performed_by'];
}
```

### 7.1 `HasApproval` — đầy đủ helper + auto-tạo subject + tự động chuyển Pending khi nội dung đổi

```php
// Modules/Approval/app/Concerns/HasApproval.php
namespace Modules\Approval\Concerns;

trait HasApproval
{
    /**
     * Tự tạo ApprovalSubject 'draft' ngay khi entity được tạo — dùng hook boot{Trait}
     * chuẩn của Eloquent (giống cách SoftDeletes/HasFactory tự đăng ký), KHÔNG cần entity
     * tự gọi tay. Chạy trong event `created` (sau `creating`) nên organization_id đã được
     * BelongsToOrganization gán xong (TenantAwareModel áp dụng cho mọi domain model).
     *
     * `updating` (KHÔNG phải `updated`) — TỰ ĐỘNG gọi ReviseContentAction mỗi khi 1 trường
     * "nội dung" (khai báo ở approvalWatchedAttributes()) SẮP thay đổi, KHÔNG chờ module tiêu
     * thụ tự nhớ gọi. Đây là điểm mấu chốt để đảm bảo đúng bản chất nghiệp vụ (§1): nội dung đã
     * duyệt/đã live mà bị sửa PHẢI được đánh dấu "có bản chờ duyệt" — nhưng KHÔNG ảnh hưởng tới
     * cái đang hiển thị công khai (đó là việc của `public_snapshot`, không phải của cột status).
     *
     * CỐ Ý dùng `updating` (trước khi UPDATE chạy) + `isDirty()`, KHÔNG dùng `updated` (sau khi
     * UPDATE đã chạy) + `wasChanged()` như thiết kế ban đầu — bug thật phát hiện khi người dùng
     * hỏi vì sao sửa nội dung sản phẩm Archived lại báo lỗi (§17.11): nếu ném exception SAU khi
     * UPDATE đã chạy (`updated`), câu UPDATE đó ĐÃ TỰ COMMIT rồi — nội dung "read-only" thực ra
     * đã bị ghi vào DB âm thầm, chỉ có cột status của ApprovalSubject là không đổi (rơi vào
     * trạng thái nửa vời, sai hoàn toàn với ý định "Archived = không sửa được nội dung"). Ném
     * exception ở `updating` (trước khi UPDATE chạy) khiến toàn bộ `save()` của entity bị huỷ
     * ngay từ đầu — không có gì được ghi, kể cả field không liên quan đang nằm chung 1 form.
     */
    public static function bootHasApproval(): void
    {
        static::created(function (Model $model): void {
            $model->ensureApprovalSubject();
        });

        static::updating(function (Model $model): void {
            $watched = $model->approvalWatchedAttributes();

            if ($model->isDirty($watched)) {
                app(\Modules\Approval\Actions\ReviseContentAction::class)->handle($model);
            }
        });
    }

    public function approvalSubject(): MorphOne
    {
        return $this->morphOne(ApprovalSubject::class, 'subject');
    }

    /**
     * KHÔNG viết gọn `$this->approvalSubject ?? $this->approvalSubject()->create(...)` — truy
     * cập `$this->approvalSubject` (vế trái) khiến Eloquent cache luôn kết quả null lên
     * `$relations['approvalSubject']` khi chưa có subject; nếu chỉ trả về giá trị vừa tạo mà
     * không gọi setRelation(), các lần đọc `$model->approvalSubject` sau đó trong CÙNG
     * request/instance vẫn thấy null dù DB đã có bản ghi — bug thật phát hiện khi viết smoke
     * test cho Phase 1, sửa bằng cách kiểm tra tường minh rồi tự cache lại.
     */
    public function ensureApprovalSubject(): ApprovalSubject
    {
        if ($this->approvalSubject) {
            return $this->approvalSubject;
        }

        $created = $this->approvalSubject()->create([
            'organization_id' => $this->organization_id,
            'status'          => ApprovalStatus::Draft,
        ]);

        $this->setRelation('approvalSubject', $created);

        return $created;
    }

    /**
     * Danh sách trường được coi là "nội dung" cần duyệt lại khi đổi (vd name/description/ảnh),
     * KHÔNG bao gồm trường vận hành/kinh doanh (giá, tồn kho…) — §2.3, §9. Entity dùng
     * HasApproval PHẢI override method này (contract bắt buộc, không có default an toàn ngầm
     * dùng getFillable() — trộn cả trường kinh doanh vào sẽ vô tình bắt duyệt lại những thay
     * đổi không liên quan tới nội dung công khai, gây phiền và sai bản chất nghiệp vụ ở §1).
     */
    abstract public function approvalWatchedAttributes(): array;

    public function approvalStatus(): ?ApprovalStatus
    {
        return $this->approvalSubject?->status;
    }

    public function isApprovalDraft(): bool     { return $this->approvalStatus() === ApprovalStatus::Draft; }
    public function isApprovalPending(): bool   { return $this->approvalStatus() === ApprovalStatus::Pending; }
    public function isApproved(): bool          { return $this->approvalStatus() === ApprovalStatus::Approved; }
    public function isApprovalPublished(): bool { return $this->approvalStatus() === ApprovalStatus::Published; }
    public function isApprovalArchived(): bool  { return $this->approvalStatus() === ApprovalStatus::Archived; }

    /**
     * Lịch sử duyệt (submit/approve/reject/publish/archive/revise) của entity này, mới nhất
     * trước — tiện dùng trực tiếp trong Blade (vd tab "Lịch sử duyệt" trên trang edit) mà
     * không cần tự viết `$product->approvalSubject?->logs()->latest('id')->get()` mỗi nơi. Cố
     * ý trả `Collection` (không phải 1 relation query builder qua `hasManyThrough` xuyên bảng
     * polymorphic — phức tạp và dễ sai hơn cần thiết) vì `ApprovalLog` luôn truy vấn được gọn
     * qua quan hệ `approvalSubject` có sẵn. Sắp xếp theo `id` (không phải `created_at`) — phát
     * hiện khi viết smoke test Phase 2: nhiều transition liên tiếp trong cùng 1 giây (test,
     * script, hoặc double-click nhanh) khiến `created_at` cấp độ giây không đủ phân biệt thứ
     * tự; `id` tăng dần tuyệt đối nên luôn đúng và ổn định.
     */
    public function approvalLogs(): \Illuminate\Support\Collection
    {
        return $this->approvalSubject?->logs()->latest('id')->get() ?? collect();
    }

    /**
     * Entity có được coi là "đã từng công khai" hay không — tiêu chí là public_snapshot khác
     * null (đã Publish ít nhất 1 lần) VÀ chưa Archived, KHÔNG dựa vào status hiện tại. Một
     * entity đang ở status=pending (vì vừa bị sửa nội dung) vẫn PHẢI trả về true ở đây — đó
     * chính là điểm cốt lõi của v3.2 (§1): còn bản đã duyệt để hiển thị, dù đang có bản sửa
     * chờ duyệt song song.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->approvalSubject
            && $this->approvalSubject->public_snapshot !== null
            && $this->approvalSubject->status !== ApprovalStatus::Archived;
    }

    /**
     * Scope dùng ở MỌI query của cổng thông tin công khai cho entity có HasApproval — cùng
     * tiêu chí với isPubliclyVisible(), viết dạng query để lọc được ở tầng DB thay vì load hết
     * rồi filter bằng PHP. KHÔNG lọc theo status=published (sai — xem §1, §17.4). Không tự
     * trộn logic này vào query nội bộ/CMS (nơi biên tập viên cần thấy cả Draft/Pending của
     * chính họ, đọc trực tiếp cột hiện tại chứ không qua snapshot).
     */
    public function scopePubliclyVisible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('approvalSubject', function ($q) {
            $q->whereNotNull('public_snapshot')->where('status', '!=', ApprovalStatus::Archived);
        });
    }

    /**
     * Dữ liệu THỰC SỰ nên hiển thị ra cổng thông tin công khai: các trường "nội dung" (khai
     * báo ở approvalWatchedAttributes()) lấy từ public_snapshot (bản đã duyệt, đóng băng —
     * không phải bản đang chỉnh sửa dở trên chính entity); mọi trường KHÁC (giá, tồn kho…) lấy
     * trực tiếp từ entity vì luôn hiệu lực ngay, không thuộc phạm vi gate của Approval (§2.3).
     * Trả về mảng (không phải Model) — Blade/API layer của module tiêu thụ tự build response
     * cuối cùng từ mảng này, KHÔNG đọc thẳng $entity->name khi hiển thị công khai.
     *
     * Chỉ là `array_merge` trong bộ nhớ (không query DB thêm vì `approvalSubject` thường đã
     * eager-load sẵn từ query danh sách, §9.6) — đủ rẻ cho phần lớn trường hợp, KHÔNG cần cache
     * ở mức entity riêng lẻ. Nếu 1 trang public gọi `publicContent()` cho hàng nghìn bản ghi
     * trong 1 request (listing lớn) và profiling cho thấy đây là điểm nghẽn thật, cache theo
     * response/fragment (Blade `@cache` hoặc HTTP cache ở tầng CDN) — KHÔNG cache ở mức
     * `Cache::remember()` từng entity một trong hàm này (thêm round-trip cache còn chậm hơn
     * chính `array_merge`), theo đúng nguyên tắc "chỉ tối ưu khi đo được nghẽn thật" (§17.3).
     */
    public function publicContent(): array
    {
        $snapshot = $this->approvalSubject?->public_snapshot ?? [];

        return array_merge($this->attributesToArray(), $snapshot);
    }
}
```

> **Lưu ý khi backfill (§4.1):** `bootHasApproval()` chỉ bắt sự kiện `created`/`updated` **kể từ lúc thêm trait trở đi** — entity **đã tồn tại trước khi thêm trait** sẽ không tự có `ApprovalSubject`, và quan trọng hơn — sẽ **không có `public_snapshot`**, nên `isPubliclyVisible()`/`scopePubliclyVisible()` sẽ trả `false`/loại bỏ toàn bộ dữ liệu cũ khỏi cổng thông tin cho tới khi backfill chạy xong. Command `approval:backfill-subjects` (§4.1) vì vậy PHẢI set luôn `public_snapshot` ban đầu (snapshot chính nội dung hiện tại của entity tại thời điểm backfill) cho các bản ghi được coi là đã "Published" — không chỉ set `status`, xem `initial_status_resolver` cập nhật ở §9.6.

Đăng ký morph map trong `ApprovalServiceProvider::boot()` — xem §5.

---

## 8. Actions

Theo đúng cấu trúc `Modules/Post/app/Features/ArticleAuthoring/Actions/` — mỗi transition 1 Action (`AsAction`), gọi qua `$action->handle(...)`, **không tự check quyền** (Controller của module tiêu thụ tự `$this->authorize()` trước khi gọi — xem §9).

### 8.1 `Concerns\LogsApprovalActions` — chi tiết hoá cách ghi `from_status`/`to_status`

```php
// Modules/Approval/app/Actions/Concerns/LogsApprovalActions.php
trait LogsApprovalActions
{
    /**
     * Khoá row ApprovalSubject (SELECT ... FOR UPDATE) trong 1 transaction để tránh
     * race condition: 2 request đồng thời (vd double-click "Duyệt") có thể cùng đọc
     * status hiện tại là 'pending', cùng pass canTransitionTo(), rồi cùng ghi log —
     * ra 2 dòng ApprovalLog cho cùng 1 transition thật. lockForUpdate() đảm bảo request
     * thứ 2 phải chờ request 1 commit xong, đọc lại status MỚI, và tự fail đúng
     * InvalidTransitionException thay vì log trùng (xem §17.1).
     *
     * $parent (entity dùng HasApproval, vd Product) BẮT BUỘC truyền vào để đồng bộ lại cache
     * quan hệ `approvalSubject` trên chính nó sau transition — bug thật phát hiện khi seed demo
     * data nhiều bước liên tiếp trên CÙNG 1 object (§17.8): `transition()` luôn thao tác trên 1
     * bản ApprovalSubject fetch RIÊNG (`$locked`, để lockForUpdate() đúng), KHÔNG phải object
     * được truyền vào; nếu không gọi $parent->setRelation() ở đây, `$parent->approvalSubject`
     * (đã cache từ HasApproval::bootHasApproval() lúc entity vừa tạo) sẽ MÃI MÃI là bản cũ
     * trong suốt vòng đời của object đó — khiến ensureApprovalSubject()/ReviseContentAction đọc
     * nhầm status cũ ở transition kế tiếp trên cùng object.
     */
    private function transition(Model $parent, ApprovalSubject $subject, ApprovalStatus $to, string $action, ?string $reason = null, array $extraAttributes = []): ApprovalSubject
    {
        return DB::transaction(function () use ($parent, $subject, $to, $action, $reason, $extraAttributes) {
            /** @var ApprovalSubject $locked */
            $locked = ApprovalSubject::whereKey($subject->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($to)) {
                throw new InvalidTransitionException($locked->status->value, $to->value);
            }

            $from = $locked->status;
            $locked->update(array_merge(['status' => $to], $extraAttributes));

            ApprovalLog::create([
                'organization_id'     => $locked->organization_id,
                'approval_subject_id' => $locked->id,
                'action'              => $action,
                'from_status'         => $from->value,
                'to_status'           => $to->value,
                'reason'              => $reason,
                'performed_by'        => auth()->id(), // null nếu chạy từ job/command hệ thống
            ]);

            $parent->setRelation('approvalSubject', $locked);

            return $locked;
        });
    }
}
```

### 8.2 Ví dụ đầy đủ — `SubmitForApprovalAction`, `ApproveAction`, `PublishAction`, `ArchiveAction`

```php
class SubmitForApprovalAction
{
    use AsAction, LogsApprovalActions;

    public function handle(Model $subject): ApprovalSubject
    {
        return $this->transition($subject, $subject->ensureApprovalSubject(), ApprovalStatus::Pending, 'submit');
    }
}

class ApproveAction
{
    use AsAction, LogsApprovalActions;

    public function handle(Model $subject): ApprovalSubject
    {
        return $this->transition(
            $subject,
            $subject->approvalSubject,
            ApprovalStatus::Approved,
            'approve',
            extraAttributes: ['approved_by' => auth()->id(), 'approved_at' => now()],
        );
    }
}

class PublishAction
{
    use AsAction, LogsApprovalActions;

    /**
     * Đây là NƠI DUY NHẤT ghi public_snapshot (§3, §4) — chụp lại đúng giá trị hiện tại của
     * các trường approvalWatchedAttributes() và đóng băng vào ApprovalSubject. Từ thời điểm
     * này, cổng thông tin công khai hiển thị đúng bản vừa chụp cho tới lần PublishAction tiếp
     * theo (dù entity có bị sửa tiếp — ReviseContentAction §8.4 không đụng vào snapshot này).
     */
    public function handle(Model $subject): ApprovalSubject
    {
        $snapshot = collect($subject->approvalWatchedAttributes())
            ->mapWithKeys(fn (string $attribute) => [$attribute => $subject->getAttribute($attribute)])
            ->all();

        return $this->transition(
            $subject,
            $subject->approvalSubject,
            ApprovalStatus::Published,
            'publish',
            extraAttributes: ['public_snapshot' => $snapshot],
        );
    }
}

class ArchiveAction
{
    use AsAction, LogsApprovalActions;

    public function handle(Model $subject): ApprovalSubject
    {
        return $this->transition($subject, $subject->approvalSubject, ApprovalStatus::Archived, 'archive');
    }
}
```

### 8.3 Ví dụ đầy đủ — `RejectAction` (bắt buộc `reason`, theo mẫu `TakeDownArticleTranslationAction`)

```php
// Modules/Approval/app/Actions/RejectAction.php
namespace Modules\Approval\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\Concerns\LogsApprovalActions;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

class RejectAction
{
    use AsAction, LogsApprovalActions;

    /**
     * $reason bắt buộc — Controller của module tiêu thụ nên validate lại qua
     * $request->validate(['reason' => ['required','string','min:10']]) TRƯỚC khi gọi Action
     * (đúng như TranslationController::unpublish/takedown), nhưng Action vẫn tự validate lại
     * ở đây (defense-in-depth — Action cũng có thể được gọi từ artisan tinker/queue job không
     * qua Controller).
     */
    public function handle(Model $subject, string $reason): ApprovalSubject
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:10']])->validate();

        return $this->transition($subject, $subject->approvalSubject, ApprovalStatus::Draft, 'reject', $reason);
    }
}
```

### 8.4 `ReviseContentAction` — được `HasApproval::bootHasApproval()` tự gọi, không gọi tay từ Controller

```php
// Modules/Approval/app/Actions/ReviseContentAction.php
namespace Modules\Approval\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\Concerns\LogsApprovalActions;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

/**
 * Chạy tự động (§7.1) mỗi khi 1 trường trong approvalWatchedAttributes() đổi. KHÔNG map trực
 * tiếp tới 1 nút bấm nào trên UI — đây là phản ứng hệ thống, không phải hành động người dùng
 * chủ động chọn (khác 5 Action còn lại).
 */
class ReviseContentAction
{
    use AsAction, LogsApprovalActions;

    public function handle(Model $subject): ApprovalSubject
    {
        $approval = $subject->ensureApprovalSubject();

        return match ($approval->status) {
            // Nội dung đã qua duyệt (Approved) hoặc đã live (Published) mà bị sửa → đánh dấu
            // "đang có bản chờ duyệt" (status=Pending) để hiện lên dashboard/nút "Duyệt lại".
            // CỐ Ý không đụng tới public_snapshot ở đây — cổng thông tin vẫn tiếp tục hiển thị
            // đúng bản đã duyệt trước đó (isPubliclyVisible() không phụ thuộc status, §7.1),
            // chỉ khi PublishAction (§8.2) chạy lại thì snapshot mới đổi sang bản mới.
            ApprovalStatus::Approved, ApprovalStatus::Published
                => $this->transition($subject, $approval, ApprovalStatus::Pending, 'revise'),

            // Archived coi là read-only — sửa nội dung khi đã lưu trữ là bug ở tầng Policy của
            // module tiêu thụ (đáng lẽ phải chặn từ trước); ném exception ở đây như 1 lớp
            // phòng vệ thứ 2 (defense-in-depth), KHÔNG âm thầm bỏ qua.
            ApprovalStatus::Archived
                => throw new \Modules\Approval\Exceptions\InvalidTransitionException($approval->status->value, ApprovalStatus::Pending->value),

            // Draft: chưa từng submit, sửa thoải mái, không cần transition.
            // Pending: đang chờ duyệt sẵn rồi, sửa thêm không cần transition (không tạo log
            // 'revise' trùng lặp vô nghĩa).
            //
            // Lưu ý: CHỈ nhánh Approved/Published ở trên gọi transition() — đây là nơi DUY
            // NHẤT trong hàm này tạo ApprovalLog (action=revise). Nhánh `default` (Draft/
            // Pending) trả thẳng $approval không đổi gì; nhánh Archived throw exception TRƯỚC
            // khi có cơ hội gọi transition(). Vì vậy log 'revise' chỉ xuất hiện đúng lúc có 1
            // bản đã duyệt/đã live thật sự bị sửa — không bao giờ có log rác cho Draft/Pending.
            default => $approval,
        };
    }
}
```

`ApprovalLog.action` có thêm giá trị `revise` (cột `string(20)` ở §4 đã đủ chỗ, không cần đổi migration).

---

## 9. Tích hợp thực tế với `Product` (ví dụ áp dụng đầy đủ)

Đây là ví dụ minh hoạ **cách áp dụng** `HasApproval` cho entity thật đầu tiên — xem cảnh báo ở §2.3: đây là tính năng mới, cần xác nhận nhu cầu thật trước khi làm ở Phase 4.

### 9.1 Model

```php
// Modules/Product/app/Models/Product.php
use Modules\Approval\Concerns\HasApproval;

class Product extends TenantAwareModel
{
    use HasApproval;

    /**
     * Chỉ các trường NỘI DUNG hiển thị công khai — sửa 1 trong các trường này khi Product
     * đang Approved/Published sẽ tự động bị HasApproval::bootHasApproval() (§7.1) chuyển về
     * Pending và ẩn khỏi cổng thông tin cho tới khi duyệt lại. KHÔNG đưa price/status/
     * shopee_url/tiktok_url/sort_order… vào đây — đó là trục vận hành/kinh doanh, đổi giá hay
     * bật/tắt tồn kho không cần (và không nên) chờ duyệt lại nội dung (§1, §2.3).
     */
    public function approvalWatchedAttributes(): array
    {
        return ['name', 'short_description', 'description', 'cover_image_url'];
    }

    // ... giữ nguyên phần còn lại — ProductStatus KHÔNG đổi, 2 trục độc lập (§2.3)
}
```

`UpdateProductAction` hiện có (`Modules/Product/app/Features/CatalogManagement/Actions/UpdateProductAction.php`) **không cần sửa gì** — `$product->update($data)` tự kích hoạt `bootHasApproval()`'s `updated` hook, tự gọi `ReviseContentAction` nếu có trường theo dõi thay đổi. Đây chính là lý do thiết kế tự động ở §7.1/§8.4 thay vì bắt Controller/Action gọi tay.

### 9.2 Permission mới cần thêm (thuộc về `Product`, không phải `Approval`)

`ProductPolicy` hiện chỉ có `view/create/edit/delete` (`product.view/create/edit/delete`). Cần thêm permission mới **trong `Modules/Product`** — `Approval` không cung cấp permission:

```php
// app/Enums/PermissionEnum.php — thêm case mới
case PRODUCT_PUBLISH = 'product.publish';
```

Seed vào `ProductPermissionSeeder` (tạo mới, theo mẫu `PostPermissionSeeder`) — gán `product.publish` cho `ceo`/`ops`, tương tự cách `post_article.publish` được gán.

### 9.3 `ProductPolicy` — bổ sung method cho từng transition

```php
class ProductPolicy
{
    // ... giữ nguyên viewAny/view/create/update/delete

    public function submitForApproval(User $user, Product $product): bool
    {
        return $user->can('product.edit');
    }

    public function approve(User $user, Product $product): bool
    {
        return $user->can('product.publish');
    }

    public function reject(User $user, Product $product): bool
    {
        return $user->can('product.publish');
    }

    public function publishApproval(User $user, Product $product): bool
    {
        return $user->can('product.publish');
    }

    public function archiveApproval(User $user, Product $product): bool
    {
        return $user->can('product.publish');
    }
}
```

> Đặt tên `publishApproval`/`archiveApproval` (không phải `publish`/`archive` trần) để tránh trùng ý nghĩa với các ability khác có thể thêm sau này trên Product (vd nếu Product có riêng khái niệm "publish lên kênh bán hàng" độc lập với approval).

### 9.4 `ProductAdminController` — route mới + authorize

```php
public function submitApproval(Product $product, SubmitForApprovalAction $action): RedirectResponse
{
    $this->authorize('submitForApproval', $product);
    return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã gửi sản phẩm để chờ duyệt.');
}

public function approveContent(Product $product, ApproveAction $action): RedirectResponse
{
    $this->authorize('approve', $product);
    return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã duyệt nội dung sản phẩm.');
}

public function rejectContent(Request $request, Product $product, RejectAction $action): RedirectResponse
{
    $this->authorize('reject', $product);
    $reason = $request->validate(['reason' => ['required', 'string', 'min:10']])['reason'];
    return $this->runApprovalTransition($product, fn () => $action->handle($product, $reason), 'Đã từ chối duyệt.');
}

public function publishContent(Product $product, PublishAction $action): RedirectResponse
{
    $this->authorize('publishApproval', $product);
    return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã xuất bản nội dung sản phẩm.');
}

public function archiveContent(Product $product, ArchiveAction $action): RedirectResponse
{
    $this->authorize('archiveApproval', $product);
    return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã lưu trữ sản phẩm.');
}

private function runApprovalTransition(Product $product, \Closure $callback, string $successMessage): RedirectResponse
{
    try {
        $callback();
    } catch (InvalidTransitionException $e) {
        return back()->with('error', $e->getMessage());
    }
    return back()->with('success', $successMessage);
}
```

```php
// Modules/Product/routes/web.php — thêm vào group hiện có
Route::post('{product}/submit-approval', [ProductAdminController::class, 'submitApproval'])->name('submit-approval');
Route::post('{product}/approve-content', [ProductAdminController::class, 'approveContent'])->name('approve-content');
Route::post('{product}/reject-content', [ProductAdminController::class, 'rejectContent'])->name('reject-content');
Route::post('{product}/publish-content', [ProductAdminController::class, 'publishContent'])->name('publish-content');
Route::post('{product}/archive-content', [ProductAdminController::class, 'archiveContent'])->name('archive-content');
```

### 9.5 Blade — 2 badge độc lập + nút transition

```blade
{{-- Modules/Product/resources/views/admin/products/edit.blade.php --}}
<div class="flex gap-2">
    <span class="badge {{ $product->status->badgeClass() }}">{{ $product->status->label() }}</span>
    @if ($product->approvalStatus())
        <span class="badge {{ $product->approvalStatus()->badgeClass() }}">
            Duyệt: {{ $product->approvalStatus()->label() }}
        </span>
    @endif
</div>

<div class="flex gap-2 mt-2">
    @if ($product->isApprovalDraft() && auth()->user()->can('submitForApproval', $product))
        <form method="POST" action="{{ route('backend.products.submit-approval', $product) }}">
            @csrf
            <button class="btn btn-sm btn-warning">Gửi duyệt</button>
        </form>
    @endif

    @if ($product->isApprovalPending() && auth()->user()->can('approve', $product))
        <form method="POST" action="{{ route('backend.products.approve-content', $product) }}">
            @csrf
            <button class="btn btn-sm btn-info">Duyệt</button>
        </form>
    @endif
    {{-- reject/publish/archive tương tự — ẩn theo canTransitionTo() + @can --}}
</div>
```

### 9.6 Query & render cổng thông tin công khai — bắt buộc dùng `publiclyVisible()` + `publicContent()`

Hiện `Product` chưa có trang public riêng (`ProductPickerApiController` chỉ phục vụ CMS chọn sản phẩm khi soạn bài, không phải cổng thông tin công khai). Khi module nào đó (Post, hoặc 1 trang catalog public tương lai) hiển thị `Product` ra ngoài, cần **2 bước**, không phải 1:

**1. Lọc danh sách** — `publiclyVisible()` (§7.1) cùng điều kiện `ProductStatus` sẵn có, 2 điều kiện độc lập AND với nhau:
```php
$products = Product::query()
    ->where('status', ProductStatus::Active) // trục vận hành: còn kinh doanh không
    ->publiclyVisible()                      // trục nội dung: đã từng publish & chưa archived
    ->get();
```

**2. Render nội dung** — với TỪNG product lấy được, dùng `publicContent()` (§7.1) thay vì đọc thẳng field, để đảm bảo trường nội dung lấy từ bản đã duyệt (`public_snapshot`), không phải bản đang chỉnh sửa dở nếu product đang `pending`:
```php
@foreach ($products as $product)
    @php($content = $product->publicContent())
    <h3>{{ $content['name'] }}</h3>
    <p>{{ $content['short_description'] }}</p>
    <span>{{ $product->display_price }}</span> {{-- giá KHÔNG qua publicContent(), luôn đọc trực tiếp --}}
@endforeach
```

Thiếu bước 1 (`publiclyVisible()`) = hiện cả sản phẩm chưa từng publish. Thiếu bước 2 (`publicContent()`, đọc thẳng `$product->name`) = **rò rỉ bản nháp đang chờ duyệt ra công khai ngay khi vừa sửa** — đúng lỗ hổng cốt lõi module này sinh ra để chặn (§1). Nên thêm test riêng khẳng định mọi query + render public hiện có của các entity dùng `HasApproval` đều tuân thủ đúng cả 2 bước (§16).

### 9.7 Backfill khi tích hợp

Chạy ngay sau khi merge (Product đã có dữ liệu sẵn):
```bash
php artisan approval:backfill-subjects product --dry-run   # soát trước
php artisan approval:backfill-subjects product             # chạy thật
```
Resolver gợi ý (`Modules/Product/app/Support/ProductInitialApprovalStatusResolver.php`):
```php
class ProductInitialApprovalStatusResolver implements ResolvesInitialApprovalStatus
{
    public static function resolve(Model $entity): ApprovalStatus
    {
        /** @var Product $entity */
        return match ($entity->status) {
            ProductStatus::Active, ProductStatus::OutOfStock, ProductStatus::Discontinued => ApprovalStatus::Published,
            ProductStatus::Inactive => ApprovalStatus::Draft,
        };
    }
}
```
Khai báo trong `config/approval.php`: `'initial_status_resolver' => \Modules\Product\Support\ProductInitialApprovalStatusResolver::class`. Mục đích: sản phẩm đang bán bình thường (Active/OutOfStock/Discontinued) không bị coi là "chưa duyệt" sau khi bật tính năng — tránh gãy catalog hiện có.

### 9.8 Badge duyệt nội dung trên trang danh sách (`index.blade.php`) — bắt buộc eager-load

Trang danh sách Product (`ProductAdminController::index()`) hiển thị NHIỀU sản phẩm cùng lúc — khác trang edit (chỉ 1 sản phẩm/request). Thêm cột badge duyệt nội dung vào đây đòi hỏi sửa **2 chỗ**, không chỉ Blade:

**1. Eager-load `approvalSubject` trong query** (`Modules/Product/app/Features/CatalogManagement/Queries/ListProductsForAdminHandler.php`):
```php
$q = Product::query()->with(['category:id,name', 'approvalSubject']);
```
Thiếu dòng này sẽ ném `Illuminate\Database\LazyLoadingViolationException` ngay khi danh sách có **≥ 2 sản phẩm** và Blade gọi `$p->approvalStatus()` cho từng dòng — Eloquent strict mode (`Model::shouldBeStrict()`, bật khi không phải production, `app/Providers/AppServiceProvider.php`) chặn lazy-load relation trên collection nhiều phần tử để bắt N+1, nhưng **không** chặn khi chỉ có 1 model (`->first()`/route-model-binding) — đây là lý do trang edit (§9.5) không cần sửa gì mà vẫn chạy đúng, còn trang danh sách thì bắt buộc phải eager-load tường minh.

**2. Thêm cột badge trong Blade** (`Modules/Product/resources/views/admin/products/index.blade.php`), độc lập với cột "Trạng thái" (kinh doanh) đã có:
```blade
<td class="text-center">
    @if ($p->approvalStatus())
        <span class="badge badge-sm {{ $p->approvalStatus()->badgeClass() }}">
            {{ $p->approvalStatus()->label() }}
        </span>
    @else
        <span class="text-base-content/30 text-xs">—</span>
    @endif
</td>
```
`—` hiển thị cho sản phẩm chưa có `ApprovalSubject` (chưa chạy backfill, §9.7, §17.9) — không lỗi, chỉ thiếu dữ liệu.

---

## 10. Notification — recipient do module tiêu thụ quyết định

`Approval` cung cấp **class notification chung**, nhưng — giống hệt cách `TakeDownArticleTranslationAction` tự chọn recipient bằng domain logic của Post (`User::role(['ceo','ai_operator'])->get()`) — **việc chọn ai nhận notification là domain-specific, không thể generic hoá trong `Approval`**. Action generic (`SubmitForApprovalAction`…) không tự gửi notification; module tiêu thụ tự gọi sau khi transition thành công.

```php
// Modules/Approval/app/Notifications/ApprovalStatusChangedNotification.php
class ApprovalStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly Model $subject,
        private readonly ApprovalStatus $to,
        private readonly string $subjectLabel,  // vd "Sản phẩm \"Áo thun ABC\""
        private readonly string $url,
    ) {}

    protected function notificationType(): string { return 'approval_status_changed'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'approval_status_changed',
            title:    "{$this->subjectLabel} đã chuyển sang \"{$this->to->label()}\"",
            body:     "Trạng thái phê duyệt vừa được cập nhật.",
            url:      $this->url,
            icon:     'task',
            severity: $this->to === ApprovalStatus::Archived ? 'info' : 'success',
        );
    }
}
```

Ví dụ dùng trong `ProductAdminController::submitApproval` (recipient = user có `product.publish`, đúng domain logic thuộc về Product):
```php
DB::afterCommit(function () use ($product) {
    $recipients = User::where('organization_id', $product->organization_id)->permission('product.publish')->get();
    if ($recipients->isNotEmpty()) {
        Notification::send($recipients, new ApprovalStatusChangedNotification(
            $product, ApprovalStatus::Pending, "Sản phẩm \"{$product->name}\"", route('backend.products.edit', $product),
        ));
    }
});
```

Không tự triển khai kênh Reverb/broadcast thủ công — `RespectsNotificationPreferences::toBroadcast()` đã lo việc đó tự động theo tuỳ chọn user, như mọi notification khác trong hệ thống.

---

## 11. Policies & Permissions — tóm tắt nguyên tắc

`Approval` không seed permission cho từng transition entity (đó là việc của module tiêu thụ, xem ví dụ đầy đủ ở §9.2–9.3). Nhưng module có **2 permission của chính nó**, gate 2 UI xuyên-entity thuộc về `Approval`, seed trong `ApprovalPermissionSeeder`:

| Permission | Gate | Vai trò được cấp | Trang |
|---|---|---|---|
| `approval.view_dashboard` | `viewDashboard` | `ceo`, `ops`, `system_admin` | "Chờ duyệt của tôi" (§12) — chỉ pending item **user tự duyệt được** |
| `approval.view_history` | `viewApprovalHistory` | `ceo`, `system_admin` | "Lịch sử duyệt" (§12.1) — **toàn bộ** log, mọi entity, mọi trạng thái, dành cho giám sát |

`ops` chỉ có `view_dashboard` (việc hàng ngày), không có `view_history` (giám sát toàn hệ thống) — tách biệt có chủ đích, không gộp 2 quyền làm 1.

---

## 12. Dashboard "Chờ duyệt của tôi"

Thiết kế dựa trên `config('approval.subjects')` (§5) + Gate/Policy đã có của từng module tiêu thụ — **không cần** một registry/interface riêng cho mỗi module tự đăng ký cách check quyền (over-engineering khi mới có 1 entity — `Product`). Nếu về sau hiệu năng N+1 Gate-check trở thành vấn đề thật (nhiều nghìn pending item), mới cân nhắc thêm registry batch-check; §17.3 nêu rõ ngưỡng.

```php
// Modules/Approval/app/Services/ApprovalDashboardService.php
namespace Modules\Approval\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

class ApprovalDashboardService
{
    /**
     * @return Collection<int, ApprovalSubject> mỗi phần tử đã eager-load `subject`, nhóm sẵn
     *         theo `subject_type` để Controller/Blade group hiển thị (dùng `label` ở config).
     */
    public function pendingFor(User $user, int $organizationId): Collection
    {
        return collect(config('approval.subjects'))
            ->flatMap(function (array $config, string $type) use ($user, $organizationId) {
                return ApprovalSubject::query()
                    ->where('organization_id', $organizationId)
                    ->where('subject_type', (new $config['model'])->getMorphClass())
                    ->where('status', ApprovalStatus::Pending)
                    ->with('subject')
                    ->get()
                    ->filter(function (ApprovalSubject $s) use ($user) {
                        // Gate::forUser($user)->allows(...) thay vì $user->can(...) — tường
                        // minh rằng đây là 1 lần authorization độc lập cho TỪNG item, không
                        // liên quan tới user hiện tại đang đăng nhập trong request gốc (dùng
                        // được cả khi $user truyền vào khác auth()->user(), vd job/console).
                        // Ability 'approve' phải tồn tại trên Policy của từng subject_type
                        // (§9.3) — nếu Policy chưa có method này, Gate trả false an toàn.
                        return $s->subject && Gate::forUser($user)->allows('approve', $s->subject);
                    });
            })
            ->groupBy(fn (ApprovalSubject $s) => $s->subject_type)
            ->flatten(1);
    }
}
```

```php
// Modules/Approval/app/Http/Controllers/ApprovalDashboardController.php
class ApprovalDashboardController extends Controller
{
    public function index(Request $request, ApprovalDashboardService $service): View
    {
        $this->authorize('viewDashboard'); // Gate::define riêng, gate bằng permission approval.view_dashboard (§11)

        $organizationId = TenantContext::getOrganizationId();
        $pending = $service->pendingFor($request->user(), $organizationId)
            ->groupBy(fn (ApprovalSubject $s) => $s->subject_type); // group theo loại entity để hiển thị từng khối

        return view('approval::dashboard.index', compact('pending'));
    }
}
```

```php
// Modules/Approval/routes/web.php
Route::middleware(['auth', 'tenant'])
    ->get('dashboard/approvals', [ApprovalDashboardController::class, 'index'])
    ->name('backend.approval.dashboard');
```

View mặc định (`Modules/Approval/resources/views/dashboard/index.blade.php`) chỉ hiển thị tên model + link `route()` do config `label` trỏ tới — module tiêu thụ có nhiều nhu cầu hiển thị riêng (ảnh preview, mô tả ngắn…) có thể publish override view này, không bắt buộc. Ví dụ tối thiểu:

```blade
{{-- Modules/Approval/resources/views/dashboard/index.blade.php --}}
{{-- $pending: Collection<string subject_type, Collection<ApprovalSubject>> — key chính là
     morph map alias (vd 'product'), khớp thẳng với key trong config('approval.subjects'). --}}
@foreach ($pending as $subjectType => $items)
    <h3>{{ config("approval.subjects.{$subjectType}.label", $subjectType) }} ({{ $items->count() }})</h3>
    <ul>
        @foreach ($items as $approvalSubject)
            @php($entity = $approvalSubject->subject)
            <li>
                {{ class_basename($entity) }} #{{ $entity->id }}
                — <a href="{{ $entity->approvalDashboardUrl ?? '#' }}">Xem &amp; duyệt</a>
                <span class="text-sm text-gray-500">gửi lúc {{ $approvalSubject->updated_at->diffForHumans() }}</span>
            </li>
        @endforeach
    </ul>
@endforeach
```

`approvalDashboardUrl` (accessor tuỳ chọn trên entity, vd `getApprovalDashboardUrlAttribute()` trả `route('backend.products.edit', $this)`) là cách nhẹ để module tiêu thụ tự cung cấp link "Xem & duyệt" đúng route của mình mà `Approval` không cần biết trước route name của từng entity — nếu entity không định nghĩa accessor này, view fallback về `#` (không link) thay vì lỗi.

### 12.1 "Lịch sử duyệt" — trang giám sát toàn bộ (khác dashboard ở trên)

Dashboard §12 CỐ Ý chỉ hiển thị pending item mà **chính user đang đăng nhập** có quyền duyệt — không phải nơi để xem "toàn bộ đã xảy ra gì". Cho nhu cầu giám sát/kiểm tra (system_admin, ceo), thêm 1 trang riêng liệt kê **mọi** `ApprovalLog` (mọi entity, mọi trạng thái, mọi hành động — submit/approve/reject/publish/archive/revise), gate bằng permission riêng `approval.view_history` (§11), KHÔNG lọc theo Gate per-item như dashboard (vì mục đích là xem toàn cảnh, không phải "việc tôi cần làm"):

```php
// Modules/Approval/app/Http/Controllers/ApprovalHistoryController.php
class ApprovalHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewApprovalHistory');

        $logs = ApprovalLog::query()
            ->where('organization_id', TenantContext::getOrganizationId())
            ->with(['subject.subject', 'performedBy:id,name,email'])
            ->when($request->string('subject_type')->value(), fn ($q, $t) => $q->whereHas('subject', fn ($s) => $s->where('subject_type', $t)))
            ->when($request->string('action')->value(), fn ($q, $a) => $q->where('action', $a))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('approval::history.index', compact('logs'));
    }
}
```

`ApprovalLog::subject()` (quan hệ tới `ApprovalSubject`) và `ApprovalSubject::subject()` (morphTo tới entity thật) CÙNG TÊN `subject` nhưng khác quan hệ — `with(['subject.subject', ...])` chain đúng 2 tầng; dễ nhầm khi đọc code, đã ghi chú rõ trong `ApprovalLog` model. View lọc theo `subject_type` (dropdown từ `config('approval.subjects')`) và `action`, hiển thị bảng: thời gian, entity (+ link `approvalDashboardUrl` nếu có), hành động (`ApprovalLog::actionLabel()` — helper mới trả nhãn tiếng Việt), chuyển trạng thái (from→to badge), người thực hiện, lý do (khi reject).

Route: `GET dashboard/approvals/history` → `backend.approval.history` (cùng file `Modules/Approval/routes/web.php` với route dashboard, §13).

---

## 13. Routes (tổng hợp)

`Modules/Approval` sở hữu 2 route: `GET dashboard/approvals` (§12) và `GET dashboard/approvals/history` (§12.1). Toàn bộ route transition (`submit-approval`, `approve-content`…) thuộc về module tiêu thụ — xem ví dụ đầy đủ ở §9.4.

**Sidebar** (`resources/views/layouts/partials/sidebar.blade.php` — ở app gốc, không phải trong `Modules/Approval`, theo đúng cách các module khác thêm mục menu): 2 mục standalone (không phải dropdown `<details>`, vì mỗi mục chỉ dẫn tới 1 trang) đặt ngay dưới "Dashboard" ở đầu sidebar — "Chờ duyệt của tôi" gate bằng `@can('approval.view_dashboard')`, "Lịch sử duyệt" gate bằng `@can('approval.view_history')`. Đây là chỗ dễ quên khi thêm 1 route/trang mới — route tồn tại không tự động có mục menu, phải tự thêm tay vào sidebar partial.

---

## 14. UI/UX (tối thiểu)

- Badge trạng thái dùng `$status->badgeClass()` — mẫu §9.5.
- Với entity đã có badge trạng thái riêng (Product có `ProductStatus`), hiển thị **2 badge song song**, không gộp — tránh nhầm lẫn 2 trục khác nhau (§2.3). Áp dụng cả ở trang danh sách (§9.8), không chỉ trang sửa.
- Nút hành động ẩn/hiện theo `canTransitionTo()` + `@can` — module tiêu thụ tự viết Blade (§9.5), `Approval` không cung cấp view chung.
- Dashboard (§12) — bảng đơn giản, filter theo `subject_type`, dùng `label` từ config.
- Lịch sử duyệt (§12.1) — bảng đầy đủ hơn dashboard, filter theo `subject_type` + `action`, không lọc theo Gate per-item (khác dashboard).

---

## 15. Phased Implementation Plan

| Phase | Nội dung | Output kiểm tra được |
|---|---|---|
| 1 | Module scaffold, Migrations (kể cả `approved_by`/`approved_at`), `ApprovalStatus`, `ApprovalSubject`/`ApprovalLog`, `HasApproval` (đủ helper + auto-boot), `config/approval.php`, morph map | `migrate` sạch; gắn trait vào 1 model test → tạo record mới tự có `ApprovalSubject` draft |
| 2 | 5 Actions + `LogsApprovalActions` (có lock) + `InvalidTransitionException` + `approval:backfill-subjects` | Test transition hợp lệ/không hợp lệ, log đúng from/to; backfill idempotent chạy 2 lần không tạo trùng |
| 3 | `ApprovalStatusChangedNotification` (generic) | Class notification tồn tại, test `toDatabase()` trả đúng cấu trúc `NotificationData` |
| 4 | Tích hợp `Product` (§9) — permission `product.publish` mới, `ProductPolicy`, routes, Blade, resolver backfill, notification cụ thể | Thao tác Submit→Approve→Publish→Archive qua UI thật trên Product; backfill chạy trên dữ liệu Product hiện có không gãy catalog |
| 5 | Dashboard "chờ duyệt" (§12), permission `approval.view_dashboard` | Dashboard hiển thị đúng pending item mà user có quyền duyệt |
| 6 | (Tuỳ chọn, chỉ nếu có nhu cầu thật) áp dụng cho entity khác qua `HasApproval` | Model mới migrate + gắn trait không cần sửa `Approval` module |

---

## 16. Testing & Acceptance Criteria

- Submit → Pending → Approve → Approved (kèm `approved_by`/`approved_at` được set) → Publish → Published → Archive → Archived; log đủ 4 dòng, mỗi dòng đúng `from_status`/`to_status`.
- Reject: Pending → Draft, `reason` bắt buộc min 10 ký tự (Controller **và** Action đều validate) — thiếu reason bị chặn, không tạo `ApprovalLog`, không đổi status.
- Transition không hợp lệ (vd Draft → Published trực tiếp) ném `InvalidTransitionException`, không đổi status, không tạo log.
- Tạo entity mới có `HasApproval` → tự động có `ApprovalSubject` status=`draft` ngay sau `created` (test `bootHasApproval`).
- **Race condition**: 2 request "Approve" gửi đồng thời trên cùng 1 `ApprovalSubject` đang `pending` → chỉ 1 request thành công (1 dòng log `approve`), request còn lại nhận `InvalidTransitionException` (đã chuyển sang `approved` rồi) — verify bằng test giả lập 2 transaction chồng nhau hoặc test `lockForUpdate()` bằng 2 connection.
- `approval:backfill-subjects product --dry-run` không ghi DB, in đúng số lượng dự kiến; chạy thật xong chạy lại lần 2 → `count = 0` (idempotent, không tạo trùng nhờ `whereDoesntHave('approvalSubject')`).
- `performed_by` = `null` khi Action chạy từ job/command hệ thống (không có `auth()->id()`).
- Hai entity khác nhau cùng `subject_type` không đụng `unique(subject_type, subject_id)`.
- `Relation::morphMap()` hoạt động — cột `subject_type` trong DB lưu key ngắn (`product`), không phải FQCN thô; đồng thời xác nhận model KHÔNG có trong `config('approval.subjects')` (vd 1 model bất kỳ khác dùng Activitylog/Notification) vẫn hoạt động bình thường, không bị `ClassMorphViolationException`.
- Dashboard: user không có `product.publish` không thấy Product nào trong danh sách pending dù `ApprovalSubject.status = pending`.
- **Sửa nội dung sau khi Published (kịch bản quan trọng nhất — §1):** entity ở `Published` (đã có `public_snapshot`), sửa 1 trường trong `approvalWatchedAttributes()` → `ApprovalSubject.status` tự chuyển `Pending` (log `action=revise`, `from_status=published`, `to_status=pending`), NHƯNG:
  - `publicContent()`/query công khai vẫn trả về **bản CŨ** (từ `public_snapshot`, chưa đổi) — không gián đoạn hiển thị.
  - `$product->name` (đọc thẳng, không qua `publicContent()`) đã là **giá trị MỚI** — xác nhận bản sửa nằm sẵn trên entity, chỉ chưa được đưa vào snapshot công khai.
  - `scopePubliclyVisible()` vẫn trả về entity này (không bị loại) — khác hẳn hành vi sai của v3.1.
  - Sau khi Approve → Publish lại: `public_snapshot` được ghi đè bằng giá trị mới, `publicContent()` từ lúc này mới trả về bản mới.
- **Sửa nội dung sau khi Approved (chưa kịp Publish)**: tương tự — tự chuyển về `Pending`; vì chưa từng Publish lần nào ở nhánh này thì `public_snapshot` vẫn `null` (chưa từng hiển thị công khai) — không có gì để "giữ nguyên hiển thị" cả, khác trường hợp trên.
- **Sửa trường KHÔNG nằm trong `approvalWatchedAttributes()`** (vd đổi `price` của Product) khi đang `Published` → `ApprovalSubject.status` giữ nguyên `published`, không tạo log `revise` — xác nhận 2 trục độc lập (§2.3) hoạt động đúng, và `publicContent()` phải trả `price` mới ngay lập tức (đọc trực tiếp entity, không qua snapshot).
- **Sửa nội dung khi đang `Archived`** → `ReviseContentAction` ném `InvalidTransitionException`, `update()` ở tầng entity thất bại (transaction rollback) — xác nhận entity đã lưu trữ thực sự read-only ở tầng nội dung.
- **Sửa nội dung khi đang `Draft`/`Pending`** → không tạo dòng `ApprovalLog` mới (không transition), tránh log rác vô nghĩa.
- Backfill (`approval:backfill-subjects`) cho record cũ được resolver map thành `Published` → `public_snapshot` được set ngay lúc backfill (không null) → `isPubliclyVisible()`/`scopePubliclyVisible()` trả `true` ngay, không làm gián đoạn catalog hiện có.

---

## 17. Edge Cases & Risks

### 17.1 Race condition khi 2 transition đồng thời

Đã xử lý bằng `lockForUpdate()` trong `LogsApprovalActions::transition()` (§8.1) — mọi transition chạy trong `DB::transaction()`, khoá row trước khi kiểm tra `canTransitionTo()`. Cần đảm bảo **mọi** Action mới viết sau này đều đi qua `transition()`, không tự ý `update()` thẳng lên `ApprovalSubject` (nếu không sẽ bypass lock).

### 17.2 Soft-delete cascade — `ApprovalSubject` mồ côi (⚠️ CHƯA áp dụng vào code — vẫn chỉ là khuyến nghị)

`ApprovalSubject` có `softDeletes` nhưng **không có FK ngược** tới entity chủ (polymorphic không tạo được FK thật). Nếu entity gốc (`Product`) bị `forceDelete()` (xoá cứng, bỏ qua soft-delete), `ApprovalSubject` tương ứng sẽ mồ côi (không lỗi FK vì không có constraint, nhưng dữ liệu vô nghĩa còn lại). Khuyến nghị: thêm hook nhẹ ở module tiêu thụ khi tích hợp (Phase 4), KHÔNG đặt sẵn trong `Approval` (vì `Approval` không biết trước entity nào sẽ `forceDelete`).

**Xác nhận lại khi rà soát v4.3 (13/07/2026): hook này CHƯA được thêm vào code.** `Modules/Product/app/Models/Product.php::booted()` hiện tại (đã tích hợp `HasApproval` đầy đủ, §9.1) chỉ có đúng 1 hook `creating` (gán `uuid`), KHÔNG có `forceDeleted`. `grep -rn "forceDeleted" Modules/Product/` không ra kết quả nào. Rủi ro mô tả ở đoạn trên (mồ côi `ApprovalSubject` khi `forceDelete()` Product) vẫn còn tồn tại thật trong code hiện tại — đây là nợ kỹ thuật nhỏ, chưa gây sự cố vì `Product` trong thực tế chưa có chỗ nào gọi `forceDelete()` (chỉ dùng `delete()` mềm qua `SoftDeletes`), nhưng nếu sau này có thao tác xoá cứng (vd lệnh dọn dữ liệu, GDPR xoá vĩnh viễn) thì cần thêm đoạn code dưới đây trước khi dùng.

Ví dụ copy-paste được cho `Product` (**vẫn là đề xuất, chưa merge**) — đặt ngay trong `booted()` đã có sẵn của `Product`, cùng chỗ model đã tự set `uuid` khi `creating`, theo đúng convention hiện tại của model này (không dùng Observer class riêng — `Product` vốn đã xử lý sự kiện ngay trong `booted()`):

```php
// Modules/Product/app/Models/Product.php
protected static function booted(): void
{
    static::creating(function (self $model): void {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    });

    // Dọn ApprovalSubject mồ côi khi Product bị xoá cứng — forceDelete() bỏ qua SoftDeletes
    // nên không có cơ hội nào khác để dọn dẹp bản ghi polymorphic này (§17.2).
    // TODO: chưa thêm vào code thật — xem ghi chú xác nhận ở trên.
    static::forceDeleted(function (self $model): void {
        $model->approvalSubject?->forceDelete();
    });
}
```

Nếu entity chỉ dùng `SoftDeletes` (không bao giờ `forceDelete`, là trường hợp phổ biến nhất trong codebase hiện tại), không cần thêm gì — `ApprovalSubject` tồn tại song song, không ảnh hưởng.

### 17.3 Performance khi nhiều `subject_type`

- Index `(organization_id, subject_type, status)` (Migration #1, §4) đủ cho query dashboard hiện tại (lọc theo từng type riêng lẻ, không JOIN nhiều bảng).
- `ApprovalDashboardService::pendingFor()` (§12) chạy 1 query riêng cho **mỗi** subject_type trong config (N query, N = số entity đã tích hợp) rồi lọc bằng Gate — chấp nhận được khi N còn nhỏ (< 10 entity, như dự kiến MVP). Nếu N tăng lớn hoặc số pending item mỗi loại lên tới hàng nghìn, cân nhắc: (a) cache kết quả `pendingFor()` theo user+org trong vài chục giây, hoặc (b) thêm cột non-polymorphic phụ trợ (denormalize permission cần thiết ngay trên `ApprovalSubject`, vd `required_permission` string) để lọc bằng SQL thay vì Gate-check từng item trong PHP — **chỉ làm khi đo được nghẽn thật**, không làm trước.
- Không dùng `whereHasMorph()` cho truy vấn thường xuyên (dashboard) — quét toàn bảng `approval_subjects` không lợi dụng được index theo `subject_type` cụ thể; luôn lọc `subject_type` tường minh như code §12.
- **Query công khai lớn (§9.6)** — `publiclyVisible()` tự nó chỉ là 1 `whereHas` có index hỗ trợ (`idx_approval_org_type_status` + `whereNotNull`), đủ nhanh ở quy mô thông thường. Nếu 1 trang catalog public liệt kê hàng chục nghìn bản ghi mỗi request và đo được `publiclyVisible()`/`publicContent()` là điểm nghẽn thật: **ưu tiên cache response/fragment (Blade `@cache`, response cache theo route) hoặc HTTP cache ở tầng CDN cho toàn trang/toàn danh sách** — rẻ và hiệu quả hơn nhiều so với cache từng entity trong `publicContent()` (§7.1); chỉ khi mức đó vẫn không đủ mới cân nhắc materialized view/bảng denormalize riêng cho trang catalog — đây là thay đổi kiến trúc lớn, ngoài phạm vi module `Approval`, thuộc về module tiêu thụ tự quyết định khi thật sự cần.

### 17.4 Entity cần sửa 2 lần liên tiếp trước khi được duyệt lại — snapshot không bị mất

Nếu editor sửa nội dung nhiều lần trong lúc đang chờ duyệt lại (status vẫn `Pending` sau lần sửa đầu, các lần sửa tiếp theo rơi vào nhánh `default` của `ReviseContentAction` — không tạo thêm log `revise`), `public_snapshot` **không đổi** cho tới khi `PublishAction` chạy — cổng thông tin vẫn hiển thị đúng bản đã duyệt gần nhất trong suốt quá trình, chỉ 1 lần chụp snapshot mới khi thực sự Publish. Đây là hệ quả đúng của thiết kế, không phải bug — không cần xử lý gì thêm.

Rủi ro thật cần lưu ý: nếu Reject xảy ra sau khi đã có nhiều lần sửa chồng lên nhau (Pending → Draft), toàn bộ các lần sửa đó bị "gộp" lại thành 1 bản duy nhất đang nằm trên entity (không có lịch sử từng lần sửa riêng lẻ) — `ApprovalLog` chỉ ghi lại **transition trạng thái**, không ghi **diff nội dung**. Nếu nghiệp vụ cần xem "ai đã sửa gì" ở mức field-level, đây là tính năng ngoài phạm vi MVP (cần content revision history riêng, không phải việc của `Approval`).

### 17.5 Trường nội dung không được khai báo đúng trong `approvalWatchedAttributes()`

Rủi ro triển khai lớn nhất của cơ chế tự động ở §7.1/§8.4: nếu module tiêu thụ khai báo thiếu 1 trường nội dung công khai thật sự (vd quên thêm `cover_image_url`), sửa trường đó sẽ **không** kích hoạt chuyển về `Pending`, và (khác v3.1) cũng **không** được đưa vào `public_snapshot` ở lần Publish kế tiếp cho tới khi 1 trường CÓ theo dõi cũng đổi cùng lúc — nghĩa là `publicContent()` vẫn đọc trực tiếp field đó từ entity (vì nó merge non-watched fields sống), nên trong TRƯỜNG HỢP NÀY nội dung mới thật ra vẫn lộ ra công khai ngay lập tức, không qua duyệt — đúng lỗ hổng mà module này sinh ra để chặn. Vì `approvalWatchedAttributes()` là `abstract` (bắt buộc override, không có default ngầm định), lỗi này chỉ có thể là thiếu sót khi liệt kê danh sách, không phải quên gọi hàm — cần review kỹ danh sách này trong code review khi tích hợp entity mới (Phase tương ứng ở §15), và nên có test liệt kê tường minh "các trường mong đợi phải nằm trong watched list" để phát hiện sớm nếu ai đó thêm cột mới vào entity mà quên cập nhật danh sách.

### 17.6 Hai trục trạng thái độc lập trên cùng entity (Product)

Rủi ro UX: user thấy 2 badge ("Đang hoạt động" + "Chờ duyệt") có thể hiểu nhầm là xung đột. Cần copy UI rõ ràng ("Trạng thái kinh doanh" vs "Trạng thái duyệt nội dung") — xem §9.5, §14. Không giải quyết bằng cách gộp 2 enum làm 1 (sẽ phá vỡ `ProductStatus` hiện tại và ngữ nghĩa tồn kho không liên quan gì đến duyệt nội dung).

### 17.7 Không giả định Post sẽ migrate sang `Approval`

Nếu về sau có yêu cầu đó, đây là thay đổi lớn (đổi 7 case status thành 5, gộp `Scheduled`/`Unpublished`, chuyển dữ liệu `post_publishing_logs` sang `approval_logs`) cần đặc tả riêng, ngoài phạm vi module này.

### 17.8 Cache quan hệ `approvalSubject` bị "stale" sau nhiều transition liên tiếp trên cùng object (đã sửa)

Phát hiện khi viết seeder demo dữ liệu (chạy Submit→Approve→Publish→sửa nội dung liên tiếp trên **cùng 1 biến `$product`** trong 1 script, không `refresh()` giữa các bước — đúng cách code thật sẽ làm khi 1 request xử lý nhiều bước, vd 1 background job duyệt hàng loạt). `transition()` (§8.1) luôn thao tác trên 1 bản `ApprovalSubject` fetch RIÊNG qua `lockForUpdate()` để tránh race condition (§17.1), KHÔNG phải object nhận vào — nếu không đồng bộ lại, `$product->approvalSubject` (đã cache từ lúc entity được tạo, qua `HasApproval::bootHasApproval()`) sẽ mãi mãi là bản CŨ. Hệ quả cụ thể quan sát được: sau `PublishAction`, gọi tiếp `$product->update([...watched field...])` để kích hoạt `ReviseContentAction` — action này gọi `$subject->ensureApprovalSubject()`, đọc phải `status='draft'` (cache từ lúc tạo) thay vì `'published'` thật trong DB, rơi vào nhánh `default` (không làm gì) thay vì chuyển đúng sang `Pending` — **không tạo log `revise`, không tạo hiệu ứng gì**, âm thầm sai mà không có exception nào báo.

**Đã sửa**: `transition()` giờ nhận thêm tham số `$parent` (chính entity, vd `Product`) và gọi `$parent->setRelation('approvalSubject', $locked)` ngay sau khi transition xong (§8.1) — mọi Action đều truyền `$subject` gốc vào, không chỉ `$subject->approvalSubject`. Từ đó, gọi nhiều Action liên tiếp trên CÙNG 1 object trong cùng request luôn thấy đúng state mới nhất, không cần tự `refresh()` thủ công.

**Bài học cho test/QA**: các bài test đơn lẻ-transition-rồi-`refresh()` (Phase 1–4) không phát hiện được lỗi này vì `refresh()` che giấu triệu chứng (luôn query lại DB thay vì dùng cache) — chỉ lộ ra khi test/seed một chuỗi nhiều transition liên tiếp KHÔNG refresh giữa chừng, đúng pattern code thật sẽ gặp. Khi viết thêm Action mới trong tương lai, luôn viết ít nhất 1 test theo kiểu "nhiều bước liên tiếp trên cùng object, không refresh" để bắt lại lớp lỗi này nếu tái phát.

### 17.9 `approval:backfill-subjects` luôn báo "0 bản ghi" nếu quên set `TenantContext` (đã sửa)

Phát hiện khi thắc mắc vì sao 2 Product có sẵn trong tổ chức "demo" (tạo trước khi tích hợp `HasApproval`) không có badge duyệt nội dung dù lệnh backfill đã "chạy thành công" nhiều lần trước đó. Nguyên nhân: `Product` (và mọi model dùng `BelongsToOrganization`) có global scope `OrganizationScope` — khi `TenantContext` CHƯA được set (đúng trạng thái mặc định của 1 tiến trình `artisan` console, không đi qua middleware `IdentifyOrganization`), scope này tự áp `whereRaw('0=1')` làm failsafe chống rò rỉ dữ liệu chéo tổ chức. Hệ quả: `$modelClass::query()->whereDoesntHave('approvalSubject')` LUÔN trả về tập rỗng, khiến lệnh báo "Đã xử lý 0 bản ghi" — trông như "đã chạy xong, không còn gì để làm" nhưng thực ra chưa từng thấy được bản ghi thật nào cả.

**Đã sửa**: `BackfillApprovalSubjectsCommand` (§4.1) giờ loop qua **mọi `Organization`**, gọi `TenantContext::set($organization)` trước khi query cho từng tổ chức, `TenantContext::flush()` sau khi xong. Bài học: **bất kỳ command/job chạy ngoài vòng đời HTTP request nào thao tác trên model `BelongsToOrganization`** đều phải tự set `TenantContext` (hoặc lặp qua từng organization) — không có middleware nào làm việc đó thay cho console/queue.

### 17.10 Hiển thị nhiều entity cùng lúc (danh sách) vs 1 entity (trang sửa) — khác nhau về yêu cầu eager-load

Xem chi tiết ở §9.8. Tóm tắt: Eloquent strict mode chỉ ném `LazyLoadingViolationException` khi lazy-load 1 relation chưa nạp trên collection có **≥ 2 phần tử** — 1 model đơn lẻ (`->first()`, route-model-binding) không bị chặn. Vì vậy trang edit Product (§9.5) hoạt động đúng ngay cả khi không cố ý eager-load, nhưng trang danh sách (§9.8) bắt buộc phải `->with('approvalSubject')` tường minh trong Query Handler. Quy tắc chung khi thêm badge/thông tin duyệt vào bất kỳ trang danh sách nào (không riêng Product): luôn eager-load `approvalSubject` ngay tại tầng Query, đừng dựa vào việc "trang edit chạy được nên trang danh sách chắc cũng chạy được".

### 17.11 Sửa nội dung khi Archived — exception phải ném TRƯỚC khi ghi DB, không phải sau (đã sửa, bug nghiêm trọng)

Phát hiện khi người dùng hỏi "sao lưu sản phẩm archived lại báo lỗi". Kiểm tra kỹ lộ ra bug nghiêm trọng hơn câu hỏi ban đầu: bản thiết kế trước (`static::updated` + `wasChanged()`, §7.1) gọi `ReviseContentAction` **SAU KHI** câu UPDATE của entity đã chạy và tự commit (Eloquent fire `updating` → [UPDATE query] → `updated` → `saved`; không có transaction bao ngoài). Hệ quả thật quan sát được: sửa `description` của 1 Product đang `Archived` → `ReviseContentAction` ném `InvalidTransitionException` đúng như thiết kế, NHƯNG nội dung mới **đã bị ghi vào `products.description` từ trước đó** — `ProductAdminController::update()` không bắt được exception nên trả lỗi 500 cho user, còn dữ liệu thì im lặng sai lệch: cột `status` của `ApprovalSubject` vẫn `archived` (không đổi) trong khi nội dung thực tế trên entity đã khác — đúng thứ mà "Archived = read-only nội dung" phải ngăn chặn nhưng lại không ngăn được.

**Đã sửa (§7.1)**: đổi `static::updated` → `static::updating` (trước khi UPDATE chạy) và `wasChanged()` → `isDirty()` (kiểm tra thay đổi CHƯA lưu thay vì đã lưu). Ném exception ở `updating` khiến Eloquent huỷ toàn bộ `save()` trước khi câu UPDATE kịp chạy — không trường nào trong form bị ghi, kể cả trường không liên quan đang gửi chung 1 request (đã verify: sửa `price` một mình trên sản phẩm Archived vẫn lưu bình thường; sửa `description` bị chặn tuyệt đối, không ghi gì).

**Đã bổ sung xử lý lỗi thân thiện**: `ProductAdminController::update()` (§9.4) giờ bắt `InvalidTransitionException`, `back()->withInput()->with('error', ...)` thay vì để lộ lỗi 500 — đây là lớp UX cuối cùng cho trường hợp hiếm khi user cố tình sửa nội dung 1 sản phẩm đã lưu trữ (Policy `update()` KHÔNG chặn toàn bộ form vì các trường vận hành như giá vẫn cần sửa được bình thường dù nội dung đã archived, §2.3 — chỉ chặn đúng lúc đụng vào trường nội dung).

**Bài học chung**: bất kỳ hook nào cần "chặn hẳn 1 thao tác ghi nếu điều kiện X" PHẢI đặt ở sự kiện **trước** khi query thực thi (`saving`/`creating`/`updating`), không phải sự kiện **sau** (`saved`/`created`/`updated`) — nếu không, "chặn" chỉ là ảo giác: query đã chạy xong, chỉ có tác dụng phụ (ở đây là transition ApprovalSubject) là không xảy ra, còn thay đổi chính vẫn lọt qua.

---

## 18. Platform Approval Gateway — Kiểm duyệt tập trung xuyên tổ chức (hệ thống nội bộ Hà Kiên)

### 18.1 Bối cảnh & bản chất nghiệp vụ

Hệ thống là nội bộ của **Hà Kiên**. Mọi tổ chức/doanh nghiệp (Organization) đăng ký sử dụng nền tảng, và mọi sản phẩm (Product) do các tổ chức đó tạo ra, đều PHẢI được đội ngũ kiểm duyệt tập trung của Hà Kiên duyệt trước khi được publish/active ra cổng thông tin công khai — **không phải doanh nghiệp tự duyệt nội dung của chính mình**. Post giữ nguyên luồng nội bộ hiện tại, không thuộc phạm vi này.

Đây là mô hình **Platform Approval Gateway** — mở rộng trực tiếp từ `Modules/Approval` (không viết engine mới), thêm 1 vai trò xuyên tổ chức (`content_moderator`) và áp dụng KHÔNG ĐIỀU KIỆN (không có flag "ngành hàng cần duyệt" — mọi Organization/Product đều qua kiểm duyệt tập trung, không có ngoại lệ).

### 18.2 `content_moderator` — vai trò xuyên tổ chức

Tài khoản kiểm duyệt của Hà Kiên: `organization_id = NULL` (giống quy ước `super-admin` đã có), role Spatie `content_moderator`, seed qua `Modules/Approval/database/seeders/ContentModeratorSeeder.php` (tài khoản mặc định `moderator@system.local` / `Admin@123!` — **đổi mật khẩu ngay khi deploy**).

**Bug thật quan trọng phát hiện khi build tính năng này — KHÔNG dùng `$user->hasRole('content_moderator')`:** verify trực tiếp cho thấy Spatie's team-scoped `hasRole()` (`config('permission.teams') = true`, `team_foreign_key = organization_id`) chỉ trả `true` khi **ambient `getPermissionsTeamId()` CŨNG đang null** tại thời điểm gọi. Vì content_moderator thao tác trên dữ liệu của NHIỀU tổ chức khác nhau trong cùng 1 luồng xử lý (mỗi request cần `TenantContext::runForOrganization()` để `OrganizationScope` không chặn — xem §18.4), ambient Spatie team context không ổn định qua từng bước.

**Giải pháp:** `App\Models\User::isContentModerator()` — query thẳng bảng `model_has_roles` JOIN `roles`, **không** phụ thuộc `getPermissionsTeamId()`:
```php
public function isContentModerator(): bool
{
    if ($this->organization_id !== null) return false;
    return DB::table(config('permission.table_names.model_has_roles'))
        ->join('roles', 'roles.id', '=', '...model_has_roles.role_id')
        ->where('...model_has_roles.model_id', $this->id)
        ->where('...model_has_roles.model_type', static::class)
        ->where('roles.name', 'content_moderator')
        ->exists();
}
```
Dùng method này ở MỌI nơi cần biết "đây có phải content_moderator không" — Policy (`ProductPolicy`/`OrganizationPolicy`), Gate (`viewDashboard`/`viewApprovalHistory`), `ApprovalDashboardService`. Verify: trả đúng bất kể `setPermissionsTeamId()` đang là gì (null, org thật, hay org không tồn tại).

### 18.3 `Organization` là subject thứ 2 của `HasApproval` — khác biệt cấu trúc với Product

Đăng ký trong `config/approval.php`: `'organization' => ['model' => \App\Shared\Tenancy\Models\Organization::class, ...]`.

**Organization LÀ tenant root, không phải entity tenant-scoped như Product** — không có cột `organization_id` trỏ vào chính nó. `HasApproval` (§7.1) đã tổng quát hoá bằng method overridable `approvalOrganizationId()` (mặc định `return $this->organization_id;`), Organization override trả `return $this->id;`.

**2 class Organization — đặt `HasApproval` ở class GỐC:** `App\Shared\Tenancy\Models\Organization` (dùng bởi `RegisterOrganizationAction`, `TenantContext`) và `Modules\Organization\Models\Organization extends BaseOrganization` (dùng bởi `OrganizationController`/CRUD admin, thêm quan hệ `members()`/`orgSettings()`). `HasApproval` + `approvalOrganizationId()` + `approvalWatchedAttributes()` đặt ở class GỐC (kế thừa xuống subclass tự động) vì `RegisterOrganizationAction` tạo instance qua class gốc trực tiếp.

**Bug thật (nghiêm trọng) phát hiện khi verify — `Gate::policy()` không tự "đi lên" cây kế thừa:** `OrganizationServiceProvider` chỉ đăng ký `Gate::policy(Modules\Organization\Models\Organization::class, OrganizationPolicy::class)` (subclass). Khi content_moderator gọi `$user->can('approve', $organization)` trên 1 instance class GỐC (từ `RegisterOrganizationAction`), Gate không tìm thấy Policy khớp chính xác class → luôn `false`, im lặng, không lỗi gì báo hiệu. **Đã sửa:** đăng ký thêm `Gate::policy(App\Shared\Tenancy\Models\Organization::class, OrganizationPolicy::class)`; đồng thời đổi type-hint của mọi method trong `OrganizationPolicy` từ subclass sang class GỐC (an toàn cho cả 2, vì subclass IS-A class gốc — chiều ngược lại thì không), và bỏ gọi `$organization->members()` (chỉ có ở subclass) sang query thẳng `OrganizationMember::where(...)`.

`approvalWatchedAttributes()` của Organization: `['name', 'description', 'industry', 'logo_path', 'website', 'address', 'tax_code']` — KHÔNG gồm `status`/`settings`/`owner_id` (vận hành nội bộ) hay `approved_by`/`approved_at` (cột cũ trong bảng `organizations`, chưa từng dùng, không liên quan `ApprovalSubject` — xem §0 "dead schema" đã phát hiện trước đó khi research).

### 18.4 Truy vấn xuyên tổ chức — 3 lớp scope cần xử lý đúng

Nội dung sau đây là phần khó nhất, phát hiện qua verify thật (không phải suy đoán):

1. **`TenantContext::runForOrganization($organization, $callback)`** (helper có sẵn, chưa từng dùng trước đây) — bọc quanh MỌI lệnh gọi Action (`SubmitForApprovalAction`/`ApproveAction`/`RejectAction`/`PublishAction`/`ArchiveAction`) trong `ProductAdminController`/`OrganizationController`/`RegisterOrganizationAction`. content_moderator không có `TenantContext` của riêng mình khớp bất kỳ tổ chức nào — không bọc, mọi query trên `ApprovalSubject`/`ApprovalLog` (đều có `OrganizationScope`) sẽ ăn phải failsafe `whereRaw('0=1')`. Áp dụng bọc này UNCONDITIONALLY (kể cả khi chính doanh nghiệp tự `submitForApproval`) — vô hại vì khi đó set lại đúng org họ đã đứng sẵn.

2. **`ApprovalDashboardService::pendingForModerator()`** (mới, song song với `pendingFor()`) — `withoutGlobalScope(OrganizationScope::class)` trên `ApprovalSubject` để thấy pending item của MỌI tổ chức. **Bug thật:** dùng `with('subject')` (eager-load morphTo mặc định) tưởng chừng an toàn nhưng KHÔNG — Laravel tự query RIÊNG theo từng model type bên trong morphTo, và query con đó VẪN áp `OrganizationScope` của chính model đó (Product/Organization), khiến `$s->subject` luôn `null` với content_moderator dù `ApprovalSubject` đã lấy đúng. **Đã sửa:** tự fetch entity thủ công (`$modelClass::withoutGlobalScope(...)->whereIn('id', ...)->get()->keyBy('id')`) rồi `setRelation()` lại — không dựa vào eager-load tự động cho trường hợp cross-org này. Áp dụng y hệt cho `ApprovalHistoryController` (lịch sử xuyên tổ chức).

3. **`approval:backfill-subjects` — `whereDoesntHave` bị "rò scope theo vòng lặp org":** command loop qua từng Organization để set `TenantContext` (§4.1, đã có từ trước). Với `subject_type=organization` (entity KHÔNG tự tenant-scoped, khác Product), bug thật: ở vòng lặp org #2, subquery `whereDoesntHave('approvalSubject')` bị lọc theo `organization_id=2` (TenantContext đang set), kết luận SAI rằng org #1 (đã có `ApprovalSubject` với `organization_id=1` từ vòng lặp #1) "chưa có subject" → cố insert lại → vỡ `uq_approval_subject`. **Đã sửa:** `whereDoesntHave('approvalSubject', fn ($q) => $q->withoutGlobalScope(OrganizationScope::class))` — subquery kiểm tra tồn tại phải LUÔN nhìn thấy dữ liệu global, bất kể vòng lặp org nào đang chạy.

### 18.5 Tự động gửi duyệt — không chờ bấm nút

`CreateProductAction::handle()` và `RegisterOrganizationAction::handle()` tự gọi `SubmitForApprovalAction` ngay sau khi tạo (Draft → Pending tự động, không đợi doanh nghiệp bấm "Gửi duyệt" thủ công) — khớp đúng yêu cầu "mọi đăng ký/chỉnh sửa đều phải kiểm duyệt". `Organization.status` (trục vận hành, `OrganizationStatus::Active`) KHÔNG đổi — CEO vẫn đăng nhập/dùng hệ thống bình thường ngay; chỉ riêng `ApprovalStatus` của hồ sơ là `Pending` chờ Hà Kiên duyệt (đúng nguyên tắc 2 trục độc lập, §2.3). Sửa nội dung tiếp theo (khi đã Approved/Published) vẫn qua cơ chế tự động sẵn có (`ReviseContentAction`, §7.1/§8.4) — không cần thêm gì.

### 18.6 Policy — permission cũ bị loại bỏ

`ProductPolicy`/`OrganizationPolicy`: `approve`/`reject`/`publishApproval`/`archiveApproval` đổi từ `$user->can('product.publish')` sang `$user->isContentModerator()` — **doanh nghiệp không còn tự duyệt được nữa dù có quyền gì**. Permission `product.publish` (không còn dùng ở đâu) đã bị xoá khỏi `ProductPermissionSeeder` và revoke khỏi các role đã gán (`ceo`, `ops`, `system_admin`) + xoá record `Permission` — tránh permission chết gây hiểu nhầm.

`view`/`update` (Product) và `view` (Organization) được nới thêm cho `isContentModerator()` — content_moderator cần load được trang xem chi tiết trước khi duyệt (Product không có route "show" riêng nên dùng chung `edit`; Organization có `show` riêng nên dùng đúng `show`, không cấp `update`).

### 18.7 Permission/Gate mới

| Gate/permission | Kiểm tra | Vai trò |
|---|---|---|
| `viewDashboard` | `$user->can('approval.view_dashboard') OR isContentModerator()` | ceo/ops/system_admin (org mình) + content_moderator (mọi org) |
| `viewApprovalHistory` | `$user->can('approval.view_history') OR isContentModerator()` | ceo/system_admin (org mình) + content_moderator (mọi org) |
| `ProductPolicy::approve/reject/publishApproval/archiveApproval` | `isContentModerator()` | Chỉ content_moderator |
| `OrganizationPolicy::approve/reject/publishApproval/archiveApproval` | `isContentModerator()` | Chỉ content_moderator |

### 18.8 Route mới

`Modules/Organization/routes/web.php` — 5 route tương tự Product: `organizations/{organization}/{submit-approval,approve-content,reject-content,publish-content,archive-content}`, controller method trong `OrganizationController` (không tạo controller riêng — tái dùng resource controller CRUD đã có).

### 18.9 Đã verify thật (end-to-end, không chỉ đọc code)

- Product mới tạo → tự động `Pending` (không cần bấm Gửi duyệt) → CEO chính tổ chức đó **không** duyệt được (`can('approve')` = false) → content_moderator duyệt được, thấy đúng trong dashboard xuyên tổ chức → Approve + Publish qua `TenantContext::runForOrganization()` → `isPubliclyVisible() = true`.
- Organization mới đăng ký (qua `RegisterOrganizationAction` thật) → `ApprovalStatus = Pending` tự động, `OrganizationStatus = Active` (CEO dùng được ngay, 2 trục độc lập) → Owner không tự duyệt được → content_moderator thấy trong dashboard, duyệt + publish được → `isPubliclyVisible() = true`.
- Dashboard với ≥ 2 pending item cùng loại (test với 2 Product) — không dính `LazyLoadingViolationException`, badge tên tổ chức hiển thị đúng cho từng item.
- Toàn bộ luồng approve/reject test qua **Controller thật** (`ProductAdminController::approveContent/rejectContent`), không chỉ gọi thẳng Action — xác nhận HTTP-layer hoạt động đúng, không chỉ tầng service.
- `approval:backfill-subjects organization` — dry-run, chạy thật, chạy lại xác nhận idempotent (0 bản ghi) — sau khi sửa bug §18.4.3.

### 18.10 Post (bài viết) — phân cấp 2 tầng thay vì 1 tầng phẳng

Khác Product/Organization (kiểm duyệt thông tin, 1 tầng `content_moderator`), Post đi theo đúng mô hình toà soạn tin tức thật: **"cộng tác viên gửi bài → biên tập viên (content_editor) duyệt sơ bộ → trưởng phòng nội dung (content_head) duyệt cuối cùng"** trước khi hiển thị ra cổng thông tin. `Modules/Post` giữ nguyên engine riêng (`TranslationStatus`, KHÔNG migrate sang `HasApproval`/`ApprovalSubject` — xem §17.7, rủi ro cao không cần thiết) — chỉ đổi **Policy gate**, tận dụng đúng 2 bước đã có sẵn trong state machine:

```
Draft → Submitted → Approved → Published/Scheduled → Unpublished/Archived
         (doanh nghiệp)  (content_editor)  (content_head)      (content_head)
```

**Ánh xạ quyền** (`PostArticlePolicy`):
- `approve` (Submitted→Approved): `content_editor` HOẶC `content_head`.
- `publish`/`schedule`/`archive`/`unpublish` (mọi bước sau đó): CHỈ `content_head`.
- Cấp trên làm được việc cấp dưới (content_head duyệt sơ bộ thay được), **không áp dụng ngược** (content_editor không tự publish được) — đúng phân cấp tổ chức thật.
- `submitForReview`/`update`/`delete` giữ nguyên `post_article.edit` (đội marketing/cộng tác viên tự làm, không đổi).

**2 role mới** (`Modules/Approval/database/seeders/ContentReviewHierarchySeeder.php`, tài khoản `organization_id=null` giống `content_moderator`): `content_editor` (`editor@system.local`), `content_head` (`content-head@system.local`). `User::hasGlobalRole()` (refactor từ `isContentModerator()` cũ, giờ dùng chung cho cả 3 role — `isContentModerator()`/`isContentEditor()`/`isContentHead()` đều gọi qua đây) — vẫn KHÔNG dùng `hasRole()` team-scoped, lý do y hệt §18.2.

**Bug thật phát hiện riêng cho Post (khác Product/Organization)**: `ArticleAdminController::authorizeArticle()` là 1 check quyền RAW (`$user->can($permission) && (...)`), không đi qua Policy — phải sửa RIÊNG, không tự động ăn theo thay đổi `PostArticlePolicy`. Và quan trọng hơn: các Action của Post (`PublishArticleAction` đọc `$translation->article->is_sponsored`) **lazy-load quan hệ `article`** — nếu chưa cache và content_editor/content_head không có `TenantContext` khớp tổ chức của bài viết, `PostArticle` (có `OrganizationScope`) resolve `null` → lỗi "gọi method trên null" thật. Đã sửa `TranslationController::runTransition()` bọc `$callback()` trong `TenantContext::runForOrganization($translation->organization, ...)` + `loadMissing('article')` ngay trong cùng block trước khi thoát — pattern giống hệt Product/Organization (§18.4.1) dù Post không dùng `ApprovalSubject`.

**Đã verify thật qua Controller** (không chỉ gọi Action trực tiếp): tạo bài → submit → marketing (đội của tổ chức) bị chặn duyệt → editor duyệt được (không publish được) → head duyệt được (thay editor) + publish được → toàn bộ qua `TranslationController::approve()/publish()` thật, không lỗi lazy-load.

**Phạm vi CHƯA làm (nói rõ để không gây hiểu nhầm)**: chưa có dashboard "bài viết chờ duyệt xuyên tổ chức" riêng cho content_editor/content_head (khác Product/Organization đã có `ApprovalDashboardService`) — hiện tại editor/head cần biết trước ID bản dịch cần duyệt (vd qua thông báo/link trực tiếp) để thao tác qua `TranslationController`, chưa duyệt được qua trang danh sách `ArticleAdminController::index()` (danh sách đó vẫn lọc theo 1 tổ chức qua `TenantContext`, editor/head không có). Nếu cần trang duyệt tập trung xuyên tổ chức cho Post tương tự Product/Organization, đây là việc tiếp theo.

### 18.11 Menu sidebar "Bài viết chờ duyệt" — dashboard xuyên tổ chức cho Post (lấp khoảng trống §18.10)

Bổ sung đúng phần "CHƯA làm" nêu ở cuối §18.10. Route mới `GET dashboard/posts/articles/pending-review` (`ArticleAdminController::pendingReview()`, đặt TRƯỚC `Route::resource('articles', ...)` để tránh khớp nhầm `articles/{article}`), Query/Handler riêng `ListPendingReviewTranslationsQuery/Handler` — lấy cả `Submitted` (chờ content_editor) và `Approved` (chờ content_head), lọc lại từng item bằng `Gate::forUser($user)->allows('approve'|'publish', $t)` (content_head thấy cả 2, content_editor chỉ thấy `Submitted` — đúng phân cấp §18.10). Cùng pattern tránh `LazyLoadingViolationException` đã dùng ở `ApprovalDashboardService::pendingForModerator()`: tự fetch `PostArticle` (+ `organization`) với `withoutGlobalScope(OrganizationScope::class)` rồi `setRelation()` lại, không dựa eager-load mặc định.

**Bug thật phát hiện khi thêm menu — Gate name mismatch:** sidebar dùng `@can('approval.view_dashboard')` (chuỗi permission Spatie) thay vì `@can('viewDashboard')` (tên Gate thật đăng ký ở `ApprovalServiceProvider::boot()`, §18.2). 2 chuỗi này KHÁC NHAU hoàn toàn — Blade `@can()` với 1 string vừa có thể khớp Gate tên đó VỪA có thể khớp permission cùng tên, nhưng ở đây không cái nào trùng nên luôn đánh giá theo permission Spatie (team-scoped) → `content_moderator` (không có permission này, chỉ có role) → sidebar không hiện link, dù trang vẫn truy cập bình thường nếu vào thẳng URL (Gate `viewDashboard` đã tự OR `isContentModerator()`). Verify thật: `$moderator->can('approval.view_dashboard')` = false, nhưng `Gate::forUser($moderator)->allows('viewDashboard')` = true. Đã sửa sidebar dùng đúng `@can('viewDashboard')`/`@can('viewApprovalHistory')`. **Bài học**: khi định nghĩa Gate riêng (không map thẳng 1 permission), luôn dùng ĐÚNG TÊN GATE ở mọi nơi kiểm tra (Controller, Blade) — không tự ý đổi sang chuỗi permission "tương ứng" nhìn có vẻ hợp lý.

Sidebar: mục "Bài viết chờ duyệt" đứng riêng (không nằm trong dropdown "Bài viết" hiện có, vì dropdown đó gate bằng `post_article.view` mà content_editor/content_head không có) — check trực tiếp `auth()->user()?->isContentEditor() || isContentHead()`.

Đã verify thật qua Controller: tạo + submit 1 bài → editor xem trang pending-review thấy đúng bài + tên tổ chức → marketing (đội của tổ chức, không phải editor/head) bị chặn 403 khi truy cập trang này.
