<?php

namespace Modules\Sop\Services;

use Illuminate\Support\Collection;

/**
 * Renders an SOP flowchart as an SVG string from step/connector data.
 * Mirrors the layout algorithm of the Alpine.js sopFlowchart component.
 */
class SopSvgRenderer
{
    // Layout constants — must match JS component
    private const NODE_W       = 96;
    private const NODE_H       = 64;
    private const DIA_W        = 90;
    private const DIA_H        = 58;
    private const OVAL_W       = 72;
    private const OVAL_H       = 44;
    private const GAP_X        = 40;
    private const START_X      = 24;
    private const START_Y      = 28;
    private const BRANCH_DROP  = 86;

    private const SHAPE_MAP = [
        'start'        => ['shape' => 'oval',          'color' => '#1D9E75', 'fill' => '#E1F5EE'],
        'end'          => ['shape' => 'oval_double',    'color' => '#1D9E75', 'fill' => '#E1F5EE'],
        'action'       => ['shape' => 'rect',           'color' => '#378ADD', 'fill' => '#E6F1FB'],
        'decision'     => ['shape' => 'diamond',        'color' => '#EF9F27', 'fill' => '#FAEEDA'],
        'sub_sop'      => ['shape' => 'rect_double',    'color' => '#1D9E75', 'fill' => '#E1F5EE'],
        'notification' => ['shape' => 'parallelogram',  'color' => '#7F77DD', 'fill' => '#EEEDFE'],
        'wait'         => ['shape' => 'rounded_rect',   'color' => '#888780', 'fill' => '#F1EFE8'],
    ];

    private const CONNECTOR_COLORS = [
        'sequence'   => '#B4B2A9',
        'yes_branch' => '#639922',
        'no_branch'  => '#E24B4A',
        'trigger'    => '#7F77DD',
        'return'     => '#EF9F27',
        'exception'  => '#E24B4A',
    ];

    public function render(array $flowData, bool $showDuration = false): string
    {
        $steps      = collect($flowData['steps']);
        $connectors = collect($flowData['connectors']);

        $nodes    = $this->computeNodes($steps, $showDuration);
        $connLines = $this->computeConnectors($nodes, $connectors);

        $maxX = $nodes->max(fn($n) => $n['x'] + $n['w']) + self::START_X;
        $maxY = $nodes->max(fn($n) => $n['y'] + $n['h']) + self::BRANCH_DROP + 40;
        $width  = max($maxX, 200);
        $height = max($maxY, 120);

        $svg  = '<svg xmlns="http://www.w3.org/2000/svg"';
        $svg .= ' width="' . $width . '" height="' . $height . '"';
        $svg .= ' viewBox="0 0 ' . $width . ' ' . $height . '"';
        $svg .= ' style="background:#f8f9fa;font-family:Arial,Helvetica,sans-serif;">';
        $svg .= $this->defs();
        $svg .= $this->renderConnectors($connLines);
        $svg .= $this->renderNodes($nodes, $showDuration);
        $svg .= '</svg>';

        return $svg;
    }

    private function computeNodes(Collection $steps, bool $showDuration): Collection
    {
        $nodes = collect();
        $cx    = self::START_X;

        foreach ($steps as $step) {
            $step   = (array) $step;
            $type   = $step['step_type'] ?? 'action';
            $isDec  = $type === 'decision';
            $isOval = in_array($type, ['start', 'end']);
            $w = $isDec ? self::DIA_W : ($isOval ? self::OVAL_W : self::NODE_W);
            $h = $isDec ? self::DIA_H : ($isOval ? self::OVAL_H : self::NODE_H);
            $shape = self::SHAPE_MAP[$type] ?? self::SHAPE_MAP['action'];

            $nodes->push(array_merge($step, $shape, [
                'x'  => $cx,
                'y'  => self::START_Y,
                'w'  => $w,
                'h'  => $h,
                'cx' => $cx + $w / 2,
                'cy' => self::START_Y + $h / 2,
            ]));
            $cx += $w + self::GAP_X;
        }

        return $nodes;
    }

    private function computeConnectors(Collection $nodes, Collection $connectors): Collection
    {
        $nodeById = $nodes->keyBy('id');
        $lines    = collect();

        foreach ($connectors as $conn) {
            $conn = (array) $conn;
            $from = (array) ($nodeById->get($conn['from_step_id']));
            $to   = (array) ($nodeById->get($conn['to_step_id']));
            if (empty($from) || empty($to)) continue;

            $color  = $conn['color_hex'] ?? (self::CONNECTOR_COLORS[$conn['connector_type']] ?? '#B4B2A9');
            $dashed = in_array($conn['connector_type'], ['trigger', 'exception']);

            if ($conn['connector_type'] === 'no_branch') {
                $dropY  = $from['y'] + $from['h'] + self::BRANCH_DROP;
                $path   = "M{$from['cx']},{$from['y']}+{$from['h']} L{$from['cx']},{$dropY}";
                $path   = "M{$from['cx']}," . ($from['y'] + $from['h']) . " L{$from['cx']},{$dropY}";
                $labelX = $from['cx'] + 12;
                $labelY = $from['y'] + $from['h'] + 14;
            } elseif ($to['x'] > $from['x'] + $from['w']) {
                $path   = "M" . ($from['x'] + $from['w']) . ",{$from['cy']} L{$to['x']},{$to['cy']}";
                $labelX = ($from['x'] + $from['w'] + $to['x']) / 2;
                $labelY = $from['cy'] - 8;
            } else {
                $midY   = $from['cy'] - 30;
                $path   = "M{$from['cx']},{$from['y']} L{$from['cx']},{$midY} L{$to['cx']},{$midY} L{$to['cx']},{$to['y']}";
                $labelX = ($from['cx'] + $to['cx']) / 2;
                $labelY = $midY - 6;
            }

            $lines->push(array_merge($conn, [
                'path'   => $path,
                'color'  => $color,
                'dashed' => $dashed,
                'labelX' => $labelX,
                'labelY' => $labelY,
            ]));
        }

        return $lines;
    }

    private function defs(): string
    {
        $colors = ['gray' => '#B4B2A9', 'green' => '#639922', 'red' => '#E24B4A', 'purple' => '#7F77DD', 'orange' => '#EF9F27'];
        $out = '<defs>';
        foreach ($colors as $name => $color) {
            $out .= '<marker id="arr-' . $name . '" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">';
            $out .= '<path d="M2 1L8 5L2 9" fill="none" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round"/>';
            $out .= '</marker>';
        }
        $out .= '</defs>';
        return $out;
    }

    private function renderConnectors(Collection $lines): string
    {
        $out = '';
        foreach ($lines as $conn) {
            $dash      = $conn['dashed'] ? ' stroke-dasharray="5 3"' : '';
            $markerKey = match($conn['connector_type'] ?? '') {
                'yes_branch' => 'green',
                'no_branch', 'exception' => 'red',
                'trigger' => 'purple',
                'return'  => 'orange',
                default   => 'gray',
            };
            $out .= '<path d="' . htmlspecialchars($conn['path'], ENT_XML1) . '"';
            $out .= ' fill="none" stroke="' . $conn['color'] . '" stroke-width="1.2"' . $dash;
            $out .= ' marker-end="url(#arr-' . $markerKey . ')"/>';
            if (!empty($conn['label'])) {
                $out .= '<text x="' . $conn['labelX'] . '" y="' . $conn['labelY'] . '"';
                $out .= ' text-anchor="middle" font-size="10" fill="' . $conn['color'] . '">';
                $out .= htmlspecialchars($conn['label'], ENT_XML1);
                $out .= '</text>';
            }
        }
        return $out;
    }

    private function renderNodes(Collection $nodes, bool $showDuration): string
    {
        $out = '';
        foreach ($nodes as $node) {
            $out .= $this->renderNode($node, $showDuration);
        }
        return $out;
    }

    private function renderNode(array $node, bool $showDuration): string
    {
        $shape = $node['shape'] ?? 'rect';
        $color = $node['color'] ?? '#378ADD';
        $fill  = $node['fill']  ?? '#E6F1FB';
        $x = $node['x']; $y = $node['y']; $w = $node['w']; $h = $node['h'];
        $cx = $node['cx']; $cy = $node['cy'];
        $title = mb_substr($node['title'] ?? '', 0, 16) . (mb_strlen($node['title'] ?? '') > 16 ? '…' : '');
        $out   = '<g>';

        switch ($shape) {
            case 'oval':
            case 'oval_double':
                $out .= '<ellipse cx="' . $cx . '" cy="' . $cy . '" rx="' . ($w / 2) . '" ry="' . ($h / 2) . '"';
                $out .= ' fill="' . $fill . '" stroke="' . $color . '" stroke-width="1"/>';
                break;
            case 'diamond':
                $out .= '<path d="M' . $cx . ',' . $y . ' L' . ($x + $w) . ',' . $cy . ' L' . $cx . ',' . ($y + $h) . ' L' . $x . ',' . $cy . ' Z"';
                $out .= ' fill="' . $fill . '" stroke="' . $color . '" stroke-width="0.8"/>';
                break;
            case 'rounded_rect':
                $out .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="16"';
                $out .= ' fill="' . $fill . '" stroke="' . $color . '" stroke-width="0.8"/>';
                break;
            case 'rect_double':
                $out .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="6"';
                $out .= ' fill="' . $fill . '" stroke="' . $color . '" stroke-width="0.8"/>';
                $out .= '<rect x="' . ($x + 3) . '" y="' . ($y + 3) . '" width="' . ($w - 6) . '" height="' . ($h - 6) . '" rx="3"';
                $out .= ' fill="none" stroke="' . $color . '" stroke-width="0.5"/>';
                break;
            default: // rect / parallelogram
                $out .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="6"';
                $out .= ' fill="' . $fill . '" stroke="' . $color . '" stroke-width="0.8"/>';
        }

        $textY = $showDuration && !empty($node['duration_minutes']) ? $cy - 7 : $cy;
        $out .= '<text x="' . $cx . '" y="' . $textY . '"';
        $out .= ' text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="500" fill="' . $color . '">';
        $out .= htmlspecialchars($title, ENT_XML1);
        $out .= '</text>';

        if ($showDuration && !empty($node['duration_minutes'])) {
            $out .= '<text x="' . $cx . '" y="' . ($cy + 10) . '"';
            $out .= ' text-anchor="middle" dominant-baseline="central" font-size="9" fill="' . $color . '" opacity="0.7">';
            $out .= htmlspecialchars($node['duration_minutes'] . ' ph', ENT_XML1);
            $out .= '</text>';
        }

        $out .= '</g>';
        return $out;
    }
}
