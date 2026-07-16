<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ocop\Models\OcopCategory;

/**
 * spec/danhmuc.html — danh mục OCOP đã chuẩn hóa theo bảng phân loại sản phẩm chính thức (nhà
 * nước quy định, thống nhất toàn quốc, seed bởi OcopCategorySeeder). KHÔNG còn create/update/
 * destroy — bảng phân loại đã cố định theo quy định pháp luật, không tùy biến theo dev/module.
 */
class OcopCategoryAdminController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', OcopCategory::class);

        $categoryTree = OcopCategory::tree();

        return view('ocop::admin.categories.index', compact('categoryTree'));
    }
}
