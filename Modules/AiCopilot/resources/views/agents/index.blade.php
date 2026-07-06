@extends('layouts.backend')
@section('title', 'AI Agents')

@section('content')
<div x-data="{ confirmDelete: null }">

    {{-- Page header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">AI Agents</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Cấu hình các agent AI cho tổ chức</p>
        </div>
        @can('ai_copilot.config')
        <a href="{{ route('ai.agents.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tạo Agent
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert alert-success py-3 px-4 mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-error py-3 px-4 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    {{-- System agents --}}
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider mb-3">System Agents</h2>
        <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <table class="table table-sm">
                <thead class="bg-base-200/60">
                    <tr>
                        <th>Slug / Tên</th>
                        <th>Loại</th>
                        <th>Provider / Model</th>
                        <th>Tokens / Temp</th>
                        <th>Trạng thái</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents->where('is_system', true) as $agent)
                    <tr class="hover:bg-base-200/30">
                        <td>
                            <div class="font-mono text-xs text-primary">{{ $agent->slug }}</div>
                            <div class="text-sm font-medium">{{ $agent->name }}</div>
                            @if($agent->description)
                            <div class="text-xs text-base-content/50 mt-0.5 max-w-xs truncate">{{ $agent->description }}</div>
                            @endif
                        </td>
                        <td><span class="badge badge-ghost badge-sm">{{ $agent->task_type }}</span></td>
                        <td>
                            <div class="text-xs font-semibold uppercase">{{ $agent->provider }}</div>
                            <div class="font-mono text-xs text-base-content/60">{{ $agent->model }}</div>
                        </td>
                        <td class="text-xs text-base-content/70">
                            <div>{{ $agent->max_tokens }} tokens</div>
                            <div>temp {{ $agent->temperature }}</div>
                        </td>
                        <td>
                            @if($agent->is_active)
                            <span class="badge badge-success badge-sm">Active</span>
                            @else
                            <span class="badge badge-ghost badge-sm">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('ai.agents.edit', $agent) }}" class="btn btn-ghost btn-xs gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Sửa
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-sm text-base-content/50 py-6">Chưa có system agents.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Custom agents --}}
    <div>
        <h2 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider mb-3">Custom Agents (Của tổ chức)</h2>
        <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <table class="table table-sm">
                <thead class="bg-base-200/60">
                    <tr>
                        <th>Slug / Tên</th>
                        <th>Loại</th>
                        <th>Provider / Model</th>
                        <th>Tokens / Temp</th>
                        <th>Trạng thái</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents->where('is_system', false) as $agent)
                    <tr class="hover:bg-base-200/30">
                        <td>
                            <div class="font-mono text-xs text-secondary">{{ $agent->slug }}</div>
                            <div class="text-sm font-medium">{{ $agent->name }}</div>
                        </td>
                        <td><span class="badge badge-ghost badge-sm">{{ $agent->task_type }}</span></td>
                        <td>
                            <div class="text-xs font-semibold uppercase">{{ $agent->provider }}</div>
                            <div class="font-mono text-xs text-base-content/60">{{ $agent->model }}</div>
                        </td>
                        <td class="text-xs text-base-content/70">
                            <div>{{ $agent->max_tokens }} tokens</div>
                            <div>temp {{ $agent->temperature }}</div>
                        </td>
                        <td>
                            @if($agent->is_active)
                            <span class="badge badge-success badge-sm">Active</span>
                            @else
                            <span class="badge badge-ghost badge-sm">Inactive</span>
                            @endif
                        </td>
                        <td class="flex gap-1">
                            <a href="{{ route('ai.agents.edit', $agent) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            <button @click="confirmDelete = {{ $agent->id }}" class="btn btn-ghost btn-xs text-error">Xóa</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-sm text-base-content/50 py-6">Chưa có custom agent nào. <a href="{{ route('ai.agents.create') }}" class="link link-primary">Tạo ngay</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Delete confirm modal --}}
    <template x-if="confirmDelete !== null">
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Xác nhận xóa agent</h3>
                <p class="py-3 text-sm text-base-content/70">Hành động này không thể hoàn tác. Toàn bộ lịch sử request liên quan vẫn được giữ lại.</p>
                <div class="modal-action">
                    <button @click="confirmDelete = null" class="btn btn-ghost btn-sm">Hủy</button>
                    <form :action="`/dashboard/ai/agents/${confirmDelete}`" method="POST" @submit.prevent="$el.submit()">
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
