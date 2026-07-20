@extends('layouts.backend')
@section('title', 'Lịch sử gửi bản tin')

@section('content')
<div>

@foreach(['success','error'] as $type)
    @if(session($type))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition.opacity.duration.500ms class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
        <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
    </div>
    @endif
@endforeach

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Lịch sử gửi bản tin</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Số liệu mở/click chi tiết xem trực tiếp trên Resend Dashboard.</p>
    </div>
    @can('sendBroadcast', \Modules\Newsletter\Models\NewsletterBroadcastLog::class)
    <a href="{{ route('backend.newsletter.broadcast.create') }}" class="btn btn-primary btn-sm gap-1.5">
        + Soạn bản tin mới
    </a>
    @endcan
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Chủ đề</th>
                    <th>Thời điểm gửi/lên lịch</th>
                    <th>Người gửi</th>
                    <th>Resend Broadcast ID</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="font-medium">{{ $log->subject }}</td>
                    <td class="text-xs text-base-content/60">
                        @if($log->scheduled_at)
                            Lên lịch: {{ $log->scheduled_at->format('d/m/Y H:i') }}
                        @else
                            {{ $log->created_at->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td class="text-xs">{{ $log->sentBy?->name ?? 'Hệ thống' }}</td>
                    <td class="text-xs text-base-content/40 font-mono">{{ $log->resend_broadcast_id ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-xs text-base-content/40 py-6">Chưa gửi bản tin nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="p-4 border-t border-base-200">
        {{ $logs->links() }}
    </div>
    @endif
</div>

</div>
@endsection
