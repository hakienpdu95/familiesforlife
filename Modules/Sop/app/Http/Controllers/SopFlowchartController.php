<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Repositories\SopFlowchartRepository;

class SopFlowchartController extends Controller
{
    public function data(SopProcess $sop): JsonResponse
    {
        $this->authorize('view', $sop);

        $data = app(SopFlowchartRepository::class)->getFlowchartData($sop->id);

        return response()->json($data);
    }
}
