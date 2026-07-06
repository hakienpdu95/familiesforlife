@extends('layouts.backend')
@section('title', 'Quản lý Assessment')


@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Quản lý Assessment</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Cấu hình chấm điểm khảo sát theo maturity model</p>
    </div>
    @can('assessment.config')
    <a href="{{ route('assessments.create') }}" class="btn btn-primary btn-sm">+ Tạo Scoring Profile</a>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success mb-4 py-2 text-sm">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr class="text-xs text-base-content/50 uppercase">
                    <th>Assessment Code</th>
                    <th>Tên</th>
                    <th>Nguồn</th>
                    <th>Aggregation</th>
                    <th>Classification</th>
                    <th class="text-center">Scoring</th>
                    <th class="text-center">Active</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assessments as $a)
                <tr class="hover:bg-base-200/30">
                    <td class="font-mono text-xs font-semibold text-primary">{{ $a->assessment_code }}</td>
                    <td class="font-medium">{{ $a->name }}</td>
                    <td>
                        @switch($a->source_type ?? 'standalone')
                            @case('global_template')
                                <span class="badge badge-xs badge-info">Global template</span>
                                @break
                            @case('survey_linked')
                                <span class="badge badge-xs badge-success font-mono text-xs">
                                    Survey: {{ $a->source_ref ?? '—' }}
                                </span>
                                @break
                            @case('lead_scoring')
                                <span class="badge badge-xs badge-warning">Lead scoring</span>
                                @break
                            @default
                                <span class="badge badge-xs badge-ghost">Standalone</span>
                        @endswitch
                    </td>
                    <td>
                        <span class="badge badge-xs badge-ghost">{{ $a->aggregation_model }}</span>
                    </td>
                    <td>
                        <span class="badge badge-xs badge-ghost">{{ $a->classification_type }}</span>
                    </td>
                    <td class="text-center">
                        @if($a->has_scoring)
                        <span class="badge badge-xs badge-success">On</span>
                        @else
                        <span class="badge badge-xs badge-ghost">Off</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($a->is_active)
                        <span class="badge badge-xs badge-success">Active</span>
                        @else
                        <span class="badge badge-xs badge-error">Inactive</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex justify-end gap-1">
                            @can('assessment.config')
                            <a href="{{ route('assessments.config.index', $a->assessment_code) }}"
                               class="btn btn-xs btn-ghost" title="Cấu hình">⚙</a>
                            @endcan
                            @can('assessment.results')
                            <a href="{{ route('assessments.results.index', $a->assessment_code) }}"
                               class="btn btn-xs btn-ghost" title="Kết quả">📊</a>
                            @endcan
                            @can('assessment.config')
                            <a href="{{ route('assessments.edit', $a->assessment_code) }}"
                               class="btn btn-xs btn-ghost">Sửa</a>
                            <form method="POST" action="{{ route('assessments.destroy', $a->assessment_code) }}"
                                  onsubmit="return confirm('Xóa assessment này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-ghost text-error">Xóa</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-base-content/40 text-sm">
                        Chưa có assessment nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assessments->hasPages())
    <div class="p-4">{{ $assessments->links() }}</div>
    @endif
</div>
@endsection
