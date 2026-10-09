@php
    $kpi        = $content['content_kpis'];
    $audience   = $content['audience'];
    $queue      = $content['review_queue'];
    $moderation = $content['moderation'];
    $myStats    = $content['my_stats'];
    $authors    = $content['top_authors'];
    $system     = $content['system_health'];
    $pendingTotal = $kpi['pending_submit'] + $kpi['pending_publish'];

    $canArticles = Gate::allows('viewAny', \Modules\Post\Models\PostArticle::class);
    $canEvents   = Gate::allows('viewAny', \Modules\Event\Models\Event::class);
    $pendingLink = $audience['editor']
        ? route('backend.post.articles.pending-review')
        : ($canArticles ? route('backend.post.articles.index', ['st' => 'submitted']) : null);

    $cards = [
        [
            'label' => 'Bài xuất bản hôm nay',
            'value' => $kpi['published_today'],
            'hint'  => 'Tuần này: ' . number_format($kpi['published_week']),
            'color' => 'primary',
            'link'  => $canArticles ? route('backend.post.articles.index', ['st' => 'published']) : null,
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>',
        ],
        [
            'label' => 'Lượt xem 7 ngày',
            'value' => $kpi['views_7d'],
            'hint'  => $kpi['views_trend'] === null
                ? 'Chưa đủ dữ liệu so sánh'
                : ($kpi['views_trend'] >= 0 ? '▲ ' : '▼ ') . abs($kpi['views_trend']) . '% so với 7 ngày trước',
            'hint_color' => $kpi['views_trend'] === null ? '' : ($kpi['views_trend'] >= 0 ? 'text-success' : 'text-error'),
            'color' => 'info',
            'link'  => null,
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
        ],
        [
            'label'  => 'Bài đang chờ duyệt',
            'value'  => $pendingTotal,
            'hint'   => $kpi['pending_submit'] . ' chờ duyệt sơ bộ · ' . $kpi['pending_publish'] . ' chờ xuất bản',
            'color'  => $pendingTotal > 0 ? 'warning' : 'success',
            'urgent' => $pendingTotal > 0,
            'link'   => $pendingLink,
            'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
        [
            'label' => 'Sự kiện đang diễn ra',
            'value' => $kpi['events_ongoing'],
            'hint'  => $kpi['breaking_active'] . ' tin nóng đang chạy',
            'color' => 'secondary',
            'link'  => $canEvents ? route('backend.event.index') : null,
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        ],
    ];

    $tones = [
        'primary'   => ['bg-primary/10',   'text-primary'],
        'info'      => ['bg-info/10',      'text-info'],
        'warning'   => ['bg-warning/10',   'text-warning'],
        'success'   => ['bg-success/10',   'text-success'],
        'secondary' => ['bg-secondary/10', 'text-secondary'],
    ];

    $statusBadge = [
        'submitted' => ['label' => 'Chờ duyệt sơ bộ', 'class' => 'badge-warning'],
        'approved'  => ['label' => 'Chờ xuất bản',    'class' => 'badge-info'],
        'scheduled' => ['label' => 'Đã lên lịch',     'class' => 'badge-ghost'],
    ];
@endphp

{{-- ── KPI ──────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    @foreach($cards as $card)
    @php $tag = $card['link'] ? 'a' : 'div'; @endphp
    @php [$toneBg, $toneText] = $tones[$card['color']]; @endphp
    <{{ $tag }} @if($card['link']) href="{{ $card['link'] }}" @endif
       class="card bg-base-100 border {{ ($card['urgent'] ?? false) ? 'border-warning/40' : 'border-base-200' }} shadow-sm {{ $card['link'] ? 'hover:shadow-md hover:border-primary/30 transition-all' : '' }}">
        <div class="card-body p-4">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl {{ $toneBg }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $toneText }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $card['icon'] !!}</svg>
                </div>
                @if($card['urgent'] ?? false)
                <span class="w-2 h-2 rounded-full bg-warning animate-pulse"></span>
                @endif
            </div>
            <p class="text-3xl font-bold text-base-content leading-none tabular-nums mt-3">{{ number_format($card['value']) }}</p>
            <p class="text-sm text-base-content/70 mt-1">{{ $card['label'] }}</p>
            <p class="text-xs mt-0.5 truncate {{ $card['hint_color'] ?? 'text-base-content/40' }}">{{ $card['hint'] }}</p>
        </div>
    </{{ $tag }}>
    @endforeach
</div>

{{-- ── Biểu đồ ──────────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 border border-base-200 shadow-sm mb-6"
     x-data="contentTrendChart({{ Js::from(['url' => route('backend.dashboard.charts.content-trend')]) }})">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="font-semibold text-sm text-base-content">Lượt xem &amp; bài xuất bản</h2>
                <p class="text-xs text-base-content/40 mt-0.5">Lượt xem bài viết theo ngày, so với số bài được xuất bản</p>
            </div>
            <div class="flex items-center gap-1 bg-base-200 rounded-lg p-0.5">
                <template x-for="opt in [7, 14, 30]" :key="opt">
                    <button type="button" @click="setRange(opt)"
                            :class="days === opt ? 'bg-base-100 text-base-content shadow-sm' : 'text-base-content/50 hover:text-base-content'"
                            class="px-3 py-1 rounded-md text-xs font-medium transition-all" x-text="opt + ' ngày'"></button>
                </template>
            </div>
        </div>
        <div class="relative w-full" style="height: 300px;">
            <div x-show="loading" class="skeleton absolute inset-0 rounded-xl"></div>
            <div x-show="error" x-cloak class="absolute inset-0 flex items-center justify-center text-xs text-error/70">Không thể tải dữ liệu biểu đồ</div>
            <div x-ref="chart" class="w-full h-full"></div>
        </div>
    </div>
</div>

{{-- ── Khối theo vai trò ───────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    @if($queue !== null)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="font-semibold text-sm text-base-content">Cần xử lý — Bài chờ duyệt</h2>
                    @if($queue['total'] > 0)<span class="badge badge-warning badge-sm">{{ $queue['total'] }}</span>@endif
                </div>
                <a href="{{ route('backend.post.articles.pending-review') }}" class="text-xs text-primary hover:underline">Xem tất cả</a>
            </div>
            @forelse($queue['items'] as $t)
            @php $badge = $statusBadge[$t->status->value] ?? ['label' => $t->status->label(), 'class' => 'badge-ghost']; @endphp
            <div class="px-5 py-3 flex items-center gap-3 border-b border-base-200 last:border-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-base-content truncate">{{ $t->title }}</p>
                    <p class="text-xs text-base-content/45 mt-0.5 truncate">
                        {{ $t->article?->createdBy?->name ?? '—' }} · {{ strtoupper($t->locale) }} · {{ $t->updated_at?->diffForHumans() }}
                    </p>
                </div>
                <span class="badge {{ $badge['class'] }} badge-sm shrink-0">{{ $badge['label'] }}</span>
                @if($t->article)
                <a href="{{ route('backend.post.articles.edit', $t->article) }}?locale={{ $t->locale }}" class="btn btn-primary btn-xs shrink-0">Xem / Duyệt</a>
                @endif
            </div>
            @empty
            <div class="py-12 text-center">
                <p class="text-sm font-medium text-base-content/40">Không có bài nào đang chờ bạn duyệt</p>
            </div>
            @endforelse
        </div>
    </div>
    @endif

    @if($moderation !== null)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="font-semibold text-sm text-base-content">Hồ sơ chờ kiểm duyệt</h2>
                    @if($moderation['total'] > 0)<span class="badge badge-warning badge-sm">{{ $moderation['total'] }}</span>@endif
                </div>
                @can('viewDashboard')
                <a href="{{ route('backend.approval.dashboard') }}" class="text-xs text-primary hover:underline">Mở hàng chờ duyệt</a>
                @endcan
            </div>
            @foreach($moderation['items'] as $item)
            <div class="px-5 py-3 flex items-center justify-between border-b border-base-200 last:border-0">
                <span class="text-sm text-base-content/80">{{ $item['label'] }}</span>
                <span class="badge {{ $item['total'] > 0 ? 'badge-warning' : 'badge-ghost' }} badge-sm tabular-nums">{{ $item['total'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($myStats !== null)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200">
                <h2 class="font-semibold text-sm text-base-content">Bài viết của tôi</h2>
            </div>
            <div class="grid grid-cols-3 divide-x divide-base-200 border-b border-base-200">
                @foreach([['Tổng bài viết', $myStats['articles']], ['Đã xuất bản', $myStats['published']], ['Tổng lượt xem', $myStats['views']]] as [$label, $value])
                <div class="px-4 py-4 text-center">
                    <p class="text-2xl font-bold text-base-content tabular-nums">{{ number_format($value) }}</p>
                    <p class="text-xs text-base-content/50 mt-0.5">{{ $label }}</p>
                </div>
                @endforeach
            </div>
            <p class="px-5 pt-3 pb-1 text-xs font-semibold text-base-content/40 uppercase tracking-wide">Đang trong quy trình duyệt</p>
            @forelse($myStats['in_review'] as $t)
            @php $badge = $statusBadge[$t->status->value] ?? ['label' => $t->status->label(), 'class' => 'badge-ghost']; @endphp
            <div class="px-5 py-2.5 flex items-center gap-3">
                <a href="{{ route('backend.post.articles.edit', $t->article) }}?locale={{ $t->locale }}"
                   class="flex-1 min-w-0 text-sm text-base-content truncate hover:text-primary">{{ $t->title }}</a>
                <span class="badge {{ $badge['class'] }} badge-sm shrink-0">{{ $badge['label'] }}</span>
            </div>
            @empty
            <p class="px-5 pb-5 pt-1 text-sm text-base-content/40">Không có bài nào đang chờ duyệt.</p>
            @endforelse
            <div class="h-2"></div>
        </div>
    </div>
    @endif

    @if($authors->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200">
                <h2 class="font-semibold text-sm text-base-content">Phóng viên nổi bật</h2>
                <p class="text-xs text-base-content/40 mt-0.5">Theo số bài xuất bản trong 30 ngày qua</p>
            </div>
            <table class="table table-sm">
                <thead>
                    <tr><th class="w-10">#</th><th>Phóng viên</th><th class="text-right">Bài</th><th class="text-right">Lượt xem</th></tr>
                </thead>
                <tbody>
                    @foreach($authors as $i => $author)
                    <tr>
                        <td class="text-base-content/40">{{ $i + 1 }}</td>
                        <td class="font-medium">{{ $author['name'] }}</td>
                        <td class="text-right tabular-nums">{{ number_format($author['articles']) }}</td>
                        <td class="text-right tabular-nums">{{ number_format($author['views']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($system !== null)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200 flex items-center justify-between">
                <h2 class="font-semibold text-sm text-base-content">Tình trạng hệ thống</h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-base-content/35">Cập nhật {{ \Illuminate\Support\Carbon::parse($system['checked_at'])->diffForHumans() }}</span>
                    @can('viewSystemMonitor')
                    <a href="{{ route('backend.system-monitor') }}" class="text-xs text-primary hover:underline">Xem chi tiết</a>
                    @endcan
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-base-200 border-b border-base-200">
                @foreach([
                    ['Tài khoản hoạt động', $system['users_active'], ''],
                    ['Đang online (15 phút)', $system['users_online'] ?? '—', ''],
                    ['Job đang chờ', $system['jobs_pending'], ''],
                    ['Job lỗi', $system['jobs_failed'], $system['jobs_failed'] > 0 ? 'text-error' : ''],
                ] as [$label, $value, $class])
                <div class="px-4 py-4 text-center">
                    <p class="text-2xl font-bold tabular-nums {{ $class ?: 'text-base-content' }}">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                    <p class="text-xs text-base-content/50 mt-0.5">{{ $label }}</p>
                </div>
                @endforeach
            </div>
            <div class="px-5 py-4 space-y-3">
                @if($system['disk_used_pct'] !== null)
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/60">Dung lượng ổ đĩa</span>
                        <span class="text-base-content/50">{{ $system['disk_used_pct'] }}% · còn {{ $system['disk_free_gb'] }} GB</span>
                    </div>
                    <progress class="progress {{ $system['disk_used_pct'] >= 90 ? 'progress-error' : ($system['disk_used_pct'] >= 75 ? 'progress-warning' : 'progress-success') }} w-full"
                              value="{{ $system['disk_used_pct'] }}" max="100"></progress>
                </div>
                @endif
                <div class="flex flex-wrap gap-1.5">
                    @foreach(['PHP ' . $system['php_version'], 'Laravel ' . $system['laravel'], 'Môi trường: ' . $system['environment'], 'Queue: ' . $system['queue'], 'Cache: ' . $system['cache']] as $tag)
                    <span class="badge badge-ghost badge-sm">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200 flex items-center justify-between">
                <h2 class="font-semibold text-sm text-base-content">Hoạt động gần đây</h2>
                @can('activitylog.view')
                <a href="{{ route('activitylog.index') }}" class="text-xs text-primary hover:underline">Xem log đầy đủ</a>
                @endcan
            </div>
            @forelse($recent_activity as $log)
            <div class="px-5 py-2.5 border-b border-base-200 last:border-0">
                <p class="text-sm text-base-content leading-snug">
                    <span class="font-medium">{{ $log->causer?->name ?? 'Hệ thống' }}</span>
                    <span class="text-base-content/60">{{ $log->description }}</span>
                    @if($log->subject_type)<span class="text-base-content/40 text-xs">({{ class_basename($log->subject_type) }})</span>@endif
                </p>
                <p class="text-xs text-base-content/35 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
            </div>
            @empty
            <p class="py-12 text-center text-sm text-base-content/40">Chưa có hoạt động nào</p>
            @endforelse
        </div>
    </div>
    @endif

</div>

{{-- ── Truy cập nhanh ───────────────────────────────────────────────────── --}}
@php
    $shortcuts = [];
    if (Gate::allows('create', \Modules\Post\Models\PostArticle::class)) $shortcuts[] = ['Viết bài mới', route('backend.post.articles.create')];
    if ($audience['editor']) $shortcuts[] = ['Bài chờ duyệt', route('backend.post.articles.pending-review')];
    if ($canArticles) $shortcuts[] = ['Danh sách bài viết', route('backend.post.articles.index')];
    if (Gate::allows('viewAny', \Modules\Post\Models\PostBreakingNews::class)) $shortcuts[] = ['Tin nóng', route('backend.post.breaking-news.items.index')];
    if ($canEvents) $shortcuts[] = ['Sự kiện', route('backend.event.index')];
    if (Gate::allows('viewDashboard')) $shortcuts[] = ['Hồ sơ chờ kiểm duyệt', route('backend.approval.dashboard')];
    if (Gate::allows('platform-users.manage')) $shortcuts[] = ['Nhân sự Platform', route('backend.platform-users.index')];
@endphp
@if($shortcuts)
<div>
    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Truy cập nhanh</p>
    <div class="flex flex-wrap gap-2">
        @foreach($shortcuts as [$label, $url])
        <a href="{{ $url }}"
           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-base-200 bg-base-100 text-xs font-medium text-base-content/70 hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-all">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('contentTrendChart', ({ url }) => ({
        days: 30,
        loading: true,
        error: false,
        chart: null,

        init() {
            const saved = parseInt(sessionStorage.getItem('dash_content_days') || '30', 10);
            this.days = [7, 14, 30].includes(saved) ? saved : 30;

            const start = () => {
                this.load();
                new ResizeObserver(() => this.chart?.resize()).observe(this.$refs.chart);
                new MutationObserver(() => this.render(this.lastData))
                    .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
            };
            window.ECharts ? start() : document.addEventListener('echarts:ready', start, { once: true });
        },

        setRange(days) {
            if (this.days === days) return;
            this.days = days;
            sessionStorage.setItem('dash_content_days', String(days));
            this.load();
        },

        async load() {
            this.loading = true;
            this.error = false;
            try {
                const res = await fetch(`${url}?days=${this.days}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.lastData = await res.json();
                this.render(this.lastData);
            } catch (e) {
                console.error('[dashboard] content-trend', e);
                this.error = true;
            } finally {
                this.loading = false;
            }
        },

        render(d) {
            if (!d) return;
            const dark = document.documentElement.getAttribute('data-theme') === 'dark';
            const muted = dark ? '#94a3b8' : '#64748b';
            const grid = dark ? '#334155' : '#eef2f7';

            this.chart?.dispose();
            this.chart = window.ECharts.init(this.$refs.chart, dark ? 'dark' : null, { renderer: 'canvas' });
            this.chart.setOption({
                backgroundColor: 'transparent',
                tooltip: { trigger: 'axis' },
                legend: { top: 0, right: 0, textStyle: { color: muted, fontSize: 12 } },
                grid: { left: 8, right: 8, top: 36, bottom: 4, containLabel: true },
                xAxis: {
                    type: 'category', data: d.labels, boundaryGap: true,
                    axisLabel: { color: muted, fontSize: 11 }, axisLine: { lineStyle: { color: grid } },
                },
                yAxis: [
                    { type: 'value', name: 'Lượt xem', minInterval: 1, nameTextStyle: { color: muted, fontSize: 11 },
                      axisLabel: { color: muted, fontSize: 11 }, splitLine: { lineStyle: { color: grid } } },
                    { type: 'value', name: 'Bài', minInterval: 1, nameTextStyle: { color: muted, fontSize: 11 },
                      axisLabel: { color: muted, fontSize: 11 }, splitLine: { show: false } },
                ],
                series: [
                    {
                        name: 'Lượt xem', type: 'line', smooth: true, symbol: 'circle', symbolSize: 6,
                        data: d.views, itemStyle: { color: '#6366f1' },
                        areaStyle: { color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                            colorStops: [{ offset: 0, color: 'rgba(99,102,241,.28)' }, { offset: 1, color: 'rgba(99,102,241,0)' }] } },
                    },
                    {
                        name: 'Bài xuất bản', type: 'bar', yAxisIndex: 1, barMaxWidth: 14,
                        data: d.published, itemStyle: { color: '#22c55e', borderRadius: [3, 3, 0, 0] },
                    },
                ],
            });
        },
    }));
});
</script>
@endpush
