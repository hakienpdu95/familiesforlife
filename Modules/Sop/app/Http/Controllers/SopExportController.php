<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\Sop\Jobs\ExportSopFlowchartJob;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Repositories\SopFlowchartRepository;
use Modules\Sop\Services\SopSvgRenderer;

class SopExportController extends Controller
{
    public function __construct(
        private readonly SopFlowchartRepository $flowchartRepo,
        private readonly SopSvgRenderer         $svgRenderer,
    ) {}

    /**
     * Export SOP as PDF — text-based (no SVG), using dompdf.
     * Route: GET /dashboard/sop/{sop}/export/pdf
     */
    public function exportPdf(SopProcess $sop): Response
    {
        $this->authorize('view', $sop);

        $flowData = $this->flowchartRepo->getFlowchartData($sop->id);
        $steps    = collect($flowData['steps']);

        $sop->load([
            'owner:id,name',
            'department:id,name',
            'branch:id,name',
            'approvedBy:id,name',
        ]);

        $pdf = Pdf::loadView('sop::sop.export.pdf', [
            'sop'      => $sop,
            'steps'    => $steps,
            'flowData' => $flowData,
        ])->setPaper('a4', 'landscape');

        $filename = $sop->code . '-v' . $sop->version . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export SOP flowchart as PNG — renders SVG then converts via Imagick.
     * Route: GET /dashboard/sop/{sop}/export/png
     */
    public function exportPng(SopProcess $sop): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('view', $sop);

        $flowData = $this->flowchartRepo->getFlowchartData($sop->id);

        if (collect($flowData['steps'])->isEmpty()) {
            abort(422, 'SOP chưa có bước nào để xuất.');
        }

        if (!extension_loaded('imagick')) {
            // Fallback: queue async job and notify user
            ExportSopFlowchartJob::dispatch(
                $sop->id,
                $sop->uuid,
                auth()->id(),
            );
            return response()->json([
                'message' => 'PNG export đang được xử lý. Vui lòng thử lại sau.',
            ], 202);
        }

        $svg = $this->svgRenderer->render($flowData, showDuration: true);
        $png = $this->convertSvgToPng($svg);

        $filename = $sop->code . '-v' . $sop->version . '.png';

        return response($png, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Print-friendly full-page view — no sidebar, @media print optimized.
     * Route: GET /dashboard/sop/{sop}/print
     */
    public function printView(SopProcess $sop): \Illuminate\View\View
    {
        $this->authorize('view', $sop);

        $flowData = $this->flowchartRepo->getFlowchartData($sop->id);
        $svg      = $this->svgRenderer->render(
            collect($flowData['steps'])->isEmpty() ? $flowData : $flowData,
            showDuration: true
        );

        $sop->load([
            'owner:id,name',
            'department:id,name',
            'branch:id,name',
            'versions' => fn($q) => $q->where('status', 'approved')->orderByDesc('version_number')->limit(1),
        ]);

        return view('sop::sop.print', [
            'sop'      => $sop,
            'flowData' => $flowData,
            'steps'    => collect($flowData['steps']),
            'svg'      => $svg,
        ]);
    }

    private function convertSvgToPng(string $svg): string
    {
        $imagick = new \Imagick();
        $imagick->setBackgroundColor(new \ImagickPixel('white'));
        $imagick->setResolution(144, 144);
        $imagick->readImageBlob($svg);
        $imagick->setImageFormat('png');
        $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        return $imagick->getImageBlob();
    }
}
