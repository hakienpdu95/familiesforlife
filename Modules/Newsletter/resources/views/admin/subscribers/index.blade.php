@extends('layouts.backend')
@section('title', 'Người đăng ký bản tin')

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
        <h1 class="text-2xl font-bold text-base-content">Người đăng ký bản tin</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tổng {{ $subscribers->total() }} người — đồng bộ 2 chiều với Resend Contacts.</p>
    </div>
    @can('sendBroadcast', \Modules\Newsletter\Models\NewsletterBroadcastLog::class)
    <a href="{{ route('backend.newsletter.broadcast.create') }}" class="btn btn-primary btn-sm gap-1.5">
        + Soạn bản tin
    </a>
    @endcan
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng ký</th>
                    <th class="text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                <tr>
                    <td>{{ $subscriber->full_name }}</td>
                    <td class="text-xs">{{ $subscriber->email }}</td>
                    <td>
                        <span class="badge badge-sm {{ $subscriber->status->badgeClass() }}">{{ $subscriber->status->label() }}</span>
                    </td>
                    <td class="text-xs text-base-content/60">{{ $subscriber->subscribed_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-right">
                        @can('removeSubscriber', $subscriber)
                        <form method="POST" action="{{ route('backend.newsletter.subscribers.destroy', $subscriber) }}"
                              onsubmit="return confirm('Xoá subscriber {{ addslashes($subscriber->email) }}? Người này sẽ ngừng nhận bản tin.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-xs text-base-content/40 py-6">Chưa có ai đăng ký.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subscribers->hasPages())
    <div class="p-4 border-t border-base-200">
        {{ $subscribers->links() }}
    </div>
    @endif
</div>

</div>
@endsection
