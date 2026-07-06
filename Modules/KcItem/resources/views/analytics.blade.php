@extends('layouts.backend')
@section('title', 'Analytics KC')


@section('content')

<div x-data="kcAnalyticsPage({
    topViewedUrl:    '{{ route('backend.api.kc.analytics.top-viewed') }}',
    byTypeUrl:       '{{ route('backend.api.kc.analytics.by-type') }}',
    expiringSoonUrl: '{{ route('backend.api.kc.analytics.expiring-soon') }}',
    unreadUrl:       '{{ route('backend.api.kc.analytics.unread') }}'
})" x-init="init()">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Analytics Kho tri thức</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Báo cáo tổng quan về mức độ sử dụng và chất lượng tài liệu</p>
        </div>
        <button @click="refresh()" class="btn btn-ghost btn-sm gap-1.5" :disabled="loading">
            <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Làm mới
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6">

        {{-- ── 1. Top tài liệu xem nhiều nhất ──────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h2 class="card-title text-base font-semibold">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Top tài liệu xem nhiều nhất
                    </h2>
                    <div class="join">
                        <button class="join-item btn btn-xs"
                                :class="topDays === 7 ? 'btn-primary' : 'btn-ghost'"
                                @click="setTopDays(7)">7 ngày</button>
                        <button class="join-item btn btn-xs"
                                :class="topDays === 30 ? 'btn-primary' : 'btn-ghost'"
                                @click="setTopDays(30)">30 ngày</button>
                    </div>
                </div>

                <div x-show="topViewedLoading" class="flex justify-center py-8">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div x-show="!topViewedLoading && topViewed.length === 0" class="text-center py-8 text-base-content/40">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Chưa có dữ liệu
                </div>

                <div x-show="!topViewedLoading && topViewed.length > 0" class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-xs text-base-content/50">
                                <th class="w-8">#</th>
                                <th>Tên tài liệu</th>
                                <th>Loại</th>
                                <th class="text-right">Lượt xem kỳ này</th>
                                <th class="text-right">Tổng view</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in topViewed" :key="item.id">
                                <tr class="hover">
                                    <td class="font-mono text-xs text-base-content/40" x-text="idx + 1"></td>
                                    <td>
                                        <a :href="item.url" class="link link-hover font-medium text-sm" x-text="item.title"></a>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline badge-xs" x-text="typeLabel(item.type)"></span>
                                    </td>
                                    <td class="text-right font-semibold" x-text="item.period_views"></td>
                                    <td class="text-right text-base-content/50 text-xs" x-text="item.view_count"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── 2. Thống kê theo loại ─────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base font-semibold mb-4">
                    <svg class="w-5 h-5 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                    Thống kê theo loại tài liệu
                </h2>

                <div x-show="byTypeLoading" class="flex justify-center py-8">
                    <span class="loading loading-spinner loading-md text-secondary"></span>
                </div>

                <div x-show="!byTypeLoading && byType.length === 0" class="text-center py-8 text-base-content/40">
                    Chưa có dữ liệu
                </div>

                <div x-show="!byTypeLoading && byType.length > 0" class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-xs text-base-content/50">
                                <th>Loại tài liệu</th>
                                <th class="text-right">Số lượng</th>
                                <th class="text-right">Đánh giá TB</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in byType" :key="row.type">
                                <tr class="hover">
                                    <td>
                                        <span class="badge badge-outline badge-sm" x-text="typeLabel(row.type)"></span>
                                    </td>
                                    <td class="text-right font-semibold" x-text="row.total"></td>
                                    <td class="text-right">
                                        <template x-if="row.avg_rating">
                                            <span class="flex items-center justify-end gap-1">
                                                <svg class="w-3 h-3 text-warning" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span x-text="row.avg_rating.toFixed(1)" class="font-medium text-sm"></span>
                                            </span>
                                        </template>
                                        <template x-if="!row.avg_rating">
                                            <span class="text-base-content/30 text-xs">—</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── 3. Sắp hết hạn (30 ngày) ─────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base font-semibold mb-4">
                    <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tài liệu sắp hết hạn
                    <span x-show="expiringSoon.length > 0" class="badge badge-warning badge-sm" x-text="expiringSoon.length + ' tài liệu'"></span>
                </h2>

                <div x-show="expiringSoonLoading" class="flex justify-center py-8">
                    <span class="loading loading-spinner loading-md text-warning"></span>
                </div>

                <div x-show="!expiringSoonLoading && expiringSoon.length === 0" class="text-center py-8 text-base-content/40">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Không có tài liệu nào sắp hết hạn trong 30 ngày tới
                </div>

                <div x-show="!expiringSoonLoading && expiringSoon.length > 0" class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-xs text-base-content/50">
                                <th>Tên tài liệu</th>
                                <th>Loại</th>
                                <th class="text-right">Hết hạn</th>
                                <th class="text-right">Ngày còn lại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in expiringSoon" :key="item.id">
                                <tr class="hover">
                                    <td>
                                        <a :href="item.url" class="link link-hover font-medium text-sm" x-text="item.title"></a>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline badge-xs" x-text="typeLabel(item.type)"></span>
                                    </td>
                                    <td class="text-right text-sm" x-text="item.expired_date"></td>
                                    <td class="text-right">
                                        <span class="badge badge-xs"
                                              :class="item.days_left <= 7 ? 'badge-error' : 'badge-warning'"
                                              x-text="item.days_left + ' ngày'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── 4. Chưa có lượt xem ──────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base font-semibold mb-4">
                    <svg class="w-5 h-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                    Tài liệu chưa có lượt xem
                    <span x-show="unread.length > 0" class="badge badge-error badge-sm" x-text="unread.length + ' tài liệu'"></span>
                </h2>

                <div x-show="unreadLoading" class="flex justify-center py-8">
                    <span class="loading loading-spinner loading-md text-error"></span>
                </div>

                <div x-show="!unreadLoading && unread.length === 0" class="text-center py-8 text-base-content/40">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Tất cả tài liệu đã được xem ít nhất một lần
                </div>

                <div x-show="!unreadLoading && unread.length > 0" class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-xs text-base-content/50">
                                <th>Tên tài liệu</th>
                                <th>Loại</th>
                                <th>Ngày tạo</th>
                                <th>Người phụ trách</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in unread" :key="item.id">
                                <tr class="hover">
                                    <td>
                                        <a :href="item.url" class="link link-hover font-medium text-sm" x-text="item.title"></a>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline badge-xs" x-text="typeLabel(item.type)"></span>
                                    </td>
                                    <td class="text-xs text-base-content/50" x-text="item.created_at"></td>
                                    <td class="text-xs text-base-content/70" x-text="item.owner || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
function kcAnalyticsPage(config) {
    return {
        loading: false,
        topViewedLoading: true,
        byTypeLoading: true,
        expiringSoonLoading: true,
        unreadLoading: true,

        topDays: 7,
        topViewed: [],
        byType: [],
        expiringSoon: [],
        unread: [],

        typeLabels: {
            document:   'Tài liệu',
            sop:        'SOP',
            video:      'Video',
            form:       'Biểu mẫu',
            faq:        'FAQ',
            case_study: 'Case Study',
            policy:     'Policy',
        },

        typeLabel(type) {
            return this.typeLabels[type] || type;
        },

        async init() {
            await Promise.all([
                this.loadTopViewed(),
                this.loadByType(),
                this.loadExpiringSoon(),
                this.loadUnread(),
            ]);
        },

        async refresh() {
            this.loading = true;
            await this.init();
            this.loading = false;
        },

        async setTopDays(days) {
            this.topDays = days;
            await this.loadTopViewed();
        },

        async loadTopViewed() {
            this.topViewedLoading = true;
            try {
                const res = await fetch(`${config.topViewedUrl}?days=${this.topDays}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.topViewed = data.items || [];
            } catch (e) {
                this.topViewed = [];
            } finally {
                this.topViewedLoading = false;
            }
        },

        async loadByType() {
            this.byTypeLoading = true;
            try {
                const res = await fetch(config.byTypeUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.byType = data.items || [];
            } catch (e) {
                this.byType = [];
            } finally {
                this.byTypeLoading = false;
            }
        },

        async loadExpiringSoon() {
            this.expiringSoonLoading = true;
            try {
                const res = await fetch(config.expiringSoonUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.expiringSoon = data.items || [];
            } catch (e) {
                this.expiringSoon = [];
            } finally {
                this.expiringSoonLoading = false;
            }
        },

        async loadUnread() {
            this.unreadLoading = true;
            try {
                const res = await fetch(config.unreadUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.unread = data.items || [];
            } catch (e) {
                this.unread = [];
            } finally {
                this.unreadLoading = false;
            }
        },
    };
}
</script>

@endsection
