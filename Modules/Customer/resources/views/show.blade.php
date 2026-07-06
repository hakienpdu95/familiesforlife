@extends('layouts.backend')
@section('title', $customer->display_name)

@section('content')

@can('delete', $customer)
<form id="form-delete-customer" method="POST" action="{{ route('customer.destroy', $customer) }}" class="hidden">
    @csrf @method('DELETE')
</form>
@endcan

<div x-data="customerShowPage()"
     data-customer-show
     data-customer-id="{{ $customer->id }}"
     data-csrf="{{ csrf_token() }}">

    {{-- ── Page header ──────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">

        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                <span class="text-xs font-mono text-base-content/30">{{ $customer->customer_code ?? '#'.$customer->id }}</span>

                <span class="badge badge-sm badge-soft {{ $customer->customer_type->badgeClass() }}">
                    {{ $customer->customer_type->label() }}
                </span>

                <span class="badge badge-sm badge-soft {{ $customer->lifecycle_stage->badgeClass() }}">
                    {{ $customer->lifecycle_stage->label() }}
                </span>
            </div>

            <h1 class="text-xl font-bold text-base-content leading-tight">
                {{ $customer->display_name }}
            </h1>

            @if($customer->notes)
            <p class="text-sm text-base-content/50 mt-1 line-clamp-2">{{ $customer->notes }}</p>
            @endif
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('customer.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Danh sách
            </a>
            @can('update', $customer)
            <a href="{{ route('customer.edit', $customer) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Sửa
            </a>
            @endcan
            @can('delete', $customer)
            <button type="button" class="btn btn-ghost btn-sm text-error gap-1.5"
                    onclick="document.getElementById('form-delete-customer').submit()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Xóa
            </button>
            @endcan
        </div>

    </div>

    {{-- ── Tab nav ───────────────────────────────────────────────────── --}}
    <div class="border-b border-base-200 mb-6">
        <nav class="flex gap-6" role="tablist">

            @php $tabs = [
                ['id'=>'info',     'label'=>'Thông tin'],
                ['id'=>'activity', 'label'=>'Hoạt động (' . $customer->activities->count() . ')'],
                ['id'=>'notes',    'label'=>'Ghi chú (' . $customer->customerNotes->count() . ')'],
                ['id'=>'leads',    'label'=>'Cơ hội liên quan (' . $customer->leads->count() . ')'],
            ]; @endphp

            @foreach($tabs as $t)
            <button type="button" role="tab"
                    :aria-selected="tab === '{{ $t['id'] }}'"
                    @click="tab = '{{ $t['id'] }}'"
                    class="pb-3 text-sm font-medium border-b-2 transition-colors"
                    :class="tab === '{{ $t['id'] }}'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                {{ $t['label'] }}
            </button>
            @endforeach

        </nav>
    </div>

    {{-- ── Tab: Thông tin ───────────────────────────────────────────── --}}
    <div x-show="tab === 'info'" class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-6 items-start">

        {{-- Main info card --}}
        <div class="space-y-4">

            {{-- Contact info --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide mb-4">Thông tin liên hệ</h2>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">

                        @if($customer->customer_type->value === 1)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Họ và tên</dt>
                            <dd class="font-medium">{{ $customer->first_name }} {{ $customer->last_name }}</dd>
                        </div>
                        @if($customer->gender)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Giới tính</dt>
                            <dd>{{ $customer->gender === 'M' ? 'Nam' : ($customer->gender === 'F' ? 'Nữ' : 'Khác') }}</dd>
                        </div>
                        @endif
                        @if($customer->date_of_birth)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Ngày sinh</dt>
                            <dd>{{ $customer->date_of_birth->format('d/m/Y') }}</dd>
                        </div>
                        @endif
                        @endif

                        @if($customer->customer_type->value === 2)
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-base-content/40 mb-0.5">Tên doanh nghiệp</dt>
                            <dd class="font-medium">{{ $customer->company_name }}</dd>
                        </div>
                        @if($customer->tax_code)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Mã số thuế</dt>
                            <dd class="font-mono">{{ $customer->tax_code }}</dd>
                        </div>
                        @endif
                        @if($customer->industry)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Ngành nghề</dt>
                            <dd>{{ $customer->industry }}</dd>
                        </div>
                        @endif
                        @if($customer->company_size)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Quy mô</dt>
                            <dd>{{ $customer->company_size->label() }}</dd>
                        </div>
                        @endif
                        @if($customer->representative_name)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Người đại diện</dt>
                            <dd>{{ $customer->representative_name }}
                                @if($customer->representative_title)
                                <span class="text-base-content/40"> — {{ $customer->representative_title }}</span>
                                @endif
                            </dd>
                        </div>
                        @endif
                        @endif

                        @if($customer->primary_email)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Email chính</dt>
                            <dd><a href="mailto:{{ $customer->primary_email }}" class="link link-hover text-primary">{{ $customer->primary_email }}</a></dd>
                        </div>
                        @endif
                        @if($customer->secondary_email)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Email phụ</dt>
                            <dd><a href="mailto:{{ $customer->secondary_email }}" class="link link-hover text-primary">{{ $customer->secondary_email }}</a></dd>
                        </div>
                        @endif
                        @if($customer->primary_phone)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Điện thoại chính</dt>
                            <dd><a href="tel:{{ $customer->primary_phone }}" class="link link-hover">{{ $customer->primary_phone }}</a></dd>
                        </div>
                        @endif
                        @if($customer->secondary_phone)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">Điện thoại phụ</dt>
                            <dd>{{ $customer->secondary_phone }}</dd>
                        </div>
                        @endif
                        @if($customer->website)
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-base-content/40 mb-0.5">Website</dt>
                            <dd><a href="{{ $customer->website }}" target="_blank" rel="noopener" class="link link-hover text-primary">{{ $customer->website }}</a></dd>
                        </div>
                        @endif

                    </dl>
                </div>
            </div>

            {{-- Address --}}
            @if($customer->address_line || $customer->province_name)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide mb-4">Địa chỉ</h2>
                    <p class="text-sm">
                        @if($customer->address_line){{ $customer->address_line }}@endif
                        @if($customer->ward_name), {{ $customer->ward_name }}@endif
                        @if($customer->province_name), {{ $customer->province_name }}@endif
                    </p>
                </div>
            </div>
            @endif

            {{-- Custom meta fields --}}
            @if($fieldDefs->count() && $customer->meta->count())
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide mb-4">Thông tin bổ sung</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        @foreach($customer->meta as $meta)
                        @if($meta->definition)
                        <div>
                            <dt class="text-xs text-base-content/40 mb-0.5">{{ $meta->definition->label }}</dt>
                            <dd>{{ $meta->getValue() ?? '—' }}</dd>
                        </div>
                        @endif
                        @endforeach
                    </dl>
                </div>
            </div>
            @endif

        </div>

        {{-- Sidebar card --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4 text-sm space-y-3">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Thông tin CRM</p>

                    @if($customer->assignee)
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Phụ trách</span>
                        <span class="font-medium">{{ $customer->assignee->name }}</span>
                    </div>
                    @endif

                    @if($customer->source)
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Nguồn</span>
                        <span>{{ $customer->source->label }}</span>
                    </div>
                    @endif

                    @if($customer->province_name)
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Khu vực</span>
                        <span>{{ $customer->province_name }}</span>
                    </div>
                    @endif

                    @if($customer->last_activity_at)
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Hoạt động cuối</span>
                        <span class="text-xs">{{ $customer->last_activity_at->diffForHumans() }}</span>
                    </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Số hoạt động</span>
                        <span>{{ $customer->activity_count }}</span>
                    </div>

                    <div class="divider my-1"></div>

                    <div class="flex items-center justify-between text-xs text-base-content/40">
                        <span>Tạo lúc</span>
                        <span>{{ $customer->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                </div>
            </div>

            @if($customer->tags->count())
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Tags</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($customer->tags as $tag)
                        <span class="badge badge-sm" style="{{ $tag->color ? 'background:'.$tag->color.'20;color:'.$tag->color.';border-color:'.$tag->color.'40' : '' }}">
                            {{ $tag->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

    </div>

    {{-- ── Tab: Hoạt động ─────────────────────────────────────────────── --}}
    <div x-show="tab === 'activity'">

        @can('update', $customer)
        <div class="card bg-base-100 shadow-sm border border-base-200 mb-3">
            <div class="card-body py-3 px-4">
                <div x-data="{ open: false }">
                    <button class="btn btn-sm btn-ghost gap-1.5 text-primary" @click="open = !open">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ghi lại hoạt động
                    </button>
                    <div x-show="open" x-transition class="mt-3 pt-3 border-t border-base-200">
                        <form id="activity-form" @submit.prevent="submitActivity($event)">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="form-control">
                                    <label class="label py-0 pb-1">
                                        <span class="label-text text-xs font-medium">Loại <span class="text-error">*</span></span>
                                    </label>
                                    <select name="type" class="select select-bordered select-xs w-full" required>
                                        @foreach(\Modules\Customer\Enums\CustomerActivityType::cases() as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0 pb-1">
                                        <span class="label-text text-xs font-medium">Ngày hoàn thành</span>
                                    </label>
                                    <input type="datetime-local" name="completed_at"
                                           value="{{ now()->format('Y-m-d\TH:i') }}"
                                           class="input input-bordered input-xs w-full">
                                </div>
                            </div>
                            <div class="form-control mb-3">
                                <label class="label py-0 pb-1">
                                    <span class="label-text text-xs font-medium">Tiêu đề <span class="text-error">*</span></span>
                                </label>
                                <input type="text" name="title" required
                                       class="input input-bordered input-xs w-full"
                                       placeholder="VD: Gọi điện tư vấn sản phẩm">
                            </div>
                            <div class="form-control mb-3">
                                <label class="label py-0 pb-1">
                                    <span class="label-text text-xs font-medium">Nội dung / Kết quả</span>
                                </label>
                                <textarea name="description" rows="2"
                                          class="textarea textarea-bordered textarea-xs w-full"
                                          placeholder="Ghi tóm tắt nội dung trao đổi..."></textarea>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-primary btn-xs" :disabled="actSaving">
                                    <span x-show="actSaving" class="loading loading-spinner loading-xs mr-1"></span>
                                    Lưu hoạt động
                                </button>
                                <button type="button" class="btn btn-ghost btn-xs" @click="open = false">Hủy</button>
                            </div>
                            <p x-show="actError" x-text="actError" class="text-xs text-error mt-2"></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0">
                @if($customer->activities->isEmpty())
                <div class="py-12 text-center text-base-content/30">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">Chưa có hoạt động nào</p>
                </div>
                @else
                <ul id="activity-list" class="divide-y divide-base-200">
                    @foreach($customer->activities as $act)
                    <li class="px-5 py-4 flex gap-3">
                        <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="text-xs">{{ $act->type?->icon() ?? '•' }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-medium text-sm">{{ $act->title }}</p>
                                <span class="text-xs text-base-content/40 shrink-0">
                                    {{ $act->completed_at?->format('d/m/Y H:i') ?? $act->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            @if($act->description)
                            <p class="text-sm text-base-content/60 mt-1">{{ $act->description }}</p>
                            @endif
                            <p class="text-xs text-base-content/40 mt-1">{{ $act->actor_name }}</p>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Tab: Ghi chú ────────────────────────────────────────────────── --}}
    <div x-show="tab === 'notes'">

        <div class="card bg-base-100 shadow-sm border border-base-200 mb-3">
            <div class="card-body py-3 px-4">
                <form @submit.prevent="submitNote($event)">
                    <textarea id="note-input" name="content" rows="3"
                              class="textarea textarea-bordered textarea-sm w-full mb-2"
                              placeholder="Thêm ghi chú..." required></textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary btn-xs" :disabled="noteSaving">
                            <span x-show="noteSaving" class="loading loading-spinner loading-xs mr-1"></span>
                            Thêm ghi chú
                        </button>
                    </div>
                    <p x-show="noteError" x-text="noteError" class="text-xs text-error mt-1"></p>
                </form>
            </div>
        </div>

        <div class="space-y-2" id="notes-list">
            @forelse($customer->customerNotes as $note)
            <div class="card bg-base-100 shadow-sm border {{ $note->is_pinned ? 'border-warning/40 bg-warning/5' : 'border-base-200' }}"
                 data-note-id="{{ $note->id }}">
                <div class="card-body py-3 px-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            @if($note->is_pinned)
                            <span class="badge badge-xs badge-warning mb-1.5">📌 Đã ghim</span>
                            @endif
                            <p class="text-sm whitespace-pre-line">{{ $note->content }}</p>
                            <p class="text-xs text-base-content/40 mt-2">
                                {{ $note->author_name }} &bull; {{ $note->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <button class="btn btn-ghost btn-xs btn-square text-base-content/30 hover:text-warning"
                                    title="{{ $note->is_pinned ? 'Bỏ ghim' : 'Ghim' }}"
                                    onclick="toggleCustomerNotePin({{ $note->id }}, this)">
                                <svg class="w-3.5 h-3.5" fill="{{ $note->is_pinned ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                            </button>
                            <button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error"
                                    title="Xóa ghi chú"
                                    onclick="deleteCustomerNote({{ $note->id }}, this)">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body py-12 text-center text-base-content/30">
                    <p class="text-sm">Chưa có ghi chú nào</p>
                </div>
            </div>
            @endforelse
        </div>

    </div>

    {{-- ── Tab: Cơ hội liên quan ───────────────────────────────────── --}}
    <div x-show="tab === 'leads'">
        @if($customer->leads->isEmpty())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-16 text-center">
                <p class="text-base-content/30 text-sm">Chưa có cơ hội liên quan.</p>
            </div>
        </div>
        @else
        <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/50">
                        <th>Tiêu đề / Khách hàng</th>
                        <th>Tình trạng</th>
                        <th>Giá trị</th>
                        <th>Ngày chốt DK</th>
                        <th>Phụ trách</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customer->leads as $lead)
                    <tr class="hover">
                        <td class="font-medium text-sm">
                            {{ $lead->title ?? $lead->contact_name ?? '—' }}
                            @if($lead->title && $lead->contact_name)
                            <p class="text-xs text-base-content/40 font-normal">{{ $lead->contact_name }}</p>
                            @endif
                        </td>
                        <td>
                            @if($lead->stage)
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full shrink-0"
                                      style="background:{{ $lead->stage->color ?? '#94a3b8' }}"></span>
                                <span class="text-sm">{{ $lead->stage->label }}</span>
                            </div>
                            @else
                            <span class="text-base-content/30 text-xs">—</span>
                            @endif
                        </td>
                        <td class="text-sm">
                            @if($lead->expected_value)
                            <span class="font-medium text-success">
                                {{ number_format($lead->expected_value) }} {{ $lead->currency }}
                            </span>
                            @else
                            <span class="text-base-content/30 text-xs">—</span>
                            @endif
                        </td>
                        <td class="text-sm text-base-content/60">
                            {{ $lead->expected_close_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="text-sm text-base-content/60">
                            {{ $lead->assignee?->name ?? '—' }}
                        </td>
                        <td>
                            <a href="{{ route('lead.show', $lead) }}"
                               class="btn btn-ghost btn-xs gap-1">
                                Xem
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>

@push('scripts')
@vite(['Modules/Customer/resources/assets/js/customer.js'], 'build/backend')
@endpush
@endsection
