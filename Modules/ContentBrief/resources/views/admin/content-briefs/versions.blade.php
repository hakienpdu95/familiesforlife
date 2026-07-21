@extends('layouts.backend')
@section('title', 'Lịch sử phiên bản')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Lịch sử phiên bản</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $brief->title }}</p>
    </div>
    <a href="{{ route('backend.content_brief.items.edit', $brief) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@foreach(['success','error'] as $type)
    @if(session($type))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
    </div>
    @endif
@endforeach

<div class="space-y-4">
@foreach($versions as $version)
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2">
                    <span class="font-semibold">v{{ $version->version_number }}</span>
                    <span class="badge {{ $version->status->badgeClass() }} badge-sm">{{ $version->status->label() }}</span>
                    @if($brief->current_version_id === $version->id)
                    <span class="badge badge-outline badge-sm">Hiện tại</span>
                    @endif
                    <span class="text-xs text-base-content/40">trigger: {{ $version->trigger->value }}</span>
                </div>
                <div class="text-xs text-base-content/50">
                    {{ $version->created_at->format('d/m/Y H:i') }} — {{ $version->createdBy?->name }}
                </div>
            </div>

            @if($version->status->value === 'rejected' && $version->rejected_reason)
            <div class="alert alert-error text-xs py-2 mb-2">Lý do từ chối: {{ $version->rejected_reason }}</div>
            @endif

            @if($version->restored_from_version_id)
            <p class="text-xs text-base-content/40 mb-2">Phục hồi từ version #{{ $version->restoredFrom?->version_number }}</p>
            @endif

            {{-- Diff tóm tắt so với version liền trước (§4.2 — scalar-compare + positional) --}}
            @if(!empty($version->diff_against_previous))
            <details class="mb-2">
                <summary class="text-xs cursor-pointer text-primary">Xem thay đổi so với v{{ $version->version_number - 1 }} ({{ count($version->diff_against_previous) }} field)</summary>
                <ul class="text-xs mt-2 space-y-1 pl-4 list-disc">
                    @foreach($version->diff_against_previous as $change)
                    <li><span class="font-mono">{{ $change['field'] }}</span> đã thay đổi</li>
                    @endforeach
                </ul>
            </details>
            @endif

            @if($brief->current_version_id === $version->id && $version->status->value === 'approved')
            <div class="divider my-2"></div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold">Generation</h3>
                <form method="POST" action="{{ route('backend.content_brief.items.generate', $brief) }}">
                    @csrf
                    <button class="btn btn-xs btn-secondary">Yêu cầu sinh nội dung</button>
                </form>
            </div>

            @forelse($version->generations as $generation)
            <div class="border border-base-200 rounded-lg p-3 mb-2 text-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="badge {{ $generation->status->badgeClass() }} badge-sm">{{ $generation->status->label() }}</span>
                    <span class="text-base-content/40">
                        @if($generation->requested_at)
                        Yêu cầu lúc {{ $generation->requested_at->format('d/m/Y H:i') }}
                        ({{ $generation->requested_at->diffForHumans(null, true) }})
                        @endif
                    </span>
                </div>

                @if($generation->status->value === 'failed' && $generation->error_message)
                <p class="text-error mb-1.5">Lỗi: {{ $generation->error_message }}</p>
                @endif

                @if(in_array($generation->status->value, ['pending', 'processing']))
                <div class="flex gap-1.5 mt-1.5">
                    <button type="button" class="btn btn-2xs btn-outline" onclick="complete_modal_{{ $generation->id }}.showModal()">Nhập kết quả thủ công</button>
                    <button type="button" class="btn btn-2xs btn-outline btn-error" onclick="fail_modal_{{ $generation->id }}.showModal()">Báo lỗi</button>
                </div>

                <dialog id="complete_modal_{{ $generation->id }}" class="modal">
                    <div class="modal-box max-w-2xl">
                        <h3 class="font-bold text-base mb-2">Nhập kết quả thủ công (dán JSON)</h3>
                        <p class="text-xs text-base-content/50 mb-2">
                            Cấu trúc chuẩn: <code>{"title": "...", "meta_description": "...", "sections": [{"heading": "...", "level": 2, "content_html": "..."}], "word_count": 0, "seo_keywords_used": []}</code>
                        </p>
                        <form method="POST" action="{{ route('backend.content_brief.generations.complete', $generation) }}">
                            @csrf
                            <textarea name="output_json" required rows="10" class="textarea textarea-bordered w-full font-mono text-xs" placeholder='{"title": "...", "sections": []}'></textarea>
                            <div class="modal-action">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="complete_modal_{{ $generation->id }}.close()">Huỷ</button>
                                <button type="submit" class="btn btn-primary btn-sm">Ghi nhận output</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </dialog>

                <dialog id="fail_modal_{{ $generation->id }}" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-base mb-2">Báo lỗi generation</h3>
                        <form method="POST" action="{{ route('backend.content_brief.generations.fail', $generation) }}">
                            @csrf
                            <textarea name="error_message" required rows="3" class="textarea textarea-bordered w-full" placeholder="Lý do thất bại..."></textarea>
                            <div class="modal-action">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="fail_modal_{{ $generation->id }}.close()">Huỷ</button>
                                <button type="submit" class="btn btn-error btn-sm">Xác nhận</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </dialog>
                @endif

                @if($generation->status->value === 'completed')
                <button type="button" class="btn btn-2xs btn-outline mt-1.5" onclick="output_modal_{{ $generation->id }}.showModal()">Xem/Sao chép JSON output</button>
                <dialog id="output_modal_{{ $generation->id }}" class="modal">
                    <div class="modal-box max-w-2xl">
                        <h3 class="font-bold text-base mb-2">JSON output</h3>
                        <pre class="bg-base-200 rounded p-3 text-xs overflow-x-auto">{{ json_encode($generation->output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        <div class="modal-action">
                            <form method="dialog"><button class="btn btn-sm">Đóng</button></form>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </dialog>
                @endif
            </div>
            @empty
            <p class="text-xs text-base-content/40">Chưa có generation nào cho version này.</p>
            @endforelse
            @endif

            @if($version->status->value !== 'approved' || $brief->current_version_id !== $version->id)
            <form method="POST" action="{{ route('backend.content_brief.items.restore', [$brief, $version]) }}" class="mt-2">
                @csrf
                <button class="btn btn-2xs btn-ghost">Tạo bản nháp mới từ version này</button>
            </form>
            @endif
        </div>
    </div>
@endforeach
</div>

@endsection
