@extends('layouts.backend')

@section('title', 'Kanban Board — Recruitment')


@section('content')
<div class="p-6 space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Kanban Pipeline</h1>
            <p class="text-sm opacity-60 mt-0.5">
                Job Post UUID: <code class="font-mono text-xs">{{ $jpJobPostUuid }}</code>
                · <span class="font-medium">{{ $totalActive }}</span> ứng viên đang active
            </p>
        </div>
        <a href="{{ route('backend.recruitment.candidates.index') }}" class="btn btn-ghost btn-sm">
            ← Danh sách ứng viên
        </a>
    </div>

    {{-- Kanban columns --}}
    <div class="flex gap-4 overflow-x-auto pb-4" style="min-height: 70vh;">
        @forelse($stages as $stage)
        <div class="shrink-0 w-72 flex flex-col">
            {{-- Column header --}}
            <div class="rounded-t-lg px-3 py-2.5 flex items-center gap-2"
                 style="{{ $stage->color_hex ? 'background: ' . $stage->color_hex . '20; border-top: 3px solid ' . $stage->color_hex : 'background: oklch(var(--b2))' }}">
                <span class="font-semibold text-sm flex-1">{{ $stage->name }}</span>
                <span class="badge badge-sm font-mono">{{ $stage->candidate_count }}</span>
            </div>

            {{-- Cards container --}}
            <div class="bg-base-200/50 rounded-b-lg flex-1 p-2 space-y-2"
                 id="stage-col-{{ $stage->id }}"
                 data-stage-id="{{ $stage->id }}">

                {{-- Cards loaded via JS --}}
                <div class="text-center py-8 text-xs opacity-40" id="stage-empty-{{ $stage->id }}">
                    @if($stage->candidate_count === 0)
                    Không có ứng viên
                    @else
                    <span class="loading loading-spinner loading-xs"></span>
                    @endif
                </div>

            </div>
        </div>
        @empty
        <div class="flex-1 flex items-center justify-center text-sm opacity-50">
            Chưa có pipeline stages nào. <a href="{{ route('backend.recruitment.pipeline-stages.index') }}" class="link ml-1">Cấu hình ngay</a>
        </div>
        @endforelse
    </div>

</div>
@endsection

@push('scripts')
<script>
(function() {
    var JP_UUID  = '{{ $jpJobPostUuid }}';
    var BASE_URL = '{{ url('/backend/api/recruitment/applications') }}';
    var CSRF     = '{{ csrf_token() }}';

    function esc(v) {
        if (v == null) return '';
        return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function loadStageCards(stageId) {
        var url = BASE_URL + '?jp_job_post_id=' + encodeURIComponent(JP_UUID)
                           + '&stage_id=' + stageId
                           + '&status=active&size=50';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var col     = document.getElementById('stage-col-' + stageId);
                var empty   = document.getElementById('stage-empty-' + stageId);
                var items   = (res.data || []);
                if (empty) empty.remove();

                if (items.length === 0) {
                    col.innerHTML = '<div class="text-center py-8 text-xs opacity-40">Không có ứng viên</div>';
                    return;
                }

                items.forEach(function(item) {
                    var card = document.createElement('a');
                    card.href = esc(item.show_url);
                    card.className = 'block bg-base-100 rounded-lg p-3 shadow-sm border border-base-200 hover:shadow-md hover:border-primary/30 transition-all';
                    card.innerHTML =
                        '<div class="font-medium text-sm">' + esc(item.candidate_name) + '</div>'
                        + '<div class="text-xs opacity-50 truncate">' + esc(item.candidate_email) + '</div>'
                        + (item.candidate_title ? '<div class="text-xs opacity-60 mt-1">' + esc(item.candidate_title) + '</div>' : '')
                        + '<div class="flex items-center gap-1 mt-2">'
                        +   '<span class="badge badge-xs badge-outline">' + esc(item.source_label) + '</span>'
                        +   (item.is_disqualified ? '<span class="badge badge-xs badge-warning">DQ</span>' : '')
                        + '</div>'
                        + '<div class="text-xs opacity-40 mt-1">' + esc(item.applied_at) + '</div>';
                    col.appendChild(card);
                });
            })
            .catch(function(e) { console.error('Stage ' + stageId, e); });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-stage-id]').forEach(function(col) {
            loadStageCards(col.dataset.stageId);
        });
    });
})();
</script>
@endpush
