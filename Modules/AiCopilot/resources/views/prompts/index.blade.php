@extends('layouts.backend')
@section('title', 'Prompt Library')

@section('content')
<div x-data="{ confirmDelete: null }">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Prompt Library</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý prompt templates cho AI agents</p>
        </div>
        @can('prompt.full')
        <a href="{{ route('ai.prompts.create', $agentId ? ['agent_id' => $agentId] : []) }}"
           class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tạo Prompt
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert alert-success py-3 px-4 mb-4 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Filter by agent --}}
    <div class="flex flex-wrap items-center gap-2 mb-5">
        <a href="{{ route('ai.prompts.index') }}"
           class="badge @if(!$agentId) badge-primary @else badge-ghost hover:badge-primary @endif cursor-pointer px-3 py-3 text-xs">
            Tất cả
        </a>
        @foreach($agents as $ag)
        <a href="{{ route('ai.prompts.index', ['agent_id' => $ag->id]) }}"
           class="badge @if($agentId == $ag->id) badge-primary @else badge-ghost hover:badge-primary @endif cursor-pointer px-3 py-3 text-xs gap-1">
            @if($ag->is_system)<span class="opacity-50">⚙</span>@endif
            {{ $ag->name }}
        </a>
        @endforeach
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <table class="table table-sm">
            <thead class="bg-base-200/60">
                <tr>
                    <th>Tên Prompt</th>
                    <th>Agent</th>
                    <th>Version</th>
                    <th>Biến</th>
                    <th>Trạng thái</th>
                    <th class="w-40"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($prompts as $prompt)
                <tr class="hover:bg-base-200/30">
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm">{{ $prompt->name }}</span>
                            @if($prompt->is_default)
                            <span class="badge badge-primary badge-xs">Default</span>
                            @endif
                        </div>
                        @if($prompt->description)
                        <div class="text-xs text-base-content/50 mt-0.5 max-w-xs truncate">{{ $prompt->description }}</div>
                        @endif
                        @if(is_null($prompt->organization_id))
                        <div class="text-xs text-warning mt-0.5">System prompt</div>
                        @endif
                    </td>
                    <td>
                        <div class="text-xs font-mono text-primary">{{ $prompt->agent?->slug }}</div>
                        <div class="text-xs text-base-content/60">{{ $prompt->agent?->name }}</div>
                    </td>
                    <td>
                        <span class="badge badge-ghost badge-sm">v{{ $prompt->version }}</span>
                    </td>
                    <td class="text-xs text-base-content/70">
                        {{ count($prompt->variables_schema ?? []) }} biến
                    </td>
                    <td>
                        @if($prompt->is_active)
                        <span class="badge badge-success badge-sm">Active</span>
                        @else
                        <span class="badge badge-ghost badge-sm">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            @if(!$prompt->is_default)
                            <form action="{{ route('ai.prompts.setDefault', $prompt) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-xs text-primary" title="Đặt làm default">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('ai.prompts.edit', $prompt) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            @if(!($prompt->is_default && is_null($prompt->organization_id)))
                            <button @click="confirmDelete = {{ $prompt->id }}" class="btn btn-ghost btn-xs text-error">Xóa</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-sm text-base-content/50 py-8">
                        Chưa có prompt nào.
                        <a href="{{ route('ai.prompts.create') }}" class="link link-primary ml-1">Tạo ngay</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Delete modal --}}
    <template x-if="confirmDelete !== null">
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Xóa prompt này?</h3>
                <p class="py-3 text-sm text-base-content/70">Prompt sẽ bị xóa khỏi thư viện. Các AI request đã thực hiện với prompt này vẫn được lưu lại.</p>
                <div class="modal-action">
                    <button @click="confirmDelete = null" class="btn btn-ghost btn-sm">Hủy</button>
                    <form :action="`/dashboard/ai/prompts/${confirmDelete}`" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error btn-sm">Xóa</button>
                    </form>
                </div>
            </div>
            <div class="modal-backdrop" @click="confirmDelete = null"></div>
        </div>
    </template>

</div>
@endsection
