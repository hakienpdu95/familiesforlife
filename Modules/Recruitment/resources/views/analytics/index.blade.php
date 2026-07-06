@extends('layouts.backend')

@section('title', 'Analytics — Recruitment')


@section('content')
<div x-data="rcAnalytics" class="p-6 space-y-6 max-w-5xl">

    <h1 class="text-xl font-bold">Recruitment Analytics</h1>

    {{-- Overview cards --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
            <div class="stat-title">Tổng ứng viên</div>
            <div class="stat-value text-primary" x-text="overview.total_candidates ?? '—'"></div>
            <div class="stat-desc" x-text="'Đang active: ' + (overview.active_candidates ?? '—')"></div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
            <div class="stat-title">Đơn ứng tuyển</div>
            <div class="stat-value text-info" x-text="overview.total_applications ?? '—'"></div>
            <div class="stat-desc" x-text="'Đang xử lý: ' + (overview.active_applications ?? '—')"></div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
            <div class="stat-title">Hire tháng này</div>
            <div class="stat-value text-success" x-text="overview.hired_this_month ?? '—'"></div>
            <div class="stat-desc" x-text="'Tổng offer accepted: ' + (overview.offers_accepted ?? '—')"></div>
        </div>
    </div>

    {{-- Funnel --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h3 class="font-semibold mb-4">Funnel chuyển đổi theo stage</h3>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Stage</th>
                            <th class="text-right">Tổng ứng viên</th>
                            <th class="text-right">Đạt</th>
                            <th class="text-right">Tỉ lệ đạt</th>
                            <th>Tiến trình</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in funnel" :key="row.id">
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span x-show="row.color_hex" class="w-2 h-2 rounded-full shrink-0"
                                              :style="'background:' + row.color_hex"></span>
                                        <span x-text="row.name"></span>
                                    </div>
                                </td>
                                <td class="text-right" x-text="row.total"></td>
                                <td class="text-right" x-text="row.passed"></td>
                                <td class="text-right">
                                    <span x-text="row.pass_rate !== null ? row.pass_rate + '%' : '—'"></span>
                                </td>
                                <td class="w-36">
                                    <div class="w-full bg-base-200 rounded-full h-2">
                                        <div class="bg-primary h-2 rounded-full"
                                             :style="'width:' + (row.pass_rate ?? 0) + '%'"></div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="funnel.length === 0">
                            <td colspan="5" class="text-center opacity-40 py-4">Chưa có dữ liệu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-5">

        {{-- Time to hire --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="font-semibold mb-4">Thời gian tuyển dụng (ngày)</h3>
                <div x-show="timeToHire.length === 0" class="text-center py-8 opacity-40 text-sm">
                    Chưa có dữ liệu hire
                </div>
                <template x-for="row in timeToHire" :key="row.jp_job_post_id">
                    <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                        <div>
                            <p class="text-sm font-mono opacity-60 text-xs" x-text="row.jp_job_post_id ?? 'Direct'"></p>
                            <p class="text-xs opacity-40" x-text="row.hired + ' người hire'"></p>
                        </div>
                        <span class="font-bold text-lg" x-text="row.avg_days_to_hire + ' ngày'"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- Source effectiveness --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="font-semibold mb-4">Hiệu quả nguồn ứng viên</h3>
                <div x-show="source.length === 0" class="text-center py-8 opacity-40 text-sm">
                    Chưa có dữ liệu
                </div>
                <template x-for="row in source" :key="row.source">
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium capitalize" x-text="row.source"></span>
                            <span class="opacity-60" x-text="row.total + ' đơn · ' + row.conversion_rate + '% hire'"></span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2">
                            <div class="bg-success h-2 rounded-full"
                                 :style="'width:' + row.conversion_rate + '%'"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    var BASE = '{{ url('dashboard/recruitment/analytics') }}';

    Alpine.data('rcAnalytics', function() {
        return {
            overview: {},
            funnel: [],
            timeToHire: [],
            source: [],

            init: function() {
                var self = this;

                fetch(BASE + '/overview', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(d) { self.overview = d; });

                fetch(BASE + '/funnel', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(d) { self.funnel = d; });

                fetch(BASE + '/time-to-hire', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(d) { self.timeToHire = d; });

                fetch(BASE + '/source', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(d) { self.source = d; });
            },
        };
    });
});
</script>
@endpush
