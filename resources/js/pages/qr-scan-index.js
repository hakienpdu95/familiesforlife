import { t } from '../shared/i18n.js';

const esc = (v) => (v == null ? '' : String(v)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;'));

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const readJson = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
};

const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';

const charts = [];

const mount = (id, option) => {
    const el = document.getElementById(id);
    if (!el) return;
    const chart = window.ECharts.init(el, isDark() ? 'dark' : null, { renderer: 'canvas' });
    chart.setOption({ backgroundColor: 'transparent', ...option });
    charts.push(chart);
};

const axisStyle = { axisTick: { show: false }, axisLine: { lineStyle: { opacity: 0.3 } } };
const valueAxis = { type: 'value', minInterval: 1, splitLine: { lineStyle: { opacity: 0.35 } } };

function renderCharts() {
    const stats = readJson('qr-scan-stats');
    if (!stats || !window.ECharts) return;

    const { trend, labels } = stats;
    const dayLabels = trend.dates.map((d) => { const [, m, day] = d.split('-'); return `${day}/${m}`; });

    mount('qr-scan-trend', {
        tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
        legend: { bottom: 0, icon: 'circle', itemWidth: 8, itemHeight: 8, textStyle: { fontSize: 11 } },
        grid: { left: 4, right: 8, top: 12, bottom: 34, containLabel: true },
        xAxis: { type: 'category', data: dayLabels, ...axisStyle },
        yAxis: valueAxis,
        dataZoom: trend.dates.length > 45 ? [{ type: 'inside' }] : [],
        series: [
            ...trend.series.map((s) => ({
                name: s.label,
                type: 'bar',
                stack: 'scans',
                barMaxWidth: 26,
                itemStyle: { color: stats.sourceColors[s.key] || stats.sourceColors.unknown },
                emphasis: { focus: 'series' },
                data: s.data,
            })),
            {
                name: labels.visitors,
                type: 'line',
                smooth: true,
                symbol: 'circle',
                symbolSize: 5,
                itemStyle: { color: '#f59e0b' },
                lineStyle: { width: 2 },
                data: trend.visitors,
            },
        ],
    });

    mount('qr-scan-sources', {
        tooltip: { trigger: 'item', formatter: (p) => `${p.name}<br><b>${p.value}</b> (${p.percent}%)` },
        series: [{
            type: 'pie',
            radius: ['58%', '88%'],
            avoidLabelOverlap: true,
            label: { show: false },
            itemStyle: { borderColor: isDark() ? '#1d232a' : '#fff', borderWidth: 2 },
            data: stats.sources.map((s) => ({ name: s.label, value: s.total, itemStyle: { color: s.color } })),
        }],
    });

    const peakColor = (values) => {
        const max = Math.max(...values);
        return values.map((v) => ({ value: v, itemStyle: { color: v === max && max > 0 ? '#1d4ed8' : '#93c5fd', borderRadius: [3, 3, 0, 0] } }));
    };

    mount('qr-scan-hours', {
        tooltip: { trigger: 'axis', formatter: (p) => `${p[0].axisValue}:00 – ${p[0].axisValue}:59<br><b>${p[0].value}</b> ${labels.scans}` },
        grid: { left: 4, right: 4, top: 12, bottom: 4, containLabel: true },
        xAxis: { type: 'category', data: Array.from({ length: 24 }, (_, h) => String(h).padStart(2, '0')), ...axisStyle, axisLabel: { interval: 2, fontSize: 10 } },
        yAxis: valueAxis,
        series: [{ type: 'bar', barMaxWidth: 16, data: peakColor(stats.hours) }],
    });

    mount('qr-scan-weekdays', {
        tooltip: { trigger: 'axis', formatter: (p) => `${p[0].axisValue}<br><b>${p[0].value}</b> ${labels.scans}` },
        grid: { left: 4, right: 4, top: 12, bottom: 4, containLabel: true },
        xAxis: { type: 'category', data: stats.weekdayLabels.map((l) => l.replace(/^Thứ /, 'T').replace(/^Chủ nhật$/, 'CN')), ...axisStyle, axisLabel: { fontSize: 10 } },
        yAxis: valueAxis,
        series: [{ type: 'bar', barMaxWidth: 22, data: peakColor(stats.weekdays) }],
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => charts.forEach((c) => c.resize()), 120);
    }, { passive: true });
}

function renderHistory() {
    const el = document.getElementById('qr-scan-history');
    if (!el || !window.Tabulator) return;

    const params = JSON.parse(el.dataset.params || '{}');
    const badges = JSON.parse(el.dataset.sourceBadges || '{}');
    const showTarget = el.dataset.showTarget !== '0';
    Object.keys(params).forEach((k) => (params[k] == null || params[k] === '') && delete params[k]);

    new window.Tabulator(el, {
        ajaxURL: el.dataset.url,
        ajaxParams: params,
        ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
        ajaxError: (error) => console.error('[qr-scan] API error', error),
        pagination: true,
        paginationMode: 'remote',
        paginationSize: 25,
        paginationSizeSelector: [25, 50, 100],
        paginationCounter: 'rows',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        locale: 'vi-VN',
        langs: {
            'vi-VN': {
                pagination: {
                    page_size: t('Dòng/trang'), page_title: t('Trang'),
                    first: '«', last: '»', prev: '‹', next: '›',
                    first_title: t('Trang đầu'), last_title: t('Trang cuối'),
                    prev_title: t('Trang trước'), next_title: t('Trang sau'),
                    counter: { showing: '', of: t('trong'), rows: t('dòng'), pages: t('trang') },
                },
            },
        },
        columns: [
            {
                title: t('Thời gian'), field: 'scanned_at', width: 165, headerSort: false,
                formatter: (c) => {
                    const d = c.getRow().getData();
                    return `<div class="leading-tight"><span class="text-xs tabular-nums whitespace-nowrap">${esc(d.scanned_at)}</span><br><span class="font-mono text-[10px] text-base-content/40" title="${esc(t('Mã lượt quét'))}">#${esc((d.scan_id || '').slice(0, 8))}</span></div>`;
                },
            },
            {
                title: t('Mã QR'), field: 'qr_code', width: 105, headerSort: false, visible: showTarget,
                formatter: (c) => {
                    const d = c.getRow().getData();
                    return d.qr_code ? `<a href="${esc(d.qr_url)}" target="_blank" rel="noopener" class="font-mono text-sm font-semibold link link-primary">${esc(d.qr_code)}</a>` : EMPTY;
                },
            },
            {
                title: t('Đối tượng'), field: 'title', minWidth: 180, headerSort: false, visible: showTarget,
                formatter: (c) => {
                    const d = c.getRow().getData();
                    return `<div class="text-xs text-base-content/50">${esc(d.kind)}</div><div class="text-sm font-medium">${esc(d.title)}</div>`;
                },
            },
            {
                title: t('Ứng dụng quét'), field: 'app_label', width: 175, headerSort: false,
                formatter: (c) => {
                    const d = c.getRow().getData();
                    const tag = d.in_app ? `<span class="badge badge-warning badge-soft badge-xs">${t('Trong app')}</span>` : `<span class="badge badge-ghost badge-xs">${t('Trình duyệt')}</span>`;
                    return `<div class="text-sm font-medium">${esc(d.app_label)}</div>${tag}`;
                },
            },
            {
                title: t('Thiết bị'), field: 'device', width: 165, headerSort: false,
                formatter: (c) => {
                    const d = c.getRow().getData();
                    return `<div class="text-sm">${esc(d.brand)}</div><div class="text-xs text-base-content/50">${esc(d.os_label)} · ${esc(d.device)}</div>`;
                },
            },
            {
                title: t('Nguồn'), field: 'source_label', width: 160, headerSort: false,
                formatter: (c) => {
                    const d = c.getRow().getData();
                    return `<span class="badge badge-sm whitespace-nowrap ${badges[d.source] || 'badge-ghost'}">${esc(d.source_label)}</span>`;
                },
            },
            { title: t('Ngôn ngữ'), field: 'locale', width: 85, hozAlign: 'center', headerSort: false, formatter: (c) => (c.getValue() ? esc(c.getValue()) : EMPTY) },
            {
                title: t('Lượt'), field: 'is_repeat', width: 95, hozAlign: 'center', headerSort: false,
                formatter: (c) => (c.getValue()
                    ? `<span class="badge badge-ghost badge-sm" title="${esc(t('Cùng khách quét lại mã này trong 30 phút'))}">${t('Quét lại')}</span>`
                    : `<span class="badge badge-success badge-soft badge-sm">${t('Mới')}</span>`),
            },
            { title: t('Khách'), field: 'visitor', width: 95, headerSort: false, formatter: (c) => `<span class="font-mono text-xs text-base-content/60" title="${esc(t('Mã khách ẩn danh — mỗi trình duyệt/ứng dụng là một khách'))}">${esc(c.getValue())}</span>` },
        ],
        placeholder: `<div class="py-12 text-center opacity-40"><p class="text-sm">${t('Chưa có lượt quét nào')}</p></div>`,
    });
}

const boot = () => {
    renderHistory();
    if (window.ECharts) renderCharts();
    else document.addEventListener('echarts:ready', renderCharts, { once: true });
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
else boot();
