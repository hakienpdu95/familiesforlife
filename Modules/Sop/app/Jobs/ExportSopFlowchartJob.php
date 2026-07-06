<?php

namespace Modules\Sop\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Repositories\SopFlowchartRepository;
use Modules\Sop\Services\SopSvgRenderer;

/**
 * Queued PNG export — renders SVG via SopSvgRenderer then converts to PNG using Imagick.
 * Stores result in storage/app/exports/sop/{uuid}.png, available for 1 hour.
 */
class ExportSopFlowchartJob extends SopJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        private readonly int    $sopId,
        private readonly string $sopUuid,
        private readonly string $notifyUserId,
    ) {
        parent::__construct();
    }

    public function handle(SopFlowchartRepository $repo, SopSvgRenderer $renderer): void
    {
        $this->withTenant(function () use ($repo, $renderer) {
            $data = $repo->getFlowchartData($this->sopId);

            $svg = $renderer->render($data, showDuration: true);

            $png = $this->svgToPng($svg);

            $path = 'exports/sop/' . $this->sopUuid . '.png';
            Storage::disk('local')->put($path, $png);
        });
    }

    private function svgToPng(string $svg): string
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
