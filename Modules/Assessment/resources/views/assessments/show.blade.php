@extends('layouts.backend')
@section('title', $assessment->assessment_code)


@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $assessment->name }}</h1>
        <p class="font-mono text-sm text-base-content/50 mt-0.5">{{ $assessment->assessment_code }}</p>
    </div>
    <div class="flex gap-2">
        @can('assessment.config')
        <a href="{{ route('assessments.config.index', $assessment->assessment_code) }}" class="btn btn-primary btn-sm">⚙ Cấu hình</a>
        @endcan
        @can('assessment.results')
        <a href="{{ route('assessments.results.index', $assessment->assessment_code) }}" class="btn btn-ghost btn-sm">📊 Kết quả</a>
        @endcan
        @can('assessment.config')
        <a href="{{ route('assessments.edit', $assessment->assessment_code) }}" class="btn btn-ghost btn-sm">Sửa</a>
        @endcan
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl">
    @foreach([
        ['label' => 'Aggregation', 'value' => $assessment->aggregation_model],
        ['label' => 'Classification', 'value' => $assessment->classification_type],
        ['label' => 'Scoring', 'value' => $assessment->has_scoring ? 'Bật' : 'Tắt'],
        ['label' => 'Trạng thái', 'value' => $assessment->is_active ? 'Active' : 'Inactive'],
    ] as $item)
    <div class="card bg-base-100 border border-base-200 shadow-sm p-4">
        <p class="text-xs text-base-content/50">{{ $item['label'] }}</p>
        <p class="font-medium text-sm mt-1">{{ $item['value'] }}</p>
    </div>
    @endforeach
</div>
@endsection
