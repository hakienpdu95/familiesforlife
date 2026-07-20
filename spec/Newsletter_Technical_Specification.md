# Module Bản tin (Newsletter) — Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai

**Phiên bản:** 2.3 — làm rõ hành vi xoá/khôi phục subscriber ở các trạng thái biên (theo phản hồi review)
**Ngày:** 20/07/2026
**Pattern stack:** AVSA + CQRS-lite + Laravel Modules (NWIDART 13) + Laravel Actions (lorisleiva 2.x)
**Module mới:** `Modules/Newsletter`
**Phạm vi:** Nền tảng (platform-wide), không tenant-scoped (giữ nguyên quyết định ở v1.0, xem §0 mục 1)
**Bên thứ 3 tích hợp:** **Resend** — Contacts API (lưu danh sách), Broadcasts API (gửi hàng loạt), Webhook (đồng bộ trạng thái 2 chiều)

> **Lịch sử phiên bản**
> - **v1.0** — thiết kế module Newsletter tự xây toàn bộ: campaign compose + duyệt 2 tầng + gửi hàng loạt bằng `Bus::batch()`/queue riêng + click-tracking tự đếm.
> - **v2.0** — **thu hẹp phạm vi đúng theo yêu cầu thực tế**: ứng dụng chỉ làm 2 việc — (a) thu thập `full_name`+`email` qua form công khai, lưu DB; (b) đồng bộ sang **Resend** và để Resend lo toàn bộ phần "gửi hàng loạt" (Broadcasts), unsubscribe hợp chuẩn, và số liệu mở/click (Resend đã có dashboard riêng cho việc này). Bỏ hẳn: bảng người nhận/campaign/click-tracking tự xây, quy trình duyệt 2 tầng, `Bus::batch()`, queue rate-limiting tự viết — toàn bộ phần đó Resend đã làm sẵn, tốt hơn tự xây lại (xem §2).
> - **v2.1** — theo phản hồi review, nâng 2 mục từ "cân nhắc sau" lên **làm ngay trong lần triển khai này**: (a) **log lại mỗi lần gửi Broadcast** (`newsletter_broadcast_logs` — subject, scheduled_at, `resend_broadcast_id`, người gửi) — chi phí thấp, giá trị cao cho audit nội bộ mà không phải tra Resend Dashboard mỗi lần; (b) **double opt-in** — thêm ngay từ đầu dưới dạng cấu hình bật/tắt (`NEWSLETTER_DOUBLE_OPT_IN`, mặc định tắt) để khi cần bật (danh sách bị spam đăng ký giả) chỉ cần đổi 1 biến `.env`, không cần thêm migration/deploy code mới. Việc xác nhận payload webhook thật (trước là Open Question #2) được nâng thành **bước bắt buộc** trong Phase 4 (§14), không còn là câu hỏi mở tuỳ chọn.
> - **v2.2** — theo phản hồi review tiếp theo: (a) thêm **`RemoveSubscriberAction`** — admin xoá (soft-delete) thủ công 1 subscriber + tự động unsubscribe song song bên Resend, bù lại giới hạn "không sửa/xoá tay" của v2.1 (vd yêu cầu xoá theo GDPR, dọn entry rác/spam rõ ràng mà chưa kịp bounce/complain qua Resend); (b) tách rõ **Lộ trình mở rộng** (§15) thành thứ tự ưu tiên tường minh thay vì 1 bảng phẳng — nhiều Segment PHẢI làm trước, Preference Center chỉ có ý nghĩa sau đó.
> - **v2.3 (bản này)** — làm rõ 3 hành vi ở trạng thái biên (không đổi kiến trúc, chỉ định nghĩa tường minh — xem §0 mục 16): (a) xoá subscriber đang `pending_confirmation` — soft-delete bình thường, không có gì để gọi bên Resend vì chưa từng đồng bộ; (b) đăng ký lại sau khi bị admin xoá — sửa đúng 1 chỗ **có lỗi thật** trong logic v2.2 (điều kiện double opt-in dựa vào `$isNew`, vốn luôn `false` cho subscriber được `restore()` dù trước đó thực chất đúng là 1 sự kiện đăng ký mới) — đổi sang điều kiện `status !== Active && confirmed_at là null`, đúng ý định ban đầu hơn; (c) xác nhận rõ **giữ nguyên** `resend_contact_id` khi soft-delete, không null hoá.

---

## 0. Quyết định đã chốt

| # | Chủ đề | Quyết định | Vì sao |
|---|---|---|---|
| 1 | **Phạm vi tenant** | Platform-wide, không `organization_id` | Giữ nguyên từ v1.0 — Newsletter là kênh của nền tảng, không phải từng tổ chức thuê bao |
| 2 | **Dữ liệu thu thập** | **Chỉ** `full_name` + `email` | Đúng yêu cầu thu hẹp — không thêm field nào khác (không phone, không sở thích/category...) |
| 3 | **Nơi lưu trữ chính** | 2 nơi, có phân vai rõ: **DB nội bộ** (`newsletter_subscribers`) là bản ghi "ai đăng ký qua site của mình" phục vụ hiển thị/báo cáo nội bộ; **Resend Contacts** là nguồn sự thật cho "ai đang thật sự nhận được email, ai đã unsubscribe/bounce" | Không trùng lặp trách nhiệm: DB của mình không cần tự làm lại toàn bộ compliance/deliverability mà Resend đã làm tốt hơn |
| 4 | **Gửi bản tin hàng loạt** | **Resend Broadcasts API** (`POST /broadcasts` + `POST /broadcasts/{id}/send`) — **KHÔNG** tự xây `Bus::batch()`/queue/rate-limit riêng như v1.0 | Resend đã tự lo throttle gửi, retry, deliverability, và **cả unsubscribe lẫn số liệu mở/click** cho broadcast — tự xây lại y hệt là công sức thừa, rủi ro deliverability thấp hơn (domain reputation của Resend > tự gửi SMTP thô) |
| 5 | **Unsubscribe** | Dùng merge tag `{{{RESEND_UNSUBSCRIBE_URL}}}` của Resend trong nội dung broadcast — Resend **tự động host trang unsubscribe** và xử lý, **KHÔNG** tự xây route/trang unsubscribe riêng như v1.0 | Xác nhận qua tài liệu Resend (`resend.com/docs/dashboard/broadcasts/introduction`) — Resend cung cấp cơ chế này sẵn, kể cả tuỳ biến giao diện trang unsubscribe qua dashboard Resend. Tự xây lại là dư thừa |
| 6 | **Đồng bộ trạng thái 2 chiều** | Lắng nghe **Laravel event có sẵn** từ package `resend/resend-laravel` (`ContactUpdated`, `ContactDeleted`, `EmailBounced`, `EmailComplained`) — route webhook + xác thực chữ ký **đã có sẵn trong package**, không cần tự xây | `vendor/resend/resend-laravel` (đã có trong `composer.json`, xem §2.1) tự đăng ký `POST {RESEND_PATH}/webhook` + `VerifyWebhookSignature` + dispatch Event cho từng loại webhook — chỉ cần viết Listener, không cần Controller/route/verify-signature mới |
| 7 | **Gửi mail tự động khi có người đăng ký** | 1 email chào mừng đơn giản (`WelcomeSubscriberMail`, Laravel `Mailable` thường qua mailer `resend` đã cấu hình sẵn) — **KHÔNG** dùng Broadcast API cho việc này (Broadcast dành cho gửi hàng loạt tới cả danh sách, không phải 1 email giao dịch đơn lẻ) | Đúng bản chất: 1 email cho 1 người ngay lúc đăng ký là **transactional**, dùng thẳng `Mail::send()` (đã hoạt động, không cần cấu hình gì thêm); Broadcast dùng cho khi admin **chủ động** gửi bản tin tới toàn bộ danh sách sau này |
| 8 | **Ai kích hoạt gửi Broadcast** | 1 action admin tối giản: nhập subject + nội dung HTML → gọi Resend Broadcast API tạo + gửi ngay hoặc lên lịch (`scheduled_at` — Resend tự hỗ trợ, không cần cron riêng) | Không cần quy trình duyệt 2 tầng phức tạp như v1.0 — phạm vi đã thu hẹp, đội ngũ nhỏ, gửi bản tin không phải hoạt động thường xuyên tới mức cần gate nhiều tầng |
| 9 | **Đặt tên contact bên Resend** | Model Contact của Resend chỉ có `first_name`/`last_name` (không có field "full name" gộp) — đưa nguyên `full_name` vào `first_name`, để `last_name` trống | Tách tên tiếng Việt theo khoảng trắng đầu tiên không đáng tin (vd "Nguyễn Văn A" tách sai) — giữ nguyên cả cụm trong 1 field an toàn hơn suy đoán sai |
| 10 | **Người quản trị** | Tái dùng role platform-wide `platform_content_editor`/`platform_content_head` (giống v1.0 §0 mục 3) | Không đổi — vẫn đúng dù phạm vi module thu hẹp |
| 11 | **1 segment Resend duy nhất** | Toàn bộ subscriber vào **1** Resend Segment (trước gọi là "Audience") cấu hình qua `NEWSLETTER_RESEND_SEGMENT_ID` — không phân nhóm theo category như v1.0 | Đúng yêu cầu thu hẹp — không cần segmentation phức tạp; Resend Segment id tạo 1 lần thủ công trên dashboard Resend lúc setup, dán vào `.env` |
| 12 | **Gọi Resend API bất đồng bộ, không đồng bộ ngay trong request** | `SubscribeAction` chỉ lưu DB nội bộ rồi **dispatch job** (`SyncSubscriberToResendJob implements ShouldQueue`) làm phần gọi Contact API + gửi welcome mail | Resend giới hạn **10 request/giây/team** (xác nhận qua `resend.com/docs/api-reference/introduction`, tính chung mọi API key) — nếu 1 bài viết viral gây đăng ký đột biến, gọi API đồng bộ ngay trong request vừa dễ dính 429 vừa làm chậm phản hồi cho người dùng đang chờ submit form. Đẩy qua queue (đã có sẵn `QUEUE_CONNECTION=database`) để Laravel tự retry khi gặp 429/lỗi tạm thời, không chặn trải nghiệm đăng ký |
| 13 | **Log lịch sử gửi Broadcast** | Bảng riêng, append-only: `newsletter_broadcast_logs` (`resend_broadcast_id`, `subject`, `scheduled_at`, `sent_by`, `created_at`, không có `updated_at` — đúng tiền lệ `post_publishing_logs`) — ghi 1 dòng ngay sau khi `SendBroadcastAction` gọi Resend thành công | Chi phí thấp (1 bảng, 1 câu `INSERT` sau khi gọi API), giá trị cao: biết **ai** đã gửi **gì**, lúc nào, mà không phải đăng nhập Resend Dashboard mỗi lần cần audit — nhất là khi nhiều người có quyền `platform_content_head` |
| 14 | **Double opt-in** | Thêm ngay từ đầu (không để "sau"), nhưng ở dạng **cấu hình bật/tắt**: `NEWSLETTER_DOUBLE_OPT_IN` (mặc định `false`). Khi bật: subscriber vào trạng thái `pending_confirmation`, phải bấm link xác nhận (email riêng, không phải welcome mail) trước khi chuyển `active` + được đồng bộ sang Resend. Khi tắt (mặc định): hành vi y hệt trước — active ngay, đồng bộ ngay | Thêm cột `confirmed_at` + status mới **ngay từ migration đầu tiên** rẻ hơn nhiều so với thêm sau khi đã có subscriber cũ không có `confirmed_at` (phải backfill). Nếu danh sách bắt đầu bị spam đăng ký giả, chỉ cần đổi 1 biến `.env` và deploy lại, không cần viết thêm migration/code giữa chừng |
| 15 | **Xoá thủ công (admin)** | Thêm `RemoveSubscriberAction` — admin soft-delete 1 subscriber trong DB nội bộ, đồng thời dispatch job set `unsubscribed=true` cho contact tương ứng bên Resend (nếu đã có `resend_contact_id`) | Không phải mọi trường hợp cần xoá đều tự đến từ Resend (bounce/complaint/tự unsubscribe) — có lúc admin cần chủ động xoá tay: yêu cầu xoá dữ liệu cá nhân (GDPR/tinh thần tương tự), entry rác/email giả rõ ràng phát hiện thủ công, hoặc dọn dẹp trước khi đối chiếu số liệu. Không xoá cứng (`forceDelete`) — vẫn giữ nguyên tinh thần "không mất lịch sử" đã áp dụng xuyên suốt các module khác (`SoftDeletes` đã có sẵn trên `NewsletterSubscriber`, chỉ cần thêm hành động gọi nó qua UI) |
| 16 | **Hành vi 3 trạng thái biên** (§9.1/§9.5) | (a) Xoá subscriber `pending_confirmation` → soft-delete bình thường, KHÔNG gọi Resend (chưa từng đồng bộ, guard `resend_contact_id` tự đúng); (b) Đăng ký lại sau khi bị xoá → yêu cầu double opt-in chỉ áp dụng nếu `status !== Active` **và** `confirmed_at` còn null (không phải dựa vào "có phải bản ghi mới" — 1 bản ghi được `restore()` không phải bản ghi mới nhưng vẫn là 1 sự kiện đăng ký thật); (c) Soft-delete **giữ nguyên** `resend_contact_id`, không null hoá | Không định nghĩa rõ 3 điểm này dễ dẫn tới cách hiểu/triển khai khác nhau giữa người viết Action và người viết test — đặc biệt mục (b): điều kiện ban đầu dùng `$isNew` (bản ghi có tồn tại trước khi gọi `firstOrNew` hay không) **sai về mặt ý nghĩa nghiệp vụ** cho trường hợp restore, vì 1 subscriber vừa được khôi phục từ soft-delete vẫn *là* 1 lượt "bắt đầu nhận tin" cần áp dụng đúng chính sách double opt-in hiện hành, không phải "coi như đã tồn tại nên bỏ qua" |

---

## 1. Giới thiệu & Mục tiêu

Nền tảng cần 1 cách đơn giản để độc giả để lại email nhận thông tin, và để platform gửi thông tin tới họ — **không cần tự xây hạ tầng gửi email hàng loạt** (deliverability, throttle, bounce handling, unsubscribe hợp chuẩn) vì ứng dụng **đã dùng Resend** làm mailer production (`config/mail.php`, `.env`) và Resend có sẵn đầy đủ các API cho đúng nhu cầu này.

**Việc của module này, đúng 2 việc:**
1. Hứng form công khai (`full_name` + `email`) → lưu DB nội bộ (để platform có bản ghi/báo cáo riêng) → đẩy sang Resend Contact.
2. Cho phép platform soạn 1 bản tin (subject + HTML) → gọi API Resend để **Resend** gửi tới toàn bộ danh sách.

Mọi phần "khó" của 1 hệ thống gửi email hàng loạt thật sự (deliverability, rate limit theo domain reputation, unsubscribe hợp chuẩn RFC 8058, số liệu mở/click) đều **giao cho Resend xử lý** — ứng dụng không tự làm lại.

---

## 2. Khảo sát hạ tầng có sẵn (quan trọng — quyết định toàn bộ thiết kế ở §0)

### 2.1 Package Resend đã cài sẵn, chưa dùng hết tính năng

`composer.json`/`composer.lock` đã có:
- `resend/resend-laravel` (`^1.4`) — package Laravel wrapper, đã cấu hình `MAIL_MAILER=resend` cho việc gửi mail thường (`Mail::send()`/`Mailable`).
- `resend/resend-php` (`^1.0`, dependency của package trên) — SDK PHP đầy đủ, cho phép gọi **toàn bộ API Resend** (không chỉ gửi mail): `Resend::contacts()`, `Resend::broadcasts()`, `Resend::domains()`, v.v. — đã có sẵn, **chưa từng được dùng** trong codebase ngoài việc gửi mail transactional.

**Package `resend-laravel` đã tự đăng ký sẵn** (`vendor/resend/resend-laravel/src/ResendServiceProvider.php`):
- Route `POST {RESEND_PATH}/webhook` (mặc định `resend/webhook`) — nhận webhook từ Resend.
- Middleware `VerifyWebhookSignature` — tự động bật nếu có cấu hình `RESEND_WEBHOOK_SECRET` trong `.env` (hiện **chưa** set — bổ sung ở Phase 3, §14).
- `WebhookController` tự parse `payload['type']` và **dispatch sẵn Laravel Event** tương ứng (`Resend\Laravel\Events\ContactCreated`, `ContactUpdated`, `ContactDeleted`, `EmailBounced`, `EmailComplained`, `EmailDelivered`, `EmailOpened`, `EmailClicked`, `EmailSent`, `EmailFailed`, `EmailSuppressed`, `EmailReceived`, `DomainCreated/Updated/Deleted`) — module chỉ cần **đăng ký Listener** cho các event cần dùng, không cần viết Controller/route/verify chữ ký nào mới.

### 2.2 API Contacts/Segments/Broadcasts — đã xác minh qua tài liệu chính thức Resend (không đoán)

| API | Method + Path | Ghi chú |
|---|---|---|
| Tạo contact | `POST /contacts` | Body: `email` (bắt buộc), `first_name`, `last_name`, `unsubscribed` (bool), `segments` (mảng segment id) — **không có `audience_id`**, xem §0 mục 9 |
| Lấy contact theo email/id | `GET /contacts/{id\|email}` | Dùng để kiểm tra tồn tại trước khi tạo (tránh trùng) |
| Cập nhật contact | `PATCH /contacts/{id\|email}` | Dùng để set `unsubscribed=true` hoặc cập nhật tên |
| Thêm contact vào segment | `POST /contacts/{contact_id}/segments/{segment_id}` | `Resend::contacts()->segments->add()` trong SDK |
| Tạo broadcast | `POST /broadcasts` | Body **bắt buộc**: `segment_id` (⚠ tài liệu Resend ghi rõ **"Audiences are now called Segments"** — đổi tên khái niệm gần đây, không phải audience_id như SDK cũ), `from`, `subject`. Tuỳ chọn: `html`, `text`, `scheduled_at` (chấp nhận cả ngôn ngữ tự nhiên "in 1 hour" lẫn ISO 8601), `send` (bool, gửi ngay nếu `true`) |
| Gửi broadcast đã tạo | `POST /broadcasts/{id}/send` | Dùng khi tạo broadcast với `send=false` trước đó rồi gửi sau (vd sau khi xem trước) |
| Tạo/quản lý segment | `POST/GET/DELETE /segments` | `Resend::segments()->create(['name' => ...])` — dùng service này để tạo segment "Người đăng ký bản tin" 1 lần lúc setup (§14 Phase 3) |

> **Bẫy đặt tên trong chính SDK — đã kiểm tra trực tiếp `vendor/resend/resend-php` (v1.3.0, khớp `composer.lock`)**: Facade (`vendor/resend/resend-laravel/src/Facades/Resend.php`) khai báo **cả 2** phương thức riêng biệt — `Resend::audiences()` (trả về `\Resend\Service\Audience`, gọi `/audiences`) **và** `Resend::segments()` (trả về `\Resend\Service\Segment`, gọi `/segments`). Đây **không phải 2 cách gọi tương đương** — `audiences()` là **API cũ giữ lại trong SDK cho tương thích ngược**, không còn là khái niệm dùng cho Broadcast (Broadcast API hiện tại chỉ nhận `segment_id`, không nhận `audience_id`). **Module này chỉ dùng `Resend::segments()`**, tuyệt đối không dùng `Resend::audiences()` dù SDK vẫn cho gọi được — dùng nhầm sẽ tạo ra 1 "audience" không thể dùng làm `segment_id` khi tạo broadcast.
>
> Còn 1 lớp `Contacts\Segment` **khác nữa** (`Resend::contacts()->segments`, đã dẫn ở §2.2 dòng "Thêm contact vào segment") — đây là sub-service quản lý **liên kết contact ↔ segment** (thêm/xoá/liệt kê segment của 1 contact cụ thể), khác với `Resend::segments()` ở trên vốn quản lý **bản thân định nghĩa segment** (tạo/xoá/liệt kê segment). 2 lớp phục vụ 2 việc khác nhau, không thay thế nhau được.

**Merge tag bắt buộc trong nội dung broadcast**: `{{{RESEND_UNSUBSCRIBE_URL}}}` — Resend tự thay bằng link unsubscribe đã ký cho từng người nhận, tự host trang xử lý, cập nhật `unsubscribed=true` cho đúng contact đó và bắn webhook `contact.updated` — module chỉ cần **nhắc admin** chèn merge tag này khi soạn (validate ở tầng `SendBroadcastAction`, xem §9.4), không cần tự xây gì thêm.

**Giới hạn tốc độ gọi API** (`resend.com/docs/api-reference/introduction`): mặc định **10 request/giây/team**, tính chung mọi API key, vượt ngưỡng trả `429`. SDK tự gắn `User-Agent` đúng chuẩn (`resend-php`) nên không cần lo phần đó, nhưng **tần suất gọi từ phía mình** (mỗi lượt subscribe = 1-2 request) cần tính tới — xem §0 mục 12 (đẩy qua queue thay vì gọi đồng bộ trong request).

### 2.3 RBAC platform-wide (Lớp A) — tái dùng nguyên vẹn (không đổi so với v1.0)

`app/Models/User.php`: `isPlatformContentEditor()`, `isPlatformContentHead()`, `hasRole('super-admin')` (bypass qua `Gate::before()`).

---

## 3. Phạm vi (Scope Boundary)

### 3.1 Trong phạm vi (v2.2)

1. Form công khai thu thập `full_name` + `email`, lưu DB nội bộ, đồng bộ sang Resend Contact + gán vào Segment cấu hình sẵn.
2. Gửi email chào mừng tự động (transactional, qua Laravel Mail) ngay khi đăng ký — hoặc email xác nhận nếu double opt-in đang bật (§0 mục 14).
3. Double opt-in tuỳ chọn (cấu hình bật/tắt) — trạng thái `pending_confirmation` + xác nhận qua link ký chữ ký trước khi active.
4. Trang admin xem danh sách subscriber (đọc từ DB nội bộ — nhanh, không gọi API Resend mỗi lần xem danh sách) + **xoá thủ công 1 subscriber** khi cần (§0 mục 15), đồng bộ unsubscribe song song bên Resend.
5. Trang admin soạn + gửi 1 bản tin (subject + HTML) tới toàn bộ Segment qua Resend Broadcast API — gửi ngay hoặc lên lịch.
6. Log lại mỗi lần gửi Broadcast vào `newsletter_broadcast_logs` (§0 mục 13).
7. Đồng bộ ngược trạng thái (bounce/complaint/unsubscribe xảy ra bên Resend) về DB nội bộ qua Listener lắng nghe Event có sẵn của package.

### 3.2 Ngoài phạm vi (cố ý không làm ở v2.2)

| Nghiệp vụ | Vì sao không làm ở đây |
|---|---|
| Tự xây gửi hàng loạt (`Bus::batch`, queue rate-limit riêng) | Resend Broadcast API đã làm — xem §0 mục 4 |
| Tự xây trang/route unsubscribe | Resend tự host qua merge tag `{{{RESEND_UNSUBSCRIBE_URL}}}` — xem §0 mục 5 |
| Đo mở/click liên kết trong bản tin | Resend dashboard đã có sẵn số liệu này cho từng broadcast — không cần tự đếm lại |
| Quy trình duyệt 2 tầng trước khi gửi | Thu hẹp phạm vi — 1 người có quyền (`platform_content_head`) soạn và gửi trực tiếp |
| Phân segment theo category/sở thích (nhiều Segment) | Chỉ 1 danh sách duy nhất — xem §0 mục 11. Làm khi thật sự cần chia nhóm nội dung theo chủ đề |
| **Preference Center** (trang cho subscriber tự chọn tần suất/chủ đề quan tâm) | Chỉ có ý nghĩa khi đã có nhiều Segment/chủ đề để chọn (mục trên) — chưa cần khi cả nền tảng mới có 1 danh sách duy nhất. Làm sau, cùng lúc với "nhiều Segment" |
| Import/Export Excel subscriber | Không nằm trong yêu cầu thu hẹp lần này — nếu cần sau, có thể thêm bằng `rap2hpoutre/fast-excel` đã sẵn trong `composer.json` mà không đổi schema |

---

## 4. Data Model

```
NewsletterSubscriber   [full_name, email, resend_contact_id, status, subscribed_at, confirmed_at, unsubscribed_at]
NewsletterBroadcastLog [resend_broadcast_id, subject, scheduled_at, sent_by]  — append-only, độc lập, không FK sang subscriber
```

**2 bảng**, không liên kết nhau — không có bảng campaign/recipient/link nào tự xây (đã giao hết cho Resend quản lý ở phía họ, §0 mục 4/5).

### 4.1 Migration — `newsletter_subscribers`

```php
Schema::create('newsletter_subscribers', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique(); // dùng nội bộ (route-model-binding admin), không lộ ra unsubscribe công khai (§0 mục 5)
    $table->string('full_name', 150);
    $table->string('email', 255)->unique();
    $table->string('resend_contact_id', 36)->nullable(); // id contact bên Resend, null nếu đồng bộ thất bại (xem §9.1) hoặc đang chờ xác nhận double opt-in
    $table->string('status', 20)->default('active'); // SubscriberStatus: pending_confirmation|active|unsubscribed|bounced|complained
    $table->string('source', 50)->nullable(); // vd 'public_form'
    $table->timestamp('subscribed_at')->useCurrent();
    $table->timestamp('confirmed_at')->nullable(); // §0 mục 14 — chỉ có giá trị khi double opt-in đang/đã bật; null nếu single opt-in (mặc định)
    $table->timestamp('unsubscribed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('status');
    $table->index('resend_contact_id');
});
```

### 4.2 Migration — `newsletter_broadcast_logs` (§0 mục 13)

```php
Schema::create('newsletter_broadcast_logs', function (Blueprint $table) {
    $table->id();
    $table->string('resend_broadcast_id', 36)->nullable(); // null nếu request tạo broadcast thất bại trước khi có id (hiếm — SendBroadcastAction ném exception trước khi tới bước log, xem §9.4)
    $table->string('subject', 255);
    $table->timestamp('scheduled_at')->nullable(); // null = gửi ngay lúc đó, không lên lịch
    $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->index('sent_by');
});
```

Không có `updated_at` — append-only, đúng tiền lệ `post_publishing_logs` (`Modules/Post/app/Models/PostPublishingLog.php`, `const UPDATED_AT = null`). Không soft-delete — log không tự xoá qua UI.

---

## 5. Enum

```php
// Modules/Newsletter/app/Enums/SubscriberStatus.php
enum SubscriberStatus: string
{
    case PendingConfirmation = 'pending_confirmation'; // §0 mục 14 — chỉ dùng khi NEWSLETTER_DOUBLE_OPT_IN=true
    case Active              = 'active';
    case Unsubscribed        = 'unsubscribed';
    case Bounced             = 'bounced';    // đồng bộ từ webhook EmailBounced, xem §9.3
    case Complained          = 'complained'; // đồng bộ từ webhook EmailComplained

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Chờ xác nhận email',
            self::Active               => 'Đang nhận tin',
            self::Unsubscribed         => 'Đã huỷ đăng ký',
            self::Bounced              => 'Email không gửi được (bounce)',
            self::Complained           => 'Đã báo cáo spam',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'badge-info',
            self::Active               => 'badge-success',
            self::Unsubscribed         => 'badge-ghost',
            self::Bounced              => 'badge-warning',
            self::Complained           => 'badge-error',
        };
    }
}
```

---

## 6. Model

```php
// Modules/Newsletter/app/Models/NewsletterSubscriber.php
class NewsletterSubscriber extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'newsletter_subscribers';
    protected $fillable = ['uuid', 'full_name', 'email', 'resend_contact_id', 'status', 'source', 'subscribed_at', 'confirmed_at', 'unsubscribed_at'];
    protected $casts = [
        'status' => SubscriberStatus::class,
        'subscribed_at' => 'datetime', 'confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string { return 'uuid'; }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', SubscriberStatus::Active);
    }
}
```

```php
// Modules/Newsletter/app/Models/NewsletterBroadcastLog.php — append-only, không sửa (§4.2).
class NewsletterBroadcastLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'newsletter_broadcast_logs';
    protected $fillable = ['resend_broadcast_id', 'subject', 'scheduled_at', 'sent_by'];
    protected $casts = ['scheduled_at' => 'datetime'];

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by');
    }
}
```

---

## 7. Cấu hình

### 7.1 `.env` (bổ sung)

```
RESEND_WEBHOOK_SECRET=       # lấy từ Resend Dashboard → Webhooks → sau khi tạo endpoint (xem §14 Phase 3/4)
NEWSLETTER_RESEND_SEGMENT_ID= # id Segment (trước gọi Audience) tạo 1 lần thủ công trên Resend Dashboard
NEWSLETTER_FROM_ADDRESS="Bản tin <noreply@yourdomain.com>" # domain PHẢI đã verify ở Resend, dùng chung domain đã verify cho MAIL_FROM_ADDRESS
NEWSLETTER_DOUBLE_OPT_IN=false # §0 mục 14 — bật (true) nếu danh sách có dấu hiệu bị spam đăng ký giả
```

### 7.2 `Modules/Newsletter/config/config.php`

```php
return [
    'resend_segment_id' => env('NEWSLETTER_RESEND_SEGMENT_ID'),
    'from_address'       => env('NEWSLETTER_FROM_ADDRESS', config('mail.from.address')),
    'double_opt_in'      => env('NEWSLETTER_DOUBLE_OPT_IN', false),
];
```

---

## 8. Directory Structure

```
Modules/Newsletter/
├── app/
│   ├── Enums/SubscriberStatus.php
│   ├── Models/(NewsletterSubscriber.php, NewsletterBroadcastLog.php)
│   ├── Mail/(WelcomeSubscriberMail.php, ConfirmSubscriptionMail.php)
│   ├── Policies/NewsletterPolicy.php
│   ├── Jobs/(SyncSubscriberToResendJob.php, UnsubscribeSubscriberFromResendJob.php)
│   ├── Listeners/SyncSubscriberFromResendWebhookListener.php
│   ├── Features/
│   │   ├── PublicSubscription/
│   │   │   ├── Actions/    (SubscribeAction.php, ConfirmSubscriptionAction.php)
│   │   │   ├── Data/       (SubscribeData.php)
│   │   │   └── Http/       (PublicSubscriptionController.php)
│   │   ├── SubscriberManagement/
│   │   │   ├── Actions/    (RemoveSubscriberAction.php)
│   │   │   ├── Http/       (SubscriberAdminController.php)
│   │   │   └── Queries/    (ListSubscribersForAdminQuery/Handler.php)
│   │   └── BroadcastSending/
│   │       ├── Actions/    (SendBroadcastAction.php)
│   │       ├── Data/       (BroadcastData.php)
│   │       ├── Http/       (BroadcastAdminController.php)
│   │       └── Queries/    (ListBroadcastLogsForAdminQuery/Handler.php)
│   └── Providers/(NewsletterServiceProvider.php, EventServiceProvider.php)
├── config/config.php
├── database/migrations/(2026_07_20_000001_create_newsletter_subscribers_table.php, 2026_07_20_000002_create_newsletter_broadcast_logs_table.php)
├── resources/views/admin/{subscribers/index.blade.php, broadcast/{create.blade.php, logs.blade.php}}, emails/{welcome.blade.php, confirm.blade.php}
└── routes/web.php
```

---

## 9. Business rules & Actions

### 9.1 `SubscribeAction` — thu thập, lưu DB, dispatch đồng bộ (§0 mục 12 — không gọi Resend đồng bộ trong request)

```php
class SubscribeAction
{
    use AsAction;

    public function handle(string $fullName, string $email): NewsletterSubscriber
    {
        $subscriber     = NewsletterSubscriber::withTrashed()->firstOrNew(['email' => $email]);
        $doubleOptIn    = config('newsletter.double_opt_in');
        // Chụp lại TRƯỚC khi fill() ghi đè — cho bản ghi hoàn toàn mới, thuộc tính này chưa hề
        // được set nên là null (không phải 'active' dù cột DB có default, vì default đó chỉ áp
        // dụng ở tầng SQL, không phản ánh vào model PHP mới tạo trong bộ nhớ).
        $previousStatus = $subscriber->status;

        if ($subscriber->trashed()) {
            $subscriber->restore(); // §0 mục 16 — khôi phục sau khi admin đã xoá thủ công (§9.5)
        }

        // §0 mục 14/16 — double opt-in chỉ áp dụng khi email này CHƯA TỪNG được xác nhận
        // (`confirmed_at` null) VÀ hiện KHÔNG đang active. 2 điều kiện này cùng lúc xử lý đúng
        // 3 tình huống dễ nhầm:
        //   - Subscriber đang active, lỡ submit lại form (double-click) → giữ nguyên active,
        //     KHÔNG hạ cấp xuống pending_confirmation (tránh vô tình chặn newsletter của người
        //     đã xác nhận từ trước chỉ vì họ bấm nút 2 lần).
        //   - Subscriber từng xác nhận (`confirmed_at` đã set), sau đó bị xoá/tự unsubscribe,
        //     giờ đăng ký lại → coi như đã chứng minh sở hữu email 1 lần rồi, KHÔNG bắt xác
        //     nhận lại — vào active ngay.
        //   - Subscriber CHƯA TỪNG xác nhận (`confirmed_at` null — kể cả trường hợp họ subscribe
        //     lúc double opt-in còn tắt) mà nay bị xoá/unsubscribe rồi đăng ký lại, hoặc hoàn
        //     toàn mới → bắt xác nhận lại đúng theo cấu hình hiện tại.
        $requiresConfirmation = $doubleOptIn
            && $previousStatus !== SubscriberStatus::Active
            && is_null($subscriber->confirmed_at);

        $status = $requiresConfirmation ? SubscriberStatus::PendingConfirmation : SubscriberStatus::Active;

        $subscriber->fill([
            'full_name'       => $fullName,
            'status'          => $status,
            'source'          => $subscriber->source ?? 'public_form',
            'subscribed_at'   => $subscriber->subscribed_at ?? now(),
            'unsubscribed_at' => null,
            // §0 mục 16 — KHÔNG đụng resend_contact_id ở đây: nếu subscriber từng có (kể cả sau
            // khi bị soft-delete, §9.5 cố tình giữ nguyên cột này), giữ nguyên để
            // SyncSubscriberToResendJob nhận ra và UPDATE đúng contact cũ thay vì tạo trùng.
        ])->save();

        if ($status === SubscriberStatus::PendingConfirmation) {
            Mail::to($email)->queue(new ConfirmSubscriptionMail($subscriber));

            return $subscriber;
        }

        // Cả 2 job đều queue độc lập — lỗi/chậm bên nào không ảnh hưởng bên kia, và không
        // chặn phản hồi HTTP cho người dùng đang chờ submit form (§0 mục 12). Dispatch lại
        // ngay cả khi KHÔNG phải subscriber mới (vd vừa restore từ soft-delete) — Resend contact
        // có thể đang unsubscribed=true từ lần RemoveSubscriberAction trước, cần đồng bộ lại.
        SyncSubscriberToResendJob::dispatch($subscriber->id, $fullName, $email);

        // Chỉ gửi welcome mail khi đây thật sự là 1 sự kiện "bắt đầu nhận tin" mới — so với
        // $previousStatus đã chụp TRƯỚC fill()/save(), KHÔNG dùng wasChanged(): với bản ghi mới
        // insert, Eloquent KHÔNG gọi syncChanges() trong performInsert() (chỉ performUpdate() có
        // gọi) — wasChanged() luôn trả `false` cho record vừa tạo, bỏ sót đúng trường hợp phổ
        // biến nhất (subscriber hoàn toàn mới). Đã xác nhận bằng test thật khi triển khai.
        if ($status === SubscriberStatus::Active && $previousStatus !== SubscriberStatus::Active) {
            Mail::to($email)->queue(new WelcomeSubscriberMail($subscriber));
        }

        return $subscriber;
    }
}
```

**`ConfirmSubscriptionAction`** (chỉ dùng khi `NEWSLETTER_DOUBLE_OPT_IN=true`) — người nhận bấm link ký chữ ký (`URL::signedRoute('newsletter.public.confirm', ['subscriber' => $subscriber->uuid])`) trong `ConfirmSubscriptionMail`:

```php
class ConfirmSubscriptionAction
{
    use AsAction;

    public function handle(NewsletterSubscriber $subscriber): NewsletterSubscriber
    {
        if ($subscriber->status !== SubscriberStatus::PendingConfirmation) {
            return $subscriber; // đã xác nhận rồi (bấm lại link cũ) hoặc đã unsubscribe — không làm gì thêm
        }

        $subscriber->update(['status' => SubscriberStatus::Active, 'confirmed_at' => now()]);

        SyncSubscriberToResendJob::dispatch($subscriber->id, $subscriber->full_name, $subscriber->email);
        Mail::to($subscriber->email)->queue(new WelcomeSubscriberMail($subscriber));

        return $subscriber;
    }
}
```

Route xác nhận dùng `GET` (không phải `POST`) vì đây là hành động **thêm vào** danh sách (ngược với unsubscribe, §0 mục 5 v1.0 — GET-thì-huỷ-luôn mới là anti-pattern; GET-thì-xác-nhận-đăng-ký không có rủi ro tương tự vì hệ quả xấu nhất của 1 lượt fetch tự động từ mail client là kích hoạt sớm 1 đăng ký hợp lệ, không phải mất dữ liệu ngoài ý muốn của người dùng).

```php
// Modules/Newsletter/app/Jobs/SyncSubscriberToResendJob.php
class SyncSubscriberToResendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300, 900]; // giãn cách tăng dần — chịu được cả rate-limit 429 tạm thời lẫn Resend downtime ngắn

    public function __construct(
        private readonly int $subscriberId,
        private readonly string $fullName,
        private readonly string $email,
    ) {}

    /** §0 mục 9 — nguyên cụm full_name vào first_name, không tự tách. */
    public function handle(): void
    {
        $subscriber = NewsletterSubscriber::find($this->subscriberId);
        if (! $subscriber) {
            return; // đã bị xoá giữa lúc job chờ hàng đợi — không còn gì để đồng bộ
        }

        $segmentId = config('newsletter.resend_segment_id');
        $attrs     = ['first_name' => $this->fullName, 'unsubscribed' => false];

        if ($subscriber->resend_contact_id) {
            Resend::contacts()->update($subscriber->resend_contact_id, $attrs);
            return;
        }

        $contact = Resend::contacts()->create([...$attrs, 'email' => $this->email, 'segments' => [$segmentId]]);
        $subscriber->update(['resend_contact_id' => $contact->id]);
    }
}
```

Không `catch` lỗi Resend trong job — để job **fail tự nhiên và tự retry** theo `$tries`/`$backoff` (bao gồm cả trường hợp 429 do vượt 10 request/giây, §2.2). Sau 5 lần thử vẫn lỗi, job vào `failed_jobs` — subscriber vẫn tồn tại đầy đủ trong DB nội bộ (`resend_contact_id=null`), có thể xử lý lại thủ công (`php artisan queue:retry` hoặc lệnh đồng bộ lại sau, không cấp thiết cho v2.0).

### 9.2 `WelcomeSubscriberMail` (transactional, không phải Broadcast)

```php
class WelcomeSubscriberMail extends Mailable
{
    public function __construct(private readonly NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(from: config('newsletter.from_address'), subject: 'Cảm ơn bạn đã đăng ký nhận bản tin');
    }

    public function content(): Content
    {
        return new Content(view: 'newsletter::emails.welcome', with: ['fullName' => $this->subscriber->full_name]);
    }
}
```

**`ConfirmSubscriptionMail`** (chỉ gửi khi double opt-in bật, §0 mục 14) — tương tự `WelcomeSubscriberMail` nhưng nội dung là lời mời bấm xác nhận, kèm `URL::signedRoute('newsletter.public.confirm', ['subscriber' => $subscriber->uuid])` (không hết hạn — subscriber có thể xác nhận trễ vài ngày vẫn hợp lệ, khác `unsubscribe` ở v1.0 vốn không cần giới hạn thời gian tương tự).

### 9.3 Đồng bộ ngược từ Resend — Listener lắng nghe Event có sẵn của package

```php
// Modules/Newsletter/app/Listeners/SyncSubscriberFromResendWebhookListener.php
class SyncSubscriberFromResendWebhookListener
{
    public function handleContactUpdated(\Resend\Laravel\Events\ContactUpdated $event): void
    {
        $data = $event->payload['data'] ?? [];
        $subscriber = NewsletterSubscriber::where('resend_contact_id', $data['id'] ?? null)->first();

        if ($subscriber && ($data['unsubscribed'] ?? false)) {
            $subscriber->update(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()]);
        }
    }

    public function handleContactDeleted(\Resend\Laravel\Events\ContactDeleted $event): void
    {
        $data = $event->payload['data'] ?? [];
        NewsletterSubscriber::where('resend_contact_id', $data['id'] ?? null)->update(['status' => SubscriberStatus::Unsubscribed]);
    }

    public function handleEmailBounced(\Resend\Laravel\Events\EmailBounced $event): void
    {
        $email = $event->payload['data']['to'][0] ?? null;
        if ($email) {
            NewsletterSubscriber::where('email', $email)->update(['status' => SubscriberStatus::Bounced]);
        }
    }

    public function handleEmailComplained(\Resend\Laravel\Events\EmailComplained $event): void
    {
        $email = $event->payload['data']['to'][0] ?? null;
        if ($email) {
            NewsletterSubscriber::where('email', $email)->update(['status' => SubscriberStatus::Complained]);
        }
    }
}
```

Đăng ký trong `Modules/Newsletter/app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    \Resend\Laravel\Events\ContactUpdated::class => [[SyncSubscriberFromResendWebhookListener::class, 'handleContactUpdated']],
    \Resend\Laravel\Events\ContactDeleted::class => [[SyncSubscriberFromResendWebhookListener::class, 'handleContactDeleted']],
    \Resend\Laravel\Events\EmailBounced::class   => [[SyncSubscriberFromResendWebhookListener::class, 'handleEmailBounced']],
    \Resend\Laravel\Events\EmailComplained::class => [[SyncSubscriberFromResendWebhookListener::class, 'handleEmailComplained']],
];
```

> **Payload thật của mỗi event cần xác nhận lại đúng theo response thật khi bật `RESEND_WEBHOOK_SECRET` và test trực tiếp** — cấu trúc `payload['data']` ở trên dựa theo format chuẩn Resend Webhook Events, **bắt buộc** verify lại bằng webhook test thật của Resend Dashboard ở Phase 4 (§14) trước khi coi là chốt, không phải tuỳ chọn.

### 9.4 `SendBroadcastAction` — soạn & gửi bản tin

```php
class SendBroadcastAction
{
    use AsAction;

    public function handle(string $subject, string $bodyHtml, ?string $scheduledAt = null): void
    {
        if (! str_contains($bodyHtml, '{{{RESEND_UNSUBSCRIBE_URL}}}')) {
            throw new \InvalidArgumentException('Nội dung bản tin phải chứa merge tag {{{RESEND_UNSUBSCRIBE_URL}}} (bắt buộc để tuân thủ unsubscribe).');
        }

        $broadcast = Resend::broadcasts()->create([
            'segment_id' => config('newsletter.resend_segment_id'),
            'from'       => config('newsletter.from_address'),
            'subject'    => $subject,
            'html'       => $bodyHtml,
            'scheduled_at' => $scheduledAt, // null = không lên lịch, gửi theo bước sau
        ]);

        Resend::broadcasts()->send($broadcast->id, $scheduledAt ? ['scheduled_at' => $scheduledAt] : []);

        // §0 mục 13 — ghi log NGAY SAU khi cả 2 lệnh gọi Resend thành công (không ghi trước —
        // nếu create()/send() ném exception, không để lại log "đã gửi" sai sự thật).
        NewsletterBroadcastLog::create([
            'resend_broadcast_id' => $broadcast->id,
            'subject'             => $subject,
            'scheduled_at'        => $scheduledAt,
            'sent_by'             => auth()->id(),
        ]);
    }
}
```

### 9.5 `RemoveSubscriberAction` — xoá thủ công (§0 mục 15)

```php
class RemoveSubscriberAction
{
    use AsAction;

    public function handle(NewsletterSubscriber $subscriber): void
    {
        // §0 mục 16 — bất kể subscriber đang ở status nào (kể cả `pending_confirmation`), luôn
        // set unsubscribed_at + soft-delete. Guard resend_contact_id TỰ NHIÊN xử lý đúng
        // trường hợp pending_confirmation: subscriber đó CHƯA TỪNG được SubscribeAction dispatch
        // SyncSubscriberToResendJob (§9.1 — nhánh double opt-in return sớm trước khi dispatch),
        // nên resend_contact_id chắc chắn vẫn null → job KHÔNG được gọi, đúng vì không có gì để
        // gọi (Resend chưa từng biết tới người này).
        if ($subscriber->resend_contact_id) {
            UnsubscribeSubscriberFromResendJob::dispatch($subscriber->resend_contact_id);
        }

        // KHÔNG set resend_contact_id = null ở đây (§0 mục 16) — cố tình GIỮ NGUYÊN cột này dù
        // đã soft-delete, để nếu người này đăng ký lại sau (SubscribeAction, §9.1), job đồng bộ
        // nhận ra contact cũ và UPDATE lại (Resend::contacts()->update(), unsubscribed=false),
        // thay vì CREATE ra 1 contact trùng cho cùng 1 email trên Resend.
        $subscriber->update(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()]);
        $subscriber->delete(); // soft-delete — KHÔNG forceDelete, giữ lịch sử (§0 mục 15)
    }
}
```

```php
// Modules/Newsletter/app/Jobs/UnsubscribeSubscriberFromResendJob.php — cùng cấu trúc retry với
// SyncSubscriberToResendJob (§9.1), tách file riêng vì mục đích khác (unsubscribe, không phải tạo/cập nhật).
class UnsubscribeSubscriberFromResendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(private readonly string $resendContactId) {}

    public function handle(): void
    {
        Resend::contacts()->update($this->resendContactId, ['unsubscribed' => true]);
    }
}
```

> ⚠ **`UnsubscribeSubscriberFromResendJob` KHÔNG được gọi `Resend::contacts()->remove()`** (xoá cứng contact bên Resend) — chỉ set `unsubscribed=true`, giữ nguyên contact bên đó. Đây là **quyết định có chủ đích, không phải thiếu sót** — người implement thấy `Resend::contacts()->remove()` có sẵn trong SDK (§2.2) không được tự ý đổi sang gọi nó ở đây. Lý do: xoá cứng mất luôn lịch sử tương tác (mở/click) phía Resend, trong khi mục tiêu thật của "xoá thủ công" là **ngừng gửi cho người này**, không phải xoá mọi dấu vết. Nếu sau này cần xoá cứng thật sự (đúng nghĩa GDPR "xoá vĩnh viễn"), đó là quyết định riêng cần cân nhắc kỹ hơn (1 Action **khác**, `ForgetSubscriberAction`, không sửa action này) — xem §16 mục 4.

---

## 10. Routes

```php
// Modules/Newsletter/routes/web.php

// Công khai
Route::post('newsletter/subscribe', [PublicSubscriptionController::class, 'subscribe'])
    ->middleware('throttle:10,1')->name('newsletter.public.subscribe');

// Chỉ có ý nghĩa khi NEWSLETTER_DOUBLE_OPT_IN=true (§0 mục 14) — vẫn đăng ký route kể cả khi
// tắt, để bật/tắt chỉ cần đổi .env, không cần đổi route.
Route::get('newsletter/confirm/{subscriber}', [PublicSubscriptionController::class, 'confirm'])
    ->middleware('signed')->name('newsletter.public.confirm');

// Admin — platform-wide
Route::middleware(['auth'])->prefix('dashboard/newsletter')->name('backend.newsletter.')->group(function () {
    Route::get('subscribers', [SubscriberAdminController::class, 'index'])->name('subscribers.index');
    Route::delete('subscribers/{subscriber}', [SubscriberAdminController::class, 'destroy'])->name('subscribers.destroy');
    Route::get('broadcast', [BroadcastAdminController::class, 'create'])->name('broadcast.create');
    Route::post('broadcast', [BroadcastAdminController::class, 'send'])->name('broadcast.send');
    Route::get('broadcast/logs', [BroadcastAdminController::class, 'logs'])->name('broadcast.logs');
});
```

Không cần route unsubscribe/webhook riêng — cả 2 đã có sẵn (Resend tự host unsubscribe; `resend-laravel` đã tự đăng ký route webhook, xem §2.1).

---

## 11. Permissions

```php
// Modules/Newsletter/app/Policies/NewsletterPolicy.php
class NewsletterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformContentEditor() || $user->isPlatformContentHead();
    }

    /** Xoá thủ công (§0 mục 15) — cùng cấp viewAny(), coi là thao tác dọn dẹp danh sách thường
     *  ngày (đã soft-delete + đồng bộ unsubscribe Resend, không phải xoá cứng), không cần
     *  gate cao như sendBroadcast(). */
    public function removeSubscriber(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Gửi broadcast tới toàn bộ danh sách — hành động khó thu hồi, chỉ cấp cao hơn. */
    public function sendBroadcast(User $user): bool
    {
        return $user->isPlatformContentHead();
    }
}
```

`super-admin` bypass qua `Gate::before()` sẵn có.

---

## 12. UI/UX Admin (tối giản)

- **`dashboard/newsletter/subscribers`** — bảng: họ tên, email, trạng thái (badge theo `SubscriberStatus::badgeClass()`, gồm cả `pending_confirmation` nếu double opt-in đang bật), ngày đăng ký, nút "Xoá" (confirm dialog) trên mỗi dòng gọi `RemoveSubscriberAction` (§0 mục 15/§9.5). Không có nút "Sửa" — full_name/email không cho sửa tay (nếu sai, xoá rồi để họ tự đăng ký lại là đủ, tránh lệch dữ liệu 2 chiều với Resend do sửa tay 1 phía).
- **`dashboard/newsletter/broadcast`** — form: subject (input) + nội dung (Jodit, kèm nút "Chèn link unsubscribe bắt buộc" tự động chèn `{{{RESEND_UNSUBSCRIBE_URL}}}` vào cuối nội dung để tránh admin quên) + tuỳ chọn "Gửi ngay" hoặc "Lên lịch" (datetime picker, dùng `flatpickr.js` đã có). Submit gọi `SendBroadcastAction`.
- **`dashboard/newsletter/broadcast/logs`** — bảng lịch sử đã gửi (§0 mục 13): subject, thời điểm gửi/lên lịch, người gửi (`sentBy.name`), link tới `resend_broadcast_id` trên Resend Dashboard nếu muốn xem số liệu mở/click chi tiết (không hợp nhất số liệu vào đây — xem §3.2).
- **Sidebar**: mục "Bản tin" — "Người đăng ký" + "Soạn bản tin" + "Lịch sử gửi", hiện khi `isPlatformContentEditor()`/`isPlatformContentHead()`, cùng pattern mục "Bài viết chờ duyệt" đã có ở `resources/views/layouts/partials/sidebar.blade.php`.

---

## 13. Testing & Acceptance Criteria

1. Submit form subscribe với `full_name`+`email` hợp lệ → tạo `NewsletterSubscriber` status=active, gọi `Resend::contacts()->create()` với đúng `email`/`first_name`/`segments`, gửi `WelcomeSubscriberMail`.
2. Subscribe lại đúng email đang **active** → **không** tạo bản ghi trùng (update tại chỗ), **không** gửi lại welcome mail (`$previousStatus === Active` nên điều kiện gửi welcome mail false, §9.1 — đã verify thật, phát hiện `wasChanged()` không đáng tin cho bản ghi mới insert lúc triển khai, sửa thành so `$previousStatus`).
3. `SyncSubscriberToResendJob` lỗi/timeout (kể cả 429) → subscriber vẫn được lưu DB nội bộ ngay từ `SubscribeAction` (`resend_contact_id=null` cho tới khi job chạy thành công), request HTTP submit form vẫn trả về nhanh bình thường (job chạy nền, không chặn response) — job tự retry theo `$tries`/`$backoff`, vào `failed_jobs` nếu hết lượt thử.
4. `SendBroadcastAction` với nội dung **thiếu** `{{{RESEND_UNSUBSCRIBE_URL}}}` → ném `InvalidArgumentException`, không gọi API Resend.
5. Webhook `contact.updated` với `unsubscribed=true` → subscriber tương ứng (theo `resend_contact_id`) chuyển status `unsubscribed`.
6. Webhook `email.bounced` → subscriber tương ứng (theo `email`) chuyển status `bounced`.
7. User chỉ có `isPlatformContentEditor()` (không phải content_head) → gọi route gửi broadcast bị 403.
8. `SendBroadcastAction` chạy thành công → tạo đúng 1 `NewsletterBroadcastLog` với `resend_broadcast_id`/`subject`/`sent_by` khớp; nếu `Resend::broadcasts()->create()` ném exception → **không** tạo log nào (không log sai sự thật "đã gửi" khi thực ra chưa gửi được).
9. Bật `NEWSLETTER_DOUBLE_OPT_IN=true`, subscribe email mới → status `pending_confirmation`, gửi `ConfirmSubscriptionMail` (không gửi `WelcomeSubscriberMail`), **không** dispatch `SyncSubscriberToResendJob` (chưa đồng bộ Resend khi chưa xác nhận).
10. Bấm link xác nhận (`ConfirmSubscriptionAction`) đúng 1 lần → status chuyển `active`, `confirmed_at` được set, dispatch `SyncSubscriberToResendJob`, gửi `WelcomeSubscriberMail`. Bấm lại link đó lần 2 → không lặp lại các hành động trên (status đã khác `pending_confirmation`).
11. Tắt `NEWSLETTER_DOUBLE_OPT_IN` (mặc định `false`) → hành vi subscribe y hệt trước khi có tính năng này (test #1 vẫn pass nguyên, không bị phá bởi migration/enum mới thêm).
12. `RemoveSubscriberAction` với subscriber đã có `resend_contact_id` → subscriber bị soft-delete (`deleted_at` được set, không mất khỏi DB), dispatch `UnsubscribeSubscriberFromResendJob` với đúng `resend_contact_id`; với subscriber **chưa** có `resend_contact_id` (vd đang chờ sync) → soft-delete local, **không** dispatch job nào (không có gì để gọi).
13. User chỉ có quyền cơ bản (`viewAny`, tức `isPlatformContentEditor()`) → vẫn xoá được subscriber (test đúng `removeSubscriber()` dùng chung mức `viewAny`, không yêu cầu `platform_content_head`).
14. **(§0 mục 16a)** Bật double opt-in, subscribe email mới (status → `pending_confirmation`, `resend_contact_id` vẫn `null`) → gọi `RemoveSubscriberAction` ngay lúc này → soft-delete thành công, **không** dispatch `UnsubscribeSubscriberFromResendJob` (không có `resend_contact_id` để gọi) — khác biệt tường minh với test #12 (trường hợp subscriber active đã có `resend_contact_id`).
15. **(§0 mục 16b)** Subscribe email (double opt-in tắt) → active, có `confirmed_at` **null** (vì chưa từng qua `ConfirmSubscriptionAction`) và có `resend_contact_id`. Gọi `RemoveSubscriberAction` xoá. Bật `NEWSLETTER_DOUBLE_OPT_IN=true`, subscribe lại đúng email đó → vì `confirmed_at` vẫn null (chưa từng xác nhận thật) và `status` lúc này là `unsubscribed` (không phải `Active`) → phải chuyển `pending_confirmation`, KHÔNG vào thẳng active dù đây là bản ghi cũ được `restore()` — đây chính là chỗ sửa lỗi logic `$isNew` cũ (§0 mục 16).
16. **(§0 mục 16c)** Sau khi `RemoveSubscriberAction` chạy xong, kiểm tra `resend_contact_id` trên bản ghi (kể cả đã `withTrashed()`) → vẫn giữ nguyên giá trị cũ, không bị set về `null`.

---

## 14. Phased Implementation Plan

| Phase | Nội dung |
|---|---|
| 1 | Migration (cả 2 bảng, §4.1/§4.2) + Enum (đủ 5 case, kể cả `PendingConfirmation`) + Model (cả 2) + `NewsletterServiceProvider`/`NewsletterPolicy`. Thêm đủ cột/enum ngay từ Phase 1 dù double opt-in để tắt mặc định — tránh migration backfill sau (§0 mục 14). |
| 2 | `SubscribeAction` (đã có nhánh double opt-in, mặc định tắt nên hành vi = single opt-in) + `PublicSubscriptionController` + form công khai + `WelcomeSubscriberMail`. |
| 3 | Tạo 1 Segment thủ công trên Resend Dashboard (`Resend::segments()->create()` hoặc qua giao diện), dán `NEWSLETTER_RESEND_SEGMENT_ID` vào `.env`, set `RESEND_WEBHOOK_SECRET` + đăng ký URL webhook trên Resend Dashboard trỏ về `{APP_URL}/resend/webhook`. |
| 4 | `SyncSubscriberFromResendWebhookListener` + `EventServiceProvider`. **Bắt buộc** (không phải tuỳ chọn): trigger "Send test event" thật từ Resend Dashboard cho từng loại webhook đang lắng nghe, log payload thật ra, đối chiếu lại đúng cấu trúc `payload['data']` đã giả định ở §9.3 **trước khi coi Phase này xong** — nếu lệch, sửa lại Listener theo payload thật, không phải theo suy đoán. |
| 5 | `SendBroadcastAction` (kèm ghi `NewsletterBroadcastLog` ngay từ bản đầu, §0 mục 13) + trang admin soạn/gửi bản tin + trang lịch sử gửi + sidebar. |
| 6 | Trang admin danh sách subscriber (đọc) + `RemoveSubscriberAction`/`UnsubscribeSubscriberFromResendJob` + nút "Xoá" (§0 mục 15). |
| 7 | Nếu `NEWSLETTER_DOUBLE_OPT_IN` được bật thật (quyết định vận hành, không phải lúc code): `ConfirmSubscriptionAction` + `ConfirmSubscriptionMail` + route confirm — đã thiết kế sẵn từ Phase 1-2, chỉ cần bật cấu hình và deploy view email xác nhận. |

---

## 15. Lộ trình mở rộng (ngoài phạm vi v2.3 — thứ tự có chủ đích, không phải danh sách phẳng)

Đánh số theo **thứ tự làm trước/sau có phụ thuộc lẫn nhau** — mục sau chỉ có ý nghĩa nếu mục trước đã làm, không phải danh sách tuỳ ý chọn ngẫu nhiên:

1. **Nhiều Segment / phân nhóm theo chuyên mục** — hiện tại chỉ 1 Segment duy nhất (§0 mục 11). Làm khi thật sự cần gửi nội dung khác nhau cho nhóm độc giả khác nhau (vd theo chủ đề quan tâm) — lúc đó thêm bảng segment-quan-tâm phía subscriber (tương tự `newsletter_subscriber_categories` đã thiết kế ở v1.0, có thể phục hồi lại thiết kế đó) + đổi `SendBroadcastAction` nhận `segment_id` làm tham số thay vì hằng số cấu hình.
2. **Preference Center** (trang cho subscriber tự chọn tần suất/chủ đề quan tâm, tự quản lý không cần liên hệ admin) — **chỉ có ý nghĩa sau khi đã làm mục 1** (nhiều Segment để chọn). Làm mục này trước khi có nhiều Segment sẽ không có gì để "cho subscriber chọn" — 1 trang preference với đúng 1 lựa chọn (nhận/không nhận) chính là link unsubscribe đã có sẵn (§0 mục 5), không cần xây thêm.
3. **Import/Export Excel subscriber** — độc lập với 2 mục trên, thêm bất cứ lúc nào bằng `rap2hpoutre/fast-excel` đã sẵn trong `composer.json`, không đổi schema.
4. **Quy trình duyệt trước khi gửi broadcast** — chỉ cần nếu đội ngũ phình to tới mức 1 người gửi nhầm gây rủi ro thật (hiện `platform_content_head` tự chịu trách nhiệm gửi trực tiếp là đủ).
5. **Trang thống kê mở/click ngay trong dashboard app** (thay vì link trỏ sang Resend Dashboard, §16 mục 3) — làm nếu muốn hợp nhất UI, không cấp thiết vì Resend đã có sẵn.
6. **Xoá cứng (`forceDelete`) theo yêu cầu GDPR thật sự** — hiện `RemoveSubscriberAction` (§0 mục 15) chỉ soft-delete + unsubscribe, chưa xoá vĩnh viễn. Xem §16 mục 4.

---

## 16. Open Questions

> Việc xác nhận payload webhook thật (trước là mục #2 ở bản v2.0) **không còn là câu hỏi mở** — đã nâng thành bước bắt buộc ở Phase 4 (§14), không thể bỏ qua. Chỉ còn lại các câu hỏi thật sự cần **quyết định** (không phải chỉ "cần làm"):

1. **Segment ID** (`NEWSLETTER_RESEND_SEGMENT_ID`) hiện phải tạo **thủ công** 1 lần trên Resend Dashboard trước khi Phase 2 chạy được — có cần tự động hoá bằng API (`Resend::segments()->create()`) chạy 1 lần lúc cài đặt module (migration/seeder) không, hay chấp nhận thao tác tay 1 lần là đủ? Spec này giả định **thao tác tay 1 lần**, đơn giản hơn.
2. **Ngưỡng quyết định bật `NEWSLETTER_DOUBLE_OPT_IN`** — spec chỉ nói "bật nếu danh sách có nguy cơ spam", chưa có con số cụ thể (vd tỷ lệ bounce > X% trong Y ngày). Để lại cho lúc vận hành thật tự đánh giá qua số liệu `SubscriberStatus::Bounced`/`Complained` trong `newsletter_subscribers`, chưa cần ngưỡng tự động hoá ở v2.1.
3. **`NewsletterBroadcastLog` có cần hiển thị số liệu mở/click** (gọi ngược `Resend::broadcasts()->get($id)` để lấy số liệu) ngay trong trang "Lịch sử gửi" của app, hay để nguyên dạng link trỏ sang Resend Dashboard như hiện tại (§12)? Spec này chọn **để nguyên link trỏ sang Resend** — đơn giản hơn, tránh phải tự cache/đồng bộ số liệu vốn đã có sẵn UI riêng bên Resend.
4. **`RemoveSubscriberAction` (§0 mục 15) có cần nâng lên xoá cứng thật sự** (`forceDelete()` local + `Resend::contacts()->remove()`) cho đúng yêu cầu GDPR "quyền được quên" (right to erasure), thay vì chỉ soft-delete + `unsubscribed=true` như hiện tại? Spec này **cố tình chọn mức nhẹ hơn** (§9.5 giải thích lý do) vì chưa có yêu cầu pháp lý cụ thể bắt buộc xoá cứng — nếu phát sinh yêu cầu GDPR thật (từ EU hoặc theo chính sách nội bộ), cần bổ sung thêm 1 Action riêng (`ForgetSubscriberAction`) tách biệt khỏi `RemoveSubscriberAction` thường (giữ nguyên 2 cấp độ khác nhau, không gộp chung), xem §15 mục 6.
