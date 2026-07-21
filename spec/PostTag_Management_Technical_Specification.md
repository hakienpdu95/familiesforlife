# Quản lý Tag (Post) — Trang quản trị riêng cho `PostTag`

> **Yêu cầu gốc:** "PostTag hiện chỉ nhập qua textbox (chuỗi phân tách dấu phẩy) trong form bài
> viết, chưa có trang quản trị tag riêng (đổi tên, gộp, xem bài theo tag) — mức độ ưu tiên thấp
> hơn, có thể chấp nhận được nếu số lượng tag chưa nhiều."
>
> **Xác nhận qua code + dữ liệu thật (2026-07-21):** Đúng như mô tả — `PostTag` hiện chỉ được tạo
> ngầm qua `SyncsArticleRelations::syncTags()` khi lưu bài, không có Controller/route/view quản trị
> nào. **Dữ liệu thật xác nhận mức độ ưu tiên thấp là hợp lý: hiện có đúng 0 tag trong DB** — tính
> năng gần như chưa từng được dùng, không có gì gấp.

## 1. Hiện trạng — verify code thật

### 1.1 Model `PostTag`

`Modules/Post/app/Models/PostTag.php` (27 dòng, toàn văn):

```php
class PostTag extends Model
{
    use BelongsToOrganization;
    protected $table = 'post_tags';
    protected $fillable = ['organization_id', 'name', 'slug'];
    public function articles(): BelongsToMany { ... }
}
```

- **Hiện trạng khi khảo sát (2026-07-21): tenant-scoped** (`BelongsToOrganization`) — khác
  `PostArticle`/`PostCategory` (platform-wide). Tại thời điểm khảo sát, migration
  `2026_07_13_000002_drop_organization_id_from_post_child_tables.php` xoá `organization_id` khỏi 4
  bảng con khác của Post nhưng loại trừ tường minh `post_tags`, nên lúc đó tài liệu này từng hiểu
  đây là thiết kế có chủ đích.
- **Sửa lại theo xác nhận trực tiếp từ Product Owner (2026-07-21, sau khi bản nháp đầu được viết):
  đây là hiểu sai.** `PostTag` thuộc **nền tảng vận hành** (platform), không chịu sự quản lý theo
  từng tổ chức — vì vậy **không nên có `organization_id`**, phải chuyển sang platform-wide giống
  `PostArticle`/`PostCategory`. Đây là thay đổi kiến trúc thật, nằm trong phạm vi tài liệu này (xem
  kế hoạch cụ thể ở §3.5) — không phải việc "để sau" như bản nháp trước đó từng ghi ở §7.
- Không `SoftDeletes`, không `LogsActivity` riêng — comment gốc trong model: *"Nhãn phẳng, không
  soft-delete/activity-log riêng — xoá cứng khi không còn bài dùng."*
- Không `Searchable` (Scout). Không override `getRouteKeyName()` — route dùng `id`, không phải
  `slug`. **Giữ nguyên khi implement trang quản trị — không tự ý đổi route model binding sang
  `slug`:** vì §3.6 chốt `slug` không đổi theo `name` khi rename, dùng `slug` làm route key sẽ vẫn
  hoạt động về mặt kỹ thuật nhưng URL sẽ hiển thị 1 chuỗi không còn khớp với tên hiển thị hiện tại
  của tag sau khi đổi tên (vd URL `/tags/suc-khoe/edit` dù tên đã đổi thành "Sức khỏe trẻ em"). Lưu
  ý: `PostCategory` thực ra dùng `uuid` làm route key (`getRouteKeyName()` trả `'uuid'`, có cột
  `uuid` riêng) — không phải `id` như đoạn trên có thể gây hiểu nhầm nếu đọc lướt. `PostTag` **không
  có cột `uuid`**, nên **không** cần/nên thêm cột `uuid` + đổi route binding chỉ để bắt chước
  Category — dùng thẳng `id` (khoá chính sẵn có) là đủ, thêm `uuid` cho Tag là việc ngoài phạm vi
  không có lý do nghiệp vụ nào yêu cầu (không có nhu cầu che số thứ tự tag như Category).

### 1.2 Migration `post_tags` + pivot `post_article_tag`

`Modules/Post/database/migrations/2026_07_07_000004_create_post_tags_table.php` — **hiện trạng khi
khảo sát**:

- `organization_id` **NOT NULL** (`constrained()->restrictOnDelete()`).
- Unique **theo tổ chức**: `unique(['organization_id', 'slug'], 'uq_post_tag_org_slug')` — **không
  phải unique global**.
- Pivot `post_article_tag`: chỉ `article_id`, `tag_id`, PK composite `(article_id, tag_id)`,
  **không có timestamp**.

> **Sẽ đổi khi implement (§3.5):** cột `organization_id` + FK + unique theo tổ chức ở trên sẽ bị bỏ,
> thay bằng unique global theo `slug` — vì `PostTag` chuyển sang platform-wide. Đoạn 1.2 này giữ
> nguyên như một ghi chép "hiện trạng lúc khảo sát", không phải thiết kế đích.
- *(Lưu ý phụ, không ảnh hưởng thật)*: có 1 file "generated" snapshot định nghĩa lại pivot này với
  cấu trúc khác (thêm `id()`, bỏ composite PK) nhưng guard `hasTable()` nên không bao giờ chạy trên
  DB đã có bảng gốc — chỉ là rác snapshot.

### 1.3 Luồng nhập tag hiện tại (textbox)

`Modules/Post/app/Features/ArticleAuthoring/Actions/Concerns/SyncsArticleRelations.php:29-46`
(toàn văn):

```php
private function syncTags(PostArticle $article, ArticleData $data): void
{
    $names = collect(explode(',', (string) $data->tags))
        ->map(fn ($name) => trim($name))->filter()->unique();

    $tagIds = $names->map(function (string $name) {
        $tag = PostTag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        return $tag->id;
    });

    $article->tags()->sync($tagIds);
}
```

Hành vi cần biết trước khi thiết kế trang quản trị:

- Tự tạo tag mới nếu chưa tồn tại (`firstOrCreate` theo `slug`, **hiện tại** tự động scope theo tổ
  chức qua global scope của `BelongsToOrganization` — sau khi chuyển platform-wide (§3.5), dòng code
  này không cần sửa gì, chỉ khác là không còn tự gán `organization_id` vì cột đã bị bỏ).
- Tự sinh `slug` qua `Str::slug()` — 2 tên khác hoa/thường/dấu nhưng cùng slug (vd "Sức Khỏe" vs
  "sức khỏe") **dùng chung 1 tag**, giữ nguyên `name` của lần tạo đầu tiên (lần sau trùng slug
  KHÔNG cập nhật lại `name`).
- **Không có** cơ chế gộp 2 tag gần nghĩa nhưng khác chữ (vd "trẻ sơ sinh" / "trẻ nhỏ") — đúng như
  mô tả trong yêu cầu gốc, đây chính là khoảng trống "gộp tag" cần xây mới.

### 1.4 Hiển thị hiện tại — chỉ có badge tĩnh, không có trang lọc theo tag

`Modules/Post/resources/views/public/article.blade.php:79-82`:

```blade
@if($article->tags->isNotEmpty())
<div class="flex flex-wrap gap-1.5 mt-4">
    @foreach($article->tags as $tag)
    <span class="badge badge-sm badge-outline">#{{ $tag->name }}</span>
    @endforeach
</div>
@endif
```

Badge **tĩnh** (`<span>`, không phải `<a>`). `Modules/Post/routes/web.php` **không có** route nào
kiểu `/the/{slug}` hay tương đương để độc giả bấm vào tag xem danh sách bài — khớp đúng mô tả "chưa
có trang xem bài theo tag" trong yêu cầu gốc (ở đây hiểu là phía admin trước, xem §3.3; phía công
khai để ở §7 vì là tính năng UX riêng, không phải quản trị).

### 1.5 Scout/Meilisearch — đã index tên tag để tìm kiếm, chưa lọc chính xác được

`Modules/Post/app/Models/PostArticleTranslation.php:249` —
`'tag_names' => $article?->tags->pluck('name')->all() ?? []`. `config/scout.php` —
`tag_names` **có** trong `searchableAttributes` nhưng **KHÔNG có** trong `filterableAttributes`
(chỉ `locale, status, category_slugs, province_code, format, is_featured`). Nghĩa là: gõ tên tag
vào ô tìm kiếm ra đúng bài (full-text), nhưng chưa lọc/facet chính xác theo **1 tag cụ thể** qua
Meilisearch được — muốn làm cần thêm `tag_slugs` (mảng slug, giống `category_slugs` đã làm) vào cả
`toSearchableArray()` lẫn `filterableAttributes`. Không bắt buộc cho phạm vi tài liệu này (xem §7).

## 2. Dữ liệu thật hiện có

Query trực tiếp DB `vigiadinh` (2026-07-21, buổi sáng): **0 tag** trong bảng `post_tags`. Xác nhận
đúng — hơn cả mức "chưa nhiều" mà yêu cầu gốc mô tả, tính năng gần như **chưa từng được dùng thật**.
Không có rủi ro migrate/dọn dữ liệu cũ khi xây trang quản trị này.

> **Cập nhật khi implement (2026-07-21, buổi chiều — cùng ngày):** lúc bắt đầu code, DB thật đã có
> **49 tag** (tất cả `organization_id=1`, không trùng slug giữa các tổ chức) — phát sinh từ hoạt
> động viết bài thật qua textbox (§1.3) trong khoảng thời gian giữa lúc viết spec và lúc implement.
> **Hệ quả:** kế hoạch ban đầu ở §3.5/§8 mục 7 ("sửa migration tạo bảng gốc rồi `migration:generate
> --fresh`") không còn an toàn — sẽ xoá sạch toàn bộ DB dev (users, organizations, media, bài viết),
> không chỉ riêng bảng tag. Đã đổi sang cách khác khi thực thi thật — xem §11 (Nhật ký triển khai).

## 2.1 Bài học — dữ liệu "0 hiện tại" có thể đổi giữa lúc viết spec và lúc code

Khoảng cách vài giờ giữa khảo sát (sáng) và implement (chiều) đủ để một giả định "0 dữ liệu, an
toàn tuyệt đối" trở nên sai. Nguyên tắc rút ra: **trước khi thực thi bất kỳ hành động phá huỷ nào
dựa trên "dữ liệu hiện tại là rỗng/an toàn"**, luôn verify lại số liệu **ngay tại thời điểm thực
thi**, không tin vào con số đã ghi trong spec dù spec đó viết cùng ngày. Áp dụng: nếu tài liệu này
được dùng làm tham chiếu cho một đợt implement khác (sau này), verify lại số liệu §2 trước, đừng
tin số cũ.

## 3. Đề xuất kiến trúc — mirror `CategoryManagement`, bớt phần cây/thứ tự

`Modules/Post` **đã có sẵn 1 khuôn mẫu CRUD taxonomy admin hoàn chỉnh** cùng module — không cần
nghĩ pattern mới, chỉ cần copy đúng cấu trúc và bỏ bớt phần không áp dụng cho Tag (Tag phẳng,
không phân cấp cha/con như Category nên không cần cây/kéo-thả thứ tự).

### 3.1 Đối chiếu 1-1 với `CategoryManagement` (khuôn mẫu chính)

| Thành phần | Category (đã có, dùng làm mẫu) | Tag (đề xuất mới) |
|---|---|---|
| Thư mục Feature | `Modules/Post/app/Features/CategoryManagement/` | `Modules/Post/app/Features/TagManagement/` |
| Controller | `Http/CategoryAdminController.php` — `authorizeResource`, `index/create/store/edit/update/destroy` + `reorder` riêng | `Http/TagAdminController.php` — cùng 6 method chuẩn, **bỏ `reorder`** (tag không phân cấp/thứ tự) |
| Data DTO | `Data/CategoryData.php` | `Data/TagData.php` — chỉ `name` (bỏ `parent_id`/`icon`/`color_hex`/`sort_order` — không áp dụng cho tag phẳng) |
| Actions | `CreateCategoryAction`/`UpdateCategoryAction`/`DeleteCategoryAction` | `CreateTagAction`/`UpdateTagAction`/`DeleteTagAction` + **`MergeTagsAction` mới** (§3.2, không có tiền lệ tương ứng bên Category) |
| Query | `GetCategoryTreeHandler` (cây), `ListCategoriesForAdminHandler` (tìm kiếm phẳng) | Chỉ cần `ListTagsForAdminHandler` (danh sách phẳng + đếm số bài mỗi tag, xem §3.3) — không cần bản "cây" |
| Policy | `PostCategoryPolicy` — `viewAny`/`view` qua `post_article.view`, `create/update/delete` qua `post_category.manage` | `PostTagPolicy` — cùng pattern, quyền mới `post_tag.manage` (§4) |
| Routes | `Route::resource('categories', ...)->except(['show'])` + `POST categories/reorder` | `Route::resource('tags', TagAdminController::class)->except(['show'])` + `POST tags/{tag}/merge` (§3.2) |
| Views | `admin/categories/{index,create,edit,_category-tree-row}.blade.php` | `admin/tags/{index,create,edit}.blade.php` — không cần `_tag-tree-row` (phẳng, bảng thường đủ) |
| Sidebar | `resources/views/layouts/partials/sidebar.blade.php:154-157`, sub-link trong menu "Bài viết", gate bằng `@can(PermissionEnum::POST_CATEGORY_MANAGE->value)` | Thêm 1 sub-link ngay cạnh, cùng `<details>` "Bài viết", gate bằng `PermissionEnum::POST_TAG_MANAGE->value` mới |

### 3.2 Tính năng "Gộp tag" (Merge) — không có tiền lệ trong repo, phải thiết kế mới

Đã khảo sát 2 hệ tag khác trong repo (`Modules\Lead\Models\LeadTagDefinition` — có UI quản lý CRUD
đầy đủ qua `LeadTagController.php`; `Modules\Customer\Models\CustomerTag` — đơn giản hơn) —
**không hệ nào có tính năng gộp 2 tag làm 1**. Đây là phần phải thiết kế từ đầu, không copy được.

**Đề xuất luồng:** **chốt UI — modal ở ngay trang danh sách** (`admin/tags/index.blade.php`), không
làm trang chi tiết riêng. Lý do chốt luôn thay vì để 2 lựa chọn: quy mô Tag nhỏ (0 dữ liệu thật, §2)
không cần thêm 1 trang "detail" chỉ để chứa 1 form gộp — mỗi dòng trong bảng danh sách có nút "Gộp"
mở modal (Alpine.js, đúng pattern UI đã dùng trong repo — xem `resources/js` các module khác), modal
có dropdown chọn tag đích + nút xác nhận → gọi `POST tags/{tag}/merge`. Không cần route/view
`tags/{tag}` (`show`) riêng cho việc này.

Sau khi chọn tag đích, xác nhận → `MergeTagsAction`:

1. Lấy toàn bộ `article_id` đang gắn tag nguồn (`$sourceTag->articles()->pluck('post_articles.id')`).
2. Với mỗi `article_id`: `attach` tag đích nếu bài đó **chưa có** tag đích (tránh vi phạm PK
   composite `(article_id, tag_id)` của pivot khi 1 bài vô tình đã có cả 2 tag từ trước).
3. Xoá cứng tag nguồn (`$sourceTag->delete()`) — **đã xác nhận** `post_article_tag.tag_id` có
   `->cascadeOnDelete()` (`2026_07_07_000004_create_post_tags_table.php` dòng định nghĩa pivot) —
   xoá tag nguồn tự động dọn sạch pivot cũ ở tầng DB, không cần code thêm bước xoá pivot thủ công.
4. Bọc toàn bộ trong `DB::transaction()` — gộp tag là thao tác không thể hoàn tác dễ dàng
   (`sync()`/`attach()` không giữ lịch sử), cần atomic.

> **Đã sửa lại (2026-07-21):** đoạn "chỉ cho gộp trong cùng 1 tổ chức" ở bản nháp trước không còn áp
> dụng — `PostTag` chuyển sang platform-wide (§3.5), không còn khái niệm "cùng tổ chức" để ràng
> buộc. Danh sách "tag đích" khi gộp là toàn bộ tag của nền tảng. Các ràng buộc/edge case còn lại
> của `MergeTagsAction` (không gộp vào chính nó, số lượng bài lớn, có ghi log hay không) xem §3.9.

### 3.3 Tính năng "Xem bài theo tag" — chỉ cần phía ADMIN, không cần route công khai mới

Hiểu đúng phạm vi yêu cầu gốc: đây là tính năng **quản trị** (admin xem tag đang gắn ở bài nào để
quyết định gộp/xoá/đổi tên), khác với "trang công khai lọc bài theo tag cho độc giả" (tính năng UX
riêng, để ở §7 — ngoài phạm vi tài liệu này, xem thêm lý do ở đó).

**Đề xuất cụ thể:**
- Trang danh sách tag (`admin/tags/index.blade.php`) — mỗi dòng hiện `$tag->articles()->count()`
  (số bài đang dùng tag này).
- Bấm vào số đếm → mở trang/modal liệt kê tên + link tới từng bài viết đang dùng tag đó
  (`$tag->articles()->with('mainTranslation')->get()`, tương tự cách `DeleteCategoryAction` đã
  check "còn bài viết gán trực tiếp" trước khi xoá).

### 3.4 Ràng buộc xoá tag — chọn theo tiền lệ nào?

Đã khảo sát 2 tiền lệ khác nhau, cần chọn 1 (xem quyết định §5):

| Tiền lệ | Cách làm | Ưu/nhược |
|---|---|---|
| `DeleteCategoryAction` (Post, cùng module) | **Chặn xoá** nếu còn category con hoặc còn bài viết gán trực tiếp — throw `RuntimeException`, controller catch → `back()->withErrors()` | An toàn hơn — không mất liên kết ngầm; nhưng nếu tag có ở tất cả bài thì không xoá được, buộc phải gỡ tag khỏi từng bài trước hoặc **gộp trước rồi mới xoá được** (tự nhiên khuyến khích dùng tính năng gộp) |
| `LeadTagController`/`DeleteTagAction` (Lead, CRM) | **Xoá thẳng**, cascade xoá luôn liên kết (`lead_tag_map`), không cảnh báo | Đơn giản hơn, nhưng xoá nhầm 1 tag đang dùng ở nhiều bài sẽ mất liên kết ngay không cảnh báo |

**Khuyến nghị (chưa tự quyết, xem §5):** theo tiền lệ `DeleteCategoryAction` (chặn xoá nếu còn bài
dùng) — nhất quán trong cùng module Post, và tự nhiên dẫn người dùng tới tính năng "gộp" thay vì
xoá mất liên kết. Nhưng đây là quyết định UX/nghiệp vụ, để Product Owner chốt.

### 3.5 Chuyển `PostTag` sang platform-wide — bỏ `organization_id` (bổ sung 2026-07-21)

Product Owner xác nhận trực tiếp: `PostTag` thuộc **nền tảng vận hành**, không chịu sự quản lý theo
tổ chức — khác với những gì §1.1/§1.2 ghi nhận lúc khảo sát ban đầu (migration cũ loại trừ
`post_tags` khỏi đợt bỏ `organization_id` chung của Post). Đây là phần việc **bắt buộc phải làm**
trong phạm vi tài liệu này trước khi xây trang quản trị Tag, không phải "để sau" như bản nháp §7
từng ghi (đã sửa lại — xem §7).

**Việc cần làm:**

1. **Model** `Modules/Post/app/Models/PostTag.php` — bỏ `use BelongsToOrganization;`, bỏ
   `organization_id` khỏi `$fillable`.
2. **Migration** — dự án đang ở giai đoạn dev, dùng quy trình `migration:generate --fresh` thường
   xuyên, chưa có dữ liệu Tag thật cần giữ (§2: 0 tag) — nên **cách đơn giản nhất, đúng convention
   dự án**: sửa thẳng `2026_07_07_000004_create_post_tags_table.php`, bỏ dòng
   `$table->foreignId('organization_id')->constrained()->restrictOnDelete();` và đổi
   `unique(['organization_id', 'slug'], 'uq_post_tag_org_slug')` thành `unique('slug', 'uq_post_tag_slug')`,
   rồi chạy lại `migration:generate --fresh`. **Không cần** viết migration ALTER kiểu
   `2026_07_13_000002_drop_organization_id_from_post_child_tables.php` (pattern đó dành cho môi
   trường đã có dữ liệu thật cần bảo toàn — không áp dụng ở đây).
3. **Pivot `post_article_tag`** — không đổi gì (vốn đã không có `organization_id`).
4. **`SyncsArticleRelations::syncTags()`** — không cần sửa code (§1.3), `firstOrCreate` theo `slug`
   vẫn hoạt động đúng, chỉ khác là không còn tự gán `organization_id` (cột đã bị bỏ).
5. **`TagAdminController`/Policy mới** — không cần logic lọc theo tổ chức ở đâu cả (giống hệt
   `PostCategoryPolicy`/`CategoryAdminController` hiện tại, vốn đã platform-wide).

### 3.6 Hành vi khi đổi tên (rename) — KHÔNG regenerate slug, theo đúng tiền lệ Category

Đã verify `UpdateCategoryAction.php` (Category, cùng module): **không** regenerate `slug` khi đổi
`name` — slug cố định từ lúc tạo, không có logic xử lý trùng slug ở bước update (chỉ
`CreateCategoryAction::uniqueSlug()` xử lý trùng, và chỉ áp dụng lúc tạo mới).

**Chốt cho Tag: theo đúng tiền lệ này — `UpdateTagAction` chỉ sửa `name`, KHÔNG đụng tới `slug`.**
Lý do:

- Nhất quán với Category trong cùng module — 1 hành vi cho cả 2 concept tương tự.
- Tránh hẳn rủi ro trùng slug khi update (không cần logic xử lý collision ở bước sửa).
- AC#3 ("đổi tên → N bài viết hiển thị tên mới ngay") vẫn thoả mãn đầy đủ dù không đổi slug — vì
  giao diện hiển thị tag dùng **`name`** (`article.blade.php:79-82`: `#{{ $tag->name }}`), không
  dùng `slug`. `slug` chỉ có vai trò định danh nội bộ (unique key), không hiển thị ra UI.
- **Hệ quả chấp nhận được (không phải bug):** `name` và `slug` có thể lệch nhau lâu dài sau nhiều
  lần đổi tên (vd `name` = "Sức khỏe trẻ em", `slug` vẫn là `suc-khoe` từ lần tạo đầu) — Category đã
  sống với đặc điểm này từ trước, không gây sự cố gì, nên chấp nhận tương tự cho Tag thay vì tự thêm
  logic mới mà Category không có.

### 3.7 Validate khi tạo/sửa tag — theo đúng tiền lệ `CategoryData`

Đã verify `CategoryData.php`: chỉ có `#[Required, Max(150)]` trên `name`, không có rule
trim/chuẩn hoá khoảng trắng/hoa-thường riêng, không kiểm tra `name` trùng lặp (chỉ `slug` được kiểm
tra trùng, ở bước tạo).

**Chốt cho Tag — theo đúng tiền lệ, KHÔNG thêm rule ngoài những gì Category đã có:**

- `TagData::$name` — `#[Required, Max(120)]` (khớp cột `post_tags.name` là `string(120)`, xem §1.2 —
  khác `150` của category vì cột category dài hơn).
- **Không** thêm rule unique riêng cho `name` — hành vi trùng lặp được xử lý gián tiếp qua
  `uniqueSlug()` khi tạo (xem dưới), đúng cách Category đang làm.
- **Không** thêm trim/chuẩn hoá thủ công trong DTO — dự án đã có `TrimStrings` middleware global
  (mặc định Laravel) xử lý khoảng trắng đầu/cuối cho mọi request, không cần lặp lại ở tầng DTO,
  đúng như Category hiện tại không làm việc này.
- **Tạo tag mới** — `CreateTagAction` **copy nguyên pattern** từ `CreateCategoryAction::uniqueSlug()`
  (không phải viết lại "tương tự theo trí nhớ" — dễ lệch nhẹ ở cách xử lý ký tự đặc biệt/độ dài).
  Cụ thể copy nguyên:
  ```php
  private function uniqueSlug(string $name): string
  {
      $base = Str::slug($name);
      $slug = $base;
      $i    = 2;
      while (PostTag::where('slug', $slug)->exists()) {
          $slug = "{$base}-{$i}";
          $i++;
      }
      return $slug;
  }
  ```
  Chỉ đổi `PostCategory::where(...)` → `PostTag::where(...)` (và bỏ điều kiện `organization_id` nếu
  bản gốc Category có — Category hiện đã platform-wide nên `uniqueSlug()` gốc vốn đã check global,
  không cần sửa gì thêm ngoài đổi tên Model). Không cần validate riêng độ dài `slug` — cột
  `post_tags.slug` là `string(140)` (§1.2), `Str::slug()` + hậu tố `-2`/`-3` không có khả năng vượt
  quá độ dài này với `name` đã giới hạn `Max(120)` (§3.7).

### 3.8 Đếm số bài viết trên trang danh sách — dùng `withCount`, không N+1

Đã verify `ListCategoriesForAdminHandler.php`: dùng `->withCount('articles')` trong 1 query, không
lặp gọi `->count()` riêng từng dòng.

**Chốt cho Tag: `ListTagsForAdminHandler` bắt buộc dùng `PostTag::query()->withCount('articles')`**
(tương tự Category), KHÔNG được gọi `$tag->articles()->count()` trong vòng lặp Blade/Controller —
tránh N+1 khi số tag tăng lên. AC#1 cập nhật để nêu rõ yêu cầu này (xem §6).

### 3.9 Edge case của `MergeTagsAction`

Bổ sung các ràng buộc cơ bản chưa được nêu rõ ở §3.2:

- **Chặn gộp tag vào chính nó** — `MergeTagsAction` phải validate `$sourceTag->id !== $targetTag->id`
  trước khi làm gì khác, throw lỗi validate rõ ràng ("Không thể gộp tag vào chính nó") nếu vi phạm.
- **Giới hạn số bài viết khi gộp:** dữ liệu thật hiện có 0 tag (§2), chưa có tag nào gắn số lượng
  lớn bài viết để lo ngại transaction nặng. **Khuyến nghị cụ thể cho giai đoạn này:** chấp nhận
  chạy đồng bộ trong 1 `DB::transaction()` như §3.2 đã mô tả (`pluck()` toàn bộ `article_id` rồi
  `attach()` từng cái) — không cần `chunkById()`/queue ngay. **Ngưỡng để xem lại:** nếu về sau 1 tag
  thực tế gắn tới **trên khoảng 500–1.000 bài viết** (transaction đồng bộ bắt đầu có rủi ro timeout
  HTTP request hoặc khoá bảng lâu), lúc đó mới cần đổi sang `chunkById()` khi lặp qua `article_id`
  hoặc đẩy `MergeTagsAction` ra queue job chạy nền — không làm trước vì chưa có bằng chứng nhu cầu
  (nhất quán tinh thần "không làm trước khi có tín hiệu" đã áp dụng ở §5.3/§7).
- **Ghi log khi gộp:** `PostTag` chủ đích không dùng `LogsActivity` (§1.1 — "nhãn phẳng, không
  soft-delete/activity-log riêng"), nên **không** thêm Spatie Activity Log cho riêng thao tác gộp
  (sẽ lệch thiết kế gốc của model). Thay vào đó, `MergeTagsAction` ghi 1 dòng
  `Log::info('post_tag.merge', [...])` (kênh log chuẩn Laravel, không cần bảng mới) — đủ để tra cứu
  khi cần điều tra, không phá vỡ nguyên tắc "nhãn phẳng, không audit riêng" của model.

### 3.10 Tương tác với textbox hiện tại sau khi đổi tên/gộp — giới hạn đã biết, không phải bug

Vì `SyncsArticleRelations::syncTags()` (§1.3) dùng `firstOrCreate` theo **slug**, và §3.6 chốt
**không regenerate slug khi đổi tên**, cần ghi nhận rõ 2 hệ quả sau để người dùng/người triển khai
hiểu trước, không bất ngờ khi gặp:

- **Sau khi đổi tên tag A → "Tên mới":** nếu biên tập viên gõ đúng **tên cũ** vào textbox bài viết,
  hệ thống tính `Str::slug("tên cũ")` — nếu trùng đúng slug cũ (rất có thể, vì slug không đổi theo
  §3.6) thì `firstOrCreate` **tìm thấy lại đúng tag đã đổi tên** (không tạo tag mới) — bài viết sẽ
  hiển thị tên MỚI, không phải tên cũ vừa gõ. Đây là hành vi **đúng mong đợi**, không phải vấn đề.
- **Sau khi gộp tag A vào B (A bị xoá cứng):** nếu biên tập viên gõ đúng tên cũ của A vào textbox,
  `firstOrCreate` **không tìm thấy** slug của A nữa (đã xoá) → **tạo lại 1 tag A hoàn toàn mới**,
  tách biệt khỏi tag B đã gộp trước đó. Đây **là giới hạn cần biết** — gộp không "khoá" lại tên cũ
  để chuyển hướng vĩnh viễn sang tag đích, giống các CMS lớn có "slug redirect" chuyên dụng.
  **Không sửa trong phạm vi tài liệu này** (cần thêm bảng `post_tag_aliases` mới, vượt quy mô "trang
  CRUD + gộp thủ công" — xem §7) — chỉ ghi nhận rõ để người dùng quản trị biết: sau khi gộp, cần
  tránh gõ lại tên tag cũ trong textbox nếu không muốn nó "sống lại" như 1 tag riêng.

## 4. Permission — phát hiện 1 gap có thật, không phải giả định

Đã đọc `Modules/Post/database/seeders/PostPermissionSeeder.php` (92 dòng, toàn văn) — permission
`post_category.manage` **có tồn tại** nhưng seeder này chỉ **thu hồi** nó khỏi 8 role "Lớp B" cũ
(`ceo, sales, ops, marketing, hr, ai_operator, viewer, system_admin` — theo
`spec/Platform_RBAC_Phase2_Specification.md §3.2 v3.0`, Post chuyển hẳn sang role Platform), và
**KHÔNG cấp lại** cho bất kỳ role Platform nào (`platform_content_creator` chỉ có
`post_article.create/edit/delete/manage_sponsorship`+`post_media.upload` — không có
`post_category.manage`).

**Verify trực tiếp trên DB thật:** `post_category.manage` hiện **chỉ `super-admin` có** — không
role Platform nào quản lý được category qua UI (dù route/policy đã sẵn sàng). Đây là gap có thật
của tính năng **Category** đã tồn tại từ trước, phát hiện khi khảo sát để làm Tag — nếu làm
`post_tag.manage` theo đúng khuôn mẫu (cấp cho role Platform nào đó), nên tiện thể xử lý luôn gap
tương tự bên Category (xem quyết định §5).

**Đề xuất permission mới:** `post_tag.manage` (`PermissionEnum::POST_TAG_MANAGE`), theo đúng
convention đặt tên `{domain}.{action}` đã dùng (`post_category.manage`, `banner.manage`,
`ocop.manage`).

**Đã verify (2026-07-21):** `app/Enums/PermissionEnum.php` **chưa có** case `POST_TAG_MANAGE` —
đây là 1 chỗ dễ quên khi implement vì spec này tham chiếu `PermissionEnum::POST_TAG_MANAGE->value`
ở cả bảng §3.1 (sidebar) lẫn trên, nhưng chưa từng ghi rõ "cần thêm case mới vào file nào". Cần
thêm ngay cạnh `POST_CATEGORY_MANAGE` (dòng 100 hiện tại):

```php
// app/Enums/PermissionEnum.php — thêm ngay dưới POST_CATEGORY_MANAGE (dòng 100)
case POST_TAG_MANAGE = 'post_tag.manage';
```

## 5. Quyết định cần chốt trước khi viết code

| # | Quyết định | Người chốt chính | Trạng thái |
|---|---|---|---|
| 1 | Role Platform nào được cấp `post_tag.manage`? (và có tiện sửa luôn gap `post_category.manage` chưa cấp cho role nào không?) | Product Owner + Tech Lead | ✅ **Đã chốt** — xem §5.1 |
| 2 | Ràng buộc xoá tag: chặn nếu còn bài dùng (theo Category) hay xoá thẳng (theo Lead)? | Product Owner | ✅ **Đã chốt** — xem §5.2 |
| 3 | "Xem bài theo tag" chỉ cần phía admin (đề xuất §3.3) hay cần luôn trang công khai cho độc giả? | Product Owner | ✅ **Đã chốt** — xem §5.3 |

### 5.1 Quyết định 1 — ĐÃ CHỐT: `platform_content_head` quản lý cả Category lẫn Tag

**Chốt: cấp `post_tag.manage` cho role `platform_content_head`, đồng thời sửa luôn gap có sẵn của
`post_category.manage` (hiện chỉ `super-admin` có, xem §4) bằng cách cấp cùng role này.** Lý do:

- Đổi tên/gộp/xoá tag (hoặc category) là thao tác ảnh hưởng ĐỒNG THỜI nhiều bài viết — cùng mức độ
  "quyết định có tính hệ thống" như publish/unpublish, vốn đã thuộc thẩm quyền
  `platform_content_head` (dù kiểm tra qua `isPlatformContentHead()` trực tiếp, không qua Spatie
  permission). Giao việc này cho `platform_content_creator` (vai trò chỉ viết/sửa bài của chính
  mình) là sai phạm vi trách nhiệm.
- Gộp chung 1 quyết định cho cả Category lẫn Tag vì cùng bản chất "quản lý taxonomy dùng chung của
  Post" — tách riêng thành 2 quyết định khác nhau (1 role cho category, 1 role khác cho tag) không
  có lý do nghiệp vụ nào biện minh, chỉ gây khó nhớ/khó audit sau này.

**Cụ thể cần sửa khi implement** (sketch, không phải code chạy được ngay — người viết code điều
chỉnh chi tiết khi thực hiện):

```php
// Modules/Post/database/seeders/PostPermissionSeeder.php
private const PERMISSIONS = [
    'post_category.manage',
    'post_tag.manage', // MỚI
    'post_article.view',
    // ... giữ nguyên các dòng còn lại
];

/**
 * MỚI — platform_content_head trước đây KHÔNG có bất kỳ Spatie permission nào từ seeder này
 * (publish/unpublish kiểm tra qua isPlatformContentHead() trực tiếp trong code, không qua Spatie
 * permission — xem comment gốc đầu file). Nhưng PostCategoryPolicy/PostTagPolicy dùng chuẩn
 * $user->can('post_category.manage'|'post_tag.manage'), nên CẦN permission Spatie thật ở đây,
 * "global role" không tự động cấp quyền theo cách Policy kiểm tra.
 */
private const PLATFORM_CONTENT_HEAD_PERMISSIONS = [
    'post_category.manage', // sửa gap cũ — trước đây chỉ super-admin có (xem §4)
    'post_tag.manage',
];

public function run(): void
{
    // ... giữ nguyên phần đầu (tạo permission, thu hồi Lớp B, cấp platform_content_creator)

    $contentHead = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
    if ($contentHead) {
        $contentHead->givePermissionTo(self::PLATFORM_CONTENT_HEAD_PERMISSIONS);
    }

    // ... giữ nguyên phần sync super-admin
}
```

Không cần thêm `post_tag.manage` vào `LOP_B_POST_PERMISSIONS` (danh sách thu hồi) — đây là
permission MỚI, 8 role Lớp B chưa từng có nên không có gì để thu hồi.

### 5.2 Quyết định 2 — ĐÃ CHỐT: Chặn xoá nếu còn bài viết dùng tag (theo tiền lệ `DeleteCategoryAction`)

**Chốt: `DeleteTagAction` chặn xoá (throw exception) nếu `$tag->articles()->exists()`, theo đúng
tiền lệ `DeleteCategoryAction` trong cùng module — KHÔNG theo tiền lệ xoá-thẳng của
`LeadTagController`/CRM.** Lý do:

- Nhất quán trong cùng module Post — Category và Tag đều là "taxonomy gắn vào bài viết", người
  dùng sẽ kỳ vọng hành vi xoá giống nhau giữa 2 màn hình cạnh nhau trong cùng khu vực quản trị.
  Theo 2 tiền lệ khác nhau cho 2 concept tương tự trong cùng module là bất nhất khó giải thích.
- Tự nhiên dẫn người dùng tới tính năng "gộp" (§3.2) thay vì mất liên kết đột ngột — đúng tinh
  thần "gộp trước, xoá sau" mà yêu cầu gốc mô tả (đổi tên/gộp/xem bài — xoá không nằm trong danh
  sách yêu cầu ban đầu, nên không nên làm hành vi xoá dễ gây mất dữ liệu hơn mức cần thiết).

**Thông báo lỗi cụ thể khi bị chặn** (để UX rõ ràng, không chỉ "không xoá được"):
`"Không thể xoá tag đang được sử dụng ở {N} bài viết. Hãy gộp tag này vào tag khác hoặc gỡ tag khỏi
từng bài trước khi xoá."` — nêu rõ số bài viết (`{N}`) và gợi ý hành động tiếp theo (gộp), khác với
`DeleteCategoryAction` gốc (chỉ báo "còn category con hoặc bài viết", không gợi ý hướng xử lý) vì
Tag có sẵn tính năng gộp làm lối thoát tự nhiên mà Category không có.

### 5.3 Quyết định 3 — ĐÃ CHỐT: Chỉ làm phía admin, không làm trang công khai

**Chốt: "xem bài theo tag" chỉ cần ở màn quản trị (đếm số bài + link xem danh sách, §3.3) — KHÔNG
làm route công khai `/the/{slug}` cho độc giả trong phạm vi tài liệu này.** Lý do:

- Đúng yêu cầu gốc: 3 khả năng liệt kê ("đổi tên, gộp, xem bài theo tag") đều là hành động của
  **người quản trị**, không phải yêu cầu UX công khai nào được nêu.
- Dữ liệu thật hiện có 0 tag (§2) — chưa có bằng chứng nhu cầu độc giả thật sự muốn duyệt bài theo
  tag. Xây thêm route công khai + đổi badge tĩnh thành link + Handler mới là công sức không nhỏ cho
  1 tính năng chưa có tín hiệu nhu cầu — giữ đúng tinh thần "ưu tiên thấp" của yêu cầu gốc.
- Đã ghi rõ ở §7 hướng làm nếu sau này cần (đổi badge thành `<a>` + Handler mirror
  `ListPublishedArticlesHandler`) — không mất thông tin, chỉ hoãn lại tới khi có tín hiệu nhu cầu
  thật, đúng nguyên tắc đã áp dụng nhất quán ở các spec khác trong repo (vd chuẩn hoá tiếng Việt
  không dấu ở Site Search: "chưa cần làm ngay — chỉ khi có bằng chứng").

## 6. Acceptance Criteria

1. Trang `backend.post.tags.index` liệt kê **toàn bộ tag của nền tảng** (không còn lọc theo tổ chức
   — xem §3.5), kèm số bài viết đang dùng mỗi tag lấy qua `withCount('articles')` — **không** N+1
   (§3.8).
2. Tạo/sửa/xoá tag qua form riêng (không qua textbox bài viết nữa) — textbox trong form bài viết
   (§1.3) **vẫn giữ nguyên hoạt động song song** (không phá luồng viết bài hiện tại), chỉ bổ sung
   thêm nơi quản lý tập trung.
3. Đổi tên 1 tag đang gắn ở N bài viết → cả N bài viết hiển thị tên mới ngay (không cần sửa lại
   từng bài) — vì hiển thị dùng `name`, không dùng `slug`. `slug` **không đổi** theo tên mới (§3.6,
   theo đúng tiền lệ Category) — `name`/`slug` có thể lệch nhau lâu dài, đây là hành vi chấp nhận
   được, không phải bug.
4. Gộp tag A vào tag B → mọi bài viết trước đó có tag A nay có tag B (không trùng lặp nếu bài đã
   có sẵn cả 2), tag A bị xoá, tổng số bài viết có tag B sau khi gộp = hợp của 2 tập bài viết trước
   đó (không cộng dồn nếu có bài trùng cả 2 tag). Gộp tag vào chính nó → bị chặn, báo lỗi rõ ràng
   (§3.9).
5. Xoá 1 tag không còn bài viết nào dùng → xoá thành công. Xoá 1 tag còn bài viết dùng → **bị chặn**
   (§5.2), thông báo rõ số bài viết đang dùng và gợi ý gộp hoặc gỡ tag khỏi bài trước.
6. `PostTag` **không còn** cột `organization_id`, không còn `BelongsToOrganization` (§3.5) — test tự
   động xác nhận **cụ thể**: tạo 1 tag khi `TenantContext` đang set tổ chức A → user thuộc tổ chức B
   (context khác hẳn) vẫn **thấy được tag đó trong danh sách VÀ gắn được nó vào bài viết của tổ chức
   B** (không bị global scope nào chặn ngầm còn sót lại). Không chỉ kiểm tra "danh sách chung" một
   chiều — phải test cả chiều tạo-ở-A/dùng-ở-B để chắc chắn không còn sót logic scope theo tổ chức
   ở bất kỳ tầng nào (Model, Query, Policy).
7. `post_tag.manage` **và** `post_category.manage` được cấp đúng cho role `platform_content_head`
   (§5.1) — verify qua test hoặc `php artisan tinker`; `super-admin` vẫn có qua `syncPermissions`
   như hiện tại, các role Lớp B không có quyền này (giữ nguyên như đã thu hồi).
8. `name` bắt buộc, tối đa 120 ký tự (`TagData`, §3.7); tạo tag trùng slug (khác hoa/thường/dấu
   ra cùng slug) → tự nối hậu tố `-2`, `-3`... (`uniqueSlug()`, giống `CreateCategoryAction`), không
   throw lỗi.

## 7. Ngoài phạm vi tài liệu này

- **Trang công khai lọc bài theo tag cho độc giả** (vd route `/the/{slug}`) — khác hẳn phạm vi
  "quản trị" của tài liệu này. Nếu làm sau, cần đồng thời: (a) đổi badge tĩnh
  (`article.blade.php:79-82`) thành `<a>`, (b) thêm route + Handler kiểu
  `ListPublishedArticlesByTagHandler` (mirror `ListPublishedArticlesHandler`).
- **Thêm `tag_slugs` vào Meilisearch `filterableAttributes`** (§1.5) — cải tiến tìm kiếm, không
  phải yêu cầu cốt lõi của trang quản trị tag. Chỉ cần khi có nhu cầu lọc chính xác theo 1 tag qua
  search UI.
- **Gộp tag hàng loạt bằng AI/gợi ý tự động** (vd tự phát hiện "trẻ sơ sinh" gần nghĩa "trẻ nhỏ") —
  vượt xa quy mô "trang CRUD + gộp thủ công" mà tài liệu này đặt ra.
- ~~Đổi kiến trúc tenant-scoped của `PostTag`... đã xác nhận đây là thiết kế có chủ đích, không phải
  việc cần sửa~~ — **đã sửa lại (2026-07-21):** đây KHÔNG còn ngoài phạm vi nữa. Product Owner xác
  nhận `PostTag` phải chuyển sang platform-wide, việc này nằm trong phạm vi bắt buộc của tài liệu
  (xem §3.5), không phải hạng mục hoãn lại.
- **Thêm bảng `post_tag_aliases` để "redirect" tên cũ sau khi gộp về tag đích** (§3.10) — vượt quy
  mô "trang CRUD + gộp thủ công"; ghi nhận là giới hạn đã biết, không phải việc cần làm ngay.

## 8. Việc KHÔNG nên làm

1. **Không xoá/thay đổi luồng textbox hiện tại** (`SyncsArticleRelations::syncTags()`) — trang quản
   trị mới bổ sung thêm cách quản lý tập trung, không thay thế cách nhập nhanh khi viết bài.
2. **Không tự thêm phân cấp cha/con cho tag** — tag vốn là nhãn phẳng theo thiết kế gốc, thêm phân
   cấp là thay đổi mô hình dữ liệu ngoài phạm vi yêu cầu.
3. **Không cấp `post_tag.manage`/`post_category.manage` cho role nào ngoài `platform_content_head`
   (và `super-admin` qua bypass sẵn có)** — đây là quyết định RBAC đã chốt (§5.1); đặc biệt không
   cấp cho `platform_content_creator` (vai trò chỉ viết/sửa bài của chính mình) dù tiện tay khi
   code, vì đổi tên/gộp/xoá tag-category ảnh hưởng nhiều bài viết cùng lúc.
4. **Không copy nguyên `reorder`/cấu trúc cây từ `CategoryManagement`** — tag phẳng không cần, thêm
   vào là code thừa không ai dùng.
5. **Không thêm `LogsActivity`/bảng activity-log riêng cho `PostTag`** kể cả cho thao tác gộp — model
   chủ đích thiết kế "nhãn phẳng, không audit riêng" (§1.1); dùng `Log::info()` kênh log chuẩn là đủ
   (§3.9).
6. **Không regenerate `slug` khi đổi tên (rename)** — dù có vẻ "tiện tay" để `name`/`slug` luôn khớp
   nhau, nhưng phá vỡ tiền lệ nhất quán với `UpdateCategoryAction` (§3.6) và tạo rủi ro trùng slug
   không cần thiết ở bước update.
7. **Không viết migration ALTER kiểu guard `hasColumn`/`getForeignKeys`** (như
   `2026_07_13_000002_drop_organization_id_from_post_child_tables.php`) **cho việc bỏ `organization_id`
   khỏi `post_tags`** — dự án đang dev phase, 0 dữ liệu thật, sửa thẳng migration tạo bảng gốc rồi
   `migration:generate --fresh` là đủ (§3.5); viết migration ALTER phức tạp hơn mức cần thiết.

## 9. Decision Log

| Ngày | Quyết định (# ở §5) | Người chốt | Nội dung chốt | Lý do |
|---|---|---|---|---|
| 2026-07-21 | 1 | Product Owner + Tech Lead (qua phiên làm việc này) | Cấp `post_tag.manage` **và** `post_category.manage` cho `platform_content_head`; sửa `PostPermissionSeeder.php` thêm block `PLATFORM_CONTENT_HEAD_PERMISSIONS` (hiện chưa tồn tại) | Đổi tên/gộp/xoá tag-category ảnh hưởng nhiều bài cùng lúc — cùng cấp độ với quyền publish/unpublish đã thuộc `platform_content_head`; gộp chung 1 quyết định cho cả Category lẫn Tag vì cùng bản chất taxonomy dùng chung (§5.1) |
| 2026-07-21 | 2 | Product Owner (qua phiên làm việc này) | `DeleteTagAction` chặn xoá nếu còn bài viết dùng tag, theo tiền lệ `DeleteCategoryAction`, không theo tiền lệ xoá-thẳng của Lead/CRM | Nhất quán hành vi trong cùng module Post; dẫn người dùng tới tính năng gộp thay vì mất liên kết đột ngột (§5.2) |
| 2026-07-21 | 3 | Product Owner (qua phiên làm việc này) | "Xem bài theo tag" chỉ làm ở màn quản trị (đếm + link); không làm route công khai `/the/{slug}` trong phạm vi tài liệu này | Đúng yêu cầu gốc (hành động của người quản trị); 0 tag trong DB hiện tại nên chưa có tín hiệu nhu cầu độc giả; hướng làm sau đã ghi ở §7 nếu cần (§5.3) |
| 2026-07-21 | 4 (bổ sung, không thuộc §5 gốc) | Product Owner (qua phiên làm việc này) | `PostTag` chuyển sang platform-wide — bỏ `organization_id`, sửa thẳng migration tạo bảng gốc (dự án đang dev phase, dùng `migration:generate --fresh`) | `PostTag` thuộc nền tảng vận hành, không chịu quản lý theo tổ chức — bản nháp đầu hiểu sai khi coi tenant-scope là thiết kế có chủ đích (§1.1/§3.5) |
| 2026-07-21 | 5 (bổ sung) | Tech Lead (qua phiên làm việc này) | Rename tag KHÔNG regenerate slug (giữ nguyên slug từ lúc tạo), theo đúng tiền lệ `UpdateCategoryAction` | Nhất quán trong module Post; AC hiển thị tên mới vẫn thoả mãn vì UI dùng `name` không dùng `slug`; tránh phát sinh logic xử lý trùng slug ở bước update (§3.6) |

## 10. Định nghĩa Hoàn thành (DoD) & Thứ tự triển khai đề xuất

Tài liệu không dài, nhưng liệt kê rõ thứ tự để người implement không phải tự suy ra từ các mục rải
rác. Đề xuất làm theo đúng thứ tự dưới đây — mỗi bước phụ thuộc bước trước, làm ngược lại dễ phải
sửa đi sửa lại:

1. **Migration + Model** (§3.5) — sửa `2026_07_07_000004_create_post_tags_table.php` bỏ
   `organization_id`, đổi unique constraint sang global theo `slug`; sửa `PostTag.php` bỏ
   `BelongsToOrganization`; `migration:generate --fresh`. *(Làm trước tiên vì mọi Action/Query/Test
   ở các bước sau đều giả định `PostTag` đã platform-wide.)*
2. **Permission + Seeder** (§5.1) — thêm case `POST_TAG_MANAGE` vào `app/Enums/PermissionEnum.php`,
   thêm `post_tag.manage` vào `PERMISSIONS` trong `PostPermissionSeeder.php`, thêm block
   `PLATFORM_CONTENT_HEAD_PERMISSIONS` cấp cả `post_tag.manage` và `post_category.manage` cho
   `platform_content_head`.
3. **CRUD cơ bản** (§3.1, §3.6, §3.7, §3.8) — `TagData`, `CreateTagAction` (có `uniqueSlug()`),
   `UpdateTagAction` (không đụng slug), `TagAdminController`, `PostTagPolicy`,
   `ListTagsForAdminHandler` (có `withCount('articles')`), views, route, sidebar link.
4. **Ràng buộc xoá** (§3.4, §5.2) — `DeleteTagAction` chặn nếu còn bài viết dùng, thông báo lỗi kèm
   số bài viết.
5. **Merge** (§3.2, §3.9) — `MergeTagsAction` (transaction, chặn gộp vào chính nó, `Log::info()`
   khi gộp), route `POST tags/{tag}/merge`, UI chọn tag đích.
6. **Test cuối cùng, xác nhận toàn bộ AC ở §6** — đặc biệt AC#6 (không còn phân biệt theo tổ chức),
   AC#4 (gộp không trùng lặp bài viết), AC#7 (permission đúng role).

Không cần làm §7 (ngoài phạm vi) hay các mục "chưa cần làm ngay" ở §3.9/§3.10 trong đợt này.

## 11. Nhật ký triển khai (2026-07-21 — đã implement xong)

Đã implement toàn bộ theo đúng thứ tự §10, theo cấu trúc AVSA + CQRS-lite + Laravel Modules +
Laravel Actions sẵn có của repo (mirror `CategoryManagement`).

**Khác với kế hoạch §3.5/§8 mục 7 — lý do xem §2/§2.1:** thay vì sửa migration tạo bảng gốc rồi
`migration:generate --fresh`, đã dùng migration ALTER thật
(`database/migrations/extensions/2026_07_21_153510_drop_organization_id_from_post_tags_table.php`,
guard `hasColumn`/`getForeignKeys`, có `down()`), áp dụng qua `php artisan migrate --path=...`
(không đụng bảng khác). 49 tag thật được giữ nguyên, verify 0 slug trùng giữa các tổ chức trước khi
đổi unique constraint. Migration tạo bảng gốc + `render_migration_file.json` + snapshot
`database/migrations/generated/` vẫn được sửa đúng như §3.5 mô tả — để môi trường **fresh mới**
(CI, máy dev khác) tạo bảng đúng ngay từ đầu, không cần chạy thêm migration ALTER này (guard tự
nhận biết cột đã không tồn tại, no-op an toàn).

**File đã tạo/sửa:**

| Loại | File |
|---|---|
| Schema | `render_migration_file.json` (entry `post_tags`), `Modules/Post/database/migrations/2026_07_07_000004_create_post_tags_table.php`, `database/migrations/generated/2026_07_21_014119_000065_create_post_tags_table.php`, `database/migrations/extensions/2026_07_21_153510_drop_organization_id_from_post_tags_table.php` (mới) |
| Model | `Modules/Post/app/Models/PostTag.php` (bỏ `BelongsToOrganization`) |
| Permission | `app/Enums/PermissionEnum.php` (+`POST_TAG_MANAGE`), `Modules/Post/database/seeders/PostPermissionSeeder.php` (+`post_tag.manage`, +block `PLATFORM_CONTENT_HEAD_PERMISSIONS`) |
| Feature `TagManagement` | `Data/TagData.php`, `Actions/{Create,Update,Delete,Merge}TagsAction.php`, `Queries/ListTagsForAdmin{Query,Handler}.php`, `Http/TagAdminController.php` (mới, mirror `CategoryManagement`) |
| Policy | `Modules/Post/app/Policies/PostTagPolicy.php` (mới), đăng ký `Gate::policy` trong `PostServiceProvider.php` |
| Routes | `Modules/Post/routes/web.php` — `Route::resource('tags', ...)->except(['show'])` + `POST tags/{tag}/merge` |
| Sidebar | `resources/views/layouts/partials/sidebar.blade.php` — sub-link "Quản lý tag" gate `PermissionEnum::POST_TAG_MANAGE` |
| Views | `Modules/Post/resources/views/admin/tags/{index,create,edit}.blade.php` (mới, `@extends('layouts.backend')`, modal gộp Alpine.js mirror pattern `Modules/Event/resources/views/admin/events/index.blade.php`) |
| Test | `Modules/Post/tests/Feature/TagManagementTest.php` (mới, 11 test — platform-wide, CRUD, rename giữ slug, xoá bị chặn, merge, permission, full HTTP flow) |

**Kết quả test:** 11/11 pass (`php artisan test Modules/Post/tests/Feature/TagManagementTest.php`);
toàn bộ `Modules/Post/tests` (25 test khác) + `tests/Feature` gốc vẫn pass sau khi thêm — không có
regression.

**Sai sót nhỏ tự phát hiện và sửa trong lúc code** (không cần người dùng can thiệp):
- `TagAdminController::merge()` — bản nháp đầu dùng rule validate `different:tag`, nhưng
  `different` so khớp theo tên field trong request, không so được với route model `{tag}` → rule
  vô nghĩa (luôn pass). Đã bỏ, dựa hẳn vào `MergeTagsAction` (đã ném `ValidationException` khi
  `sourceTag->id === targetTag->id`) để chặn gộp vào chính nó.
- Lần đầu chạy `php artisan migration:generate` (không `--fresh`) để đồng bộ file snapshot
  `generated/` đã vô tình tạo diff ~350 file (mọi migration generated/extensions đổi tên theo
  timestamp mới dù nội dung không đổi) — đã revert toàn bộ và sửa tay đúng 1 file snapshot liên
  quan thay vì chạy lại lệnh regenerate toàn cục.
