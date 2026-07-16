<?php

namespace Modules\ProvinceShowcase\Features\PublicShowcase\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

/**
 * spec/Province_Showcase_Technical_Specification.md §5/§7.1 — trang /{type}/{slug} tồn tại (200
 * OK) cho MỌI tỉnh/thành có mặt trong bảng provinces, miễn {type} khớp đúng place_type thật của
 * tỉnh đó — 404 nếu không khớp (tránh 2 URL cùng 1 nội dung). Tỉnh nào không có mặt trong
 * config('provinceshowcase.showcase_provinces') thì dùng tagline/accent_color mặc định
 * ('provinceshowcase.default') thay vì bị chặn — whitelist chỉ còn dùng để tuỳ biến hiển thị,
 * không còn dùng để quyết định 404.
 */
class ProvincePublicController extends Controller
{
    /** Danh sách toàn bộ tỉnh/thành có chuyên đề — không còn giới hạn theo whitelist (§7.1). */
    public function index(): View
    {
        $provinces = Province::orderBy('name')
            ->get()
            ->map(fn (Province $province) => [
                'province' => $province,
                'config'   => $this->showcaseConfigFor($province->slug),
            ]);

        return view('provinceshowcase::public.index', compact('provinces'));
    }

    /**
     * §4.3 — Blade tự tìm resources/views/public/custom/{slug}.blade.php trước, nếu không tồn
     * tại thì fallback resources/views/public/show.blade.php (template chung). Tỉnh nào cần
     * phong cách riêng → dev tạo file custom thủ công, kế thừa nguyên các section component.
     */
    public function show(string $type, string $slug): View
    {
        $province = Province::where('slug', $slug)->first();
        abort_if($province === null || $province->place_type !== $type, 404);

        $data = ['province' => $province, 'showcase' => $this->showcaseConfigFor($slug)];

        $customView = "provinceshowcase::public.custom.{$province->slug}";

        return ViewFacade::exists($customView)
            ? view($customView, $data)
            : view('provinceshowcase::public.show', $data);
    }

    /** @return array{tagline: string, accent_color: string} */
    private function showcaseConfigFor(string $slug): array
    {
        return config("provinceshowcase.showcase_provinces.{$slug}")
            ?? config('provinceshowcase.default');
    }
}
