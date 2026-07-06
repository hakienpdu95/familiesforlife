/**
 * Modules/Report/resources/assets/js/report.js
 * Alpine components for Report module Phase 1
 */

/* ── Shared helpers ──────────────────────────────────────────────────── */
function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtVnd(v) {
    if (!v) return '0 ₫';
    const n = parseFloat(v);
    if (n >= 1e9) return (n / 1e9).toFixed(1) + ' tỷ ₫';
    if (n >= 1e6) return (n / 1e6).toFixed(1) + ' tr ₫';
    return n.toLocaleString('vi-VN') + ' ₫';
}

function fmtPct(v) { return (parseFloat(v) || 0).toFixed(1) + '%'; }

const STATUS_COLORS = {
    active:     '#10b981',
    probation:  '#3b82f6',
    on_leave:   '#f59e0b',
    resigned:   '#ef4444',
    terminated: '#9ca3af',
};

const LEAVE_COLORS = {
    annual:    '#3b82f6',
    sick:      '#f59e0b',
    maternity: '#ec4899',
    unpaid:    '#9ca3af',
    other:     '#6b7280',
};

function initTsRemote(el, url, placeholder, onChange) {
    if (!el || !window.TomSelect) return null;
    return new window.TomSelect(el, {
        dropdownParent: 'body',
        placeholder,
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        load(query, callback) {
            fetch(url + '?q=' + encodeURIComponent(query), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            }).then(r => r.json()).then(callback).catch(() => callback([]));
        },
        onChange,
        render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
        plugins: ['clear_button'],
    });
}

/* ── HR: Headcount ───────────────────────────────────────────────────── */
document.addEventListener('alpine:init', function () {

    Alpine.data('reportHrHeadcount', function () {
        var chartTrend = null;
        var chartDept  = null;
        var fpInst     = null;
        var branchTs   = null;
        var deptTs     = null;

        return {
            loading:  false,
            error:    null,
            summary:  {},
            trend:    [],
            byDepartment: [],
            hires:        [],
            activePreset: 'month6',
            filters: { date_from: '', date_to: '', branch_id: '', department_id: '' },

            setPreset(preset) {
                var now = new Date();
                this.activePreset = preset;
                if (preset === 'month') {
                    this.filters.date_from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                } else if (preset === 'quarter') {
                    var q = Math.floor(now.getMonth() / 3);
                    this.filters.date_from = new Date(now.getFullYear(), q * 3, 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                } else if (preset === 'year') {
                    this.filters.date_from = new Date(now.getFullYear(), 0, 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                }
                this.load();
            },

            init() {
                var self = this;
                var now  = new Date();
                this.filters.date_from = new Date(now.getFullYear(), now.getMonth() - 5, 1).toISOString().slice(0,10);
                this.filters.date_to   = now.toISOString().slice(0,10);

                document.addEventListener('DOMContentLoaded', function () {
                    self._setup();
                    self.load();
                }, { once: true });
            },

            _setup() {
                var self = this;
                if (window.initDateRangePicker) {
                    fpInst = window.initDateRangePicker('#rpt-hc-date', {
                        disableMobile: true,
                        onChange(dates) {
                            if (dates.length === 2) {
                                self.filters.date_from = dates[0].toISOString().slice(0,10);
                                self.filters.date_to   = dates[1].toISOString().slice(0,10);
                                self.load();
                            }
                        },
                    });
                }
                if (window.TomSelect) {
                    var branchEl = document.getElementById('rpt-hc-branch');
                    var deptEl   = document.getElementById('rpt-hc-dept');
                    if (branchEl) branchTs = initTsRemote(branchEl, window.BRANCH_OPTIONS_URL, 'Tất cả chi nhánh', v => { self.filters.branch_id = v || ''; self.load(); });
                    if (deptEl)   deptTs   = initTsRemote(deptEl,   window.DEPT_OPTIONS_URL,   'Tất cả phòng ban', v => { self.filters.department_id = v || ''; self.load(); });
                }
            },

            async load() {
                this.loading = true;
                this.error   = null;
                try {
                    var params = new URLSearchParams();
                    if (this.filters.date_from)     params.set('date_from',     this.filters.date_from);
                    if (this.filters.date_to)       params.set('date_to',       this.filters.date_to);
                    if (this.filters.branch_id)     params.set('branch_id',     this.filters.branch_id);
                    if (this.filters.department_id) params.set('department_id', this.filters.department_id);

                    var res = await fetch(window.API_URL + '?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    this.summary = data.summary  || {};
                    this.trend   = data.trend    || [];
                    this.byDepartment = data.by_department || [];
                    this.hires   = data.new_hires || [];
                    this._renderCharts(data);
                } catch (e) {
                    this.error = 'Không tải được dữ liệu. Vui lòng thử lại.';
                } finally {
                    this.loading = false;
                }
            },

            _renderCharts(data) {
                this._renderTrend(data.trend || []);
                this._renderDept(data.by_department || []);
            },

            _renderTrend(trend) {
                var el = document.getElementById('chart-headcount-trend');
                if (!el || !window.ECharts) return;
                if (!chartTrend) chartTrend = window.ECharts.init(el);
                chartTrend.setOption({
                    tooltip: { trigger: 'axis' },
                    legend: { data: ['Tuyển dụng', 'Nghỉ việc', 'Net'], bottom: 0 },
                    grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
                    xAxis: { type: 'category', data: trend.map(t => t.period) },
                    yAxis: { type: 'value' },
                    series: [
                        { name: 'Tuyển dụng', type: 'bar', data: trend.map(t => t.hired),    itemStyle: { color: '#10b981' } },
                        { name: 'Nghỉ việc',  type: 'bar', data: trend.map(t => t.resigned), itemStyle: { color: '#ef4444' } },
                        { name: 'Net',        type: 'line', data: trend.map(t => t.net),      lineStyle: { color: '#3b82f6' }, itemStyle: { color: '#3b82f6' } },
                    ],
                });
            },

            _renderDept(depts_list) {
                var el = document.getElementById('chart-headcount-dept');
                if (!el || !window.ECharts) return;
                if (!chartDept) chartDept = window.ECharts.init(el);
                var top10 = depts_list.slice(0, 10);
                chartDept.setOption({
                    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
                    grid: { left: '3%', right: '8%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'value' },
                    yAxis: { type: 'category', data: top10.map(d => d.name).reverse(), axisTick: { show: false } },
                    series: [{
                        type: 'bar', data: top10.map(d => d.count).reverse(),
                        itemStyle: { color: '#3b82f6', borderRadius: [0, 4, 4, 0] },
                        label: { show: true, position: 'right' },
                    }],
                });
            },
        };
    });

    /* ── HR: Leave ───────────────────────────────────────────────────── */
    Alpine.data('reportHrLeave', function () {
        var chartType    = null;
        var chartMonthly = null;
        var branchTs     = null;
        var deptTs       = null;

        var LEAVE_TYPE_LABELS = {
            annual: 'Phép năm', sick: 'Ốm đau', maternity: 'Thai sản',
            unpaid: 'Không lương', other: 'Khác',
        };

        return {
            loading:       false,
            error:         null,
            stats:         {},
            byType:        [],
            monthly:       [],
            topRequesters: [],
            filters: { year: new Date().getFullYear(), leave_type: '', branch_id: '', department_id: '' },

            get hasFilters() {
                return !!(this.filters.leave_type || this.filters.branch_id || this.filters.department_id);
            },

            get activeChips() {
                var chips = [];
                if (this.filters.leave_type)    chips.push({ key: 'leave_type',    label: LEAVE_TYPE_LABELS[this.filters.leave_type] || this.filters.leave_type });
                if (this.filters.branch_id)     chips.push({ key: 'branch_id',     label: 'Chi nhánh đã chọn' });
                if (this.filters.department_id) chips.push({ key: 'department_id', label: 'Phòng ban đã chọn' });
                return chips;
            },

            removeChip(key) {
                this.filters[key] = '';
                if (key === 'branch_id'     && branchTs) branchTs.clear();
                if (key === 'department_id' && deptTs)   deptTs.clear();
                this.load();
            },

            reset() {
                this.filters.leave_type    = '';
                this.filters.branch_id     = '';
                this.filters.department_id = '';
                if (branchTs) branchTs.clear();
                if (deptTs)   deptTs.clear();
                this.load();
            },

            init() {
                var self = this;
                document.addEventListener('DOMContentLoaded', function () {
                    self._setup();
                    self.load();
                }, { once: true });
            },

            _setup() {
                var self = this;
                if (window.TomSelect) {
                    var branchEl = document.getElementById('rpt-lv-branch');
                    var deptEl   = document.getElementById('rpt-lv-dept');
                    if (branchEl) branchTs = initTsRemote(branchEl, window.BRANCH_OPTIONS_URL, 'Tất cả chi nhánh', v => { self.filters.branch_id = v || ''; self.load(); });
                    if (deptEl)   deptTs   = initTsRemote(deptEl,   window.DEPT_OPTIONS_URL,   'Tất cả phòng ban', v => { self.filters.department_id = v || ''; self.load(); });
                }
            },

            async load() {
                this.loading = true;
                this.error   = null;
                try {
                    var params = new URLSearchParams({ year: this.filters.year });
                    if (this.filters.leave_type)    params.set('leave_type',    this.filters.leave_type);
                    if (this.filters.branch_id)     params.set('branch_id',     this.filters.branch_id);
                    if (this.filters.department_id) params.set('department_id', this.filters.department_id);

                    var res = await fetch(window.API_URL + '?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    this.stats         = data.summary        || {};
                    this.byType        = data.by_type        || [];
                    this.monthly       = data.monthly_trend  || [];
                    this.topRequesters = data.top_requesters || [];
                    this._renderCharts(data);
                } catch (e) {
                    this.error = 'Không tải được dữ liệu.';
                } finally {
                    this.loading = false;
                }
            },

            _renderCharts(data) {
                this._renderType(data.by_type || []);
                this._renderMonthly(data.monthly_trend || []);
            },

            _renderType(types) {
                var el = document.getElementById('chart-leave-type');
                if (!el || !window.ECharts) return;
                if (!chartType) chartType = window.ECharts.init(el);
                chartType.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ngày ({d}%)' },
                    legend: { orient: 'vertical', left: 'left' },
                    series: [{
                        type: 'pie', radius: ['45%', '75%'],
                        label: { show: false },
                        data: types.map(t => ({
                            name: t.leave_type, value: parseFloat(t.days) || 0,
                            itemStyle: { color: LEAVE_COLORS[t.leave_type] || '#6b7280' },
                        })),
                    }],
                });
            },

            _renderMonthly(monthly) {
                var el = document.getElementById('chart-leave-monthly');
                if (!el || !window.ECharts) return;
                if (!chartMonthly) chartMonthly = window.ECharts.init(el);
                chartMonthly.setOption({
                    tooltip: { trigger: 'axis' },
                    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'category', data: monthly.map(m => m.month) },
                    yAxis: { type: 'value', name: 'Ngày' },
                    series: [{
                        type: 'bar', data: monthly.map(m => parseFloat(m.days_taken) || 0),
                        itemStyle: { color: '#f59e0b', borderRadius: [4, 4, 0, 0] },
                    }],
                });
            },
        };
    });

    /* ── Sales: Pipeline ─────────────────────────────────────────────── */
    Alpine.data('reportSalesPipeline', function () {
        var _funnelChart = null;
        var _trendChart  = null;
        var _fpFrom      = null;
        var _fpTo        = null;
        var _tomSource   = null;

        return {
            loading:    false,
            filters:    { date_from: '', date_to: '', source_id: '' },
            summary:    {},
            bySource:   [],
            byAssignee: [],

            init() {
                this._initFlatpickr();
                this._initTomSelect();
                this.load();
            },

            _initFlatpickr() {
                var self = this;
                var opts = {
                    dateFormat: 'd/m/Y',
                    allowInput: false,
                };
                if (window.flatpickr) {
                    _fpFrom = flatpickr(this.$refs.dateFrom, Object.assign({}, opts, {
                        onChange: function (dates, dateStr) { self.filters.date_from = dateStr; },
                    }));
                    _fpTo = flatpickr(this.$refs.dateTo, Object.assign({}, opts, {
                        onChange: function (dates, dateStr) { self.filters.date_to = dateStr; },
                    }));
                }
            },

            _initTomSelect() {
                var self = this;
                var el   = this.$refs.sourceSelect;
                if (!el || !window.TomSelect) return;
                _tomSource = new TomSelect(el, {
                    valueField:       'id',
                    labelField:       'name',
                    searchField:      ['name'],
                    placeholder:      'Tất cả nguồn...',
                    allowEmptyOption: true,
                    load: function (query, callback) {
                        var url = document.querySelector('[data-source-options-url]')?.dataset.sourceOptionsUrl;
                        if (!url) return callback([]);
                        fetch(url + (query ? '?q=' + encodeURIComponent(query) : ''), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (data) { callback(Array.isArray(data) ? data : (data.data || [])); })
                        .catch(function () { callback([]); });
                    },
                    onChange: function (val) { self.filters.source_id = val; },
                });
            },

            applyFilters() { this.load(); },

            resetFilters() {
                this.filters = { date_from: '', date_to: '', source_id: '' };
                if (_fpFrom) _fpFrom.clear();
                if (_fpTo)   _fpTo.clear();
                if (_tomSource) _tomSource.clear();
                this.load();
            },

            async load() {
                this.loading = true;
                try {
                    var params = new URLSearchParams();
                    if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                    if (this.filters.date_to)   params.set('date_to',   this.filters.date_to);
                    if (this.filters.source_id) params.set('source_id', this.filters.source_id);

                    var url = window.API_URL + (params.toString() ? '?' + params.toString() : '');
                    var res  = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    var data = await res.json();

                    this.summary    = data.summary     || {};
                    this.bySource   = data.by_source   || [];
                    this.byAssignee = data.by_assignee || [];

                    var self = this;
                    this.$nextTick(function () {
                        self._renderFunnelChart(data.funnel || []);
                        self._renderTrendChart(data.trend   || []);
                    });
                } catch (e) {
                    console.error('[reportSalesPipeline] load error', e);
                } finally {
                    this.loading = false;
                }
            },

            _renderFunnelChart(funnelData) {
                var el = document.getElementById('chart-pipeline-funnel');
                if (!el || !window.ECharts) return;
                if (!_funnelChart) {
                    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    _funnelChart = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                    new ResizeObserver(function () { if (_funnelChart) _funnelChart.resize(); }).observe(el);
                }
                if (!funnelData.length) { _funnelChart.clear(); return; }
                var maxVal = funnelData[0] ? funnelData[0].count || 1 : 1;
                var colors = ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#ddd6fe','#ede9fe'];
                _funnelChart.setOption({
                    tooltip: {
                        trigger: 'item',
                        formatter: function (p) {
                            return '<b>' + p.name + '</b><br/>Leads: ' + (p.value || 0).toLocaleString('vi-VN') + '<br/>Tỷ lệ: ' + (p.data.rate || '—') + '%';
                        },
                    },
                    grid: { left: 140, right: 60, top: 10, bottom: 10 },
                    xAxis: { type: 'value', show: false, max: maxVal },
                    yAxis: {
                        type: 'category',
                        data: funnelData.map(function (d) { return d.stage_name; }),
                        inverse: true,
                        axisLabel: { fontSize: 12, color: '#6b7280', width: 130, overflow: 'truncate' },
                        axisTick: { show: false },
                        axisLine: { show: false },
                    },
                    series: [{
                        type: 'bar',
                        data: funnelData.map(function (d, i) {
                            return { value: d.count, rate: d.rate, stage_name: d.stage_name,
                                     itemStyle: { color: colors[i % colors.length], borderRadius: [0, 6, 6, 0] } };
                        }),
                        barMaxWidth: 36,
                        label: {
                            show: true, position: 'right', fontSize: 12,
                            formatter: function (p) { return (p.value || 0).toLocaleString('vi-VN'); },
                        },
                        emphasis: { focus: 'self' },
                    }],
                });
            },

            _renderTrendChart(trendData) {
                var el = document.getElementById('chart-pipeline-trend');
                if (!el || !window.ECharts) return;
                if (!_trendChart) {
                    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    _trendChart = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                    new ResizeObserver(function () { if (_trendChart) _trendChart.resize(); }).observe(el);
                }
                if (!trendData.length) { _trendChart.clear(); return; }
                _trendChart.setOption({
                    tooltip: {
                        trigger: 'axis',
                        formatter: function (params) {
                            var html = '<div style="font-weight:600;margin-bottom:4px">' + (params[0] ? params[0].axisValue : '') + '</div>';
                            params.forEach(function (p) {
                                html += '<div style="display:flex;align-items:center;gap:6px;margin-top:2px">' + p.marker + '<span>' + p.seriesName + ':</span><strong>' + (p.value || 0).toLocaleString('vi-VN') + '</strong></div>';
                            });
                            return html;
                        },
                    },
                    legend: { data: ['Lead mới', 'Win', 'Lose'], bottom: 0, textStyle: { fontSize: 11 } },
                    grid: { left: 50, right: 15, top: 15, bottom: 40 },
                    xAxis: {
                        type: 'category',
                        data: trendData.map(function (d) { return d.period; }),
                        axisLabel: { fontSize: 11, color: '#6b7280' },
                        axisTick: { show: false },
                    },
                    yAxis: {
                        type: 'value', minInterval: 1,
                        splitLine: { lineStyle: { type: 'dashed', color: '#f0f0f0' } },
                        axisLabel: { fontSize: 11, color: '#6b7280' },
                    },
                    series: [
                        { name: 'Lead mới', type: 'line', data: trendData.map(function (d) { return d.new_leads || 0; }), smooth: 0.4, symbol: 'circle', symbolSize: 5, lineStyle: { color: '#6366f1', width: 2 }, itemStyle: { color: '#6366f1' }, areaStyle: { color: 'rgba(99,102,241,0.08)' } },
                        { name: 'Win',      type: 'line', data: trendData.map(function (d) { return d.won       || 0; }), smooth: 0.4, symbol: 'circle', symbolSize: 5, lineStyle: { color: '#22c55e', width: 2 }, itemStyle: { color: '#22c55e' } },
                        { name: 'Lose',     type: 'line', data: trendData.map(function (d) { return d.lost      || 0; }), smooth: 0.4, symbol: 'circle', symbolSize: 5, lineStyle: { color: '#ef4444', width: 2, type: 'dashed' }, itemStyle: { color: '#ef4444' } },
                    ],
                });
            },

            formatVnd(value) {
                if (value === undefined || value === null) return '—';
                var n = Number(value);
                if (isNaN(n)) return '—';
                if (n >= 1000000000) return (n / 1000000000).toFixed(1).replace('.', ',') + ' tỷ';
                if (n >= 1000000)    return (n / 1000000).toFixed(1).replace('.', ',') + ' triệu';
                return n.toLocaleString('vi-VN') + ' đ';
            },
        };
    });

    /* ── Sales: Conversion ───────────────────────────────────────────── */
    Alpine.data('reportSalesConversion', function () {
        var chartSource = null;
        var chartCohort = null;
        var fpInst      = null;

        return {
            loading:      false,
            error:        null,
            summary:      {},
            bands:        {},
            sources:      [],
            cohort:       [],
            activePreset: 'year',
            filters: { date_from: '', date_to: '' },

            fmt(v) {
                if (v === undefined || v === null) return '—';
                return parseInt(v).toLocaleString('vi-VN');
            },

            setPreset(preset) {
                var now = new Date();
                this.activePreset = preset;
                if (preset === 'month') {
                    this.filters.date_from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                } else if (preset === 'quarter') {
                    var q = Math.floor(now.getMonth() / 3);
                    this.filters.date_from = new Date(now.getFullYear(), q * 3, 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                } else if (preset === 'year') {
                    this.filters.date_from = new Date(now.getFullYear(), 0, 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                }
                this.load();
            },

            clearDate() {
                this.filters.date_from = '';
                this.filters.date_to   = '';
                this.activePreset      = '';
                if (fpInst) fpInst.clear();
                this.load();
            },

            init() {
                var self = this;
                var now  = new Date();
                this.filters.date_from = new Date(now.getFullYear(), 0, 1).toISOString().slice(0,10);
                this.filters.date_to   = now.toISOString().slice(0,10);

                document.addEventListener('DOMContentLoaded', function () {
                    if (window.initDateRangePicker) {
                        fpInst = window.initDateRangePicker('#filter-date-range', {
                            disableMobile: true,
                            onChange(dates) {
                                if (dates.length === 2) {
                                    self.filters.date_from = dates[0].toISOString().slice(0,10);
                                    self.filters.date_to   = dates[1].toISOString().slice(0,10);
                                    self.activePreset      = '';
                                    self.load();
                                }
                            },
                        });
                    }
                    self.load();
                }, { once: true });
            },

            async load() {
                this.loading = true;
                this.error   = null;
                try {
                    var params = new URLSearchParams();
                    if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                    if (this.filters.date_to)   params.set('date_to',   this.filters.date_to);

                    var res = await fetch(window.API_URL + '?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();

                    var o = data.overall || {};
                    this.summary = {
                        total_leads:         o.total_leads              || 0,
                        converted:           o.converted_to_customer    || 0,
                        conversion_rate:     o.conversion_rate_pct      || 0,
                        avg_days_to_convert: o.avg_days_to_convert      || 0,
                        period_label:        '',
                    };

                    var bandMap = {};
                    (data.by_score_band || []).forEach(function (b) {
                        var name = (b.band || '').toLowerCase();
                        var key  = name.indexOf('hot')  >= 0 ? 'hot'
                                 : name.indexOf('warm') >= 0 ? 'warm'
                                 : 'cold';
                        bandMap[key] = {
                            total_leads: b.leads     || 0,
                            converted:   b.converted || 0,
                            rate:        b.rate_pct  || 0,
                            avg_days:    b.avg_days  || 0,
                        };
                    });
                    this.bands  = bandMap;
                    this.sources = data.by_source      || [];
                    this.cohort  = data.monthly_cohort || [];
                    this._renderCharts(data);
                } catch (e) {
                    this.error = 'Không tải được dữ liệu.';
                } finally {
                    this.loading = false;
                }
            },

            _renderCharts(data) {
                this._renderSource(data.by_source || []);
                this._renderCohort(data.monthly_cohort || []);
            },

            _renderSource(sources) {
                var el = document.getElementById('chart-conversion-source');
                if (!el || !window.ECharts) return;
                if (!chartSource) chartSource = window.ECharts.init(el);
                chartSource.setOption({
                    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
                    legend: { data: ['Tổng leads', 'Chuyển đổi'], bottom: 0 },
                    grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
                    xAxis: { type: 'category', data: sources.map(s => s.label) },
                    yAxis: { type: 'value' },
                    series: [
                        { name: 'Tổng leads',  type: 'bar', data: sources.map(s => s.leads),     itemStyle: { color: '#93c5fd' } },
                        { name: 'Chuyển đổi',  type: 'bar', data: sources.map(s => s.converted), itemStyle: { color: '#10b981' } },
                    ],
                });
            },

            _renderCohort(cohort) {
                var el = document.getElementById('chart-conversion-cohort');
                if (!el || !window.ECharts) return;
                if (!chartCohort) chartCohort = window.ECharts.init(el);
                chartCohort.setOption({
                    tooltip: { trigger: 'axis' },
                    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'category', data: cohort.map(c => c.cohort_month) },
                    yAxis: { type: 'value', name: 'Tỷ lệ (%)' },
                    series: [{
                        type: 'line', smooth: true,
                        data: cohort.map(c => c.rate_30d_pct),
                        lineStyle: { color: '#8b5cf6' },
                        itemStyle: { color: '#8b5cf6' },
                        areaStyle: { color: 'rgba(139,92,246,0.1)' },
                    }],
                });
            },
        };
    });

    /* ── HR: Recruitment ─────────────────────────────────────────────── */
    Alpine.data('reportHrRecruitment', function () {
        var chartFunnel  = null;
        var chartMonthly = null;
        var deptTs       = null;

        return {
            loading:      false,
            error:        false,
            errorMessage: '',
            activePreset: '',
            summary:  {},
            funnel:   [],
            sources:  [],
            openJobs: [],
            monthly:  [],
            filters: { date_from: '', date_to: '', department_id: '' },

            setPreset(preset) {
                var now = new Date();
                this.activePreset = preset;
                if (preset === 'month') {
                    this.filters.date_from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                } else if (preset === 'quarter') {
                    var q = Math.floor(now.getMonth() / 3);
                    this.filters.date_from = new Date(now.getFullYear(), q * 3, 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                } else if (preset === 'year') {
                    this.filters.date_from = new Date(now.getFullYear(), 0, 1).toISOString().slice(0,10);
                    this.filters.date_to   = now.toISOString().slice(0,10);
                }
                this.load();
            },

            reset() {
                this.filters.department_id = '';
                this.activePreset = '';
                if (deptTs) deptTs.clear();
                this.load();
            },

            init() {
                var self = this;
                var now  = new Date();
                this.filters.date_from = new Date(now.getFullYear(), now.getMonth() - 2, 1).toISOString().slice(0,10);
                this.filters.date_to   = now.toISOString().slice(0,10);
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.TomSelect) {
                        var el = document.getElementById('rpt-rc-dept');
                        if (el) deptTs = initTsRemote(el, window.DEPT_OPTIONS_URL, 'Tất cả phòng ban', v => { self.filters.department_id = v || ''; self.load(); });
                    }
                    if (window.initDateRangePicker) {
                        window.initDateRangePicker('#rpt-rc-date', { disableMobile: true,
                            onChange(d) { if (d.length===2) { self.filters.date_from=d[0].toISOString().slice(0,10); self.filters.date_to=d[1].toISOString().slice(0,10); self.load(); } } });
                    }
                    self.load();
                }, { once: true });
            },

            async load() {
                this.loading = true; this.error = false; this.errorMessage = '';
                try {
                    var p = new URLSearchParams();
                    if (this.filters.date_from)     p.set('date_from',     this.filters.date_from);
                    if (this.filters.date_to)       p.set('date_to',       this.filters.date_to);
                    if (this.filters.department_id) p.set('department_id', this.filters.department_id);
                    var res = await fetch(window.API_URL + '?' + p, { headers: {'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    this.summary  = data.summary               || {};
                    this.funnel   = data.funnel                || [];
                    this.sources  = data.by_source             || [];
                    this.openJobs = data.open_jobs             || [];
                    this.monthly  = data.monthly_applications  || [];
                    this._renderFunnel(this.funnel);
                    this._renderMonthly(this.monthly);
                } catch (e) {
                    this.error        = true;
                    this.errorMessage = 'Không tải được dữ liệu. Vui lòng thử lại.';
                }
                finally { this.loading = false; }
            },

            _renderFunnel(funnel) {
                var el = document.getElementById('chart-rc-funnel');
                if (!el || !window.ECharts) return;
                if (!chartFunnel) chartFunnel = window.ECharts.init(el);
                chartFunnel.setOption({
                    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
                    grid: { left: '3%', right: '8%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'value' },
                    yAxis: { type: 'category', data: funnel.map(f => f.label).reverse(), axisTick: {show:false} },
                    series: [{ type: 'bar', data: funnel.map(f => f.count).reverse(),
                        itemStyle: { color: '#3b82f6', borderRadius: [0,4,4,0] },
                        label: { show: true, position: 'right' } }],
                });
            },

            _renderMonthly(monthly) {
                var el = document.getElementById('chart-rc-monthly');
                if (!el || !window.ECharts) return;
                if (!chartMonthly) chartMonthly = window.ECharts.init(el);
                chartMonthly.setOption({
                    tooltip: { trigger: 'axis' },
                    legend: { data: ['Ứng tuyển', 'Tuyển được'], bottom: 0 },
                    grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
                    xAxis: { type: 'category', data: monthly.map(m => m.month) },
                    yAxis: { type: 'value' },
                    series: [
                        { name: 'Ứng tuyển',  type: 'bar', data: monthly.map(m => m.applications), itemStyle: { color: '#93c5fd' } },
                        { name: 'Tuyển được', type: 'bar', data: monthly.map(m => m.hires),         itemStyle: { color: '#10b981' } },
                    ],
                });
            },
        };
    });

    /* ── HR: Performance ─────────────────────────────────────────────── */
    Alpine.data('reportHrPerformance', function () {
        var chartDist   = null;
        var chartPeriod = null;
        var deptTs      = null;

        return {
            loading:        false,
            error:          null,
            summary:        {},
            distribution:   [],
            byDepartment:   [],
            topPerformers:  [],
            lowPerformers:  [],
            periodComp:     [],
            filters: { period: '', department_id: '' },

            init() {
                var self = this;
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.TomSelect) {
                        var el = document.getElementById('rpt-pf-dept');
                        if (el) deptTs = initTsRemote(el, window.DEPT_OPTIONS_URL, 'Tất cả phòng ban', v => { self.filters.department_id = v || ''; self.load(); });
                    }
                    self.load();
                }, { once: true });
            },

            async load() {
                this.loading = true; this.error = null;
                try {
                    var p = new URLSearchParams();
                    if (this.filters.period)        p.set('period',        this.filters.period);
                    if (this.filters.department_id) p.set('department_id', this.filters.department_id);
                    var res = await fetch(window.API_URL + '?' + p, { headers: {'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    this.summary       = data.summary            || {};
                    this.distribution  = data.score_distribution || [];
                    this.byDepartment  = data.by_department      || [];
                    this.topPerformers = data.top_performers     || [];
                    this.lowPerformers = data.low_performers     || [];
                    this.periodComp    = data.period_comparison  || [];
                    this._renderDist(this.distribution);
                    this._renderPeriod(this.periodComp);
                } catch (e) { this.error = 'Không tải được dữ liệu.'; }
                finally { this.loading = false; }
            },

            _renderDist(dist) {
                var el = document.getElementById('chart-pf-distribution');
                if (!el || !window.ECharts) return;
                if (!chartDist) chartDist = window.ECharts.init(el);
                var COLORS = { excellent: '#10b981', above_expected: '#3b82f6', meets: '#f59e0b', below: '#ef4444', poor: '#9ca3af' };
                chartDist.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                    legend: { orient: 'vertical', left: 'left' },
                    series: [{ type: 'pie', radius: ['45%','75%'], label:{show:false},
                        data: dist.map(d => ({ name: d.rating, value: d.count, itemStyle: { color: COLORS[d.rating] || '#6b7280' } })) }],
                });
            },

            _renderPeriod(periods) {
                var el = document.getElementById('chart-pf-period');
                if (!el || !window.ECharts) return;
                if (!chartPeriod) chartPeriod = window.ECharts.init(el);
                chartPeriod.setOption({
                    tooltip: { trigger: 'axis' },
                    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'category', data: periods.map(p => p.period) },
                    yAxis: { type: 'value', name: 'Điểm TB', min: 0, max: 5 },
                    series: [{ type: 'line', smooth: true,
                        data: periods.map(p => parseFloat(p.avg_score).toFixed(2)),
                        lineStyle: { color: '#3b82f6' }, itemStyle: { color: '#3b82f6' },
                        areaStyle: { color: 'rgba(59,130,246,0.1)' } }],
                });
            },
        };
    });

    /* ── Sales: Activity ─────────────────────────────────────────────── */
    Alpine.data('reportSalesActivity', function () {
        var chartType  = null;
        var chartDaily = null;

        return {
            loading:   false,
            error:     null,
            summary:   {},
            byAssignee:[],
            byType:    [],
            byDay:     [],
            filters: { date_from: '', date_to: '' },

            init() {
                var self = this;
                var now  = new Date();
                this.filters.date_from = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate()).toISOString().slice(0,10);
                this.filters.date_to   = now.toISOString().slice(0,10);
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.initDateRangePicker) {
                        window.initDateRangePicker('#rpt-sa-date', { disableMobile: true,
                            onChange(d) { if (d.length===2) { self.filters.date_from=d[0].toISOString().slice(0,10); self.filters.date_to=d[1].toISOString().slice(0,10); self.load(); } } });
                    }
                    self.load();
                }, { once: true });
            },

            async load() {
                this.loading = true; this.error = null;
                try {
                    var p = new URLSearchParams();
                    if (this.filters.date_from) p.set('date_from', this.filters.date_from);
                    if (this.filters.date_to)   p.set('date_to',   this.filters.date_to);
                    var res = await fetch(window.API_URL + '?' + p, { headers: {'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    this.summary    = data.summary     || {};
                    this.byAssignee = data.by_assignee || [];
                    this.byType     = data.by_type     || [];
                    this.byDay      = data.by_day      || [];
                    this._renderType(this.byType);
                    this._renderDaily(this.byDay);
                } catch (e) { this.error = 'Không tải được dữ liệu.'; }
                finally { this.loading = false; }
            },

            _renderType(types) {
                var el = document.getElementById('chart-sa-type');
                if (!el || !window.ECharts) return;
                if (!chartType) chartType = window.ECharts.init(el);
                var C = { call:'#3b82f6', email:'#10b981', meeting:'#f59e0b', demo:'#8b5cf6', note:'#9ca3af' };
                chartType.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                    legend: { orient: 'vertical', left: 'left' },
                    series: [{ type: 'pie', radius: ['45%','75%'], label:{show:false},
                        data: types.map(t => ({ name: t.type, value: t.count, itemStyle:{ color: C[t.type]||'#6b7280' } })) }],
                });
            },

            _renderDaily(days) {
                var el = document.getElementById('chart-sa-daily');
                if (!el || !window.ECharts) return;
                if (!chartDaily) chartDaily = window.ECharts.init(el);
                chartDaily.setOption({
                    tooltip: { trigger: 'axis' },
                    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'category', data: days.map(d => d.date), axisLabel: { rotate: 45 } },
                    yAxis: { type: 'value' },
                    series: [{ type: 'bar', data: days.map(d => d.count),
                        itemStyle: { color: '#3b82f6', borderRadius: [3,3,0,0] } }],
                });
            },
        };
    });

    /* ── KPI: Cycle ──────────────────────────────────────────────────── */
    Alpine.data('reportKpiCycle', function () {
        var chartDept = null;
        var deptTs    = null;

        return {
            loading:      false,
            error:        null,
            summary:      {},
            cycles:       [],
            distribution: [],
            byDept:       [],
            topList:      [],
            atRisk:       [],
            filters: { cycle_label: '', department_id: '' },

            init() {
                var self = this;
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.TomSelect) {
                        var el = document.getElementById('rpt-kc-dept');
                        if (el) deptTs = initTsRemote(el, window.DEPT_OPTIONS_URL, 'Tất cả phòng ban', v => { self.filters.department_id = v || ''; self.load(); });
                    }
                    self.loadCycles().then(() => self.load());
                }, { once: true });
            },

            reset() {
                this.filters.cycle_label   = '';
                this.filters.department_id = '';
                if (deptTs) deptTs.clear();
                this.load();
            },

            async loadCycles() {
                try {
                    var res = await fetch(window.API_URL + '?cycle_label=__cycles__', { headers: {'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                    if (res.ok) {
                        var data = await res.json();
                        this.cycles = data.available_cycles || [];
                        if (this.cycles.length && !this.filters.cycle_label) {
                            this.filters.cycle_label = this.cycles[0];
                        }
                    }
                } catch (e) {}
            },

            async load() {
                this.loading = true; this.error = null;
                try {
                    var p = new URLSearchParams();
                    if (this.filters.cycle_label)   p.set('cycle_label',   this.filters.cycle_label);
                    if (this.filters.department_id) p.set('department_id', this.filters.department_id);
                    var res = await fetch(window.API_URL + '?' + p, { headers: {'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    this.cycles       = data.available_cycles         || this.cycles;
                    this.summary      = data.summary                  || {};
                    this.distribution = data.achievement_distribution || [];
                    this.byDept       = data.by_department            || [];
                    this.topList      = data.top_performers           || [];
                    this.atRisk       = data.at_risk                  || [];
                    this._renderDept(this.byDept);
                } catch (e) { this.error = 'Không tải được dữ liệu.'; }
                finally { this.loading = false; }
            },

            fmtPct: fmtPct,

            _renderDept(depts) {
                var el = document.getElementById('chart-kc-dept');
                if (!el || !window.ECharts) return;
                if (!chartDept) chartDept = window.ECharts.init(el);
                chartDept.setOption({
                    tooltip: { trigger: 'axis', axisPointer:{type:'shadow'} },
                    grid: { left:'3%', right:'8%', bottom:'3%', containLabel:true },
                    xAxis: { type:'value', name:'Điểm weighted' },
                    yAxis: { type:'category', data: depts.map(d => d.name).reverse(), axisTick:{show:false} },
                    series: [{ type:'bar',
                        data: depts.map(d => parseFloat(d.avg_weighted_score||0).toFixed(1)).reverse(),
                        itemStyle: { color:'#8b5cf6', borderRadius:[0,4,4,0] },
                        label: { show:true, position:'right' } }],
                });
            },
        };
    });

    /* ── KPI: Snapshot History ───────────────────────────────────────── */
    Alpine.data('reportKpiSnapshot', function () {
        var chartTrend = null;

        return {
            loading:       false,
            error:         null,
            cycles:        [],
            selectedCycle: null,
            filters:       {},

            get currentDistribution() {
                var row = this.cycles.find(function (c) { return c.cycle_label === this.selectedCycle; }, this);
                if (!row || !row.score_distribution) return [];
                var dist   = row.score_distribution;
                var keys   = Object.keys(dist);
                var total  = keys.reduce(function (s, k) { return s + (dist[k] || 0); }, 0);
                var bands  = ['A', 'B', 'C', 'D'];
                return bands.map(function (b, i) {
                    var count = dist[keys[i]] || 0;
                    return { band: b, count: count, pct: total > 0 ? Math.round(count / total * 1000) / 10 : 0 };
                });
            },

            init() {
                var self = this;
                document.addEventListener('DOMContentLoaded', function () {
                    self.load();
                }, { once: true });
            },

            async load() {
                this.loading = true; this.error = null;
                try {
                    var res = await fetch(window.API_URL, { headers: {'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    this.cycles = data.cycles || [];
                    this._renderTrend(this.cycles);
                } catch (e) { this.error = 'Không tải được dữ liệu.'; }
                finally { this.loading = false; }
            },

            _renderTrend(cycles) {
                var el = document.getElementById('chart-ks-trend');
                if (!el || !window.ECharts) return;
                if (!chartTrend) chartTrend = window.ECharts.init(el);
                chartTrend.setOption({
                    tooltip: { trigger: 'axis' },
                    grid: { left:'3%', right:'4%', bottom:'3%', containLabel:true },
                    xAxis: { type:'category', data: cycles.map(c => c.cycle_label) },
                    yAxis: { type:'value', name:'Điểm TB', min:0, max:100 },
                    series: [{
                        type: 'line', smooth: true,
                        data: cycles.map(c => parseFloat(c.avg_kpi_score||0).toFixed(1)),
                        lineStyle: { color:'#8b5cf6' }, itemStyle: { color:'#8b5cf6' },
                        areaStyle: { color:'rgba(139,92,246,0.1)' },
                        markLine: { data: [{ type:'average', name:'TB' }] },
                    }],
                });
            },
        };
    });

    /* ── Project: Overview ───────────────────────────────────────────── */
    Alpine.data('reportProjectOverview', function () {
        var _statusChart = null;
        var _deptChart   = null;
        var _tomDept     = null;

        return {
            loading: false,
            filters: { status: '', dept_id: '' },
            summary: {},
            atRisk:  [],

            init() {
                this._initTomSelect();
                this.load();
            },

            _initTomSelect() {
                var self = this;
                var el   = document.getElementById('rpt-pj-dept');
                if (!el || !window.TomSelect) return;
                _tomDept = new TomSelect(el, {
                    valueField:       'id',
                    labelField:       'name',
                    searchField:      ['name'],
                    placeholder:      'Tất cả phòng ban...',
                    allowEmptyOption: true,
                    load: function (query, callback) {
                        var url = window.DEPT_OPTIONS_URL;
                        if (!url) return callback([]);
                        fetch(url + (query ? '?q=' + encodeURIComponent(query) : ''), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (data) { callback(Array.isArray(data) ? data : (data.data || [])); })
                        .catch(function () { callback([]); });
                    },
                    onChange: function (val) { self.filters.dept_id = val; },
                });
            },

            applyFilters() { this.load(); },

            resetFilters() {
                this.filters = { status: '', dept_id: '' };
                if (_tomDept) _tomDept.clear();
                this.load();
            },

            async load() {
                this.loading = true;
                try {
                    var params = new URLSearchParams();
                    if (this.filters.status)  params.set('status',        this.filters.status);
                    if (this.filters.dept_id) params.set('department_id', this.filters.dept_id);
                    var url = window.API_URL + (params.toString() ? '?' + params.toString() : '');
                    var res  = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    var data = await res.json();
                    this.summary = data.summary          || {};
                    this.atRisk  = data.projects_at_risk || [];
                    var self = this;
                    this.$nextTick(function () {
                        self._renderStatusChart(data.by_status    || []);
                        self._renderDeptChart(data.by_department  || []);
                    });
                } catch (e) {
                    console.error('[reportProjectOverview] load error', e);
                } finally {
                    this.loading = false;
                }
            },

            _renderStatusChart(statusData) {
                var el = document.getElementById('chart-pj-status');
                if (!el || !window.ECharts) return;
                if (!_statusChart) {
                    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    _statusChart = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                    new ResizeObserver(function () { if (_statusChart) _statusChart.resize(); }).observe(el);
                }
                if (!statusData.length) { _statusChart.clear(); return; }
                var colorMap = { active: '#6366f1', completed: '#22c55e', on_hold: '#f59e0b', overdue: '#ef4444' };
                var labelMap = { active: 'Đang thực hiện', completed: 'Hoàn thành', on_hold: 'Tạm dừng', overdue: 'Quá hạn' };
                _statusChart.setOption({
                    tooltip: {
                        trigger: 'item',
                        formatter: function (p) {
                            return '<b>' + p.name + '</b><br/>Số dự án: <strong>' + (p.value || 0).toLocaleString('vi-VN') + '</strong><br/>Tỷ lệ: <strong>' + (p.percent ? p.percent.toFixed(1) : 0) + '%</strong>';
                        },
                    },
                    legend: { orient: 'horizontal', bottom: 0, textStyle: { fontSize: 11 } },
                    series: [{
                        type: 'pie', radius: ['45%', '72%'], center: ['50%', '44%'],
                        avoidLabelOverlap: true,
                        label: { show: true, position: 'outside', fontSize: 11,
                                 formatter: function (p) { return (p.percent ? p.percent.toFixed(0) : 0) + '%'; } },
                        labelLine: { length: 10, length2: 8 },
                        data: statusData.map(function (d) {
                            return { name: labelMap[d.status] || d.status, value: d.count || 0,
                                     itemStyle: { color: colorMap[d.status] || '#a1a1aa' } };
                        }),
                        emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.15)' } },
                    }],
                });
            },

            _renderDeptChart(deptData) {
                var el = document.getElementById('chart-pj-dept');
                if (!el || !window.ECharts) return;
                if (!_deptChart) {
                    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    _deptChart = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                    new ResizeObserver(function () { if (_deptChart) _deptChart.resize(); }).observe(el);
                }
                if (!deptData.length) { _deptChart.clear(); return; }
                _deptChart.setOption({
                    tooltip: {
                        trigger: 'axis', axisPointer: { type: 'shadow' },
                        formatter: function (params) {
                            var p = params[0];
                            return '<b>' + p.axisValue + '</b><br/>Số dự án: <strong>' + (p.value || 0).toLocaleString('vi-VN') + '</strong>';
                        },
                    },
                    grid: { left: 20, right: 20, top: 15, bottom: 60, containLabel: true },
                    xAxis: {
                        type: 'category',
                        data: deptData.map(function (d) { return d.name || '—'; }),
                        axisLabel: { fontSize: 11, color: '#6b7280', rotate: deptData.length > 5 ? 30 : 0, width: 90, overflow: 'truncate' },
                        axisTick: { show: false }, axisLine: { lineStyle: { color: '#e5e7eb' } },
                    },
                    yAxis: {
                        type: 'value', minInterval: 1,
                        splitLine: { lineStyle: { type: 'dashed', color: '#f0f0f0' } },
                        axisLabel: { fontSize: 11, color: '#6b7280' },
                    },
                    series: [{
                        type: 'bar',
                        data: deptData.map(function (d) { return d.count || 0; }),
                        barMaxWidth: 48,
                        itemStyle: { color: '#6366f1', borderRadius: [6, 6, 0, 0] },
                        emphasis: { itemStyle: { color: '#4f46e5' } },
                        label: { show: true, position: 'top', fontSize: 11, color: '#6b7280' },
                    }],
                });
            },

            formatVnd(value) {
                if (value === undefined || value === null) return '—';
                var n = Number(value);
                if (isNaN(n)) return '—';
                if (n >= 1000000000) return (n / 1000000000).toFixed(1).replace('.', ',') + ' tỷ';
                if (n >= 1000000)    return (n / 1000000).toFixed(1).replace('.', ',') + ' triệu';
                return n.toLocaleString('vi-VN') + ' đ';
            },
        };
    });

    /* ── Project: Tasks ──────────────────────────────────────────────── */
    Alpine.data('reportProjectTasks', function () {
        var _chartPriority = null;
        var _chartVelocity = null;
        var _tomProject    = null;

        return {
            loading: false,
            filters: { project_id: '', priority: '' },
            summary:      {},
            timeTracking: {},
            overdueTasks: [],

            init() {
                this._initTomSelectProject();
                this.load();
            },

            _initTomSelectProject() {
                var self = this;
                var el   = document.getElementById('rpt-tk-project');
                if (!el || !window.TomSelect) return;
                _tomProject = new TomSelect(el, {
                    valueField:       'id',
                    labelField:       'name',
                    searchField:      ['name'],
                    placeholder:      'Tất cả dự án...',
                    allowEmptyOption: true,
                    preload:          true,
                    load: function (query, callback) {
                        var url = window.PROJECT_OPTIONS_URL + (query ? '?q=' + encodeURIComponent(query) : '');
                        fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (data) { callback(Array.isArray(data) ? data : (data.data || [])); })
                        .catch(function () { callback([]); });
                    },
                    onChange: function (val) { self.filters.project_id = val; },
                });
            },

            resetFilters() {
                this.filters = { project_id: '', priority: '' };
                if (_tomProject) { _tomProject.clear(); _tomProject.clearOptions(); }
                this.load();
            },

            async load() {
                this.loading = true;
                try {
                    var params = new URLSearchParams();
                    if (this.filters.project_id) params.set('project_id', this.filters.project_id);
                    if (this.filters.priority)   params.set('priority',   this.filters.priority);
                    var url = window.API_URL + (params.toString() ? '?' + params.toString() : '');
                    var res  = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    var data = await res.json();
                    this.summary      = data.summary      || {};
                    this.timeTracking = data.time_tracking  || {};
                    this.overdueTasks = data.overdue_tasks  || [];
                    var self = this;
                    this.$nextTick(function () {
                        self._renderPriorityChart(data.by_priority     || []);
                        self._renderVelocityChart(data.weekly_velocity || []);
                    });
                } catch (e) {
                    console.error('[reportProjectTasks] load error', e);
                } finally {
                    this.loading = false;
                }
            },

            _renderPriorityChart(priorityData) {
                var el = document.getElementById('chart-tk-priority');
                if (!el || !window.ECharts) return;
                if (!_chartPriority) {
                    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    _chartPriority = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                    new ResizeObserver(function () { if (_chartPriority) _chartPriority.resize(); }).observe(el);
                }
                if (!priorityData.length) { _chartPriority.clear(); return; }
                var colorMap = { critical: '#ef4444', high: '#f97316', medium: '#eab308', low: '#6b7280' };
                _chartPriority.setOption({
                    tooltip: {
                        trigger: 'axis',
                        formatter: function (params) {
                            var p = params[0];
                            return '<b>' + p.name + '</b><br/>Tasks: <strong>' + (p.value || 0).toLocaleString('vi-VN') + '</strong>';
                        },
                    },
                    grid: { left: 60, right: 20, top: 15, bottom: 40 },
                    xAxis: {
                        type: 'category',
                        data: priorityData.map(function (d) { return d.priority || d.label; }),
                        axisLabel: {
                            fontSize: 12, color: '#6b7280',
                            formatter: function (val) { return val.charAt(0).toUpperCase() + val.slice(1); },
                        },
                        axisTick: { show: false }, axisLine: { lineStyle: { color: '#e5e7eb' } },
                    },
                    yAxis: {
                        type: 'value', minInterval: 1,
                        splitLine: { lineStyle: { type: 'dashed', color: '#f0f0f0' } },
                        axisLabel: { fontSize: 11, color: '#6b7280' },
                    },
                    series: [{
                        type: 'bar',
                        data: priorityData.map(function (d) {
                            return { value: d.count || 0, itemStyle: { color: colorMap[d.priority] || '#6366f1', borderRadius: [6, 6, 0, 0] } };
                        }),
                        barMaxWidth: 48,
                        label: {
                            show: true, position: 'top', fontSize: 12,
                            formatter: function (p) { return (p.value || 0).toLocaleString('vi-VN'); },
                        },
                        emphasis: { focus: 'self' },
                    }],
                });
            },

            _renderVelocityChart(velocityData) {
                var el = document.getElementById('chart-tk-velocity');
                if (!el || !window.ECharts) return;
                if (!_chartVelocity) {
                    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    _chartVelocity = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                    new ResizeObserver(function () { if (_chartVelocity) _chartVelocity.resize(); }).observe(el);
                }
                if (!velocityData.length) { _chartVelocity.clear(); return; }
                _chartVelocity.setOption({
                    tooltip: {
                        trigger: 'axis',
                        formatter: function (params) {
                            var html = '<div style="font-weight:600;margin-bottom:4px">' + (params[0] ? params[0].axisValue : '') + '</div>';
                            params.forEach(function (p) {
                                html += '<div style="display:flex;align-items:center;gap:6px;margin-top:2px">' + p.marker + '<span>' + p.seriesName + ':</span><strong>' + (p.value || 0).toLocaleString('vi-VN') + '</strong></div>';
                            });
                            return html;
                        },
                    },
                    legend: { show: false },
                    grid: { left: 50, right: 15, top: 15, bottom: 40 },
                    xAxis: {
                        type: 'category',
                        data: velocityData.map(function (d) { return d.week || d.period; }),
                        axisLabel: { fontSize: 11, color: '#6b7280', rotate: velocityData.length > 8 ? 30 : 0 },
                        axisTick: { show: false },
                    },
                    yAxis: {
                        type: 'value', minInterval: 1,
                        splitLine: { lineStyle: { type: 'dashed', color: '#f0f0f0' } },
                        axisLabel: { fontSize: 11, color: '#6b7280' },
                    },
                    series: [{
                        name: 'Story Points xong', type: 'bar',
                        data: velocityData.map(function (d) { return d.story_points_done || 0; }),
                        barMaxWidth: 36,
                        itemStyle: { color: '#22c55e', borderRadius: [4, 4, 0, 0] },
                        label: { show: true, position: 'top', fontSize: 11, color: '#6b7280' },
                    }],
                });
            },
        };
    });

});
