@extends('layouts.backend')
@section('title', 'Kết quả — ' . $assessment->assessment_code)


@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Kết quả chấm điểm</h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">{{ $assessment->assessment_code }}</p>
    </div>
    <a href="{{ route('assessments.config.index', $assessment->assessment_code) }}" class="btn btn-ghost btn-sm">⚙ Cấu hình</a>
</div>

{{-- Filters --}}
<div class="flex gap-3 mb-4 flex-wrap">
    <form method="GET" class="flex gap-2 items-center">
        <select name="band" class="select select-bordered select-sm" onchange="this.form.submit()">
            <option value="">Tất cả band</option>
            @foreach($results->pluck('maturity_level')->unique()->filter() as $band)
            <option value="{{ $band }}" {{ request('band') === $band ? 'selected' : '' }}>{{ $band }}</option>
            @endforeach
        </select>
        <select name="sort" class="select select-bordered select-sm" onchange="this.form.submit()">
            <option value="calculated_at" {{ request('sort','calculated_at') === 'calculated_at' ? 'selected' : '' }}>Mới nhất</option>
            <option value="overall_score" {{ request('sort') === 'overall_score' ? 'selected' : '' }}>Điểm</option>
        </select>
    </form>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr class="text-xs text-base-content/50 uppercase">
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Điểm tổng</th>
                    <th>Maturity Level</th>
                    <th>Tính lúc</th>
                    <th class="text-right">Xem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $r)
                <tr class="hover:bg-base-200/30">
                    <td class="font-mono text-xs text-base-content/50">#{{ $r->id }}</td>
                    <td>
                        <span class="text-xs text-base-content/40">{{ class_basename($r->subject_type) }}</span>
                        <span class="font-mono text-xs ml-1">#{{ $r->subject_id }}</span>
                    </td>
                    <td>
                        <span class="font-bold text-primary text-lg">{{ round($r->overall_score, 1) }}</span>
                        <span class="text-xs text-base-content/40">/100</span>
                    </td>
                    <td>
                        <span class="badge badge-sm badge-soft badge-info font-mono">{{ $r->maturity_level ?? '—' }}</span>
                    </td>
                    <td class="text-sm text-base-content/60">
                        {{ $r->calculated_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="text-right">
                        <a href="{{ route('assessments.results.show', [$assessment->assessment_code, $r->id]) }}"
                           class="btn btn-xs btn-ghost">Chi tiết</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-base-content/40 text-sm">
                        Chưa có kết quả nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($results->hasPages())
    <div class="p-4">{{ $results->links() }}</div>
    @endif
</div>
@endsection
