@extends('layouts.backend')
@section('title', 'Chờ duyệt của tôi')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chờ duyệt của tôi</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Danh sách nội dung đang chờ duyệt mà bạn có quyền xử lý — xem
            spec/Workflow_Approval_Technical_Specification.md §12.
        </p>
    </div>
</div>

@forelse ($pending as $subjectType => $items)
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                {{ config("approval.subjects.{$subjectType}.label", $subjectType) }}
                <span class="badge badge-warning badge-sm ml-1">{{ $items->count() }}</span>
            </p>

            <ul class="divide-y divide-base-200">
                @foreach ($items as $approvalSubject)
                    @php($entity = $approvalSubject->subject)
                    @continue(! $entity)
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <div>
                            <span class="font-medium">{{ class_basename($entity) }} #{{ $entity->id }}</span>
                            @if ($entity->name ?? false)
                                <span class="text-base-content/60">— {{ $entity->name }}</span>
                            @endif
                            @if ($entity->relationLoaded('organization') && $entity->organization)
                                <span class="badge badge-ghost badge-xs ml-1">{{ $entity->organization->name }}</span>
                            @endif
                            <span class="text-xs text-base-content/40 block">
                                gửi lúc {{ $approvalSubject->updated_at->diffForHumans() }}
                            </span>
                        </div>
                        <a href="{{ $entity->approvalDashboardUrl ?? '#' }}" class="btn btn-sm btn-primary">
                            Xem &amp; duyệt
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@empty
    <div class="alert alert-success text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span>Không có nội dung nào đang chờ bạn duyệt.</span>
    </div>
@endforelse
@endsection
