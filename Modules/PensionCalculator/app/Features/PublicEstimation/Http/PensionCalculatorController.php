<?php

namespace Modules\PensionCalculator\Features\PublicEstimation\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\PensionCalculator\Features\PublicEstimation\Actions\BuildActiveParameterSetAction;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §0/§5/§10 — trang public (không auth)
 * + API tham chiếu công khai. KHÔNG nhận/lưu bất kỳ input tài chính cá nhân nào — mọi phép
 * tính (§6-§10) chạy phía client trên payload do BuildActiveParameterSetAction cấp.
 */
class PensionCalculatorController extends Controller
{
    public function index(): View
    {
        $referenceData = BuildActiveParameterSetAction::run();

        return view('pensioncalculator::public.index', [
            'referenceData' => $referenceData,
        ]);
    }

    public function referenceData(): JsonResponse
    {
        return response()->json(BuildActiveParameterSetAction::run());
    }
}
