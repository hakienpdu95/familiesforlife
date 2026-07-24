# Author / Contributor Hub — Trang tác giả công khai + Thống kê hiệu suất phóng viên

**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.0
**Ngày:** 23/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Vị trí:** Feature mới **bên trong `Modules/Post`** (không phải module NWIDART riêng) — `Modules/Post/app/Features/AuthorHub/`
**Module liên quan:** `Modules/Post` (nguồn bài viết + tác giả), `Modules/Auth` (trang hồ sơ cá nhân đã có sẵn), `app/Enums/PermissionEnum.php` (RBAC Lớp B)

> **Lịch sử phiên bản**
> - **v1.0** — trang công khai `/tac-gia/{slug}` cho từng tác giả (avatar, tiểu sử, danh sách bài đã xuất bản) + trang danh sách `/tac-gia`; byline trên bài viết trở thành liên kết; dashboard nội bộ thống kê hiệu suất phóng viên (số bài, lượt xem, xu hướng theo thời gian) dành cho `platform_content_head`/`platform_ops`, mỗi phóng viên tự xem được thống kê của chính mình qua trang hồ sơ cá nhân.
> - **v1.1** — bỏ toàn bộ nhánh logic dựa trên `organization_id`/`account_type` (khái niệm Organization/tenancy) khỏi Author Hub: `is_public` nay mặc định `true` đồng nhất cho MỌI tác giả (không phân biệt nhân sự tòa soạn hay cộng tác viên thuộc Organization) — Author Hub không đọc trường nào thuộc miền Auth/Organization để quyết định hành vi hiển thị (§0, §2.4, §5.1, §10).
> - **v1.2** — thu hẹp phạm vi "tác giả" của Author Hub: chỉ áp dụng cho `User` có tài khoản do **hệ thống/nền tảng quản lý** (`account_type = platform`, tức `$user->isPlatform()`), KHÔNG áp dụng cho role `marketing` (Lớp B, tài khoản thuộc quản lý của 1 Organization cụ thể) dù role này vẫn tạo bài viết được theo RBAC hiện tại của `Modules/Post`. "Contributor" (cộng tác viên viết bài, không tự publish) nay = role có sẵn `platform_content_creator` — KHÔNG tạo role/permission RBAC mới. "Author" (biên tập viên, có quyền duyệt/publish) = `platform_content_head`/`platform_section_editor`/`platform_content_editor`/`platform_ops`. Đây là 1 điều kiện phạm vi (eligibility) áp dụng 1 lần ở tầng truy vấn, khác với việc đọc `organization_id` để rẽ nhánh hành vi (đã loại bỏ ở v1.1) — Author Hub vẫn không đọc bất kỳ dữ liệu nào của model `Organization` (§0, §2.4, §5.1, §6.1, §6.2, §7, §9, §10).
> - **v1.3** — gộp trang thống kê tự xem (trước đây là 1 khối tóm tắt riêng, không biểu đồ, ở `/auth/profile`) vào ĐÚNG trang chi tiết đầy đủ dành cho quản lý (`dashboard/author-hub/{user}`, §6.2) — tác giả tự xem chính mình được cấp quyền truy cập route này (`auth()->id() === $user->id || can('author_hub.manage')`) thay vì có 1 view rút gọn riêng. Loại bỏ trùng lặp UI, tác giả có insight đầy đủ (biểu đồ theo ngày + bảng bài) như quản lý xem về họ, không tốn thêm view/logic tổng hợp số liệu riêng (§5.3, §6.1, §6.2).

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Module mới hay Feature trong `Modules/Post`?** | Tiền lệ `Modules/Post/app/Features/RelatedPosts/`, `.../BreakingNews/` — feature nội tại, chỉ đọc/ghi dữ liệu Post, không cần `module.json`/`ServiceProvider` riêng | Làm **Feature `AuthorHub`** trong `Modules/Post`, KHÔNG tạo module riêng | Toàn bộ dữ liệu nguồn (`post_articles`, `post_article_translations`, `users`) đã thuộc phạm vi Post — không có lý do tách module, tránh boilerplate không cần thiết |
| **"Tác giả" là ai?** | `PostArticle::createdBy()` (`Modules/Post/app/Models/PostArticle.php:137-140`) — `belongsTo(User::class, 'created_by')`. `PostArticlePolicy::create()` chỉ check `$user->can('post_article.create')` — permission này được cấp cho **2 nhóm khác nhau**: (a) role Lớp A `platform_content_creator`/`platform_content_head` (nhân sự/cộng tác viên do **hệ thống** cấp tài khoản, `account_type=platform`, không thuộc Organization nào); (b) role Lớp B `marketing` (`config/permissions.php` dòng ~109-128) — tài khoản **do 1 Organization tạo và quản lý** (`Modules/User/app/Actions/StoreUserAction.php`), chính comment trong `PostArticlePolicy.php` gọi nhóm này là **"cộng tác viên"** ("Doanh nghiệp/đội marketing (cộng tác viên) chỉ tự submitForReview... KHÔNG tự duyệt/publish"). Không có khái niệm "guest author" tách khỏi tài khoản User nào | **"Tác giả" trong Author Hub = `User` có `account_type=platform` (`$user->isPlatform()`) xuất hiện ở `post_articles.created_by`.** Nhóm (b) `marketing` — tài khoản do Organization quản lý — **KHÔNG** thuộc phạm vi Author Hub: vẫn viết/gửi duyệt bài bình thường theo RBAC hiện tại của `Modules/Post`, nhưng không có hồ sơ `PostAuthorProfile`, không xuất hiện ở `/tac-gia`, không tính vào dashboard hiệu suất | Tài khoản của bất kỳ ai xuất hiện trong Author Hub (trang công khai + dashboard hiệu suất) phải do **hệ thống/nền tảng quản lý** (System Admin cấp qua `platform:user-create`/`PlatformUserController`), KHÔNG do 1 Organization cụ thể quản lý — tránh vòng đời tài khoản tác giả bị phụ thuộc vào 1 tổ chức bên ngoài (tổ chức đó có thể đổi nhân sự, ngừng hợp tác, xoá user...), đồng thời giữ Author Hub không đọc bất kỳ dữ liệu nào của model `Organization` |
| **"Author" (biên tập viên) vs "Contributor" (cộng tác viên viết bài) — khác nhau ở đâu?** | `isPlatformContentCreator()` (`app/Models/User.php:208-211`, comment gốc: *"Viết/sửa bài + upload media... KHÔNG có quyền publish/duyệt — tách biệt viết vs duyệt để tránh tự duyệt bài của chính mình"*) đã đúng ngữ nghĩa "cộng tác viên viết bài", và đã là role platform (không qua Organization) — không cần role RBAC mới | **Contributor** = role có sẵn `platform_content_creator` (viết + gửi duyệt, không tự publish). **Author** = các role platform có thẩm quyền biên tập/duyệt: `platform_content_head`/`platform_content_editor`/`platform_section_editor`/`platform_ops`. Cả 2 đều `account_type=platform`, tài khoản do System Admin cấp — Author Hub không tạo permission/role RBAC mới, chỉ tái dùng đúng 2 nhóm role Lớp A đã có. `is_public` mặc định `true` đồng nhất cho cả 2 (không còn lý do phân biệt mặc định vì cả 2 cùng account_type=platform, §v1.1) | Tận dụng tối đa RBAC đã có (`platform_content_creator` khớp 100% ngữ nghĩa "viết, không publish" của Contributor) — không phát minh permission mới, không đụng `Modules/Post/app/Policies/PostArticlePolicy.php`. Việc "Contributor không thuộc Organization" giải quyết ĐÚNG yêu cầu tài khoản do hệ thống quản lý, thay vì mượn role `marketing` (vốn được thiết kế cho mục đích khác — doanh nghiệp gửi bài PR/tài trợ của chính họ, không phải "viết báo cho tòa soạn") |
| **Hồ sơ tác giả lưu ở đâu?** | `users` là bảng dùng chung TOÀN NỀN TẢNG (nhiều module khác cùng phụ thuộc); tiền lệ mở rộng thông tin User theo ngữ cảnh Post đã có: `post_category_editors` (bảng phụ riêng của Post, không thêm cột vào `users` — `Modules/Post/database/migrations/2026_07_13_000004_create_post_category_editors_table.php`) | Bảng mới **`post_author_profiles`** (1-1 với `users`, sống trong `Modules/Post`) — bio, bút danh, mạng xã hội, avatar, `is_public`, `slug`. **KHÔNG** thêm cột vào `users` | Đúng tiền lệ `post_category_editors` — mở rộng ngữ cảnh Post qua bảng phụ, không đụng vào bảng `users` mà nhiều module khác (Auth, Organization, CRM...) cùng phụ thuộc |
| **Avatar tác giả** | `Modules/Auth/resources/views/profile.blade.php:19` hiện dùng **Dicebear initials avatar sinh tự động** (`https://api.dicebear.com/9.x/initials/svg?seed=...`) — **chưa có upload ảnh thật ở bất kỳ đâu trong app**. `HasTenantMedia` trait (`app/Traits/HasTenantMedia.php`) đã dùng sẵn cho `PostArticle`/`PostArticleTranslation` (collection `cover`, `jodit_content`) | `PostAuthorProfile` implements `HasMedia` + `use HasTenantMedia`, collection **`avatar`** — tác giả upload ảnh thật; **fallback Dicebear y hệt cơ chế hiện tại** nếu chưa upload | Tái dùng đúng trait/pattern Media Library đã chứng minh hoạt động tốt cho Post, không phát minh cơ chế upload mới. Fallback Dicebear giữ nguyên trải nghiệm hiện tại cho tác giả chưa upload — không bắt buộc mọi người phải có ảnh thật mới dùng được trang |
| **Nơi tác giả tự sửa hồ sơ công khai** | `/auth/profile` (`Modules/Auth/routes/web.php:52-54`, view `Modules/Auth/resources/views/profile.blade.php`) đã là trang "Hồ sơ cá nhân" duy nhất hiện có, hiển thị avatar/tên/email/trust badge | **Mở rộng thêm 1 card mới** "Hồ sơ tác giả công khai" ngay trong `auth::profile` (không tạo trang settings riêng) — chỉ hiện card này nếu user có ít nhất 1 bài đã published (`post_articles.created_by`) | Tận dụng đúng trang cá nhân đã tồn tại, tránh phân mảnh nơi user quản lý thông tin về mình ra nhiều trang khác nhau |
| **Số liệu hiệu suất có hiển thị công khai không?** | `view_count` hiện có trên `PostArticleTranslation` (`Modules/Post/app/Models/PostArticleTranslation.php:77`), tăng qua `IncrementArticleViewCountAction` — dữ liệu vận hành nội bộ, chưa từng lộ ra trang public | **KHÔNG** hiển thị `view_count`/thống kê hiệu suất trên trang `/tac-gia/{slug}` công khai — trang public chỉ hiện avatar, bio, **số lượng bài đã xuất bản**, danh sách bài (không kèm số view từng bài) | Số liệu hiệu suất là công cụ đánh giá nội bộ của tòa soạn (`platform_content_head`), không phải "vanity metric" để phô ra ngoài — tránh tạo động lực sai (viết tiêu đề câu view thay vì viết tốt) và tránh lộ thông tin cạnh tranh giữa các phóng viên ra công chúng |
| **Ai xem được thống kê hiệu suất của ai** | Chưa có tiền lệ dashboard "theo người" nào trong Post; tiền lệ dashboard thống kê gần nhất là `Modules/Post/resources/views/admin/articles/clicks.blade.php` (thống kê theo 1 bài, không phải theo người) | **v1.3 — 1 trang chi tiết DÙNG CHUNG** (`dashboard/author-hub/{user}`), phân quyền tại controller: (a) `platform_content_head`/`platform_ops` (permission `author_hub.manage`) xem **danh sách TẤT CẢ tác giả** + chi tiết BẤT KỲ ai; (b) mỗi tác giả tự xem **ĐÚNG chi tiết của chính mình** qua cùng route/view đó (`auth()->id() === $user->id`, không cần permission) — KHÔNG còn 1 khối tóm tắt riêng, rút gọn ở `/auth/profile` như bản nháp trước | Tránh xây 2 UI cho cùng 1 loại dữ liệu (tóm tắt cho tác giả, đầy đủ cho quản lý) — tác giả tự xem được đúng insight đầy đủ (biểu đồ + bảng bài) như quản lý xem về họ, tiết kiệm công build view/logic tổng hợp riêng, vẫn tách rõ "xem TẤT CẢ" (cần quyền) khỏi "xem CHÍNH MÌNH" (không cần quyền đặc biệt) |
| **Nguồn dữ liệu thống kê** | (a) `view_count` (per-translation, luỹ kế) + `published_at`/`status` đủ cho tổng số bài/tổng view luỹ kế; (b) `post_article_view_events` (`Modules/Post/app/Models/PostArticleViewEvent.php`) — **log 1 dòng/lượt xem** (`article_id`, `viewed_at`), đã có sẵn cho Related Posts Engine (đồng-xem), retention `config('post.related_posts.behavior_lookback_days')` mặc định **90 ngày** (`PruneArticleViewEventsJob`) | Tổng/trung bình luỹ kế dùng `view_count`; **biểu đồ xu hướng theo ngày dùng `post_article_view_events`** (COUNT group by ngày, join qua `article.created_by`) — **KHÔNG** tạo bảng snapshot/log riêng mới, tái dùng đúng 2 nguồn đã có | Đúng nguyên tắc "giữ v1 gọn" (không đo mới) — may mắn `post_article_view_events` đã tồn tại sẵn cho mục đích khác (Related Posts) nhưng đủ dữ liệu để tái dùng cho biểu đồ theo ngày của Author Hub. Retention 90 ngày khớp vừa đủ với tuỳ chọn kỳ dài nhất dự kiến (§4.2 `stats_period_options`) |
| **URL trang tác giả** | Route convention hiện tại: `danh-muc/{category:slug}` (category), `{slug}-d{id}.html` (bài viết) — `Modules/Post/routes/web.php:134,147` | `/tac-gia/{slug}` (trang chi tiết 1 tác giả) + `/tac-gia` (danh sách tác giả công khai) | Nhất quán với path tiếng Việt đã dùng cho `danh-muc/...`, không lẫn với pattern `{slug}-d{id}.html` của bài viết (tác giả không cần `id` phân biệt vì `slug` đã unique theo thiết kế §3) |
| **Byline hiện tại có đổi không?** | `public/article.blade.php:51`: `Bởi {{ $article->createdBy?->name ?? 'Ban biên tập' }} · {{ ... }}` — text thường, không phải link | Đổi thành **link** tới `/tac-gia/{slug}` **NẾU** tác giả có hồ sơ `is_public=true`; giữ nguyên text thường (không link) nếu không có hồ sơ hoặc `is_public=false` | Chỉ 1 dòng thay đổi tại đúng nơi đã tồn tại, không phá layout hiện có; tôn trọng lựa chọn ẩn hồ sơ của tác giả (không ép ai cũng phải có trang public) |
| **Cache trang tác giả** | Trang chủ/category hiện tại (`PublicCategoryController`) **không** dùng `Cache::remember()` | **KHÔNG cache** ở v1 | Giữ nhất quán với phần còn lại của Post public — thêm cache là tối ưu có thể làm sau nếu đo được nghẽn thật (§10) |
| **Quyền quản trị hồ sơ tác giả khác + xem dashboard toàn đội** | Tiền lệ Lớp B: `BANNER_MANAGE`/`OCOP_MANAGE`/`PAGE_MANAGE`/`CORE_IDEA_EXTRACTOR_USE`/`BREAKING_NEWS_MANAGE` — permission riêng, seed thẳng cho role cụ thể, KHÔNG qua `config/permissions.php` (`app/Enums/PermissionEnum.php:cuối file`) | Permission mới `author_hub.manage`, gán **`platform_ops`** + **`platform_content_head`** | Cùng nguyên tắc và cùng 2 role được gán cho mọi permission Lớp B khác của Post — không phát minh mô hình quyền mới |
| **Guest/freelance author (không có tài khoản User)** | Không tồn tại trong codebase hiện tại — mọi bài viết đều có `created_by` trỏ tới 1 `User` thật | **Ngoài phạm vi v1** — mọi tác giả đều PHẢI là 1 `User` đã có tài khoản (§9) | Đúng hiện trạng dữ liệu, không cần xử lý trường hợp chưa từng xảy ra; nếu cần trong tương lai đây là điểm mở rộng rõ ràng (thêm `PostAuthorProfile.user_id` nullable + cột tên hiển thị độc lập) |

---

## 1. Giới thiệu & Mục tiêu

Hiện tại, "tác giả" của một bài viết chỉ tồn tại dưới dạng 1 dòng chữ tĩnh trên trang chi tiết bài (`Bởi {{ $article->createdBy?->name }}`, `Modules/Post/resources/views/public/article.blade.php:51`) — không có trang riêng cho tác giả, không có cách nào để độc giả xem tất cả bài của cùng 1 người viết, và tòa soạn không có công cụ nào để so sánh hiệu suất giữa các phóng viên (ai viết nhiều/ít, bài nào của ai đang được đọc nhiều).

**Author / Contributor Hub** giải quyết 2 nhu cầu riêng biệt bằng cùng 1 nguồn dữ liệu (`post_articles.created_by` + `view_count` sẵn có):

1. **Công khai**: mỗi tác giả có 1 trang hồ sơ `/tac-gia/{slug}` (avatar, tiểu sử, mạng xã hội, danh sách bài đã xuất bản) + 1 trang danh sách tất cả tác giả `/tac-gia`; byline trên bài viết trở thành liên kết tới trang này.
2. **Nội bộ**: dashboard thống kê hiệu suất phóng viên (số bài xuất bản, tổng lượt xem, xu hướng theo thời gian, bài nổi bật) dành cho lãnh đạo nội dung đánh giá đội ngũ; mỗi phóng viên cũng tự xem được thống kê của chính mình.

**Nguyên tắc thiết kế cốt lõi:** không tạo bảng log/snapshot mới cho thống kê — toàn bộ số liệu tổng hợp trực tiếp từ `view_count`/`published_at`/`status` đã có sẵn trên `PostArticleTranslation`; không thêm cột vào bảng `users` dùng chung toàn nền tảng — hồ sơ tác giả sống trong bảng phụ `post_author_profiles` của riêng `Modules/Post`.

---

## 2. Khảo sát hiện trạng

### 2.1 Quan hệ tác giả ↔ bài viết đã có sẵn

`Modules/Post/app/Models/PostArticle.php:137-140`:
```php
public function createdBy(): BelongsTo
{
    return $this->belongsTo(\App\Models\User::class, 'created_by');
}
```
`created_by` đã là nguồn sự thật duy nhất cho "ai viết bài này" — Author Hub không cần cột/quan hệ mới ở `post_articles`.

### 2.2 Byline hiện tại — chỉ là text, không phải link

`Modules/Post/resources/views/public/article.blade.php:51`:
```blade
Bởi {{ $article->createdBy?->name ?? 'Ban biên tập' }} · {{ $translation->published_at?->format('d/m/Y') }}
```
`Modules/Post/resources/views/admin/articles/show.blade.php:16` cũng hiển thị tương tự ở phía admin (`Tạo bởi {{ $article->createdBy?->name ?? '—' }}`). Đây chính xác là điểm chèn link tới `/tac-gia/{slug}` (§7.3) mà không phải dựng lại layout.

### 2.3 Số liệu xem bài đã có sẵn — 2 nguồn, không cần đo mới

- **Luỹ kế**: `Modules/Post/app/Models/PostArticleTranslation.php:77` — cột `view_count` (cast `integer`). Tăng qua `Modules/Post/app/Features/PublicReading/Actions/IncrementArticleViewCountAction.php:14` (`$translation->increment('view_count')`), gọi từ `PublicArticleController` mỗi lượt đọc bài. Dùng cho tổng/trung bình view (§5.2).
- **Theo thời điểm**: `Modules/Post/app/Models/PostArticleViewEvent.php` (bảng `post_article_view_events`) — log **insert-only, 1 dòng/lượt xem thật** (`article_id`, `visitor_hash`, `viewed_at`, không `updated_at`), ghi qua `RecordArticleViewEventAction` (`Modules/Post/app/Features/RelatedPosts/Actions/`) — vốn dựng cho Related Posts Engine (tính "đồng-xem"), nhưng đủ dữ liệu để tái dùng cho biểu đồ xu hướng theo ngày của Author Hub (group by `DATE(viewed_at)`, join `article.created_by`). Retention: `PruneArticleViewEventsJob` xoá dòng cũ hơn `config('post.related_posts.behavior_lookback_days')` (mặc định **90 ngày**) — đúng bằng kỳ dài nhất dự kiến cho dashboard (§4.2).

### 2.4 Ai thực sự tạo được bài viết — 2 nhóm, không chỉ nhân sự tòa soạn

`Modules/Post/app/Policies/PostArticlePolicy.php:50-52` — `create()` chỉ check `$user->can('post_article.create')`, permission này được cấp cho **2 nhóm role khác hẳn bản chất**:

**(a) Lớp A — nhân sự tòa soạn** (`spec/Platform_RBAC_Phase2_Specification.md` v2.1, đã triển khai — mọi role dưới đây là `User` với `account_type=platform`, `organization_id=null`):

| Role | Vai trò |
|---|---|
| `platform_content_creator` | Viết/sửa bài (gộp từ 2 role dự kiến `platform_reporter` + `platform_media`), KHÔNG publish |
| `platform_content_head` | Toàn quyền nội dung nền tảng, publish/duyệt |
| `platform_section_editor` | Phụ trách 1 hoặc vài chuyên mục cụ thể (qua bảng `post_category_editors`) |
| `platform_ops` | Vận hành nền tảng (banner, tin nóng, trang tĩnh...) |
| `platform_viewer` | Chỉ xem |

**(b) Lớp B — `marketing`** (`config/permissions.php`, role thuộc `RoleEnum::MARKETING`, dòng ~109-128: `P::POST_ARTICLE_CREATE`, `P::POST_ARTICLE_EDIT`) — `User` thuộc **1 Organization cụ thể** (`organization_id` khác null), tức bên ngoài tòa soạn. Comment ngay trong `PostArticlePolicy.php` (phần "Approval workflow") tự gọi nhóm này là **"cộng tác viên"**: *"Doanh nghiệp/đội marketing (cộng tác viên) chỉ tự submitForReview (gửi duyệt) — KHÔNG tự duyệt/publish bài của chính mình"*.

→ **v1.2**: nhóm (a) Lớp A — cụ thể 4 role có thẩm quyền biên tập/duyệt (`platform_content_head`/`platform_content_editor`/`platform_section_editor`/`platform_ops`) là "**Author**", role `platform_content_creator` (viết, không publish) là "**Contributor**" — cả 2 đều `account_type=platform`, KHÔNG do Organization quản lý. Nhóm (b) Lớp B `marketing` **KHÔNG** thuộc phạm vi "tác giả" của Author Hub — tài khoản của nhóm này do 1 Organization tạo/quản lý (`Modules/User/app/Actions/StoreUserAction.php`), không phải hệ thống, nên bị loại khỏi Author Hub dù vẫn tạo/gửi duyệt bài viết bình thường theo `PostArticlePolicy` hiện hành (§0 dòng "Author vs Contributor"). Đây là 1 điều kiện eligibility (`$user->isPlatform()`) áp dụng tại tầng truy vấn của Author Hub, KHÔNG phải Author Hub đọc `organization_id` để rẽ nhánh hành vi hiển thị (nguyên tắc đó vẫn giữ từ v1.1) — Author Hub vẫn không đọc bất kỳ dữ liệu nào của model `Organization`.

### 2.5 Tiền lệ mở rộng thông tin User qua bảng phụ của Post — `post_category_editors`

`Modules/Post/database/migrations/2026_07_13_000004_create_post_category_editors_table.php`:
```php
Schema::create('post_category_editors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['post_category_id', 'user_id'], 'uq_post_category_editor');
});
```
Đúng mẫu cần copy cho `post_author_profiles` (§3) — bảng phụ sống trong `Modules/Post`, tham chiếu `users` qua FK, không đụng schema `users`.

### 2.6 Media Library — tiền lệ upload ảnh, chưa có avatar thật

`app/Traits/HasTenantMedia.php` — trait "drop-in" cho model cần upload file/ảnh, đã dùng cho `PostArticle`/`PostArticleTranslation` (collection `cover`, `jodit_content`). `Modules/Auth/resources/views/profile.blade.php:19` hiện tại:
```blade
<img src="https://api.dicebear.com/9.x/initials/svg?seed={{ urlencode($user->name ?? 'U') }}&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700"
     alt="Avatar" class="w-16 h-16 rounded-full shrink-0">
```
Xác nhận **chưa có upload avatar thật ở bất kỳ đâu trong app** — mọi avatar hiện tại đều là ảnh chữ cái sinh tự động. `PostAuthorProfile` sẽ là nơi ĐẦU TIÊN dùng Media Library cho avatar thật (§4.1), fallback Dicebear giữ nguyên hành vi hiện tại khi chưa upload.

### 2.7 Tiền lệ UI dashboard thống kê — `articles/clicks.blade.php`

`Modules/Post/resources/views/admin/articles/clicks.blade.php` (thống kê click cho 1 bài `format=redirect`) đã có sẵn mẫu UI: 4 summary card (tổng/30 ngày/trung bình/cao nhất) + biểu đồ theo ngày (ECharts, `resources/js/modules/echarts.js`, dark-mode aware qua `document.documentElement.getAttribute('data-theme')`) + danh sách top (thanh progress ngang). Dashboard hiệu suất phóng viên (§6.2) tái dùng đúng bố cục này, đổi "theo bài" thành "theo tác giả".

### 2.8 Trang cá nhân đã có sẵn — điểm mở rộng cho hồ sơ tác giả

`Modules/Auth/routes/web.php:52-54`:
```php
Route::get('/profile', fn (Request $request) => view('auth::profile', [
    'user' => $request->user(),
]))->name('profile');
```
Đây là trang duy nhất user tự quản lý thông tin về mình — Author Hub mở rộng thêm card mới ở đây (§6.1) thay vì tạo route/trang riêng.

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
PostAuthorProfile (post_author_profiles)
  ├─ user_id (FK users, unique — 1-1, cascadeOnDelete)
  ├─ slug (string, unique)                       — route công khai /tac-gia/{slug}
  ├─ pen_name (nullable string 120)               — bút danh hiển thị; null → dùng users.name
  ├─ bio (nullable text)                          — tiểu sử ngắn, hiển thị trên trang công khai
  ├─ social_links (nullable json)                 — {facebook, x, linkedin, website}, mọi khoá optional
  ├─ is_public (bool, default true)               — công tắc ẩn/hiện trang công khai (§0)
  ├─ timestamps
  (avatar qua Spatie Media Library, collection "avatar" — không phải cột riêng)
```

Không có bảng thống kê riêng — số liệu hiệu suất (§5.2) tính trực tiếp bằng query group-by trên `post_articles`/`post_article_translations` tại thời điểm xem dashboard, không snapshot.

### 3.2 Migration

`Modules/Post/database/migrations/2026_07_25_000001_create_post_author_profiles_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §3 — hồ sơ tác giả công khai, mở
 * rộng ngữ cảnh Post cho 1 User mà KHÔNG thêm cột vào bảng users dùng chung toàn nền tảng
 * (đúng tiền lệ post_category_editors). 1-1 với users — user_id unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('slug')->unique();
            $table->string('pen_name', 120)->nullable();
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('is_public')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_author_profiles');
    }
};
```

---

## 4. Model & cấu hình

### 4.1 `PostAuthorProfile` model

`Modules/Post/app/Models/PostAuthorProfile.php`:
```php
<?php

namespace Modules\Post\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §3/§4 — hồ sơ tác giả công khai,
 * 1-1 với User. Avatar qua Media Library (collection "avatar", §2.6) — KHÔNG có cột avatar
 * riêng, fallback Dicebear ở tầng view nếu chưa upload (giữ nguyên hành vi profile.blade.php).
 */
class PostAuthorProfile extends Model implements HasMedia
{
    use HasTenantMedia;

    protected $table = 'post_author_profiles';

    protected $fillable = [
        'user_id', 'slug', 'pen_name', 'bio', 'social_links', 'is_public',
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_public'    => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Display helpers ──────────────────────────────────────────────

    /** Tên hiển thị công khai — ưu tiên bút danh, fallback tên tài khoản thật. */
    public function displayName(): string
    {
        return $this->pen_name ?: (string) $this->user?->name;
    }

    /** URL avatar — ảnh thật nếu đã upload, fallback Dicebear (§2.6) nếu chưa. */
    public function avatarUrl(): string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb')
            ?: 'https://api.dicebear.com/9.x/initials/svg?seed='
                . urlencode($this->displayName())
                . '&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700';
    }

    public static function slugFor(User $user, ?string $penName = null): string
    {
        return Str::slug($penName ?: $user->name) . '-' . $user->id;
    }
}
```

### 4.2 `config/post.php` (`Modules/Post/config/config.php`) — thêm khoá `author_hub`

```php
// spec/Author_Contributor_Hub_Technical_Specification.md — trang tác giả công khai +
// thống kê hiệu suất phóng viên.
'author_hub' => [
    'articles_per_page'      => 12,  // phân trang danh sách bài trên /tac-gia/{slug}
    'stats_default_days'     => 30,  // khoảng thời gian mặc định cho dashboard hiệu suất
    'stats_period_options'   => [7, 30, 90],
],
```

---

## 5. Business rules

### 5.1 Điều kiện xuất hiện trên trang danh sách tác giả công khai (`/tac-gia`)

- Chỉ liệt kê `User` có: (a) `$user->isPlatform()` (`account_type=platform` — §0 "Tác giả là ai?", v1.2), (b) hồ sơ `PostAuthorProfile` với `is_public = true`, **và** (c) ít nhất 1 `PostArticleTranslation` với `status = published` thuộc bài mà `created_by = user.id`. User không `isPlatform()` (kể cả `marketing`/Lớp B dù có bài published) **không bao giờ** xuất hiện, bất kể `is_public`.
- Không có hồ sơ (`PostAuthorProfile` chưa tồn tại) → coi như `is_public = false` mặc định ở tầng hiển thị. Sau khi tác giả tạo hồ sơ lần đầu ở `/auth/profile` (§6.1), `is_public` mặc định `true` cho MỌI tác giả đủ điều kiện (a) — không phân biệt Author/Contributor (§0 "Author vs Contributor") — tác giả tự tắt nếu không muốn công khai.

### 5.2 Tổng hợp thống kê hiệu suất (không bảng riêng)

Với 1 tác giả và 1 khoảng thời gian (`stats_default_days` hoặc tuỳ chọn 7/30/90 ngày):
- **Số bài xuất bản trong kỳ**: đếm `PostArticleTranslation` có `status=published` và `published_at` trong khoảng, thuộc bài của tác giả.
- **Tổng lượt xem (luỹ kế)**: tổng `view_count` của các bài trên — KHÔNG giới hạn theo kỳ (`view_count` cộng dồn từ lúc xuất bản); ghi rõ trên UI "Tổng lượt xem luỹ kế" để không gây hiểu nhầm với số liệu trong kỳ.
- **Trung bình view/bài**: tổng lượt xem luỹ kế ÷ số bài đã xuất bản luỹ kế.
- **Bài nổi bật**: `view_count` cao nhất trong danh sách bài của tác giả.
- **Biểu đồ xu hướng theo ngày (trong kỳ)**: `COUNT(*) FROM post_article_view_events WHERE viewed_at BETWEEN ... GROUP BY DATE(viewed_at)`, join `article_id` → lọc theo `article.created_by` (§2.3) — **lượt xem thật theo từng ngày**, không phải chỉ đếm bài xuất bản. Ràng buộc: kỳ chọn không được vượt quá retention của bảng này (`behavior_lookback_days`, mặc định 90 ngày) — đúng khớp với `stats_period_options` (§4.2), không cần xử lý gì thêm.

### 5.3 Quyền xem

**v1.3** — gộp 2 tầng UI trước đây (khối tóm tắt riêng ở `/auth/profile` + trang chi tiết riêng cho quản lý) thành **1 trang chi tiết duy nhất** (`dashboard/author-hub/{user}`, §6.2) dùng chung cho cả 2 nhu cầu, phân quyền tại tầng controller:

- `platform_content_head`/`platform_ops` (permission `author_hub.manage`): xem **danh sách TẤT CẢ tác giả** (`dashboard/author-hub`) + xem **chi tiết BẤT KỲ tác giả nào** (`dashboard/author-hub/{user}`).
- Tác giả bất kỳ (không cần permission `author_hub.manage`): xem được **đúng 1 trang chi tiết của chính mình** (`dashboard/author-hub/{user}` với `{user} = auth()->id()`) — điều kiện truy cập: `auth()->id() === $user->id || auth()->user()->can('author_hub.manage')`. KHÔNG xem được danh sách toàn bộ tác giả (route `dashboard/author-hub` vẫn yêu cầu `author_hub.manage`) và KHÔNG xem được trang chi tiết của tác giả khác.
- Độc giả công khai: KHÔNG bao giờ thấy số liệu hiệu suất (§0).

---

## 6. Quản trị

### 6.1 Tự sửa hồ sơ tác giả — mở rộng `/auth/profile`

Thêm card mới "Hồ sơ tác giả công khai" vào `Modules/Auth/resources/views/profile.blade.php`, **chỉ hiện nếu** `auth()->user()->isPlatform()` (§0 v1.2 — loại `marketing`/Lớp B) **và** user có ít nhất 1 bài `created_by = auth()->id()` (dùng `PostArticle::where('created_by', $user->id)->exists()`):
- Avatar (upload qua Media Library, collection `avatar`, preview + crop tối thiểu, tái dùng component upload ảnh đã có cho `cover` của bài viết nếu có sẵn).
- Bút danh (`pen_name`, tuỳ chọn — để trống dùng tên tài khoản thật).
- Tiểu sử (`bio`, textarea, giới hạn ~500 ký tự).
- Mạng xã hội (Facebook/X/LinkedIn/Website — 4 input tuỳ chọn, lưu vào `social_links` json).
- Công tắc "Hiển thị trang tác giả công khai" (`is_public`).
- 1 nút/link "Xem thống kê của tôi" → `dashboard/author-hub/{user}` (`{user} = auth()->id()`) — trang chi tiết ĐẦY ĐỦ (biểu đồ + bảng bài, §6.2), KHÔNG còn khối tóm tắt riêng dựng thêm ở `/auth/profile` (v1.3 — gộp vào §5.3/§6.2 để tận dụng đúng 1 UI cho cả tác giả tự xem và quản lý xem, không xây 2 view cho cùng 1 loại dữ liệu).
- Lần đầu user bấm lưu card này → tự tạo `PostAuthorProfile` (nếu chưa có) với `slug = PostAuthorProfile::slugFor($user)` — `is_public` dùng đúng default cột DB (`true`, §3.2), KHÔNG có logic set giá trị khác nhau theo `account_type`/`organization_id` của user (§0).

### 6.2 Dashboard hiệu suất phóng viên (quản lý + tự xem — v1.3)

**Route:** `dashboard/author-hub` (`Modules/Post/routes/web.php`, middleware `auth`) — **2 action, 2 mức quyền khác nhau** (không dùng `authorizeResource` chung nữa vì action `show` có điều kiện self-access, chỉ `index` mới thuần permission):

- **Danh sách** (`dashboard/author-hub`, action `index`): yêu cầu permission `author_hub.manage` (gate check đơn giản, không cần Policy class riêng — như Breaking News §6.3). Bảng tất cả tác giả **`isPlatform()`** (có ≥1 bài published) — cột: avatar+tên, badge Author/Contributor (theo role: 4 role biên tập = Author, `platform_content_creator` = Contributor, §0), số bài xuất bản (theo kỳ chọn), tổng lượt xem luỹ kế, trung bình view/bài, link "Xem chi tiết". Bộ lọc kỳ 7/30/90 ngày (`config('post.author_hub.stats_period_options')`). User `marketing` (Lớp B) dù có bài published KHÔNG xuất hiện ở dashboard này (§0 v1.2, §9).
- **Chi tiết 1 tác giả** (`dashboard/author-hub/{user}`, action `show`): điều kiện truy cập `auth()->id() === $user->id || auth()->user()->can('author_hub.manage')` (check inline trong controller, §5.3) — **dùng chung cho quản lý xem người khác VÀ tác giả tự xem chính mình**, không phân nhánh view. Tái dùng đúng bố cục `articles/clicks.blade.php` (§2.7) — 4 summary card (số bài/tổng view luỹ kế/TB view/bài nổi bật) + biểu đồ ECharts **lượt xem thật theo ngày trong kỳ** (nguồn `post_article_view_events`, §5.2) + bảng danh sách bài kèm `view_count` từng bài, sort theo view giảm dần. Nếu `$user` không `isPlatform()` (§0 v1.2) → 404, áp dụng cho cả 2 trường hợp truy cập.
- Sidebar (`@can(AUTHOR_HUB_MANAGE)`, §6.3) chỉ hiện link tới **danh sách** cho người có quyền quản lý — tác giả tự xem đi vào **chi tiết** qua link "Xem thống kê của tôi" ở `/auth/profile` (§6.1), không có mục sidebar riêng cho self-view.

### 6.3 Permission

`app/Enums/PermissionEnum.php` — thêm case mới, cùng khối Lớp B với `BREAKING_NEWS_MANAGE`:
```php
// ══ AUTHOR HUB (Trang tác giả công khai + thống kê hiệu suất phóng viên) ═══
// spec/Author_Contributor_Hub_Technical_Specification.md §6.3 — gán cho platform_ops +
// platform_content_head (AuthorHubPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B)
// — cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE/CORE_IDEA_EXTRACTOR_USE/
// BREAKING_NEWS_MANAGE.
case AUTHOR_HUB_MANAGE = 'author_hub.manage';
```
Seeder `Modules/Post/database/seeders/AuthorHubPermissionSeeder.php` — cùng cấu trúc `BreakingNewsPermissionSeeder.php` (gán `platform_ops` + `platform_content_head`, sync cho `super-admin`). Sidebar: thêm mục mới trong `resources/views/layouts/partials/sidebar.blade.php` bọc `@can(\App\Enums\PermissionEnum::AUTHOR_HUB_MANAGE->value)`, cùng mẫu mục "Tin nóng" hiện có (dòng 220-223).

---

## 7. Render công khai

### 7.1 Routes — thêm vào `Modules/Post/routes/web.php` (nhóm public, cùng chỗ `danh-muc/...`)

```php
Route::get('tac-gia', [AuthorHubPublicController::class, 'index'])->name('post.public.author-hub.index');
Route::get('tac-gia/{authorProfile:slug}', [AuthorHubPublicController::class, 'show'])->name('post.public.author-hub.show');
```

### 7.2 Trang danh sách tác giả (`/tac-gia`)

Lưới card (avatar + tên + số bài đã xuất bản), lấy từ `PostAuthorProfile::where('is_public', true)->whereHas('user', fn ($q) => $q->where('account_type', AccountType::Platform))->whereHas('user.articlesCreated', ...)` (§5.1, điều kiện `isPlatform()` — §0 v1.2) — không hiển thị số liệu hiệu suất (§0).

### 7.3 Trang chi tiết tác giả (`/tac-gia/{slug}`)

Cấu trúc giống trang category (`Modules/Post/resources/views/public/category/show.blade.php` nếu có, hoặc layout tương đương): header (avatar to, tên, bio, icon mạng xã hội) + danh sách bài đã xuất bản của tác giả (phân trang `config('post.author_hub.articles_per_page')`, tái dùng `<x-frontend.article-card>` nếu đã có component này cho category). Trả 404 nếu `is_public = false`, không tồn tại, **hoặc** `user` không còn `isPlatform()` (§0 v1.2 — ví dụ tài khoản đổi loại sau khi hồ sơ đã tồn tại).

### 7.4 Byline trở thành liên kết

`Modules/Post/resources/views/public/article.blade.php:51` đổi từ:
```blade
Bởi {{ $article->createdBy?->name ?? 'Ban biên tập' }} · {{ $translation->published_at?->format('d/m/Y') }}
```
thành:
```blade
@php $authorProfile = $article->createdBy?->authorProfile; @endphp
Bởi
@if($authorProfile?->is_public)
    <a href="{{ route('post.public.author-hub.show', $authorProfile) }}" class="hover:underline">{{ $authorProfile->displayName() }}</a>
@else
    {{ $article->createdBy?->name ?? 'Ban biên tập' }}
@endif
· {{ $translation->published_at?->format('d/m/Y') }}
```
Thêm quan hệ `authorProfile()` (`hasOne(PostAuthorProfile::class)`) vào `App\Models\User` — quan hệ ĐỌC duy nhất cần thêm vào model `users` dùng chung (không phải cột, không phá vỡ nguyên tắc §0 "không thêm cột vào `users`").

---

## 8. Kế hoạch triển khai

1. Migration `post_author_profiles` + model `PostAuthorProfile` (§3, §4.1) + quan hệ `User::authorProfile()` (§7.4).
2. Thêm khoá `author_hub` vào `Modules/Post/config/config.php` (§4.2).
3. Permission `author_hub.manage` + `AuthorHubPermissionSeeder` (§6.3).
4. Mở rộng `/auth/profile`: card hồ sơ tác giả + upload avatar + link "Xem thống kê của tôi" (§6.1).
5. `AuthorHubAdminController`: action `index` (permission `author_hub.manage`) + action `show` (self-access HOẶC `author_hub.manage`, §5.3/§6.2) + views tái dùng bố cục `clicks.blade.php` (§6.2) — DÙNG CHUNG cho quản lý xem người khác và tác giả tự xem mình, không tách 2 controller/2 view.
6. `AuthorHubPublicController` (`index`/`show`) + routes + views `/tac-gia`, `/tac-gia/{slug}` (§7.1-§7.3).
7. Sửa byline `public/article.blade.php:51` thành link có điều kiện (§7.4).
8. Test: `is_public=false` → 404 trang chi tiết + không xuất hiện ở `/tac-gia` + byline không link; **`marketing` (Lớp B) dù có bài published + `is_public=true` (nếu lỡ có hồ sơ) → vẫn không xuất hiện ở `/tac-gia`, dashboard, byline không link (§0 v1.2)**; thống kê group-by đúng số bài/tổng view/trung bình; **`GET dashboard/author-hub` (danh sách) → 403 nếu không có `author_hub.manage`; `GET dashboard/author-hub/{user}` (chi tiết) → 200 nếu `auth()->id() === $user->id` (KHÔNG cần `author_hub.manage`) HOẶC có `author_hub.manage`, 403 với user khác không có quyền (§5.3/§6.2 v1.3)**; tự tạo `PostAuthorProfile` đúng lần đầu lưu card ở `/auth/profile` (chỉ hiện cho `isPlatform()`).

---

## 9. Ngoài phạm vi (v1)

- **Guest/freelance author không có tài khoản User** (§0) — v1 bắt buộc mọi tác giả là 1 `User` thật đã tồn tại.
- **Biểu đồ lượt xem theo ngày vượt quá 90 ngày** — bị giới hạn bởi retention của `post_article_view_events` (`behavior_lookback_days`, §2.3/§5.2); xem xu hướng dài hạn hơn 90 ngày cần thêm bảng snapshot riêng, không làm ở v1.
- **Bảng xếp hạng (leaderboard) công khai** giữa các tác giả — số liệu hiệu suất chỉ nội bộ (§0).
- **Huy hiệu/gamification** (badge "Cây bút tháng", điểm thưởng...) cho tác giả.
- **Theo dõi (follow) tác giả** / thông báo khi tác giả có bài mới.
- **Tính nhuận bút/thù lao** dựa trên số liệu hiệu suất — đây là dữ liệu tham khảo đánh giá, không phải hệ thống tính lương.
- **RSS/feed riêng theo tác giả**.
- **`marketing` (Lớp B, tài khoản do 1 Organization quản lý)** — không thuộc phạm vi "tác giả" của Author Hub dù vẫn tạo/gửi duyệt bài viết được theo `PostArticlePolicy` hiện hành (§0 v1.2). Không có hồ sơ công khai, không dashboard hiệu suất, không byline dạng link. Nếu tương lai cần mở rộng, đây là điểm mở rộng rõ ràng (bỏ điều kiện `isPlatform()` ở §5.1/§6.1/§6.2/§7.2/§7.3) — không làm ở v1.

---

## 10. Rủi ro & Đánh giá thực tiễn

| Rủi ro | Mức độ | Đánh giá |
|---|---|---|
| Phụ thuộc `post_article_view_events` — bảng sinh ra cho mục đích khác (Related Posts Engine), retention do config của module đó quyết định | Trung bình | `behavior_lookback_days` (§2.3) là config của Related Posts, không phải của Author Hub — nếu sau này team đổi giá trị này (vd rút xuống 30 ngày) vì lý do của Related Posts, dashboard hiệu suất phóng viên bị ảnh hưởng LÂY THEO mà không ai chủ đích đổi. Chấp nhận được ở v1 (đọc-only, không sửa job/config của Related Posts) nhưng nên ghi chú rõ trong code (`stats_period_options`, §4.2) rằng giá trị lớn nhất phải ≤ `behavior_lookback_days` hiện hành, để không lặng lẽ vỡ nếu 1 trong 2 phía đổi số mà không báo phía kia |
| `marketing` (Lớp B, tài khoản do Organization quản lý) viết bài nhưng không xuất hiện ở đâu trong Author Hub | Thấp | ĐÚNG CHỦ ĐÍCH (§0 v1.2, §9) — tài khoản nhóm này do 1 Organization tạo/quản lý, không phải hệ thống, nên bị loại khỏi toàn bộ Author Hub (không hồ sơ, không `/tac-gia`, không dashboard, không byline link). Họ vẫn viết/gửi duyệt bài bình thường theo `PostArticlePolicy` — chỉ không được Author Hub "vinh danh"/đo hiệu suất. Nếu tòa soạn sau này muốn đánh giá cả nguồn PR/tài trợ từ Organization, đó là 1 quyết định mở rộng phạm vi có chủ đích (§9), không phải sửa lỗi |
| Đổi role/`account_type` của 1 `User` sau khi họ đã có `PostAuthorProfile` (vd từ `platform_content_creator` sang bị thu hồi role, hoặc — hiếm — đổi `account_type` khỏi `platform`) | Thấp | Hồ sơ `PostAuthorProfile` không tự xoá theo (chỉ cascadeDelete khi User bị xoá hẳn, §10 dòng khác) — nhưng §7.3 kiểm tra `isPlatform()` tại thời điểm render nên trang chi tiết tự trả 404 nếu không còn `isPlatform()`; `/tac-gia` (danh sách) cũng tự loại theo cùng điều kiện (§7.2) — không cần job dọn dẹp riêng, hành vi tự nhất quán theo trạng thái User hiện tại mỗi lần truy vấn |
| Tác giả rời tổ chức / tài khoản bị vô hiệu hoá vẫn còn hồ sơ public | Thấp | `PostAuthorProfile.user_id` cascadeOnDelete theo `users` — nếu tài khoản bị XOÁ hẳn, hồ sơ tự mất theo. Nếu chỉ bị `is_active=false` (khoá, không xoá), hồ sơ vẫn hiển thị bình thường — đây là hành vi chấp nhận được (khoá tài khoản không đồng nghĩa xoá dấu vết bài đã xuất bản, giống cách bài viết cũ của người đã nghỉ vẫn hiển thị tác giả) |
| Trùng slug khi 2 tác giả cùng tên/bút danh | Thấp | `PostAuthorProfile::slugFor()` (§4.1) luôn nối thêm `-{user_id}` — không thể trùng vì `user_id` unique theo định nghĩa |
| Không cache trang `/tac-gia/{slug}` khi 1 tác giả có rất nhiều bài | Thấp | Danh sách bài đã phân trang (`articles_per_page`), cùng mức tải với trang category hiện tại (cũng không cache) — không phải rủi ro riêng của module này |
| Lộ thông tin tiểu sử/mạng xã hội cá nhân ngoài ý muốn | Thấp | `is_public` mặc định `true` ĐỒNG NHẤT cho MỌI tác giả `isPlatform()` (Author + Contributor, §0 v1.2) — nhưng CHỈ áp dụng sau khi tác giả chủ động lưu hồ sơ lần đầu ở `/auth/profile` (§5.1); không có cơ chế tự động điền bio/social_links thay người dùng, nên không có gì để "lộ" ngoài ý muốn trước khi họ tự nhập và lưu. Mức rủi ro giảm về Thấp so với bản nháp trước (v1.1) — vì đối tượng mặc định public nay CHỈ còn nhân sự/cộng tác viên do hệ thống cấp tài khoản (`platform_content_creator`/`platform_content_head`/...), không còn bao gồm cộng tác viên bên ngoài thuộc Organization (đã loại khỏi phạm vi ở v1.2, §9) — vẫn nên hiển thị rõ trạng thái công tắc "Hiển thị trang tác giả công khai" ngay từ lần đầu để tác giả biết và tắt ngay nếu muốn |
