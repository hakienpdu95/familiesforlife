/**
 * resources/js/modules/echarts.js
 *
 * ECharts 6 — lazy widget bundle cho dashboard & báo cáo.
 * Chỉ import những chart type + component cần dùng để giảm bundle size.
 *
 * Expose: window.ECharts (echarts instance)
 * Event : document 'echarts:ready' — fire sau khi module execute xong
 *
 * Blade: @vite(['resources/js/modules/echarts.js'], 'build/backend')
 */

import * as echarts from 'echarts/core';

import {
    LineChart,
    BarChart,
    PieChart,
    FunnelChart,
    RadarChart,
} from 'echarts/charts';

import {
    GridComponent,
    TooltipComponent,
    LegendComponent,
    TitleComponent,
    DataZoomComponent,
    MarkLineComponent,
    RadarComponent,
} from 'echarts/components';

import { CanvasRenderer } from 'echarts/renderers';

echarts.use([
    LineChart, BarChart, PieChart, FunnelChart, RadarChart,
    GridComponent, TooltipComponent, LegendComponent,
    TitleComponent, DataZoomComponent, MarkLineComponent, RadarComponent,
    CanvasRenderer,
]);

window.ECharts = echarts;

// Notify listeners that ECharts is ready
document.dispatchEvent(new CustomEvent('echarts:ready'));
