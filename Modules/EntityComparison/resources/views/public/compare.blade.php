@extends('layouts.frontend')

@section('title', 'So sánh — ' . $entityType->name)
@section('meta_description', "Bảng so sánh {$entityType->name} theo tiêu chí.")

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li><a href="{{ route('entity_comparison.public.index', $entityType) }}">{{ $entityType->name }}</a></li>
            <li>So sánh</li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-1">So sánh {{ $entityType->name }}</h1>
    <p class="text-sm text-base-content/60 mb-6">
        <a href="{{ route('entity_comparison.public.index', $entityType) }}" class="link">← Quay lại danh sách để đổi lựa chọn</a>
    </p>

    {{-- §11 — bảng responsive, scroll ngang trên mobile. --}}
    <div class="overflow-x-auto rounded-xl border border-base-200 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-100 sticky left-0 z-10 min-w-40">Tiêu chí</th>
                    @foreach($entities as $entity)
                    <th class="bg-base-100 min-w-56 align-top">
                        @if($entity->getFirstMediaUrl('cover'))
                        <img src="{{ $entity->getFirstMediaUrl('cover', 'thumb') }}" alt="{{ $entity->name }}"
                             class="w-full aspect-video object-cover rounded mb-2">
                        @endif
                        <span class="block font-bold leading-snug">{{ $entity->name }}</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                @php
                    $criterion = $row['criterion'];
                    $uniqueValues = collect($row['cells'])->unique();
                    $isDifferent = $uniqueValues->count() > 1;
                @endphp
                <tr>
                    <td class="font-medium bg-base-100 sticky left-0 z-10">
                        {{ $criterion->name }}
                        @if($criterion->trashed())
                        {{-- §9 — style cố định badge-ghost badge-sm cho tiêu chí đã ngừng sử dụng. --}}
                        <span class="badge badge-ghost badge-sm ml-1" title="Tiêu chí này đã ngừng sử dụng, không còn hiện ở form nhập mới.">Tiêu chí đã ngừng sử dụng</span>
                        @endif
                    </td>
                    @foreach($entities as $entity)
                    <td class="{{ $isDifferent ? 'bg-warning/10 font-medium' : '' }}">
                        {{ $row['cells'][$entity->uuid] ?? '—' }}
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr><td colspan="{{ $entities->count() + 1 }}" class="text-center text-base-content/40 py-8">Không có tiêu chí nào để so sánh.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
