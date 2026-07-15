<?php

namespace Modules\ProvinceShowcase\Features\PublicShowcase\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

/**
 * spec/Province_Showcase_Technical_Specification.md §5/§7.1 — trang /{type}/{slug} chỉ tồn tại
 * (200 OK) nếu slug có mặt trong config('provinceshowcase.showcase_provinces') VÀ khớp 1 dòng
 * trong bảng provinces VÀ {type} khớp đúng place_type thật của tỉnh đó — 404 nếu 1 trong 3
 * điều kiện không thỏa (tránh lộ URL cho tỉnh chưa có nội dung / tránh 2 URL cùng 1 nội dung).
 */
class ProvincePublicController extends Controller
{
    /** Danh sách tỉnh có chuyên đề (§7.1) — chỉ tỉnh thoả cả 2 điều kiện mới xuất hiện. */
    public function index(): View
    {
        $slugs = array_keys(config('provinceshowcase.showcase_provinces', []));

        $provinces = Province::whereIn('slug', $slugs)
            ->orderBy('name')
            ->get()
            ->map(fn (Province $province) => [
                'province' => $province,
                'config'   => config("provinceshowcase.showcase_provinces.{$province->slug}"),
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
        $showcaseConfig = config("provinceshowcase.showcase_provinces.{$slug}");
        abort_if($showcaseConfig === null, 404);

        $province = Province::where('slug', $slug)->first();
        abort_if($province === null || $province->place_type !== $type, 404);

        $data = ['province' => $province, 'showcase' => $showcaseConfig];

        $customView = "provinceshowcase::public.custom.{$province->slug}";

        return ViewFacade::exists($customView)
            ? view($customView, $data)
            : view('provinceshowcase::public.show', $data);
    }
}
