# Module Tích hợp n8n (N8n)

**Đặc tả Kỹ thuật — Khung nền (Base Scaffold), sẵn sàng để phát triển tính năng cụ thể sau**

**Phiên bản:** 2.3
**Ngày:** 2026-08-06
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module mới:** `Modules/N8n` (tạo bằng `php artisan module:make N8n`)
**Module phụ thuộc:** Không có module nghiệp vụ nào. Điểm mở rộng dùng event chuẩn của Laravel (§5.6). Về phân quyền, dùng lại cơ chế **Platform Roles** đã có sẵn (`app/Models/User.php`, `spec/Platform_RBAC_Technical_Specification.md`) — không phải "phụ thuộc module" theo nghĩa NWIDART, mà tái dùng 1 quy ước RBAC đã triển khai xong trên toàn hệ thống.
**Trạng thái:** Thiết kế nền — chưa có tính năng nghiệp vụ cụ thể nào, đúng chủ đích ("tích hợp chờ"). Không code trong lượt viết spec này.

> **v2.3 (vá lỗi validation ≠ DB constraint + chuẩn hoá chữ ký outbound — review vòng 2):**
> (1) **Sửa lỗi thật**: chính sách `name` ở v2.2 tự mâu thuẫn — comment nói "tên đã xoá mềm được
> phép tái sử dụng" nhưng `Rule::unique()` mặc định KHÔNG loại trừ soft-deleted (nó chạy qua query
> builder thô, không qua Eloquent global scope), nên validate PASS rồi INSERT vỡ unique constraint
> thật ở DB. Chốt lại: **`name` không bao giờ được tái sử dụng**, kể cả sau xoá mềm — khớp đúng
> hành vi `Rule::unique()` đã viết, không phải sửa code, chỉ sửa lại prose cho khớp thực tế (§2.1,
> §2.5, §7.1). (2) Chuẩn hoá tuyệt đối chuỗi byte được KÝ và được GỬI ở chiều outbound — encode
> JSON đúng 1 lần rồi dùng `Http::withBody()`, không để `Http::post($url, $payload)` tự encode lại
> (§4, §4.2 mới) — cùng nguyên tắc "ký trên raw bytes" đã áp cho chiều inbound (§5.3). (3) Polish:
> `event_name` case-sensitive (§5.3), bắt buộc `Content-Length`/từ chối chunked (§5.2), cách sinh
> `uuid` khớp quy ước `PostCategory` có sẵn (§2.1), giới hạn kích thước outbound thuộc trách nhiệm
> bên gọi (§4.2), thêm 2 test case soft-deleted lookup (§9).

> **v2.2 (chốt các điểm bảo mật/đúng đắn để implement không đoán mò — review must-fix):**
> bổ sung §2.5 (index), §3 mới (config đầy đủ + sinh/xoay secret + hành vi soft-delete), hợp đồng
> HMAC/body/response chính xác ở §5.3–§5.4, edge case của `N8nOutboundService` (exception vs
> `N8nSendResult`, outbound không ký), ví dụ request/response cụ thể (§5.9), validation rules
> (§7.1), chính sách log khi xác thực thất bại (§5.5), whitelist activity log (§7.2), testing
> strategy (§9). Đánh lại số mục để không còn nhảy cóc §2→§4.

> **v2.1 (chỉnh lại đối tượng quản lý — thuộc HỆ THỐNG, không thuộc tổ chức/doanh nghiệp nào):**
> bản v2.0 lỡ thiết kế `n8n_connections`/log theo mô hình multi-tenant mặc định của mọi model
> nghiệp vụ khác (`organization_id`, `extends TenantAwareModel`) — sai với đúng bản chất của module
> này: kết nối n8n là **hạ tầng tích hợp của platform**, do đội vận hành trung ương quản lý, không
> phải tính năng 1 tổ chức khách hàng tự cấu hình cho riêng mình. v2.1 bỏ hẳn `organization_id`
> khỏi cả 3 bảng, đổi RBAC từ `PermissionEnum`/feature-flag theo tổ chức (Lớp B — Organization
> Roles) sang **Platform Roles** (Lớp A) đã có sẵn — `platform_ops` (quản lý), `platform_viewer`
> (chỉ xem), `super-admin` (bypass mặc định qua `Gate::before`).

> **v2.0 (thu hẹp phạm vi — bỏ phụ thuộc `Modules\WorkflowAutomation`):** bản v1.0 thiết kế module
> này như 1 cầu nối vào engine `WorkflowAutomation` sẵn có. v2.0 tách hoàn toàn — module `N8n` tự
> đứng độc lập, dùng event chuẩn của Laravel làm cơ chế mở rộng thay vì đăng ký vào registry của
> module khác.

---

## 0. Quyết định đã chốt

- **Module độc lập hoàn toàn.** Không import, không extend, không đăng ký vào registry của module nào khác. Lý do: module này được viết ra để "chờ" — chưa biết chắc use case đầu tiên thuộc domain nào (Lead? Post? Assessment?) — gắn cứng vào 1 engine cụ thể ngay từ đầu sẽ ràng buộc lựa chọn tương lai không cần thiết.
- **1 endpoint nhận webhook duy nhất** cho mọi mục đích, không sinh route mới theo từng use case. Ánh xạ mọi lệnh gọi vào thành 1 Laravel event chuẩn (`N8nWebhookReceived`) — module tiêu thụ tự đăng ký listener theo cách chuẩn của Laravel, không cần sửa code của `N8n`.
- **1 service PHP duy nhất cho chiều gọi ra** (`N8nOutboundService::send()`), không phải "action step" gắn vào 1 workflow builder nào — bất kỳ Action/Job/Controller nào trong hệ thống gọi thẳng, giữ module `N8n` không biết gì về nghiệp vụ gọi nó.
- **Bảo mật theo từng "kết nối" (`N8nConnection`)**, không dùng 1 secret toàn cục — secret lộ ở 1 kết nối không ảnh hưởng kết nối khác. Secret/token luôn **do hệ thống sinh** (§3.2), không cho nhập tay.
- **Log đầy đủ CẢ 2 chiều** trong chính module này (`n8n_inbound_logs` + `n8n_outbound_logs`) — vì không còn dựa được vào lịch sử chạy của module khác.
- **Thuộc HỆ THỐNG, không thuộc bất kỳ tổ chức/doanh nghiệp nào.** `N8nConnection` KHÔNG có `organization_id`, KHÔNG `extends TenantAwareModel` — đây là hạ tầng tích hợp platform-wide, do đội vận hành trung ương (Platform Roles — `platform_ops`/`super-admin`) tạo và quản lý, không phải tính năng mà 1 tổ chức khách hàng tự cấu hình trong tenant của họ. Payload đi qua kết nối CÓ THỂ chứa dữ liệu thuộc về 1 tổ chức cụ thể (VD `lead_id` của tổ chức A) — nhưng đó là việc của module tiêu thụ (Listener tự tra `organization_id` từ chính bản ghi nghiệp vụ, VD từ `Lead` model), không phải việc của `N8n`.
- **Không lộ dữ liệu qua thông báo lỗi.** Mọi response lỗi ở endpoint inbound (§5.4) đều generic/tối thiểu — không phân biệt "token không tồn tại" với "token tồn tại nhưng sai chữ ký" bằng nội dung message khác nhau.

---

## 1. Bối cảnh & Mục tiêu

Nhiều ý tưởng tự động hoá (hiện tại chưa chốt cụ thể, sẽ phát sinh dần) đều cần chung 1 hạ tầng: nhận được lệnh gọi có xác thực từ n8n, và gọi ra n8n được khi cần. Thay vì làm lại phần hạ tầng này mỗi lần có ý tưởng mới, module `N8n` dựng sẵn phần **chung, ổn định, không đổi theo từng use case**:

1. Quản lý "kết nối" tới n8n ở tầng hệ thống (không thuộc tổ chức/doanh nghiệp nào) — tạo/sửa/tắt/xoay secret, không lộ secret ra lại sau khi lưu.
2. Nhận webhook từ n8n, xác thực (HMAC + tuỳ chọn IP allowlist + rate limit), chuẩn hoá thành 1 event nội bộ để phần còn lại của hệ thống lắng nghe.
3. Gọi webhook ra n8n theo "kết nối đặt tên" (không gõ tay URL/secret rải rác nhiều nơi).
4. Ghi log đầy đủ cả 2 chiều, kể cả những lệnh gọi vào KHÔNG khớp gì (sai token/chữ ký) — để tự debug khi tích hợp mới không như mong đợi.

**Phi mục tiêu (xem thêm §8):** không dựng UI canvas kéo-thả; không tự cài đặt/host n8n (dịch vụ ngoài, chạy tách biệt); không làm cơ chế discovery/catalog tự động cho n8n custom node ở v1; không làm replay-protection (timestamp+nonce) ở v1.

---

## 2. Kiến trúc dữ liệu

### 2.1 `n8n_connections` — đơn vị cấu hình trung tâm

Model `N8nConnection` — **KHÔNG** `extends TenantAwareModel` (khác mọi model nghiệp vụ khác trong Modules — xem lý do §0). Chỉ dùng trực tiếp `Illuminate\Database\Eloquent\Model` + `SoftDeletes` + `LogsActivity` (whitelist field log ở §7.2 — **không log giá trị secret/token/url dù đã encrypted**). Cùng mẫu với `Plan` (`vendor/laravelcm/laravel-subscriptions`, bảng `plans`) — model dữ liệu hệ thống có sẵn trong codebase, không `organization_id`, gate bằng permission/role chứ không bằng tenant scope.

```php
Schema::create('n8n_connections', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique(); // định danh public — sinh qua static::creating() gán (string) Str::uuid() nếu rỗng, ĐÚNG quy ước đã dùng ở PostCategory (Modules/Post/app/Models/PostCategory.php:57-62), không dùng trait HasUuids (khác cách PostCategory tự viết boot() thủ công, giữ nhất quán codebase thay vì trộn 2 cách)
    // KHÔNG có organization_id — xem §0.
    $table->string('name')->unique(); // BẮT BUỘC unique — N8nOutboundService::send() tra theo name/uuid (§4), trùng tên gây collision khó phát hiện. Unique index MySQL mặc định tính CẢ hàng soft-deleted — tên của 1 kết nối đã xoá mềm KHÔNG dùng lại được cho kết nối mới (§7.1), đây là quyết định CHỦ Ý (không phải hạn chế kỹ thuật chưa xử lý).
    $table->string('purpose_note', 500)->nullable(); // ghi chú mục đích, tự do — cho người sau biết kết nối này để làm gì
    $table->boolean('inbound_enabled')->default(false);
    $table->boolean('outbound_enabled')->default(false);
    $table->string('inbound_token', 64)->unique(); // KHÔNG nullable — sinh ngay lúc tạo (§3.2), luôn có URL cố định dù inbound_enabled=false
    $table->text('inbound_secret')->nullable();       // cast encrypted — n8n dùng để ký HMAC khi gọi VÀO app
    $table->text('outbound_webhook_url')->nullable(); // cast encrypted — app gọi RA n8n (URL trigger webhook của n8n)
    $table->text('outbound_secret')->nullable();      // cast encrypted — app dùng để ký HMAC khi gọi RA n8n; NULL = gửi không ký (§4.1)
    $table->json('allowed_ip_cidrs')->nullable();     // optional allowlist cho chiều inbound, để trống = không giới hạn IP
    $table->unsignedSmallInteger('rate_limit_per_minute')->nullable(); // để trống = dùng default config
    $table->timestamp('last_inbound_at')->nullable();
    $table->timestamp('last_outbound_at')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();
});
```

**Vì sao `inbound_token` không còn `nullable`**: 1 kết nối luôn có ĐÚNG 1 URL cố định ngay từ lúc tạo (kể cả khi `inbound_enabled=false` — URL tồn tại nhưng bị khoá ở bước 2 của §5.2), tránh trạng thái lấp lửng "kết nối có nhưng chưa có URL nào". Sinh tự động ở `CreateN8nConnectionAction`, không cho nhập tay (§3.2).

**Vì sao gộp `inbound_*` và `outbound_*` trên cùng 1 bản ghi** thay vì 2 bảng riêng: đa số ca dùng thực tế là 2 chiều của CÙNG 1 tích hợp (VD "app báo Lead mới sang n8n" đồng thời cũng cần "n8n báo lại kết quả gửi Zalo có thành công không") — gộp giúp người quản trị thấy trọn 1 mối quan hệ ở 1 chỗ. `inbound_enabled`/`outbound_enabled` độc lập, cho phép để trống 1 chiều khi tích hợp chỉ cần 1 chiều.

**Cast trong model:**
```php
protected $casts = [
    'inbound_secret'       => 'encrypted',
    'outbound_webhook_url' => 'encrypted',
    'outbound_secret'      => 'encrypted',
    'allowed_ip_cidrs'     => 'array',
];
```
Theo đúng tiền lệ đã có ở `Organization.ai_provider_config` (`'encrypted:array'`) — đây là dữ liệu credential, không được lưu plaintext. `inbound_token` **không** encrypted — nó đóng vai trò định tuyến (§2.2), cần query trực tiếp (`WHERE inbound_token = ?`) mà cast `encrypted` không cho phép (mỗi lần mã hoá ra ciphertext khác nhau dù cùng giá trị gốc).

### 2.2 Token vs. secret — 2 vai trò tách biệt

`inbound_token` nằm trong URL, có thể lộ qua log truy cập/proxy/trình duyệt — vai trò của nó chỉ là **định tuyến** (chọn đúng `N8nConnection`). Bí mật thật để **xác thực** là `inbound_secret`, dùng ký HMAC trên thân request, không nằm trong URL. Tách 2 vai trò để tránh lỗi kinh điển "coi URL bí mật là đủ bảo mật".

### 2.3 `n8n_inbound_logs` — audit MỌI lệnh gọi vào, kể cả không khớp gì

```php
Schema::create('n8n_inbound_logs', function (Blueprint $table) {
    $table->id();
    // KHÔNG có organization_id — xem §0. Payload có thể NHẮC tới 1 tổ chức cụ thể (VD lead_id),
    // nhưng đó là dữ liệu nghiệp vụ nằm trong payload_excerpt, không phải cột scope của bảng này.
    $table->foreignId('connection_id')->nullable()->constrained('n8n_connections')->nullOnDelete();
    $table->string('ip_address', 45)->nullable();
    $table->boolean('signature_valid')->nullable(); // null = kết nối không cấu hình secret (không xác thực chữ ký)
    $table->unsignedSmallInteger('http_status_returned');
    $table->string('event_name', 100)->nullable();  // do n8n tự đặt trong JSON body, tự do — xem §5.3
    $table->unsignedTinyInteger('listener_count')->default(0); // số listener Laravel thực sự đăng ký cho event N8nWebhookReceived (§5.6) — 0 vẫn hợp lệ, nghĩa là "nhận đúng, xác thực đúng, nhưng chưa module nào lắng nghe"
    $table->text('payload_excerpt')->nullable(); // CẮT NGẮN, CHỈ ghi khi xác thực chữ ký thành công — xem §5.5
    $table->string('error_message', 500)->nullable();
    $table->timestamp('received_at');

    $table->index(['connection_id', 'received_at']); // trang log lọc theo 1 kết nối, sort theo thời gian — Tabulator
    $table->index('received_at');                    // purge job quét theo ngày (§5.7), dashboard "log gần đây"
    $table->index('event_name');                     // lọc theo event_name khi debug 1 use case cụ thể
    $table->index('signature_valid');                // lọc nhanh "mọi lệnh gọi xác thực thất bại" (giám sát tấn công/nhầm cấu hình)
});
```

**Không dùng `TenantAwareModel`** cho bảng này (không soft-delete, không activity log, không `organization_id`) — đây là bảng audit tần suất cao, mục đích chỉ để debug ngắn hạn rồi tự dọn (§5.7), không phải dữ liệu nghiệp vụ cần theo dõi thay đổi hay scope theo tổ chức.

### 2.4 `n8n_outbound_logs` — vì module không còn dựa vào lịch sử chạy của module khác

```php
Schema::create('n8n_outbound_logs', function (Blueprint $table) {
    $table->id();
    // KHÔNG có organization_id — xem §0.
    $table->foreignId('connection_id')->constrained('n8n_connections')->cascadeOnDelete();
    $table->string('event_name', 100)->nullable(); // nhãn tự do phía gọi truyền vào, cho dễ tra log sau này
    $table->string('caller', 150)->nullable();      // FQCN của Action/Job đã gọi — tự khai báo, để biết lệnh gọi xuất phát từ đâu khi audit
    $table->boolean('success');
    $table->unsignedSmallInteger('http_status')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->string('error_message', 500)->nullable();
    $table->text('payload_excerpt')->nullable(); // cùng nguyên tắc cắt ngắn với §5.5 — chiều này bên gọi TỰ chủ động gửi, không phải input không tin cậy, nên luôn ghi được (khác chiều inbound)
    $table->timestamp('requested_at');

    $table->index(['connection_id', 'requested_at']);
    $table->index('requested_at');
    $table->index(['success', 'requested_at']); // dashboard "tỷ lệ lỗi gần đây theo kết nối"
});
```

Không dùng `TenantAwareModel` (cùng lý do §2.3) — bảng audit, không phải thực thể nghiệp vụ.

### 2.5 Hành vi Soft Delete — tra cứu vs. lưu vết lịch sử

- **Tra cứu (lookup) LUÔN loại trừ bản ghi đã xoá mềm** — mặc định Eloquent (`SoftDeletes` tự thêm `whereNull('deleted_at')`), không override:
  - Inbound theo `inbound_token` (§5.2 bước 1): kết nối đã xoá mềm → coi như không tồn tại → `404`, giống hệt token sai.
  - Outbound theo `name`/`uuid` (§4): kết nối đã xoá mềm → coi như không tồn tại → ném `N8nConnectionNotFoundException` (§4.1).
- **Log lịch sử KHÔNG mất liên kết khi xoá mềm** — `connection_id` trên `n8n_inbound_logs`/`n8n_outbound_logs` vẫn trỏ đúng bản ghi đã xoá mềm (query log không tự động ẩn theo `deleted_at` của bảng khác); `nullOnDelete()` chỉ kích hoạt khi **xoá cứng** (`forceDelete()`), việc mà UI quản trị (§7) không cung cấp — chỉ có nút "Tắt" (set `inbound_enabled`/`outbound_enabled = false`) và "Xoá" (soft delete). Đây là hành vi ĐÚNG và có chủ đích: lịch sử vẫn tra cứu được "kết nối X đã gọi Y lần trước khi bị xoá", chỉ khi nào dọn dữ liệu triệt để (thao tác DB trực tiếp, ngoài phạm vi UI) mới mất liên kết.
- **`name` không bao giờ được giải phóng** (§7.1) — nhất quán với gạch đầu dòng trên: log lịch sử tham chiếu 1 kết nối theo `connection_id` (khoá ngoại), nhưng người xem log tra cứu bằng MẮT qua cột `name` hiển thị trên UI — nếu tên được tái sử dụng, 2 kết nối khác nhau ở 2 thời điểm sẽ hiện CÙNG 1 tên trên cùng 1 trang log, gây nhầm lẫn ngay cả khi `connection_id` phía sau vẫn đúng.

---

## 3. Cấu hình hệ thống & Bảo mật kết nối

### 3.1 `config/n8n.php` — đầy đủ default + mô tả

```php
return [
    // Xoá n8n_inbound_logs/n8n_outbound_logs cũ hơn N ngày — chạy bởi PurgeOldN8nLogsAction (§5.7)
    'log_retain_days' => env('N8N_LOG_RETAIN_DAYS', 30),

    // Số ký tự đầu của JSON body được lưu vào payload_excerpt — không lưu nguyên văn (§5.5)
    'log_payload_max_chars' => env('N8N_LOG_PAYLOAD_MAX_CHARS', 2000),

    // Rate limit mặc định cho 1 kết nối KHÔNG tự đặt rate_limit_per_minute riêng (§5.8)
    'default_rate_limit_per_minute' => env('N8N_DEFAULT_RATE_LIMIT', 60),

    // Timeout (giây) khi app gọi RA n8n qua N8nOutboundService::send() (§4)
    'outbound_timeout' => env('N8N_OUTBOUND_TIMEOUT', 10),

    // Số lần thử lại khi gọi RA n8n gặp lỗi mạng/timeout tạm thời (Http::retry(), không tính lỗi 4xx/5xx nghiệp vụ)
    'outbound_max_retries' => env('N8N_OUTBOUND_MAX_RETRIES', 2),

    // Giới hạn kích thước body inbound (byte) — request vượt quá bị từ chối ở bước parse (§5.2 bước 5), TRƯỚC khi đọc hết vào memory nếu framework hỗ trợ chặn sớm theo Content-Length
    'max_inbound_body_size' => env('N8N_MAX_INBOUND_BODY_SIZE', 1_048_576), // 1MB

    // Tên header n8n dùng gửi kèm chữ ký HMAC — đổi được nếu sau này cần tương thích công cụ khác
    // đặt tên header cố định (VD Make/Zapier dùng tên khác) — mặc định theo quy ước riêng platform
    'signature_header' => env('N8N_SIGNATURE_HEADER', 'X-N8n-Signature'),
];
```

### 3.2 Sinh & xoay secret/token — luôn do hệ thống sinh, không nhận input tay

- **Thuật toán**: `bin2hex(random_bytes(32))` — chuỗi hex 64 ký tự, 256-bit entropy, đủ cho cả `inbound_token` (định tuyến) lẫn `inbound_secret`/`outbound_secret` (bí mật xác thực). Không dùng `Str::random()` (dựa trên `random_bytes()` nhưng bảng chữ cái base62 — vẫn đủ ngẫu nhiên, nhưng hex thống nhất dễ so sánh/copy hơn khi debug).
- **Hiển thị 1 LẦN DUY NHẤT**: `CreateN8nConnectionAction`/`RotateN8nConnectionSecretAction` trả giá trị plaintext trong response ngay sau khi tạo/xoay — UI hiện trong 1 khối có nút "Copy", kèm cảnh báo "sẽ không hiển thị lại". Từ request sau, API/form sửa chỉ trả về placeholder đã che (VD `sk_••••••••1a2b`, giữ 4 ký tự cuối để phân biệt các secret khi xoay nhiều lần) — KHÔNG bao giờ giải mã và trả `inbound_secret`/`outbound_secret`/`inbound_token` đầy đủ qua bất kỳ endpoint GET nào sau lần tạo/xoay đầu tiên.
- **`RotateN8nConnectionSecretAction` — xoay CHỌN LỌC**, 3 tham số độc lập (không bắt buộc xoay cả 3 cùng lúc):
  ```php
  public function handle(N8nConnection $connection, bool $rotateInboundToken = false, bool $rotateInboundSecret = false, bool $rotateOutboundSecret = false): array
  ```
  Trả về mảng chỉ chứa giá trị plaintext của (các) field VỪA xoay (VD chỉ xoay `outbound_secret` thì không trả lại `inbound_token` cũ, tránh hiểu lầm là giá trị cũng vừa đổi).
- **Xoay `inbound_token` = ĐỔI URL webhook** — khác bản chất xoay secret (URL giữ nguyên, chỉ đổi bí mật ký). Ghi rõ trong UI (dòng cảnh báo đỏ ngay cạnh nút "Tạo token mới": "Thao tác này đổi URL nhận webhook — phải cập nhật lại URL trong n8n, URL cũ ngừng nhận request ngay lập tức") và trong runbook vận hành (không thuộc phạm vi spec này, nhưng cần note ở Phase triển khai — §10).
- **Validate ở tầng nhập liệu**: người dùng KHÔNG có field nào để tự nhập `inbound_token`/`inbound_secret`/`outbound_secret` trong form tạo/sửa — chỉ có nút hành động ("Tạo kết nối" tự sinh token, "Xoay token"/"Xoay inbound secret"/"Xoay outbound secret" riêng biệt). `outbound_webhook_url` là field duy nhất trong nhóm "bí mật" cho phép nhập tay (vì đó là URL của n8n, không phải secret do hệ thống này sinh).

---

## 4. Chiều App → n8n: `N8nOutboundService`

```php
namespace Modules\N8n\Services;

use Modules\N8n\Data\N8nSendResult;
use Modules\N8n\Exceptions\N8nConnectionNotFoundException;
use Modules\N8n\Exceptions\N8nOutboundDisabledException;

class N8nOutboundService
{
    /**
     * Gọi 1 kết nối n8n theo tên hoặc uuid. Bất kỳ Action/Job/Controller nào trong
     * hệ thống đều gọi thẳng — module N8n không biết và không cần biết ai gọi nó.
     *
     * @param string $connection  name hoặc uuid của N8nConnection
     * @param array  $payload     dữ liệu gửi đi, tự do theo nhu cầu bên gọi
     * @param string|null $eventName  nhãn tự do, chỉ để ghi log — n8n phía nhận tự đọc trong payload nếu cần phân biệt
     * @param string|null $caller     FQCN của class đang gọi (VD static::class) — chỉ để ghi log, không bắt buộc
     *
     * @throws N8nConnectionNotFoundException  không tìm thấy connection theo name/uuid (kể cả đã soft-delete, §2.5) — LỖI CẤU HÌNH/LẬP TRÌNH (tên gõ sai), nên throw để fail nhanh và lộ ra ngay ở test/log lỗi, KHÔNG nuốt thành success=false
     * @throws N8nOutboundDisabledException    connection tồn tại nhưng outbound_enabled=false hoặc outbound_webhook_url rỗng — cũng là lỗi CẤU HÌNH (ai đó tắt kết nối mà code vẫn gọi), throw để phát hiện sớm
     */
    public function send(string $connection, array $payload, ?string $eventName = null, ?string $caller = null): N8nSendResult
    {
        // 1. Tra N8nConnection theo name HOẶC uuid (loại trừ soft-deleted, §2.5) — không thấy → throw N8nConnectionNotFoundException
        // 2. outbound_enabled=false HOẶC outbound_webhook_url rỗng → throw N8nOutboundDisabledException
        // 3. $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) — encode ĐÚNG 1 LẦN,
        //    dùng lại nguyên chuỗi $body này cho CẢ việc ký (bước tiếp) LẪN việc gửi (bước 4) — xem §4.2 vì sao.
        // 3b. Nếu outbound_secret CÓ cấu hình: $signature = hash_hmac('sha256', $body, $connection->outbound_secret) (hex lowercase, cùng thuật toán §5.3)
        //     Nếu outbound_secret RỖNG: bỏ qua bước ký, gửi KHÔNG có header chữ ký (§4.1) — đây là lựa chọn hợp lệ, không phải lỗi
        // 4. Http::withBody($body, 'application/json')->withHeaders([config('n8n.signature_header') => $signature, ...])
        //        ->timeout(config('n8n.outbound_timeout'))->retry(config('n8n.outbound_max_retries'), 500)->post($connection->outbound_webhook_url)
        //    — LỖI MẠNG/HTTP ở bước này (timeout, 4xx/5xx từ n8n, DNS...) KHÔNG throw — bắt bằng try/catch, gói vào N8nSendResult(success: false, ...)
        // 5. Ghi n8n_outbound_logs (§2.4), cập nhật N8nConnection.last_outbound_at
        // 6. Trả N8nSendResult (success, httpStatus, durationMs, errorMessage)
    }
}
```

### 4.1 Nguyên tắc phân loại lỗi — vì sao vừa throw exception vừa trả `N8nSendResult`

Không chọn "luôn throw" hay "luôn trả kết quả" cho MỌI trường hợp lỗi, mà tách theo **ai gây ra lỗi và có sửa được ngay không**:

- **Lỗi cấu hình/lập trình** (tên kết nối gõ sai, kết nối bị tắt mà code vẫn gọi) — luôn CÓ THỂ sửa ngay bằng cách sửa code/cấu hình, và im lặng bỏ qua sẽ khiến bug ẩn rất lâu (VD gõ sai `'n8n-marketing-atuomation'`, mọi lần gọi đều "thất bại" nhưng không ai biết vì code chỉ check `if (!$result->success)` rồi log warning chìm nghỉm). Throw exception để test suite đỏ ngay, log lỗi có stack trace đầy đủ.
- **Lỗi vận hành tạm thời** (n8n đang down, timeout mạng, n8n trả `500`) — KHÔNG phải lỗi lập trình, xảy ra bình thường trong vận hành thật, bên gọi cần 1 giá trị để tự quyết định tiếp (thử lại sau, bỏ qua, ghi log riêng) — trả `N8nSendResult(success: false)`, không throw, để 1 lần n8n down không làm crash toàn bộ luồng nghiệp vụ đang gọi nó (VD `CreateLeadAction` không nên fail cả việc tạo Lead chỉ vì n8n tạm thời không phản hồi).

**`outbound_secret = null` → cho phép gửi KHÔNG ký**, không bắt buộc: một số kết nối test/nội bộ ban đầu có thể chưa cần xác thực chiều outbound (n8n tự lọc theo URL bí mật), bắt buộc ký ngay từ đầu sẽ cản việc thử nghiệm nhanh. Khi gửi không ký, `N8nOutboundLog.error_message` vẫn để trống (đây không phải lỗi) nhưng UI danh sách kết nối hiển thị badge "Outbound: chưa ký" để nhắc người quản trị.

### 4.2 Vì sao encode JSON đúng 1 lần rồi gửi bằng `withBody()`, không để `Http::post($url, $payload)` tự encode

`Http::post($url, $payload)` (truyền mảng PHP làm tham số thứ 2) để Laravel's HTTP client **tự** `json_encode()` bên trong lúc build request — xảy ra SAU bước ký ở 3b nếu code viết theo lối "ký rồi mới gọi `post($url, $payload)`". Guzzle/Laravel không cam kết dùng CHÍNH XÁC cùng flags (`JSON_UNESCAPED_UNICODE`/`JSON_UNESCAPED_SLASHES`) hay cùng 1 lần gọi `json_encode()` như chuỗi đã tự tay encode để ký — dù nội dung logic giống hệt, 2 chuỗi byte có thể khác nhau (VD cách escape ký tự Unicode/dấu `/`), khiến n8n tính lại HMAC trên **byte thực nhận được** ra kết quả khác chữ ký đã gửi, xác thực fail dù dữ liệu hoàn toàn đúng. Encode đúng 1 lần thành biến `$body`, dùng NGUYÊN chuỗi đó cho cả bước ký lẫn bước gửi (`Http::withBody($body, 'application/json')`, không phải `post($url, $payload)`) loại bỏ hoàn toàn rủi ro lệch byte này — cùng nguyên tắc "ký trên raw body, không ký trên dữ liệu đã parse/encode lại" đã áp dụng cho chiều inbound (§5.3).

`N8nSendResult` (`Modules\N8n\Data\N8nSendResult`) — DTO đơn giản (`readonly` properties: `success`, `httpStatus`, `durationMs`, `errorMessage`).

**Kích thước payload outbound: module `N8n` không giới hạn** — khác chiều inbound (`max_inbound_body_size`, §3.1, cần thiết vì input không tin cậy từ bên ngoài), payload outbound do chính code nội bộ tự soạn nên trách nhiệm giới hạn kích thước (nếu cần) thuộc về bên gọi `N8n::send()`, không phải module này.

**Facade tiện dụng (tuỳ chọn, không bắt buộc dùng):**
```php
N8n::send('n8n-marketing-automation', ['lead_id' => $lead->id, 'stage' => $lead->stage->name]);
```
`Modules\N8n\Facades\N8n` trỏ vào `N8nOutboundService` — chỉ để gọi gọn hơn `app(N8nOutboundService::class)`, không thêm logic.

**Đồng bộ hay bất đồng bộ:** `send()` mặc định chạy đồng bộ (HTTP call chờ response, kể cả retry ở bước 4). Bên gọi tự quyết định bọc trong 1 Job nếu cần chạy nền — module `N8n` không áp đặt queue driver hay tự ý queue hộ, vì nhu cầu đồng bộ/bất đồng bộ khác nhau tuỳ nghiệp vụ gọi.

---

## 5. Chiều n8n → App: inbound webhook

### 5.1 Route

```php
// routes/api.php — KHÔNG áp middleware 'auth'/'web' (CSRF), vì n8n là server-to-server,
// không có session/cookie nào để mang theo. Bảo mật hoàn toàn dựa vào token định tuyến + HMAC.
Route::post('api/n8n/in/{token}', [N8nInboundWebhookController::class, 'handle'])
    ->middleware(['throttle:n8n-inbound']) // named limiter, §5.8
    ->name('n8n.inbound');
```

`{token}` = `N8nConnection.inbound_token`. 1 kết nối = 1 URL cố định, không đổi trừ khi chủ động tạo lại (xem §3.2 — xoay token đổi URL, xoay secret giữ nguyên URL nhưng n8n cần cập nhật lại header ký).

### 5.2 `HandleInboundN8nCallAction` — trình tự xử lý

1. Tra `N8nConnection` theo `inbound_token` (loại trừ soft-deleted, §2.5). Không tìm thấy → ghi log tối thiểu (`connection_id = null`, không có `payload_excerpt` — §5.5), trả `404` generic.
2. `inbound_enabled = false` → log + trả `404` generic (**không** `410 Gone` — `410` tiết lộ "kết nối từng tồn tại/đang tồn tại nhưng bị tắt", khác nguyên tắc "không lộ dữ liệu qua lỗi" ở §0; đổi từ bản trước dùng `410`).
3. Kiểm tra `Content-Type` phải là `application/json` và **bắt buộc phải có** header `Content-Length` (từ chối request `Transfer-Encoding: chunked` — không hỗ trợ, vì n8n's HTTP Request/Webhook node luôn gửi kèm `Content-Length` trong thực tế, nên giới hạn này không cản use case thật nào mà đổi lại tránh phải đọc hết body vào bộ nhớ mới biết kích thước) không vượt `config('n8n.max_inbound_body_size')` → sai (thiếu header, chunked, hoặc vượt giới hạn) → log + trả `400` generic, KHÔNG đọc tiếp body.
4. Đọc **raw body** bằng `$request->getContent()` — KHÔNG dùng `$request->all()`/`$request->json()` ở bước xác thực chữ ký (§5.3 giải thích vì sao raw body bắt buộc).
5. Kiểm tra `allowed_ip_cidrs` nếu có cấu hình → không khớp thì log + trả `403` generic.
6. Nếu `inbound_secret` có cấu hình: xác minh chữ ký theo đúng hợp đồng §5.3 → sai → log (`signature_valid=false`, không lưu `payload_excerpt`) + trả `401` generic.
7. Parse JSON từ raw body → lỗi cú pháp/rỗng → log (`signature_valid` giữ nguyên kết quả bước 6) + trả `400` generic, KHÔNG dispatch event.
8. Lấy `event_name` (top-level key trong JSON, tự do — n8n tự chọn giá trị, xem §5.3-body) → dispatch `N8nWebhookReceived` (§5.6).
9. Đếm số listener thực sự đăng ký cho `N8nWebhookReceived::class` (`Event::getListeners()`) → ghi vào `listener_count`.
10. Ghi `n8n_inbound_logs` đầy đủ (kể cả `payload_excerpt`, vì đã qua xác thực — §5.5), cập nhật `N8nConnection.last_inbound_at`.
11. Trả `202` với body rỗng.

### 5.3 Hợp đồng chữ ký (HMAC) — chính xác, không mơ hồ

- **Input ký**: **raw request body** (chuỗi byte gốc, chưa parse JSON) — `$request->getContent()`. Ký trên JSON đã parse-rồi-encode-lại là lỗi kinh điển vì thứ tự key/khoảng trắng có thể khác bản gốc n8n đã ký, khiến chữ ký không khớp dù nội dung "giống nhau" về mặt logic.
- **Thuật toán**: `hash_hmac('sha256', $rawBody, $connection->inbound_secret)`.
- **Định dạng output**: **hex thường (lowercase)**, KHÔNG thêm tiền tố (khác quy ước `sha256=...` của Stripe/GitHub) — chọn hex trần vì nhất quán với cách sinh secret/token ở §3.2 (cũng hex), giảm 1 bước strip tiền tố khi so sánh. Ghi rõ trong tài liệu tích hợp gửi cho người cấu hình n8n: n8n's "Crypto" node hoặc code node tự tính `HMAC-SHA256` trên body rồi set vào header dưới dạng hex, KHÔNG base64.
- **Header**: tên đọc từ `config('n8n.signature_header')`, mặc định `X-N8n-Signature` (§3.1).
- **So sánh**: `hash_equals($expectedHex, $providedHex)` — bắt buộc dùng hàm chống timing-attack, không dùng `===`.
- **Body contract** (áp dụng khi xác thực chữ ký VÀ khi parse ở bước 7 của §5.2):
  - `Content-Type: application/json` bắt buộc.
  - Body là 1 JSON object (không phải mảng/scalar ở top-level).
  - `event_name` (string, optional) — n8n tự đặt tên sự kiện, không có danh sách cố định trước (§0 — module `N8n` không biết trước use case nào). So sánh **case-sensitive** (`'lead_created' !== 'Lead_Created'`) ở mọi nơi so khớp giá trị này (Listener tự lọc, §5.6) — module `N8n` không tự động lowercase/chuẩn hoá giá trị, bên cấu hình n8n và bên viết Listener phải thống nhất đúng 1 cách viết.
  - Các key khác tự do theo nhu cầu tích hợp, đi thẳng vào `$event->payload` (§5.6).

### 5.4 Response policy — không phân biệt lý do lỗi qua nội dung trả về

Mọi response lỗi (`400`/`401`/`403`/`404`) trả **body rỗng hoặc JSON tối thiểu cố định** (VD `{"error": "invalid_request"}` dùng chung cho mọi mã lỗi không phải để phân biệt nguyên nhân, chỉ để n8n biết là JSON hợp lệ nếu cần parse) — KHÔNG bao giờ trả message kiểu "token không tồn tại" khác "chữ ký sai" khác "IP bị chặn", tránh lộ thông tin giúp kẻ tấn công dò từng bước. Log nội bộ (`error_message` trong `n8n_inbound_logs`) VẪN ghi chi tiết thật — phân biệt chỉ ẩn ở **response ra ngoài**, không ẩn ở log admin xem được.

### 5.5 Chính sách log khi xác thực thất bại — KHÔNG lưu payload của request chưa xác thực

`payload_excerpt` **chỉ được ghi khi chữ ký xác thực THÀNH CÔNG** (hoặc khi kết nối chủ động không cấu hình secret — 1 lựa chọn tường minh của người quản trị, không phải lỗ hổng). Với mọi trường hợp thất bại ở bước 1-6 của §5.2 (token sai, kết nối tắt, IP không khớp, chữ ký sai), `payload_excerpt` để `NULL` — chỉ ghi metadata (IP, thời gian, mã lỗi, `error_message`). Lý do: nội dung 1 request CHƯA xác thực là **dữ liệu do kẻ tấn công tuỳ ý kiểm soát** — lưu nó vào DB (dù chỉ để debug) là lưu dữ liệu không tin cậy vô điều kiện, rủi ro nếu sau này có tính năng hiển thị lại `payload_excerpt` ra UI (XSS lưu trữ) hoặc log chứa nội dung nhạy cảm kẻ tấn công cố tình nhét vào để dò xét hệ thống ghi log thế nào.

`payload_excerpt` cắt tối đa `config('n8n.log_payload_max_chars')` ký tự (mặc định 2000, §3.1) ngay cả khi đã xác thực — không lưu nguyên văn vô thời hạn.

### 5.6 `N8nWebhookReceived` — điểm mở rộng duy nhất, dùng event chuẩn của Laravel

```php
namespace Modules\N8n\Events;

class N8nWebhookReceived
{
    public function __construct(
        public readonly \Modules\N8n\Models\N8nConnection $connection,
        public readonly ?string $eventName,
        public readonly array $payload,
        public readonly \DateTimeInterface $receivedAt,
    ) {}
}
```

**Cách 1 module khác lắng nghe** (không cần sửa gì trong `N8n`, chỉ code ở module tiêu thụ):

```php
// Trong EventServiceProvider của module BẤT KỲ, VD Modules\Lead\Providers\EventServiceProvider
protected $listen = [
    \Modules\N8n\Events\N8nWebhookReceived::class => [
        \Modules\Lead\Listeners\ImportLeadFromN8n::class,
    ],
];
```

```php
class ImportLeadFromN8n implements \Illuminate\Contracts\Queue\ShouldQueue // nên queue nếu xử lý tốn thời gian
{
    public function handle(N8nWebhookReceived $event): void
    {
        if ($event->eventName !== 'new_lead_from_ads') return; // tự lọc theo event_name, KHÔNG có cơ chế lọc nào ở tầng N8n
        // ... xử lý $event->payload
    }
}
```

Đây chính là toàn bộ cơ chế "dễ mở rộng" của module: **1 module mới muốn phản ứng với n8n = thêm 1 Listener + đăng ký trong `EventServiceProvider` của chính module đó**. Module `N8n` không cần biết trước có bao nhiêu use case, không cần registry/config trung gian nào khác.

**Vì sao không thêm 1 lớp "điều kiện lọc" ở tầng `N8n`**: việc lọc theo `event_name` (hoặc bất kỳ field nào khác trong `payload`) là 1 câu `if` trong chính Listener — thêm 1 lớp cấu hình trung gian chỉ để làm lại việc `if` đã làm được, tăng phức tạp mà không thêm khả năng gì mới.

### 5.7 Dọn log định kỳ

`PurgeOldN8nLogsAction` — xoá `n8n_inbound_logs`/`n8n_outbound_logs` cũ hơn `config('n8n.log_retain_days', 30)`, đăng ký trong `routes/console.php` bằng `Schedule::call()`.

### 5.8 Rate limit — resolve theo connection TRƯỚC khi vào Controller

Named limiter `n8n-inbound` khai báo trong `N8nServiceProvider::boot()` bằng `RateLimiter::for()`, closure nhận `Request $request` — **tự tra `N8nConnection` theo `$request->route('token')` NGAY TRONG closure này** (route model binding thường không dùng ở đây vì cần xử lý "không tìm thấy" bằng generic response thay vì Laravel tự trả `404` mặc định của model binding):

```php
RateLimiter::for('n8n-inbound', function (Request $request) {
    $connection = N8nConnection::where('inbound_token', $request->route('token'))->first();
    $limit = $connection?->rate_limit_per_minute ?? config('n8n.default_rate_limit_per_minute');

    return Limit::perMinute($limit)->by($request->route('token'));
});
```

Vượt rate limit → Laravel tự trả `429` (không qua `HandleInboundN8nCallAction`, nên KHÔNG có bản ghi trong `n8n_inbound_logs` cho lần bị chặn — đây là giới hạn đã biết, chấp nhận được vì rate-limit hit thường xuyên không cần audit từng lần, khác lỗi xác thực).

### 5.9 Ví dụ cụ thể — request/response

**Request từ n8n:**
```http
POST /api/n8n/in/7f3a9c1e4b2d6f8a0c5e7b9d1f3a5c7e HTTP/1.1
Host: app.familiesforlife.vn
Content-Type: application/json
X-N8n-Signature: 3b1e7f... (hex 64 ký tự, HMAC-SHA256 của raw body bên dưới)

{"event_name":"lead_created_from_ads","lead_source":"facebook","campaign_id":"CMP-2026-08","raw_data":{"name":"Nguyễn Văn A","phone":"09xxxxxxxx"}}
```

**Response thành công:**
```http
HTTP/1.1 202 Accepted
Content-Type: application/json

{}
```

**Response chữ ký sai (hoặc bất kỳ lỗi xác thực nào khác — cùng hình dạng, khác mã HTTP):**
```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{"error":"invalid_request"}
```

---

## 6. Phân quyền — Platform Roles (Lớp A), không phải Organization Roles (Lớp B)

**Vì sao không dùng `PermissionEnum`/feature-flag theo tổ chức**: `PermissionEnum` (`app/Enums/PermissionEnum.php`) là permission của **Lớp B — Organization Roles** (`spec/Platform_RBAC_Technical_Specification.md` §2) — gán qua Spatie team-scoped role (`config('permission.teams') = true`, `team_foreign_key = organization_id`). Kết nối n8n không thuộc tổ chức nào (§0) nên không có "team" nào để scope permission theo.

**Dùng lại Platform Roles đã triển khai sẵn** (`app/Models/User.php:157-259`, `organization_id = null` ở tầng User — hoàn toàn tách khỏi Spatie team scoping):

| Role | Method trên `User` | Quyền trên `N8nConnection`/log |
|---|---|---|
| `super-admin` | `hasRole('super-admin')` | Toàn quyền, bypass mặc định qua `Gate::before` (`app/Providers/AppServiceProvider.php`) — không cần khai gì thêm |
| `platform_ops` | `isPlatformOps()` | Tạo/sửa/tắt/xoay secret kết nối, xem log — mở rộng phạm vi role đã có (hiện `platform_ops` mới chỉ quản Subscription) thay vì tạo role mới |
| `platform_viewer` | `isPlatformViewer()` | Chỉ xem danh sách kết nối (ẩn secret/token) + log — không sửa, không xoay secret, không tạo mới |
| 8 role `RoleEnum` (CEO/Ops/... trong 1 tổ chức) | — | **Không có quyền gì** — role Lớp B, không áp dụng cho tài nguyên Lớp A |

**Route** — theo đúng mẫu `dashboard/subscription/admin/*` và `dashboard/platform-users` đã có: **không** gắn middleware `tenant`, gate bằng kiểm tra role trực tiếp:

```php
// routes/web.php — Modules/N8n
Route::middleware(['auth']) // KHÔNG có 'tenant' — đúng mẫu dashboard/platform-users
    ->prefix('dashboard/n8n')
    ->group(function () {
        Route::get('connections',                              [N8nConnectionController::class, 'index']);   // platform_ops HOẶC platform_viewer
        Route::post('connections',                              [N8nConnectionController::class, 'store'])->middleware('can:manage-n8n');
        Route::put('connections/{connection}',                  [N8nConnectionController::class, 'update'])->middleware('can:manage-n8n');
        Route::delete('connections/{connection}',               [N8nConnectionController::class, 'destroy'])->middleware('can:manage-n8n'); // soft-delete
        Route::post('connections/{connection}/restore',         [N8nConnectionController::class, 'restore'])->middleware('can:manage-n8n');
        Route::post('connections/{connection}/rotate',          [N8nConnectionController::class, 'rotate'])->middleware('can:manage-n8n'); // body: {rotate_inbound_token, rotate_inbound_secret, rotate_outbound_secret} — §3.2
        Route::get('logs',                                      [N8nLogController::class, 'index']);          // platform_ops HOẶC platform_viewer
    });
```

`Gate::define('manage-n8n', fn ($user) => $user->isPlatformOps())` khai báo trong `N8nServiceProvider::boot()` — `super-admin` tự bypass qua `Gate::before`. Route xem (`index`) kiểm tra thẳng trong Controller (`abort_unless($user->isPlatformOps() || $user->isPlatformViewer(), 403)`, cùng khuôn `PlatformUserController.php:29`).

**Menu/sidebar**: đặt cùng nhóm với "Quản lý Subscription" (mục dành cho `platform_ops`) — không xuất hiện trong sidebar của bất kỳ tổ chức nào.

**Không cần `TenantContext::runForOrganization()`** (quy tắc bắt buộc ở §3.4 spec Platform RBAC cho MỌI trang Platform-facing hiển thị dữ liệu của 1 Organization cụ thể) — `N8nConnection` không thuộc Organization nào.

---

## 7. Cấu trúc module

### 7.1 Validation rules (FormRequest)

```php
class StoreN8nConnectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:150', Rule::unique('n8n_connections', 'name')], // Rule::unique() chạy qua query builder thô, KHÔNG qua Eloquent nên KHÔNG tự loại trừ soft-deleted — khớp ĐÚNG với unique index DB (dòng trên): validation và ràng buộc DB đồng nhất, không có khoảng hở "pass validation rồi vỡ ở INSERT"
            'purpose_note'            => ['nullable', 'string', 'max:500'],
            'inbound_enabled'         => ['boolean'],
            'outbound_enabled'        => ['boolean'],
            'outbound_webhook_url'    => ['required_if:outbound_enabled,true', 'nullable', 'url', 'max:2000'],
            'allowed_ip_cidrs'        => ['nullable', 'array'],
            'allowed_ip_cidrs.*'      => ['string', new ValidCidr], // rule tuỳ chỉnh — chấp nhận CIDR IPv4 VÀ IPv6 (VD '203.0.113.0/24', '2001:db8::/32')
            'rate_limit_per_minute'   => ['nullable', 'integer', 'min:1', 'max:6000'],
        ];
    }
}
```

**Chính sách `name`: KHÔNG tái sử dụng tên của kết nối đã xoá mềm** (đổi từ bản trước — bản đó lỡ viết "cho phép tái sử dụng" trong khi `Rule::unique()` mặc định KHÔNG loại trừ soft-deleted, tức validation với DB đã không khớp nhau: validation tưởng cho tái dùng nhưng insert sẽ vỡ ràng buộc unique thật ở DB). Chốt lại: `name` là định danh vĩnh viễn, kể cả sau khi xoá mềm — tránh nhầm lẫn khi tra lịch sử log cũ ("kết nối X" trong `n8n_outbound_logs` luôn chỉ đúng 1 kết nối duy nhất, không bao giờ trỏ nhầm sang 1 kết nối MỚI vô tình trùng tên với 1 kết nối CŨ đã xoá). Muốn dùng lại đúng cái tên đó, phải `restore()` bản ghi cũ (§6) thay vì tạo mới — và vì tên không bao giờ được giải phóng, `restore()` **không có rủi ro trùng tên** với bất kỳ kết nối nào khác đang tồn tại. Endpoint sửa (`UpdateN8nConnectionRequest`) dùng `Rule::unique(...)->ignore($connection->id)` như thường lệ — vẫn tính cả trashed vì `ignore()` chỉ loại trừ đúng bản ghi đang sửa, không loại trừ toàn bộ trashed khác.

### 7.2 Activity log — whitelist tường minh, không log giá trị bí mật

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'purpose_note', 'inbound_enabled', 'outbound_enabled', 'allowed_ip_cidrs', 'rate_limit_per_minute'])
        ->logOnlyDirty()
        ->dontLogEmptyChanges();
}
```

**Cố tình liệt kê tường minh** (`logOnly`, không phải `logFillable` như đa số model khác trong Modules) — 4 field còn lại trong `$fillable` (`inbound_token`, `inbound_secret`, `outbound_webhook_url`, `outbound_secret`) **không bao giờ** xuất hiện trong activity log, kể cả dạng đã mã hoá: Spatie Activitylog ghi lại **giá trị attribute sau khi qua accessor/cast**, với cast `encrypted` giá trị ghi vào log vẫn là ciphertext — về lý thuyết không đọc lại được nếu không có `APP_KEY`, nhưng vẫn là thực hành xấu (rò rỉ ciphertext + độ dài giúp suy đoán, và rủi ro nếu `APP_KEY` xoay/lộ trong tương lai). Loại hẳn khỏi log là lựa chọn an toàn hơn so với dựa vào "encrypted là đủ".

### 7.3 Exceptions

```
Modules/N8n/app/Exceptions/
  N8nConnectionNotFoundException.php   // §4.1
  N8nOutboundDisabledException.php     // §4.1
```

### 7.4 Cấu trúc thư mục đầy đủ

```
Modules/N8n/
  app/
    Actions/
      CreateN8nConnectionAction.php         // sinh inbound_token (§3.2), validate qua §7.1
      UpdateN8nConnectionAction.php
      RotateN8nConnectionSecretAction.php   // xoay CHỌN LỌC (§3.2)
      HandleInboundN8nCallAction.php        // §5.2
      PurgeOldN8nLogsAction.php             // §5.7
    Data/
      N8nSendResult.php                     // §4
    Events/
      N8nWebhookReceived.php                // §5.6
    Exceptions/
      N8nConnectionNotFoundException.php    // §7.3
      N8nOutboundDisabledException.php      // §7.3
    Facades/
      N8n.php                               // trỏ N8nOutboundService, §4
    Http/
      Requests/
        StoreN8nConnectionRequest.php       // §7.1
        UpdateN8nConnectionRequest.php
        RotateN8nConnectionRequest.php      // validate 3 cờ rotate_* (ít nhất 1 = true)
      Controllers/
        N8nInboundWebhookController.php     // public, §5.1 — chỉ gọi HandleInboundN8nCallAction
        N8nConnectionController.php         // admin CRUD, web-authenticated, Platform Roles
        N8nLogController.php                // admin — danh sách log inbound + outbound (Tabulator)
    Models/
      N8nConnection.php
      N8nInboundLog.php
      N8nOutboundLog.php
    Providers/
      N8nServiceProvider.php                // đăng ký rate limiter (§5.8), bind Facade, Gate::define('manage-n8n', ...) — §6
      RouteServiceProvider.php
    Rules/
      ValidCidr.php                         // §7.1
    Services/
      N8nOutboundService.php                // §4
  config/
    n8n.php                                 // §3.1
  database/migrations/
    xxxx_create_n8n_connections_table.php
    xxxx_create_n8n_inbound_logs_table.php
    xxxx_create_n8n_outbound_logs_table.php
  resources/views/
    connections/index.blade.php             // Tabulator list, badge "Outbound: chưa ký" (§4.1)
    connections/_form.blade.php             // tạo/sửa, nút "Xoay" riêng cho từng field bí mật (§3.2), hiển thị plaintext 1 lần
    logs/index.blade.php                    // Tabulator log, filter theo connection/chiều/trạng thái
  routes/
    web.php                                 // admin CRUD, §6
    api.php                                 // route inbound duy nhất, §5.1
  tests/
    Feature/
      N8nInboundWebhookTest.php             // §9
      N8nConnectionAdminTest.php
    Unit/
      N8nOutboundServiceTest.php            // §9
  module.json
```

`module.json`:
```json
{
    "name": "N8n",
    "alias": "n8n",
    "description": "Tích hợp n8n — quản lý kết nối 2 chiều (inbound webhook có xác thực + outbound service gọi theo kết nối đặt tên). Module hệ thống, không thuộc tổ chức nào; điểm mở rộng dùng event chuẩn của Laravel (N8nWebhookReceived). Xem spec/N8n_Integration_Technical_Specification.md.",
    "keywords": ["automation", "webhook", "n8n"],
    "priority": 0,
    "providers": [
        "Modules\\N8n\\Providers\\N8nServiceProvider",
        "Modules\\N8n\\Providers\\RouteServiceProvider"
    ],
    "files": []
}
```

---

## 8. Ngoài phạm vi (out of scope) — ghi rõ để tránh hiểu nhầm khi review

- **Không dựng canvas kéo-thả** kiểu n8n cho UI quản trị kết nối — form/Tabulator đơn giản là đủ.
- **Không tự cài đặt/vận hành n8n** — n8n chạy như 1 service Docker riêng, ngoài repo Laravel này.
- **Chưa làm replay-protection** (timestamp + nonce chống phát lại request cũ đã capture) ở v1 — threat model hiện tại (dữ liệu nội bộ vận hành, không phải giao dịch tài chính) chưa cần tới mức này.
- **Chưa làm endpoint discovery/catalog** để n8n custom node tự khám phá.
- **Không có cơ chế retry ở tầng nghiệp vụ cho chiều outbound** — chỉ retry ở tầng HTTP (§4.1).
- **Không migrate/tương thích ngược** với thiết kế v1.0/v2.0 — chưa từng triển khai code nên không có gì cần chuyển đổi.
- **Không tự động hết hạn (`expires_at`) cho secret/token** — xoay là thao tác chủ động, không có cơ chế bắt buộc xoay định kỳ ở v1.
- **Không có UI hiển thị lại secret cũ dưới mọi hình thức** (kể cả cho `super-admin`) — mất là mất, phải xoay để lấy giá trị mới.

---

## 9. Testing strategy

- **`N8nInboundWebhookTest` (Feature)**:
  - Chữ ký hợp lệ → `202`, event `N8nWebhookReceived` được dispatch (`Event::fake()` + `Event::assertDispatched()`), `n8n_inbound_logs` có `payload_excerpt` khớp body.
  - Chữ ký sai → `401`, `n8n_inbound_logs.signature_valid = false`, `payload_excerpt = null` (§5.5).
  - Token không tồn tại → `404`, `connection_id = null`.
  - Token của 1 kết nối ĐÃ soft-delete → `404` giống hệt token không tồn tại (§2.5) — seed 1 connection rồi gọi `->delete()` trước khi test.
  - `inbound_enabled = false` → `404` (không phải `410`, §5.2 bước 2).
  - IP không nằm trong `allowed_ip_cidrs` → `403`.
  - Vượt `rate_limit_per_minute` → `429`, gọi bằng cách seed `rate_limit_per_minute = 1` rồi gửi 2 request liên tiếp.
  - Body vượt `max_inbound_body_size` → `400`.
  - Body không phải JSON hợp lệ → `400`, không dispatch event.
- **`N8nConnectionAdminTest` (Feature)**:
  - `platform_viewer` GET danh sách → `200`, nhưng POST tạo mới → `403`.
  - `platform_ops` tạo kết nối → response chứa `inbound_token`/`inbound_secret` plaintext lần đầu; GET lại → giá trị bị che.
  - Xoay chọn lọc chỉ `outbound_secret` → `inbound_token` không đổi (assert DB), response chỉ chứa `outbound_secret` mới.
  - User thuộc 1 tổ chức (role `RoleEnum` bất kỳ, không có Platform Role) → mọi route đều `403`.
- **`N8nOutboundServiceTest` (Unit, `Http::fake()`)**:
  - Gọi tới kết nối `outbound_enabled=false` → assert `N8nOutboundDisabledException`.
  - Gọi tới tên không tồn tại → assert `N8nConnectionNotFoundException`.
  - Gọi tới tên của 1 kết nối ĐÃ soft-delete → assert `N8nConnectionNotFoundException` (cùng lỗi với "không tồn tại" — seed rồi `->delete()` trước khi gọi `send()`).
  - `Http::fake()` trả `500` → `N8nSendResult(success: false, httpStatus: 500)`, không throw.
  - `outbound_secret = null` → assert request gửi đi KHÔNG có header chữ ký (`Http::fake()` + `assertSent(fn ($request) => !$request->hasHeader(...))`).
  - `outbound_secret` có giá trị → assert header chữ ký đúng bằng `hash_hmac()` tính tay trong test.

---

## 10. Kế hoạch triển khai (phases)

1. **Scaffold**: `php artisan module:make N8n`, khai `Gate::define('manage-n8n', ...)` trong `N8nServiceProvider` (§6) — không cần permission/feature-flag mới nào.
2. **Data layer**: migration + model `N8nConnection`/`N8nInboundLog`/`N8nOutboundLog` + index (§2), `config/n8n.php` đầy đủ (§3.1).
3. **Sinh/xoay secret**: implement đúng thuật toán §3.2 trong `CreateN8nConnectionAction`/`RotateN8nConnectionSecretAction` trước tiên — mọi phase sau đều phụ thuộc cơ chế này đã đúng.
4. **Admin CRUD kết nối**: form + validation (§7.1) + hiển thị plaintext 1 lần + soft-delete/restore.
5. **Inbound**: route + rate limiter (§5.8, implement cùng lúc vì phụ thuộc `N8nConnection` đã có) + `N8nInboundWebhookController` + `HandleInboundN8nCallAction` (đúng thứ tự 11 bước §5.2) + event `N8nWebhookReceived`.
6. **Outbound**: `N8nOutboundService` + 2 exception (§7.3) + `N8nSendResult` + Facade.
7. **Vận hành**: `PurgeOldN8nLogsAction` + `Schedule::call()`, trang log (Tabulator).
8. **Test** (§9): viết song song với mỗi phase ở trên, không dồn về cuối — đặc biệt `N8nInboundWebhookTest` nên có TRƯỚC khi coi phase 5 là xong.
9. *(Không làm ngay, để mở)*: IP allowlist UI nâng cao, replay protection, discovery endpoint, `expires_at` cho secret — xem §8.

---

## 11. Phụ lục — ví dụ mục đích sử dụng trong tương lai (minh hoạ tính linh hoạt, KHÔNG thuộc phạm vi build)

Mỗi ví dụ dưới đây **không cần sửa code của module `N8n`** — chỉ cần: (a) 1 `N8nConnection` mới, (b) 1 Listener (cho chiều nhận) hoặc 1 lời gọi `N8n::send()` (cho chiều gửi) ở module nghiệp vụ liên quan, (c) cấu hình phía n8n.

- **Lead mới → thông báo kênh ngoài**: trong `CreateLeadAction`, sau khi lưu Lead thành công, gọi `N8n::send('n8n-crm-notify', ['lead_id' => $lead->id, ...], eventName: 'lead_created')` → n8n route tiếp sang Zalo OA/Telegram/Google Sheet.
- **Bài viết publish → đăng chéo mạng xã hội**: `Modules\Post` tự thêm 1 Listener cho `ArticlePublished` (event đã có sẵn của Post), trong Listener gọi `N8n::send('n8n-social-crosspost', [...])`.
- **n8n theo dõi nguồn ngoài → tự nạp ý tưởng bài viết**: n8n cron poll RSS/mạng xã hội → gọi vào `POST /api/n8n/in/{token}` với `event_name = "new_source_found"` → `Modules\CoreIdeaExtractor` tự đăng ký 1 Listener lọc đúng `event_name` này, gọi tiếp action extract nội bộ đã có.
- **Kết quả Assessment → chăm sóc đa kênh**: `Modules\Assessment` gọi `N8n::send('n8n-customer-success', ['band_code' => ..., 'score' => ...])` ngay sau khi tính điểm xong → n8n phân nhánh gửi email/Zalo theo `band_code`.
