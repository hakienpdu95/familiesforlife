<?php

namespace Modules\PensionCalculator\Features\PublicEstimation\Http;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\PensionCalculator\Features\PublicEstimation\Actions\BuildActiveParameterSetAction;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §0/§5/§10 — trang public (không auth)
 * + API tham chiếu công khai. KHÔNG nhận/lưu bất kỳ input tài chính cá nhân nào — mọi phép
 * tính (§6-§10) chạy phía client trên payload do BuildActiveParameterSetAction cấp.
 */
class PensionCalculatorController extends Controller
{
    /**
     * Bài toán #30 (spec/giadinh.md — "hệ sinh thái kinh tế phục vụ xã hội già hóa") — nối trang
     * công cụ này với 1 chuyên mục nội dung Post (nếu tòa soạn đã tạo) để tạo thành "hệ sinh
     * thái" tool + content thay vì 1 công cụ đơn lẻ. CỐ Ý là hằng số (không phải config) — đây là
     * category CHƯA CHẮC ĐÃ TỒN TẠI (quyết định tạo hay không thuộc về biên tập viên qua
     * CategoryAdminController có sẵn của Post, không phải seed cứng từ module này); nếu chưa có,
     * fetchRelatedArticles() trả rỗng và widget tự ẩn — không lỗi, không cần cấu hình gì thêm.
     */
    public const RELATED_CONTENT_CATEGORY_SLUG = 'nguoi-cao-tuoi';

    public function index(): View
    {
        $referenceData = BuildActiveParameterSetAction::run();

        return view('pensioncalculator::public.index', [
            'referenceData' => $referenceData,
            'relatedArticles' => $this->fetchRelatedArticles(),
        ]);
    }

    /** @return Collection<int, PostArticleTranslation> */
    private function fetchRelatedArticles(): Collection
    {
        return PostArticleTranslation::query()
            ->where('locale', config('post.default_locale'))
            ->where('status', 'published')
            ->whereHas('article.categories', fn ($q) => $q->where('slug', self::RELATED_CONTENT_CATEGORY_SLUG))
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'article_id', 'title', 'slug', 'excerpt', 'published_at']);
    }

    public function referenceData(): JsonResponse
    {
        return response()->json(BuildActiveParameterSetAction::run());
    }

    /**
     * Bài toán #27 (spec/giadinh.md) — thống kê TỔNG HỢP, ẨN DANH, opt-in (xem migration
     * pension_usage_logs). Validate CHẶT theo allowlist enum/range — không nhận field tự do nào
     * khác, để không thể vô tình (hay cố ý) gửi kèm dữ liệu định danh qua field lạ. Không ghi IP,
     * user agent, session hay bất kỳ khoá liên kết nào giữa các lần gửi của cùng 1 người.
     */
    public function logUsage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gender' => ['required', 'in:male,female'],
            'has_mandatory_history' => ['required', 'boolean'],
            'uses_support_group' => ['required', 'boolean'],
            'eligibility_branch' => ['required', 'in:a,b,c,d'],
            'eligible_by_years' => ['required', 'boolean'],
            'years_accumulated' => ['required', 'integer', 'min:0', 'max:80'],
            'years_required' => ['required', 'integer', 'min:0', 'max:80'],
        ]);

        DB::table('pension_usage_logs')->insert([
            ...$validated,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
