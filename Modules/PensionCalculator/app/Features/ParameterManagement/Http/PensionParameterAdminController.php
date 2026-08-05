<?php

namespace Modules\PensionCalculator\Features\ParameterManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\PensionCalculator\Enums\SupportGroupKey;
use Modules\PensionCalculator\Features\ParameterManagement\Actions\SaveParameterPeriodAction;
use Modules\PensionCalculator\Features\ParameterManagement\Actions\SavePensionRateBracketAction;
use Modules\PensionCalculator\Features\ParameterManagement\Actions\SavePriceIndexCoefficientAction;
use Modules\PensionCalculator\Models\PensionParameterPeriod;
use Modules\PensionCalculator\Models\PensionPriceIndexCoefficient;
use Modules\PensionCalculator\Models\PensionRateBracket;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §9 — quản lý tham số pháp lý theo
 * giai đoạn hiệu lực. Route đã gate `can:pension_calculator.manage` (routes/web.php) — chỉ
 * System_Admin sửa được (§9.3), CEO có `pension_calculator.view` chỉ xem qua index().
 * KHÔNG có update/destroy cho dữ liệu đã có hiệu lực (§9.1 — bất biến).
 */
class PensionParameterAdminController extends Controller
{
    public function index(): View
    {
        return view('pensioncalculator::admin.parameters.index', [
            'periods' => PensionParameterPeriod::with('supportTiers')->orderByDesc('effective_from')->get(),
            'coefficients' => PensionPriceIndexCoefficient::orderByDesc('settlement_year')->orderBy('contribution_year')->get(),
            'rateBrackets' => PensionRateBracket::orderBy('gender')->orderBy('min_years_for_base_rate')->get(),
            'usageStats' => $this->buildUsageStats(),
        ]);
    }

    /**
     * Bài toán #27 (spec/giadinh.md) — tóm tắt thô từ `pension_usage_logs` (thống kê ẩn danh,
     * opt-in — xem PublicEstimation\Http\PensionCalculatorController::logUsage()). Chỉ tổng hợp
     * COUNT/AVG trên các cột đã ẩn danh sẵn, không có truy vấn nào lộ ra 1 bản ghi đơn lẻ.
     *
     * @return array{total:int, eligible_count:int, avg_years_short:float|null, branch_counts:array<string,int>}
     */
    private function buildUsageStats(): array
    {
        $total = DB::table('pension_usage_logs')->count();
        $eligible = DB::table('pension_usage_logs')->where('eligible_by_years', true)->count();

        $avgYearsShort = DB::table('pension_usage_logs')
            ->where('eligible_by_years', false)
            ->selectRaw('AVG(years_required - years_accumulated) as avg_short')
            ->value('avg_short');

        $branchCounts = DB::table('pension_usage_logs')
            ->select('eligibility_branch')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('eligibility_branch')
            ->pluck('total', 'eligibility_branch')
            ->all();

        return [
            'total' => $total,
            'eligible_count' => $eligible,
            'avg_years_short' => $avgYearsShort !== null ? round((float) $avgYearsShort, 1) : null,
            'branch_counts' => $branchCounts,
        ];
    }

    public function createPeriod(): View
    {
        return view('pensioncalculator::admin.parameters.create-period', [
            'latestPeriod' => PensionParameterPeriod::orderByDesc('effective_from')->first(),
            'supportGroups' => SupportGroupKey::cases(),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'effective_from' => ['required', 'date'],
            'rural_poverty_line' => ['required', 'numeric', 'gt:0'],
            'reference_level' => ['required', 'numeric', 'gt:0'],
            'contribution_rate_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'ceiling_multiplier' => ['required', 'integer', 'gt:0'],
            'source_document' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'support_tiers' => ['required', 'array'],
            'support_tiers.*' => ['required', 'numeric', 'gte:0', 'lte:100'],
        ]);

        $allowedGroupKeys = array_map(fn (SupportGroupKey $g) => $g->value, SupportGroupKey::cases());
        $supportTiers = array_intersect_key($validated['support_tiers'], array_flip($allowedGroupKeys));

        $result = SaveParameterPeriodAction::run([
            ...$validated,
            'support_tiers' => $supportTiers,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('backend.pension-calculator.index')
            ->with('success', 'Đã thêm giai đoạn hiệu lực '.$result['period']->effective_from->format('d/m/Y').'.'
                .(empty($result['warnings']) ? '' : ' Cảnh báo: '.implode(' ', $result['warnings'])));
    }

    public function createPriceIndex(): View
    {
        return view('pensioncalculator::admin.parameters.create-price-index');
    }

    public function storePriceIndex(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settlement_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'source_document' => ['required', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.contribution_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'rows.*.coefficient' => ['required', 'numeric', 'gte:1'],
        ]);

        SavePriceIndexCoefficientAction::run([
            ...$validated,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('backend.pension-calculator.index')
            ->with('success', 'Đã thêm '.count($validated['rows']).' hệ số trượt giá cho năm giải quyết '.$validated['settlement_year'].'.');
    }

    public function createRateBracket(): View
    {
        return view('pensioncalculator::admin.parameters.create-rate-bracket');
    }

    public function storeRateBracket(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gender' => ['required', 'in:male,female'],
            'min_years_for_base_rate' => ['required', 'integer', 'gt:0'],
            'base_rate_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'increment_percent_per_year' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'max_rate_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'effective_from' => ['required', 'date'],
            'source_document' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        SavePensionRateBracketAction::run($validated);

        return redirect()
            ->route('backend.pension-calculator.index')
            ->with('success', 'Đã thêm bậc tỷ lệ hưởng lương hưu.');
    }
}
