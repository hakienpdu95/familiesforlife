# Module Quản lý Điều hướng Menu (Menu Navigation)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.0
**Ngày:** 14/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Ảnh tham khảo:** `spec/2026-07-14_21-26.png` (mega-menu HoneyKids — dùng để đối chiếu yêu cầu UI/UX, **không** copy nội dung/thương hiệu)
**Module liên quan:** `Modules/Post` (`PostCategory` — nguồn dữ liệu điều hướng hiện tại), `Modules/Event` (`EventCategory`), module mới **`Modules/Menu`**

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Nguồn dữ liệu menu** | Không có model/table menu riêng — nav lấy thẳng từ `PostCategory::navTree()`, cộng 1 mục "Sự Kiện" **hard-code** trong blade | Tạo model/table **`menu_items`** độc lập, decoupled khỏi `post_categories` | Cây danh mục phục vụ *phân loại bài viết*; cây menu phục vụ *điều hướng hiển thị*. Hai mục đích khác nhau — thứ tự/nhãn/độ sâu hiển thị trên menu không nên bị khoá cứng vào cấu trúc phân loại nội dung (một category có thể không muốn lên menu, một menu item có thể không map category nào — vd link tới `event.public.home`) |
| **Số cấp lồng nhau** | DB cho phép sâu vô hạn (`parent_id` tự tham chiếu, không check), nhưng **render chỉ 2 cấp** (`navTree()` chỉ eager-load 1 tầng `children`, Alpine chỉ có 1 state `subOpen`) | Hỗ trợ **đúng 3 cấp** (root → dropdown → flyout), validate chặn cấp thứ 4 | Ảnh mẫu yêu cầu 3 cấp (`THINGS TO DO → CELEBRATE → CHINESE NEW YEAR`). Cho phép sâu vô hạn không có nhu cầu thật và làm phức tạp UI/Alpine không cần thiết |
| **Icon menu** | Cột `icon` đã tồn tại trên `post_categories`, **có nhập liệu ở form admin nhưng nav blade chưa bao giờ render nó** | `menu_items.icon` — render thật trên cả 3 cấp | Tận dụng đúng field đã có, đóng gap "khai báo nhưng không dùng" |
| **Banner/CTA trong nav** (vd "Vote now — Sustainability Awards" trong ảnh mẫu) | Không có khái niệm slot quảng cáo trong nav; `promo-bar`/`cta-band` hiện tại là section trong trang chủ, không phải trong thanh nav | **Ngoài phạm vi** spec này | Đây là 1 tính năng "banner quảng cáo" khác hẳn về bản chất (nội dung ads, lịch chạy, đối tác tài trợ) — nên tách thành đặc tả riêng, tránh phình phạm vi module điều hướng |
| **Bộ chọn khu vực ("Singapore ▾")** trong ảnh mẫu | Không tồn tại — hệ thống hiện chỉ phục vụ 1 locale/1 khu vực | **Ngoài phạm vi** | Không có khái niệm multi-region; nếu cần sẽ là 1 spec riêng (vd multi-tenant theo khu vực) |
| **Link tới URL tuỳ ý / trang ngoài category** | Không hỗ trợ — mọi mục nav bắt buộc là 1 `PostCategory` | `menu_items` hỗ trợ 3 kiểu đích: **category nội bộ**, **URL tuỳ ý**, hoặc **chỉ là nhãn nhóm** (không link, chỉ mở dropdown/flyout) | Mục "Sự Kiện" hiện đang hard-code URL trong blade — cần đưa vào CRUD như 1 menu item kiểu URL, xoá hard-code |

---

## 1. Giới thiệu & Mục tiêu

Hiện tại, thanh điều hướng công khai (`resources/views/layouts/partials/frontend-nav.blade.php` và `frontend-drawer.blade.php`) không phải là 1 module quản lý — nó là hệ quả phụ của `PostCategory::navTree()`, cộng thêm 1 dòng `<li>` viết chết trong blade cho mục "Sự Kiện". Hệ quả:

1. **Không sửa được từ giao diện quản trị** — muốn đổi thứ tự/tên hiển thị/thêm link ngoài phải sửa code, không phải nghiệp vụ của Marketing/Admin.
2. **Chỉ hỗ trợ 2 cấp** — không dựng được mega-menu 3 cấp như ảnh tham khảo (`THINGS TO DO` → `CELEBRATE` → `CHINESE NEW YEAR`...).
3. **Trộn lẫn 2 khái niệm khác nhau** — "danh mục bài viết" (taxonomy nội dung) và "mục menu" (cấu trúc điều hướng) đang dùng chung 1 bảng, nên không thể: ẩn 1 category khỏi menu mà vẫn giữ nó để phân loại bài viết, đổi nhãn hiển thị trên menu khác với tên category, hay thêm 1 mục menu không gắn category nào (link ngoài, trang tĩnh...).

Module **Menu Navigation** giải quyết 3 vấn đề trên bằng 1 bảng `menu_items` mới, tự quản lý cây 3 cấp, có CRUD admin riêng, và render ra menu công khai (desktop mega-menu + drawer di động) — độc lập với `PostCategory` nhưng có thể **trỏ tới** 1 category khi cần.

**Không đổi:** `post_categories` giữ nguyên vai trò phân loại bài viết (`PostArticle` ↔ `PostCategory` qua `post_article_categories`) — spec này không sửa gì trong `Modules/Post` ngoài việc bổ sung 1 quan hệ `menuItems()` optional để biết category nào đang được tham chiếu trên menu.

---

## 2. Đối chiếu ảnh mẫu vs hiện trạng

Ảnh `spec/2026-07-14_21-26.png` gồm các phần sau — liệt kê để phân định rõ phần nào **thuộc phạm vi module này**, phần nào không:

| Vùng trong ảnh | Thuộc phạm vi Menu Navigation? |
|---|---|
| Thanh menu ngang (`THINGS TO DO`, `EAT & DRINK`, `TRAVEL`, `LIVE WELL`, `PARENTING`, `SCHOOLS`) — cấp 1 | ✅ Có |
| Dropdown cấp 2 khi hover `THINGS TO DO` (`ARTS CRAFTS & BOOKS`, `KIDS CAMPS & CLASSES`, ... `CELEBRATE`, `CALENDAR`, `TRENDING NOW`) | ✅ Có |
| Flyout cấp 3 khi hover `CELEBRATE` (`CHINESE NEW YEAR`, `HARI RAYA`, `EASTER`, ...) | ✅ Có |
| Ô tìm kiếm ("TRY 'THINGS TO DO...'") | ❌ Đã có sẵn (`frontend-topbar.blade.php`), không thuộc spec này |
| Bộ chọn khu vực "Singapore ▾" | ❌ Ngoài phạm vi (§0) |
| Banner quảng cáo "SUSTAINABILITY AWARDS 2026 — Vote now" | ❌ Ngoài phạm vi (§0) — là 1 tính năng banner riêng, không phải mục menu |

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
MenuItem (self-referencing, tối đa 3 cấp: depth 0/1/2)
  ├─ uuid, location ('header' | 'footer'), parent_id, depth
  ├─ label, icon, sort_order, is_active, open_in_new_tab
  ├─ link_type ('category' | 'url' | 'none')
  ├─ category_id  → nullable FK post_categories.id  (khi link_type = 'category')
  ├─ url           → nullable string                (khi link_type = 'url')
  ├─ created_by, updated_by, timestamps, soft delete
  └─< children (hasMany, self FK parent_id, tối đa 2 tầng con)
```

`location` cho phép 1 bảng phục vụ nhiều vị trí hiển thị (thanh nav chính + footer hiện đang tự lấy 4 category đầu tiên một cách tuỳ tiện — sau khi có `menu_items`, footer đọc `MenuItem::tree('footer')` thay vì tự ý lấy `take(4)` từ category).

### 3.2 Migration

```php
Schema::create('menu_items', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('location', 20)->default('header'); // 'header' | 'footer'
    $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
    $table->unsignedTinyInteger('depth')->default(0); // 0=cấp1, 1=cấp2, 2=cấp3 — cache lại để validate/query nhanh, không phải tính đệ quy mỗi lần

    $table->string('label', 150);       // nhãn hiển thị — ĐỘC LẬP với PostCategory.name
    $table->string('icon', 80)->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('open_in_new_tab')->default(false);

    $table->string('link_type', 10)->default('none'); // MenuLinkType enum, xem §5.1
    $table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
    $table->string('url', 2048)->nullable();

    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['location', 'parent_id', 'sort_order'], 'idx_menu_item_tree');
    $table->index(['location', 'is_active'], 'idx_menu_item_active');
    $table->index(['location', 'depth', 'sort_order'], 'idx_menu_item_depth'); // tăng tốc query dựng cây theo từng tầng (MenuItem::tree())
    $table->index(['category_id', 'location'], 'idx_menu_item_category');     // tăng tốc tra "category X đang được map vào menu item nào" (dùng ở §8.1 backfill + khi ẩn/xoá category cần biết menu item nào bị ảnh hưởng)
});
```

Không cần `unique` trên `label`/`slug` — 1 category có thể xuất hiện dưới nhiều nhãn/nhiều mục menu khác nhau (vd cùng 1 category vừa lên menu header vừa lên menu footer, với `label` khác nhau).

---

## 4. Model

`Modules/Menu/app/Models/MenuItem.php`

```php
class MenuItem extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'menu_items';

    protected $fillable = [
        'uuid', 'location', 'parent_id', 'depth', 'label', 'icon',
        'sort_order', 'is_active', 'open_in_new_tab',
        'link_type', 'category_id', 'url', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'open_in_new_tab' => 'boolean',
        'sort_order'      => 'integer',
        'depth'           => 'integer',
        'link_type'       => MenuLinkType::class,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\Modules\Post\Models\PostCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Cây 3 cấp (root → children → grandchildren), active-only, dùng cho nav công khai.
     * Đệ quy CÓ CHỦ ĐÍCH dừng ở cấp 2 (depth=2 là lá) — mega-menu chỉ cần 3 tầng, không
     * cần tổng quát hoá vô hạn cấp như GetCategoryTreeHandler bên Modules/Post.
     */
    public static function tree(string $location = 'header'): Collection
    {
        return static::active()->root()
            ->where('location', $location)
            ->with(['children' => fn ($q) => $q->active()->with([
                'children' => fn ($q2) => $q2->active()->orderBy('sort_order'),
            ])->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    /** URL đích thực tế — resolve theo link_type, dùng chung cho blade + admin preview. */
    public function resolveUrl(): ?string
    {
        return match ($this->link_type) {
            MenuLinkType::Category => $this->category?->is_active
                ? route('post.public.category', ['category' => $this->category->slug])
                : null,
            MenuLinkType::Url  => $this->url,
            MenuLinkType::None => null,
        };
    }
}
```

`resolveUrl()` tập trung logic "category bị ẩn/xoá thì menu item cha không có link" — tránh lặp `@if` rải rác trong blade.

---

## 5. Business rules

### 5.1 `MenuLinkType` (enum)

```php
enum MenuLinkType: string
{
    case Category = 'category'; // trỏ post_categories.id — dùng resolveUrl()
    case Url      = 'url';      // link tuỳ ý (nội bộ hoặc ngoài)
    case None     = 'none';     // chỉ là nhãn mở dropdown/flyout, không tự link (giống "THINGS TO DO" có thể chỉ mở dropdown)
}
```

### 5.2 Validate khi tạo/sửa (`MenuItemData` + Form Request)

| Rule | Lý do |
|---|---|
| `label` bắt buộc, max 150 | Hiển thị độc lập, không suy ra từ category |
| Nếu `link_type = category` → `category_id` bắt buộc, phải tồn tại và `is_active = true` | Không cho trỏ tới category đã ẩn/xoá |
| Nếu `link_type = url` → `url` bắt buộc, phải là URL hợp lệ (tuyệt đối hoặc tương đối bắt đầu bằng `/`) | |
| Nếu `link_type = none` → `category_id` và `url` phải để trống | Tránh dữ liệu mập mờ (vừa None vừa có url) |
| **`parent_id` chỉ được trỏ tới 1 `MenuItem` có `depth = 0`** (không cho chọn 1 item cấp 2 làm cha) | Chặn cứng cấp thứ 4 — validate ngay khi submit, không đợi tới lúc render mới cắt |
| `depth` = `parent->depth + 1` khi lưu (tự tính trong Action, không cho client gửi lên) | Cache depth để query/validate nhanh, tránh phải load cả chuỗi cha mỗi lần kiểm tra |
| Không cho xoá 1 `MenuItem` đang có `children` (giống `DeleteCategoryAction` hiện tại) | Tránh mồ côi cấp con |
| 1 category có thể được gắn vào **nhiều** `menu_items` khác nhau (không unique `category_id`) | 1 category có thể xuất hiện ở cả menu header lẫn footer |

**UI không chỉ dựa vào lỗi validate từ Action** — form tạo/sửa (§6.2) phải tự lọc `<select name="parent_id">` để **chỉ liệt kê `MenuItem` có `depth < 2`** ngay từ khi render form (query kèm `where('depth', '<', 2)`), không đợi submit rồi mới báo lỗi 422. Action ở §5.3 vẫn giữ nguyên vai trò **chốt chặn cuối** (trường hợp 2 admin sửa đồng thời, hoặc gọi thẳng API) — validate ở UI chỉ là lớp UX, không thay thế validate ở Action.

**UI filter và Action validate là 2 lớp double-check độc lập, không lớp nào được bỏ đi để thay cho lớp kia** — thiếu UI filter thì UX tệ (submit xong mới biết sai), thiếu Action validate thì mất an toàn dữ liệu (API/request thủ công vẫn có thể vượt qua UI để tạo cấp thứ 4).

### 5.3 Giới hạn 3 cấp — thực thi ở đâu

Không dùng CHECK constraint DB (SQLite dev không hỗ trợ tốt) — thực thi ở tầng Action:

```php
// CreateMenuItemAction::handle()
if ($data->parent_id) {
    $parent = MenuItem::findOrFail($data->parent_id);
    throw_if($parent->depth >= 2, ValidationException::withMessages([
        'parent_id' => 'Menu chỉ hỗ trợ tối đa 3 cấp — không thể thêm mục con vào đây.',
    ]));
    $depth = $parent->depth + 1;
}
```

---

## 6. Admin CRUD (`Modules/Menu`)

Tổ chức theo đúng pattern Feature-folder đã dùng ở `Modules/Post/app/Features/CategoryManagement` (Actions/Queries/Data/Http tách riêng):

```
Modules/Menu/
  app/
    Models/MenuItem.php
    Enums/MenuLinkType.php
    Features/MenuManagement/
      Http/MenuItemAdminController.php
      Actions/CreateMenuItemAction.php
      Actions/UpdateMenuItemAction.php
      Actions/DeleteMenuItemAction.php
      Actions/ReorderMenuItemsAction.php
      Queries/GetMenuTreeForAdminQuery(+Handler).php   // cây đầy đủ (mọi is_active) cho màn quản trị
      Data/MenuItemData.php
    Policies/MenuItemPolicy.php
    Providers/MenuServiceProvider.php
  routes/web.php
```

### 6.1 Routes

```php
Route::middleware(['auth'])->prefix('dashboard/menu-items')->name('backend.menu.')->group(function () {
    Route::resource('items', MenuItemAdminController::class)->parameters(['items' => 'menuItem']);
    Route::post('items/reorder', [MenuItemAdminController::class, 'reorder'])->name('items.reorder');
});
```

### 6.2 Giao diện quản trị

- **Trang danh sách**: hiển thị **dạng cây có thụt lề** theo `depth` (khác `post_categories/index.blade.php` hiện đang là bảng phẳng — đây là điểm cần làm đúng ngay từ đầu vì cây 3 cấp mà hiển thị phẳng sẽ rất khó quản lý), kèm badge hiển thị `location` (header/footer), toggle nhanh `is_active`, kéo-thả đổi `sort_order` trong cùng 1 cha (dùng lại action `reorder` kiểu `ReorderCategoriesAction`).
- **Form tạo/sửa**: chọn `location`, `label`, `icon` (text, cùng convention Tabler icon class như `PostCategory.icon`), chọn `link_type` (radio: Danh mục / URL tuỳ ý / Không liên kết — chỉ mở submenu), select `category_id` **chỉ hiện khi link_type=category** (populate từ `PostCategory::active()`), input `url` chỉ hiện khi `link_type=url`, checkbox `open_in_new_tab` (chỉ có ý nghĩa khi `link_type=url`), select `parent_id` **chỉ liệt kê các `MenuItem` có `depth < 2`** — xem §5.2 (Action đã chặn ở backend, UI lọc trước để tránh submit rồi mới báo lỗi).
- **Ghi chú hiển thị khi `location = footer`**: hiện ngay dưới select `parent_id` 1 dòng gợi ý cho Admin: *"Nếu muốn footer nhiều cột: tạo 1 mục cấp 1 làm tiêu đề cột, sau đó thêm các mục cấp 2 (children) bên dưới nó — footer tự động render thành cột, không cần cấu hình gì thêm"* — xem cơ chế render ở §7.5.

### 6.3 Permission

Thêm vào `app/Enums/PermissionEnum.php`:

```php
case MENU_MANAGE = 'menu.manage';
```

Gán vào `config/permissions.php` theo đúng pattern `POST_CATEGORY_MANAGE` hiện tại (chỉ **System_Admin** có toàn quyền — cấu trúc điều hướng là việc của Admin, không phải Marketing biên tập nội dung):

```php
R::ADMIN->value => [
    // ...
    P::MENU_MANAGE->value,
],
```

---

## 7. Render công khai — thay đổi cần thiết

### 7.1 Data cung cấp cho view

Thay vì mỗi controller (`PublicCategoryController`, `PublicArticleController`, ...) tự gọi `PostCategory::navTree()`, chuyển sang 1 **View Composer** dùng chung:

```php
// Modules/Menu/app/Providers/MenuServiceProvider.php
View::composer(
    ['layouts.partials.frontend-nav', 'layouts.partials.frontend-drawer', 'layouts.partials.frontend-footer'],
    fn ($view) => $view->with('menuTree', fn () => MenuItem::tree('header'))
);
```

Loại bỏ việc mỗi controller tự truyền `$categories` cho mục đích nav (giữ `$categories` nếu controller còn dùng cho việc khác, vd trang category cha-con).

### 7.2 `frontend-nav.blade.php` — mega-menu 3 cấp

Thay 2 tầng `<ul>` hiện tại bằng 3 tầng, dùng `resolveUrl()` thay vì tự dựng `route(...)`:

```blade
@foreach($menuTree as $item)
<li class="relative" @if($item->children->isNotEmpty()) @mouseleave="subOpen = subOpen === {{ $loop->index }} ? null : subOpen" @endif>
    <a href="{{ $item->resolveUrl() ?? '#' }}"
       @if($item->open_in_new_tab) target="_blank" rel="noopener" @endif
       @if($item->children->isNotEmpty()) @click.prevent="subOpen = subOpen === {{ $loop->index }} ? null : {{ $loop->index }}" @endif>
        @if($item->icon)<i class="{{ $item->icon }}"></i>@endif {{ $item->label }}
    </a>

    @if($item->children->isNotEmpty())
    <ul x-show="subOpen === {{ $loop->index }}" x-transition x-cloak @click.outside="subOpen = null">
        @foreach($item->children as $child)
        <li class="relative" @if($child->children->isNotEmpty()) @mouseenter="flyOpen = {{ $loop->parent->index }}_{{ $loop->index }}" @mouseleave="flyOpen = null" @endif>
            <a href="{{ $child->resolveUrl() ?? '#' }}">{{ $child->label }}</a>

            @if($child->children->isNotEmpty())
            {{-- Flyout cấp 3, định vị bên phải mục cấp 2 — đúng hành vi "CELEBRATE → CHINESE NEW YEAR..." trong ảnh mẫu --}}
            <ul x-show="flyOpen === '{{ $loop->parent->index }}_{{ $loop->index }}'" x-transition x-cloak
                class="absolute left-full top-0">
                @foreach($child->children as $grandchild)
                <li><a href="{{ $grandchild->resolveUrl() ?? '#' }}">{{ $grandchild->label }}</a></li>
                @endforeach
            </ul>
            @endif
        </li>
        @endforeach
    </ul>
    @endif
</li>
@endforeach
```

### 7.2.1 Accessibility & SEO

Mega-menu 3 cấp hiện chưa có trong spec draft trước — bổ sung các điểm bắt buộc sau vào markup §7.2:

| Yêu cầu | Áp dụng cụ thể |
|---|---|
| `role="menubar"` / `role="menu"` / `role="menuitem"` | `<ul>` cấp 1 → `role="menubar"`; mỗi `<ul>` dropdown/flyout (cấp 2, cấp 3) → `role="menu"`; mỗi `<a>` bên trong → `role="menuitem"` |
| `aria-haspopup="true"` | Đặt trên `<a>` của bất kỳ mục nào có `children->isNotEmpty()` (cấp 1 có dropdown, cấp 2 có flyout) |
| `aria-expanded` | `:aria-expanded="subOpen === {{ $loop->index }} ? 'true' : 'false'"` (cấp 1→2), tương tự cho `flyOpen` (cấp 2→3) — đổi động theo state Alpine, không hard-code |
| Điều hướng bàn phím | `Tab` di chuyển giữa các mục cấp 1; khi 1 mục cấp 1 đang focus, `Enter`/`Space`/`ArrowDown` mở dropdown và focus mục đầu tiên cấp 2; `ArrowRight` trên 1 mục cấp 2 có flyout mở flyout cấp 3; `Escape` đóng cấp đang mở và trả focus lên cấp cha. Cài bằng 1 hàm `@keydown` dùng chung trong `resources/js/frontend.js` (không viết `@keydown.enter`, `@keydown.space`... lặp lại trên từng `<li>`) |
| `rel="noopener"` | Bắt buộc trên mọi `<a target="_blank">` (đã có ở markup §7.2 — `@if($item->open_in_new_tab) target="_blank" rel="noopener" @endif`) |
| `rel="nofollow"` | **Chỉ** thêm khi `link_type = url` và URL trỏ ra ngoài domain (kiểm tra `parse_url($item->url, PHP_URL_HOST) !== request()->getHost()`) — **không** thêm `nofollow` cho `link_type = category` (link nội bộ, muốn Google crawl/truyền PageRank bình thường) |
| Structured data (`SiteNavigationElement`) | Render 1 khối JSON-LD ở `layouts.frontend` (không phải trong từng `<li>`) liệt kê **chỉ cấp 1** (`name`, `url`) — mega-menu 3 cấp không cần khai báo hết vào JSON-LD, Google chủ yếu dùng structured data này để hiểu điều hướng chính của site, không phải để index từng flyout item (những item đó tự có `<a href>` thật trong HTML để crawler theo, không cần JSON-LD lặp lại) |

### 7.3 Alpine state (`resources/js/frontend.js`) — thêm 1 chiều state mới

```js
Alpine.data('frontendNav', () => ({
    subOpen: null,   // index mục cấp 1 đang mở dropdown (giữ nguyên)
    flyOpen: null,   // "{level1Index}_{level2Index}" đang mở flyout cấp 3 — MỚI
    mobileSub: null,     // accordion cấp 2 (drawer) — giữ nguyên
    mobileFlySub: null,  // accordion cấp 3 (drawer) — MỚI
    search: false,
}));
```

Mega-menu tham khảo dùng **hover** để mở flyout cấp 3 (không click) — khác hành vi click-toggle hiện tại của cấp 1/2 trên desktop. Giữ nguyên click-toggle cho cấp 1/2 (đã hoạt động tốt, không có lý do đổi), chỉ dùng hover riêng cho flyout cấp 3 vì đó là hành vi UX chuẩn của mega-menu (di chuột qua "CELEBRATE" là thấy ngay flyout, không cần click).

### 7.4 Drawer di động — accordion 3 cấp

`frontend-drawer.blade.php` lồng thêm 1 tầng `@foreach($child->children as $grandchild)` bên trong tầng cấp 2 hiện có, dùng `mobileFlySub` để đóng/mở độc lập với `mobileSub`. Mobile **không có hover** — cấp 3 dùng accordion (click) giống hệt cơ chế cấp 2 hiện có, không dùng flyout hover như desktop (§7.3).

Markup cấp 3 lặp lại đúng pattern mũi tên xoay đã có ở cấp 2 (`frontend-drawer.blade.php` dòng 16-19 hiện tại), chỉ đổi tên state và thụt lề sâu hơn — **không tạo icon/component mới**:

```blade
@foreach($cat->children as $child)
<li>
    @if($child->children->isNotEmpty())
    <a href="#" class="flex items-center justify-between pl-2" @click.prevent="mobileFlySub = mobileFlySub === '{{ $loop->parent->index }}_{{ $loop->index }}' ? null : '{{ $loop->parent->index }}_{{ $loop->index }}'">
        <span>{{ $child->label }}</span>
        {{-- Cùng 1 SVG chevron như cấp 2, chỉ đổi biến điều kiện --}}
        <svg class="h-4 w-4 transition-transform" :class="mobileFlySub === '{{ $loop->parent->index }}_{{ $loop->index }}' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </a>
    <ul x-show="mobileFlySub === '{{ $loop->parent->index }}_{{ $loop->index }}'" x-transition x-cloak class="pl-4">
        @foreach($child->children as $grandchild)
        <li><a href="{{ $grandchild->resolveUrl() ?? '#' }}">{{ $grandchild->label }}</a></li>
        @endforeach
    </ul>
    @else
    <a href="{{ $child->resolveUrl() ?? '#' }}" class="pl-2">{{ $child->label }}</a>
    @endif
</li>
@endforeach
```

Khoá state dạng chuỗi `"{level1Index}_{level2Index}"` giống hệt `flyOpen` ở §7.3 — dùng chung 1 quy ước đặt tên khoá giữa desktop/mobile để dễ nhớ khi đọc code, dù 2 biến độc lập nhau (không dùng chung 1 state vì desktop/mobile render song song trong DOM — cùng lúc cả 2 breakpoint đều tồn tại, ẩn/hiện bằng CSS `hidden lg:block`, không phải bằng điều kiện PHP).

### 7.5 Footer

**Quyết định: 1 đoạn blade phục vụ cả 2 trường hợp (1 cột phẳng hiện tại lẫn N cột sau này), không cần field/cấu hình riêng để phân biệt "chế độ".** Số cột và việc có tiêu đề cột hay không hoàn toàn suy ra từ dữ liệu — dùng đúng nguyên tắc "có `children` hay không" mà `frontend-nav.blade.php` đang áp dụng cho header:

- `MenuItem` cấp 1 (`location='footer'`) **không có `children`** → render như 1 link phẳng, không tiêu đề (đúng hành vi footer hiện tại).
- `MenuItem` cấp 1 **có `children`** → render như 1 **cột**: `label` làm tiêu đề cột, các `children` cấp 2 làm danh sách link trong cột.
- 2 dạng trên **trộn lẫn tự do trong cùng 1 footer** (vd 2 mục nhóm thành cột + 1 link đơn lẻ như "Điều khoản sử dụng" không cần nhóm) — không cần khai báo trước "đây là footer kiểu mấy cột".

```blade
<div class="grid grid-cols-1 sm:grid-cols-{{ min($footerTree->count(), 4) }} gap-8">
    @foreach($footerTree as $item)
        @if($item->children->isNotEmpty())
        <div>
            <h3 class="font-bold text-sm uppercase mb-3">{{ $item->label }}</h3>
            <ul class="space-y-2 text-sm">
                @foreach($item->children as $link)
                <li><a href="{{ $link->resolveUrl() ?? '#' }}">{{ $link->label }}</a></li>
                @endforeach
            </ul>
        </div>
        @else
        <a href="{{ $item->resolveUrl() ?? '#' }}" class="text-sm">{{ $item->label }}</a>
        @endif
    @endforeach
</div>
```

Với cách này: **hôm nay** Admin chỉ tạo các `MenuItem` cấp 1 không con → footer ra đúng 1 danh sách phẳng như hiện tại (`frontend-footer.blade.php` hiện chỉ liệt kê tên, không có link con) — không có gì đổi hành vi. **Sau này** nếu cần footer 3 cột, Admin chỉ cần thêm `children` cấp 2 vào các `MenuItem` cấp 1 sẵn có qua CRUD (§6) — blade tự động chuyển sang render dạng cột, **không cần sửa model/migration, không cần sửa lại blade lần nữa**. 1 cột hay N cột do đó không phải "2 chế độ" cần thiết kế riêng như bản trước đã viết — nó là 2 điểm trên cùng 1 dải kết quả, phân biệt bởi chính cấu trúc cây mà Admin nhập.

Thay đoạn `($categories ?? collect())->take(4)` (lấy tuỳ tiện 4 category đầu) bằng `MenuItem::tree('footer')` như trên — cho phép Admin **chủ động chọn** mục/cấu trúc cột nào lên footer thay vì bị quyết định ngầm bởi thứ tự category.

> **Ghi chú tối ưu — không bắt buộc, chưa cần làm ở phiên bản này:** `min($footerTree->count(), 4)` đủ dùng cho 1-4 cột, nhưng hard-code trực tiếp trong class Tailwind như vậy sẽ không linh hoạt nếu sau này cần >4 cột hoặc 1 kiểu responsive khác (vd mobile không stack dọc mà carousel ngang). Nếu phát sinh nhu cầu đó, nên tách thành 1 wrapper class riêng (`footer-layout`, định nghĩa số cột/breakpoint trong CSS thay vì lặp lại điều kiện trong blade) hoặc thêm `config/menu.php` (`'footer_max_columns' => 4`) để chỉnh mà không sửa blade. Chưa cần làm ngay vì hiện chưa có yêu cầu cụ thể nào vượt quá 4 cột.

#### 7.5.1 Cột chứa text tĩnh (giới thiệu công ty, địa chỉ, social icon, newsletter...) — nằm ngoài phạm vi `menu_items`

Thực tế footer nhiều site có cột **không phải danh sách link** (vd cột giữa là đoạn mô tả công ty/địa chỉ/icon mạng xã hội/form đăng ký newsletter). Cột dạng này **không nên** dựng từ `MenuItem`:

- `MenuItem.label` là chuỗi ngắn (max 150) dùng làm nhãn link/tiêu đề cột — không phải chỗ chứa văn bản tự do nhiều dòng, HTML định dạng, hay 1 form (newsletter cần input + submit action, hoàn toàn khác bản chất 1 "link").
- Nhét text tĩnh vào 1 `MenuItem` không `link_type`, không `children` là dùng sai mục đích model — lặp lại đúng lỗi mà spec này đang sửa ở §1 (trộn "điều hướng" với "nội dung").

**Quyết định (chốt rõ):** cột tĩnh (text dài, social icon, newsletter) viết trực tiếp trong blade (hard-code, giống cách `frontend-header.blade.php` đang hard-code brand qua `config('app.name')`), hoặc lấy từ `config/` (vd `config('app.footer_about')`) hoặc 1 bảng "site settings" riêng nếu cần Admin sửa được — **không được** đưa vào `menu_items` dưới bất kỳ hình thức nào (kể cả `link_type=none` không children). Đây là 1 tính năng "site settings/content block" khác, **ngoài phạm vi module Menu Navigation** (đã liệt kê ở §9).

Khi đó bố cục N-cột **không còn thuần data-driven như ví dụ ở trên** — cột tĩnh chen giữa các cột động buộc blade phải định vị **theo vị trí cố định**, không lặp tuần tự qua `$footerTree`:

```blade
<div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
    {{-- Cột 1 (menu): $footerTree->get(0) --}}
    {{-- Cột 2 (tĩnh, hard-code — KHÔNG lấy từ menu_items) --}}
    {{-- Cột 3 (menu): $footerTree->get(1) --}}
</div>
```

**Giới hạn cần ghi rõ cho Admin/dev:** cách này **cố định vị trí cột tĩnh trong code**, nên footer chỉ hỗ trợ tối đa đúng số cột-menu đã định trước trong blade (vd 2 cột trái/phải quanh 1 cột tĩnh cố định) — Admin tạo thêm `MenuItem` cấp 1 thứ 3 cho `location='footer'` **sẽ không tự nhiên xuất hiện thành cột thứ 4**, vì layout không còn suy hoàn toàn từ dữ liệu như trường hợp footer thuần menu ở trên. Nếu sau này cần số cột tĩnh/động linh hoạt hơn, đó là lúc cần 1 khái niệm "footer layout slot" riêng — không đưa vào phiên bản này.

### 7.6 Xoá hard-code "Sự Kiện"

Xoá khối `<li><a href="{{ route('event.public.home') }}">Sự Kiện</a></li>` viết chết trong `frontend-nav.blade.php`. Thay bằng 1 dòng dữ liệu thật: `MenuItem::create(['location' => 'header', 'label' => 'Sự Kiện', 'link_type' => 'url', 'url' => route('event.public.home'), ...])` — tạo qua seeder khi cài đặt module hoặc nhập tay lần đầu qua admin UI.

---

## 8. Kế hoạch triển khai (phases)

| Phase | Nội dung | Có ảnh hưởng nav công khai hiện tại? |
|---|---|---|
| 1 | Migration + Model + Enum + Policy + Admin CRUD (`Modules/Menu`) | Không — nav công khai vẫn dùng `PostCategory::navTree()` cũ |
| 2 | Seed dữ liệu qua Artisan command `menu:backfill-from-categories` (§8.1) | Không |
| 3 | Đổi `frontend-nav.blade.php`, `frontend-drawer.blade.php`, `frontend-footer.blade.php` sang đọc `MenuItem::tree()` qua View Composer; xoá hard-code "Sự Kiện" | **Có** — cần QA kỹ trên staging trước khi lên production |
| 4 | (Tuỳ chọn, không bắt buộc) Dọn `PostCategory::navTree()` nếu xác nhận không còn nơi nào khác gọi tới | Không (dọn dẹp sau khi Phase 3 ổn định) |

Phase 3 là điểm rủi ro duy nhất (đổi nguồn dữ liệu render nav thật) — nên bật sau khi Phase 2 đã chạy trên staging và Admin xác nhận cây menu mới khớp 100% với cây category cũ.

### 8.1 Chi tiết `menu:backfill-from-categories` — idempotency

Command chạy 1 vòng `foreach` trên `PostCategory::active()->root()->with('children')`, với quy tắc **"đã map thì bỏ qua, chưa map thì tạo mới"**:

```php
foreach ($rootCategories as $category) {
    // Điều kiện skip: đã tồn tại 1 MenuItem cùng location trỏ đúng category_id này
    // (không quan tâm menu item đó còn active hay đã bị admin sửa/ẩn — coi như "đã map rồi",
    // không tự động tạo trùng hay tự động "sửa lại cho khớp category" đè lên thay đổi thủ công của admin).
    if (MenuItem::where('location', 'header')->where('category_id', $category->id)->exists()) {
        $this->line("Skip: category #{$category->id} ({$category->name}) đã có menu item — bỏ qua.");
        continue;
    }

    $parent = MenuItem::create([
        'location' => 'header', 'link_type' => 'category', 'category_id' => $category->id,
        'label' => $category->name, 'sort_order' => $category->sort_order, 'depth' => 0,
        'created_by' => $systemUserId,
    ]);

    foreach ($category->children as $child) {
        if (MenuItem::where('location', 'header')->where('category_id', $child->id)->exists()) {
            continue; // cùng quy tắc skip ở cấp con
        }
        MenuItem::create([
            'location' => 'header', 'parent_id' => $parent->id, 'link_type' => 'category',
            'category_id' => $child->id, 'label' => $child->name, 'sort_order' => $child->sort_order,
            'depth' => 1, 'created_by' => $systemUserId,
        ]);
    }
}
```

Vì vậy command **chạy lại bao nhiêu lần cũng an toàn**: lần đầu tạo toàn bộ cây, các lần sau chỉ tạo thêm menu item cho category **mới phát sinh** sau lần chạy trước (vd category tạo thêm giữa Phase 2 và Phase 3), không đụng tới menu item đã tồn tại — kể cả khi admin đã sửa tay `label`/`sort_order`/`is_active` của nó sau lần backfill đầu. Command **không** có bước "update" hay "xoá menu item mồ côi khi category bị xoá" — đó là việc của Admin làm thủ công qua CRUD (§6), giữ command chỉ làm đúng 1 việc: seed ban đầu.

---

## 9. Ngoài phạm vi (out of scope) — ghi rõ để tránh hiểu nhầm khi review

- Banner/quảng cáo trong thanh nav (như "Vote now" trong ảnh mẫu).
- Bộ chọn khu vực/quốc gia ("Singapore ▾").
- Đa ngôn ngữ cho nhãn menu (menu hiện tại — cũng như `PostCategory` — chỉ phục vụ 1 locale, xem `config('post.default_locale')`; nếu cần đa ngôn ngữ cho menu sẽ là phase riêng, theo đúng pattern `PostArticleTranslation` đã làm cho bài viết).
- Cá nhân hoá menu theo role/đăng nhập (toàn bộ menu là public, không có "menu riêng cho user đã login").
- Cột nội dung tĩnh trong footer (giới thiệu công ty, địa chỉ, social icon...) và mọi khái niệm "site settings/content block" đứng sau nó (xem §7.5.1) — đây là nội dung trang, không phải điều hướng, không thuộc `menu_items`.
