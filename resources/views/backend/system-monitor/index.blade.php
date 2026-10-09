@extends('layouts.backend')
@section('title', 'Giám sát hệ thống')

@section('content')
<div x-data="systemMonitor({{ Js::from(['url' => route('backend.system-monitor.data'), 'ranges' => $ranges]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Giám sát hệ thống</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Cập nhật <span x-text="updatedAt || '—'"></span>
                <span x-show="loading" x-cloak class="loading loading-spinner loading-xs align-middle ml-1"></span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <label class="label cursor-pointer gap-2">
                <span class="label-text text-xs">Tự làm mới 15s</span>
                <input type="checkbox" class="toggle toggle-sm toggle-primary" x-model="auto">
            </label>
            <button type="button" class="btn btn-ghost btn-sm" @click="load()">Làm mới</button>
            @can('viewHorizon')
            <a href="{{ url(config('horizon.path', 'horizon')) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Mở Horizon</a>
            @endcan
        </div>
    </div>

    <div x-show="error" x-cloak class="alert alert-error mb-4 text-sm" x-text="error"></div>

    <template x-if="d">
    <div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <template x-for="card in cards()" :key="card.label">
                <div class="card bg-base-100 border border-base-200 shadow-sm">
                    <div class="card-body p-4">
                        <p class="text-xs text-base-content/50" x-text="card.label"></p>
                        <p class="text-2xl font-bold tabular-nums mt-1" :class="toneText(card.pct)" x-text="card.value"></p>
                        <progress class="progress w-full mt-2" :class="toneProgress(card.pct)" :value="card.pct ?? 0" max="100"></progress>
                        <p class="text-xs text-base-content/40 mt-1 truncate" x-text="card.hint"></p>
                    </div>
                </div>
            </template>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
            <div class="card-body p-4">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div>
                        <h2 class="font-semibold text-sm">Lưu lượng &amp; thời gian phản hồi</h2>
                        <p class="text-xs text-base-content/40 mt-0.5">Request/phút và thời gian phản hồi trung bình (ms)</p>
                    </div>
                    <div class="flex items-center gap-1 bg-base-200 rounded-lg p-0.5">
                        <template x-for="r in ranges" :key="r">
                            <button type="button" @click="setRange(r)"
                                    :class="minutes === r ? 'bg-base-100 text-base-content shadow-sm' : 'text-base-content/50 hover:text-base-content'"
                                    class="px-3 py-1 rounded-md text-xs font-medium transition-all" x-text="rangeLabel(r)"></button>
                        </template>
                    </div>
                </div>
                <div x-ref="chart" class="w-full" style="height: 280px;"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-5">

            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <h2 class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Ứng dụng</h2>
                    <dl class="px-5 py-3 grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-base-content/50">RPM (5 phút)</dt><dd class="text-right tabular-nums" x-text="d.requests.last_5m.rpm"></dd>
                        <dt class="text-base-content/50">TB phản hồi (5 phút)</dt><dd class="text-right tabular-nums" :class="msTone(d.requests.last_5m.avg_ms)" x-text="ms(d.requests.last_5m.avg_ms)"></dd>
                        <dt class="text-base-content/50">Request (60 phút)</dt><dd class="text-right tabular-nums" x-text="num(d.requests.last_60m.count)"></dd>
                        <dt class="text-base-content/50">TB phản hồi (60 phút)</dt><dd class="text-right tabular-nums" :class="msTone(d.requests.last_60m.avg_ms)" x-text="ms(d.requests.last_60m.avg_ms)"></dd>
                        <dt class="text-base-content/50">Lỗi 5xx (60 phút)</dt><dd class="text-right tabular-nums" :class="d.requests.last_60m.errors > 0 ? 'text-error font-semibold' : ''" x-text="d.requests.last_60m.errors"></dd>
                        <dt class="text-base-content/50">PHP / Laravel</dt><dd class="text-right" x-text="d.server.php + ' / ' + d.server.laravel"></dd>
                    </dl>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <h2 class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Cơ sở dữ liệu</h2>
                    <template x-if="d.database.supported">
                    <dl class="px-5 py-3 grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-base-content/50">Kết nối đang mở</dt><dd class="text-right tabular-nums" x-text="d.database.connected + ' / ' + d.database.max_connections"></dd>
                        <dt class="text-base-content/50">Đỉnh kết nối</dt><dd class="text-right tabular-nums" x-text="d.database.max_used"></dd>
                        <dt class="text-base-content/50">Đang chạy</dt><dd class="text-right tabular-nums" x-text="d.database.running"></dd>
                        <dt class="text-base-content/50">Slow queries (MySQL)</dt><dd class="text-right tabular-nums" x-text="num(d.database.slow_queries) + ' (> ' + d.database.long_query_time + 's)'"></dd>
                        <dt class="text-base-content/50">Dung lượng</dt><dd class="text-right" x-text="bytes(d.database.size)"></dd>
                        <dt class="text-base-content/50">Uptime</dt><dd class="text-right" x-text="duration(d.database.uptime)"></dd>
                    </dl>
                    </template>
                    <p x-show="!d.database.supported" class="px-5 py-4 text-sm text-base-content/50" x-text="d.database.error || ('Driver ' + d.database.driver + ' chưa hỗ trợ')"></p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <h2 class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Redis</h2>
                    <dl x-show="!d.redis.error" class="px-5 py-3 grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-base-content/50">Phiên bản</dt><dd class="text-right" x-text="d.redis.version"></dd>
                        <dt class="text-base-content/50">Bộ nhớ</dt><dd class="text-right" x-text="d.redis.memory"></dd>
                        <dt class="text-base-content/50">Client kết nối</dt><dd class="text-right tabular-nums" x-text="d.redis.clients"></dd>
                        <dt class="text-base-content/50">Tỉ lệ hit</dt><dd class="text-right tabular-nums" x-text="d.redis.hit_rate === null ? '—' : d.redis.hit_rate + '%'"></dd>
                        <dt class="text-base-content/50">Uptime</dt><dd class="text-right" x-text="d.redis.uptime_days + ' ngày'"></dd>
                    </dl>
                    <p x-show="d.redis.error" class="px-5 py-4 text-sm text-error" x-text="d.redis.error"></p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <div class="px-5 py-3 border-b border-base-200 flex items-center justify-between">
                        <h2 class="font-semibold text-sm">Hàng đợi</h2>
                        <span class="badge badge-sm" :class="horizonBadge()" x-text="'Horizon: ' + horizonLabel()"></span>
                    </div>
                    <dl class="px-5 py-3 grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-base-content/50">Kết nối</dt><dd class="text-right" x-text="d.queue.connection"></dd>
                        <dt class="text-base-content/50">Job/phút</dt><dd class="text-right tabular-nums" x-text="d.queue.horizon.jobs_per_minute ?? '—'"></dd>
                        <dt class="text-base-content/50">Đang chờ</dt><dd class="text-right tabular-nums" x-text="queueTotal()"></dd>
                        <dt class="text-base-content/50">Lỗi gần đây</dt><dd class="text-right tabular-nums" :class="(d.queue.horizon.recent_failed || 0) > 0 ? 'text-error font-semibold' : ''" x-text="d.queue.horizon.recent_failed ?? '—'"></dd>
                        <dt class="text-base-content/50">Job lỗi (lưu trữ)</dt><dd class="text-right tabular-nums" :class="d.queue.failed > 0 ? 'text-error font-semibold' : ''" x-text="d.queue.failed ?? '—'"></dd>
                    </dl>
                    <div class="px-5 pb-4 flex flex-wrap gap-1.5">
                        <template x-for="(size, name) in d.queue.sizes" :key="name">
                            <span class="badge badge-sm" :class="size > 0 ? 'badge-warning' : 'badge-ghost'" x-text="name + ': ' + (size ?? '?')"></span>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <h2 class="px-5 py-3 border-b border-base-200 font-semibold text-sm">
                        Request chậm <span class="text-base-content/40 font-normal" x-text="'(≥ ' + d.requests.slow_request_ms + 'ms, 50 gần nhất)'"></span>
                    </h2>
                    <div class="overflow-x-auto max-h-96">
                        <table class="table table-sm">
                            <thead><tr><th>Thời gian</th><th>Request</th><th class="text-right">ms</th></tr></thead>
                            <tbody>
                                <template x-for="(r, i) in slowRequests" :key="i">
                                    <tr>
                                        <td class="whitespace-nowrap text-base-content/50" x-text="time(r.at)"></td>
                                        <td class="max-w-xs truncate" :title="r.path">
                                            <span class="badge badge-ghost badge-xs mr-1" x-text="r.method"></span><span x-text="r.path"></span>
                                            <span x-show="r.status >= 500" class="badge badge-error badge-xs ml-1" x-text="r.status"></span>
                                        </td>
                                        <td class="text-right tabular-nums" :class="msTone(r.ms)" x-text="num(r.ms)"></td>
                                    </tr>
                                </template>
                                <tr x-show="slowRequests.length === 0"><td colspan="3" class="text-center text-base-content/40 py-6">Chưa có request chậm</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <h2 class="px-5 py-3 border-b border-base-200 font-semibold text-sm">
                        Query chậm <span class="text-base-content/40 font-normal" x-text="'(≥ ' + d.requests.slow_query_ms + 'ms, 50 gần nhất)'"></span>
                    </h2>
                    <div class="overflow-x-auto max-h-96">
                        <table class="table table-sm">
                            <thead><tr><th>Thời gian</th><th>SQL</th><th class="text-right">ms</th></tr></thead>
                            <tbody>
                                <template x-for="(q, i) in slowQueries" :key="i">
                                    <tr>
                                        <td class="whitespace-nowrap text-base-content/50" x-text="time(q.at)"></td>
                                        <td class="max-w-md"><code class="text-xs break-all line-clamp-2" :title="q.sql" x-text="q.sql"></code></td>
                                        <td class="text-right tabular-nums" :class="msTone(q.ms)" x-text="num(q.ms)"></td>
                                    </tr>
                                </template>
                                <tr x-show="slowQueries.length === 0"><td colspan="3" class="text-center text-base-content/40 py-6">Chưa có query chậm</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </template>

    <div x-show="!d && !error" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="skeleton h-28"></div><div class="skeleton h-28"></div><div class="skeleton h-28"></div><div class="skeleton h-28"></div>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/modules/echarts.js'], 'build/backend')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('systemMonitor', ({ url, ranges }) => ({
        ranges,
        minutes: 60,
        auto: true,
        loading: false,
        error: null,
        d: null,
        slowRequests: [],
        slowQueries: [],
        updatedAt: null,
        chart: null,
        timer: null,

        init() {
            this.load();
            this.timer = setInterval(() => { if (this.auto && !document.hidden) this.load(); }, 15000);
        },

        destroy() {
            clearInterval(this.timer);
            this.chart?.dispose();
        },

        setRange(minutes) {
            this.minutes = minutes;
            this.load();
        },

        async load() {
            if (this.loading) return;
            this.loading = true;
            try {
                const res = await fetch(`${url}?minutes=${this.minutes}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Không tải được dữ liệu (HTTP ' + res.status + ')');
                const data = await res.json();
                this.d = data;
                this.slowRequests = data.slow_requests || [];
                this.slowQueries = data.slow_queries || [];
                this.updatedAt = new Date().toLocaleTimeString('vi-VN');
                this.error = null;
                this.$nextTick(() => this.renderChart(data.series));
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        renderChart(s) {
            const draw = () => {
                const el = this.$refs.chart;
                if (!el || !window.ECharts) return;
                if (!this.chart || this.chart.getDom() !== el) {
                    this.chart?.dispose();
                    this.chart = window.ECharts.init(el, null, { renderer: 'canvas' });
                    new ResizeObserver(() => this.chart?.resize()).observe(el);
                }
                const muted = '#64748b', grid = '#eef2f7';
                this.chart.setOption({
                    tooltip: { trigger: 'axis' },
                    legend: { top: 0, right: 0, textStyle: { color: muted, fontSize: 12 } },
                    grid: { left: 8, right: 8, top: 36, bottom: 4, containLabel: true },
                    xAxis: { type: 'category', data: s.labels, axisLabel: { color: muted, fontSize: 11 }, axisLine: { lineStyle: { color: grid } } },
                    yAxis: [
                        { type: 'value', name: 'req/phút', nameTextStyle: { color: muted, fontSize: 11 }, axisLabel: { color: muted, fontSize: 11 }, splitLine: { lineStyle: { color: grid } } },
                        { type: 'value', name: 'ms', nameTextStyle: { color: muted, fontSize: 11 }, axisLabel: { color: muted, fontSize: 11 }, splitLine: { show: false } },
                    ],
                    series: [
                        { name: 'Request/phút', type: 'bar', data: s.rpm, barMaxWidth: 10, itemStyle: { color: '#6366f1', borderRadius: [2, 2, 0, 0] } },
                        { name: 'TB phản hồi (ms)', type: 'line', yAxisIndex: 1, smooth: true, symbol: 'none', data: s.avg_ms, itemStyle: { color: '#f59e0b' } },
                        { name: 'Lỗi 5xx', type: 'line', yAxisIndex: 0, symbol: 'none', data: s.errors, itemStyle: { color: '#ef4444' }, lineStyle: { width: 1 } },
                    ],
                }, true);
            };
            window.ECharts ? draw() : document.addEventListener('echarts:ready', draw, { once: true });
        },

        cards() {
            const s = this.d.server, db = this.d.database;
            return [
                { label: 'CPU (load 1 phút)', pct: s.cpu_pct, value: s.cpu_pct + '%', hint: 'Load ' + s.load.join(' / ') + ' · ' + s.cores + ' core' },
                { label: 'Bộ nhớ RAM', pct: s.mem_pct, value: s.mem_pct === null ? '—' : s.mem_pct + '%', hint: this.bytes(s.mem_used) + ' / ' + this.bytes(s.mem_total) },
                { label: 'Ổ đĩa', pct: s.disk_pct, value: s.disk_pct === null ? '—' : s.disk_pct + '%', hint: this.bytes(s.disk_used) + ' / ' + this.bytes(s.disk_total) },
                { label: 'Kết nối database', pct: db.usage_pct ?? null, value: db.supported ? db.connected + ' / ' + db.max_connections : '—', hint: db.supported ? 'Đỉnh ' + db.max_used + ' · ' + db.usage_pct + '% giới hạn' : (db.error || '') },
            ];
        },

        queueTotal() {
            return Object.values(this.d.queue.sizes || {}).reduce((a, b) => a + (b || 0), 0);
        },
        horizonLabel() {
            return { running: 'đang chạy', paused: 'tạm dừng', inactive: 'không chạy' }[this.d.queue.horizon.status] || 'không rõ';
        },
        horizonBadge() {
            return { running: 'badge-success', paused: 'badge-warning', inactive: 'badge-error' }[this.d.queue.horizon.status] || 'badge-ghost';
        },
        toneText(p) { return p === null ? '' : (p >= 90 ? 'text-error' : (p >= 75 ? 'text-warning' : 'text-base-content')); },
        toneProgress(p) { return p === null ? '' : (p >= 90 ? 'progress-error' : (p >= 75 ? 'progress-warning' : 'progress-success')); },
        msTone(ms) { return ms === null ? '' : (ms >= 1000 ? 'text-error font-semibold' : (ms >= 500 ? 'text-warning' : '')); },
        rangeLabel(m) { return m < 60 * 2 ? (m / 60) + ' giờ' : (m / 60) + ' giờ'; },
        num(n) { return n === null || n === undefined ? '—' : Number(n).toLocaleString('vi-VN'); },
        ms(n) { return n === null || n === undefined ? '—' : this.num(n) + ' ms'; },
        time(iso) { return iso ? new Date(iso).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit', day: '2-digit', month: '2-digit' }) : ''; },
        bytes(b) {
            if (b === null || b === undefined) return '—';
            const u = ['B', 'KB', 'MB', 'GB', 'TB']; let i = 0; let v = b;
            while (v >= 1024 && i < u.length - 1) { v /= 1024; i++; }
            return v.toFixed(i >= 3 ? 1 : 0) + ' ' + u[i];
        },
        duration(sec) {
            const d = Math.floor(sec / 86400), h = Math.floor(sec % 86400 / 3600), m = Math.floor(sec % 3600 / 60);
            return (d ? d + ' ngày ' : '') + h + ' giờ ' + m + ' phút';
        },
    }));
});
</script>
@endpush
