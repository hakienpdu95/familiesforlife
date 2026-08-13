@extends('layouts.frontend')

@section('title', $entityType->name)
@section('meta_description', $entityType->description ?? "So sánh {$entityType->name} theo tiêu chí — lọc và chọn tối đa " . config('entity_comparison.max_compare_entities') . ' đối tượng để so sánh.')

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li>{{ $entityType->name }}</li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-1">{{ $entityType->name }}</h1>
    @if($entityType->description)
    <p class="text-sm text-base-content/60 mb-6">{{ $entityType->description }}</p>
    @else
    <div class="mb-6"></div>
    @endif

    @if(session('error'))
    <div class="alert alert-error mb-4 text-sm"><span>{{ session('error') }}</span></div>
    @endif

    {{-- ── Form lọc — §7.2 bước 1, chỉ criteria is_filterable=true ─────────────────────── --}}
    @if($criteria->isNotEmpty())
    <form method="GET" class="card bg-base-100 shadow-sm border border-base-200 mb-6">
        <div class="card-body py-4 px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($criteria as $criterion)
                @php($current = request("filters.{$criterion->id}"))
                <div class="form-control">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">{{ $criterion->name }}{{ $criterion->unit ? " ({$criterion->unit})" : '' }}</span>
                    </label>

                    @switch($criterion->type)
                        @case(\Modules\EntityComparison\Enums\CriterionType::Text)
                            <input type="text" name="filters[{{ $criterion->id }}]" value="{{ $current }}"
                                   class="input input-bordered input-sm w-full">
                            @break

                        @case(\Modules\EntityComparison\Enums\CriterionType::Boolean)
                            <select name="filters[{{ $criterion->id }}]" class="select select-bordered select-sm w-full">
                                <option value="">— Tất cả —</option>
                                <option value="1" {{ $current === '1' ? 'selected' : '' }}>Có</option>
                                <option value="0" {{ $current === '0' ? 'selected' : '' }}>Không</option>
                            </select>
                            @break

                        @case(\Modules\EntityComparison\Enums\CriterionType::Date)
                            <input type="date" name="filters[{{ $criterion->id }}]" value="{{ $current }}"
                                   class="input input-bordered input-sm w-full">
                            @break

                        @case(\Modules\EntityComparison\Enums\CriterionType::Select)
                            <select name="filters[{{ $criterion->id }}]" class="select select-bordered select-sm w-full">
                                <option value="">— Tất cả —</option>
                                @foreach($criterion->options as $option)
                                <option value="{{ $option->id }}" {{ (string) $current === (string) $option->id ? 'selected' : '' }}>{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @break

                        @case(\Modules\EntityComparison\Enums\CriterionType::MultiSelect)
                            @php($currentMulti = (array) request("filters.{$criterion->id}", []))
                            <div class="flex flex-wrap gap-3 pt-1.5">
                                @foreach($criterion->options as $option)
                                <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                    <input type="checkbox" name="filters[{{ $criterion->id }}][]" value="{{ $option->id }}"
                                           {{ in_array((string) $option->id, $currentMulti, true) ? 'checked' : '' }}
                                           class="checkbox checkbox-xs">
                                    {{ $option->label }}
                                </label>
                                @endforeach
                            </div>
                            @break

                        @case(\Modules\EntityComparison\Enums\CriterionType::Number)
                            <div class="flex gap-2">
                                <input type="number" step="any" placeholder="Từ" name="filters[{{ $criterion->id }}][min]"
                                       value="{{ request("filters.{$criterion->id}.min") }}" class="input input-bordered input-sm w-full">
                                <input type="number" step="any" placeholder="Đến" name="filters[{{ $criterion->id }}][max]"
                                       value="{{ request("filters.{$criterion->id}.max") }}" class="input input-bordered input-sm w-full">
                            </div>
                            @break

                        @case(\Modules\EntityComparison\Enums\CriterionType::Range)
                            <div class="flex gap-2">
                                <input type="number" step="any" placeholder="Từ" name="filters[{{ $criterion->id }}][min]"
                                       value="{{ request("filters.{$criterion->id}.min") }}" class="input input-bordered input-sm w-full">
                                <input type="number" step="any" placeholder="Đến" name="filters[{{ $criterion->id }}][max]"
                                       value="{{ request("filters.{$criterion->id}.max") }}" class="input input-bordered input-sm w-full">
                            </div>
                            @break
                    @endswitch
                </div>
                @endforeach
            </div>

            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                @if(request('filters'))
                <a href="{{ route('entity_comparison.public.index', $entityType) }}" class="btn btn-ghost btn-sm">Xoá lọc</a>
                @endif
            </div>
        </div>
    </form>
    @endif

    {{-- ── Danh sách + chọn để so sánh — §7.2 bước 2 ────────────────────────────────────── --}}
    <form method="GET" action="{{ route('entity_comparison.public.compare', $entityType) }}"
          x-data="{ count: 0 }" @change="count = $el.querySelectorAll('input[name=\'compare[]\']:checked').length">

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-base-content/60">{{ $entities->total() }} kết quả</p>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="count < {{ config('entity_comparison.min_compare_entities') }} || count > {{ config('entity_comparison.max_compare_entities') }}">
                So sánh (<span x-text="count"></span>)
            </button>
        </div>
        <p class="text-xs text-base-content/40 mb-4">Chọn từ {{ config('entity_comparison.min_compare_entities') }} đến {{ config('entity_comparison.max_compare_entities') }} đối tượng để so sánh.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($entities as $entity)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <label class="flex items-start gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="compare[]" value="{{ $entity->uuid }}" class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0">
                        <span>
                            @if($entity->getFirstMediaUrl('cover'))
                            <img src="{{ $entity->getFirstMediaUrl('cover', 'thumb') }}" alt="{{ $entity->name }}"
                                 class="w-full aspect-video object-cover rounded mb-2">
                            @endif
                            <span class="font-bold block leading-snug">{{ $entity->name }}</span>
                            @if($entity->description)
                            <span class="text-xs text-base-content/50 block mt-1 line-clamp-2">{{ $entity->description }}</span>
                            @endif
                        </span>
                    </label>
                </div>
            </div>
            @empty
            <p class="col-span-full text-center py-10 text-base-content/40">Không tìm thấy đối tượng phù hợp.</p>
            @endforelse
        </div>
    </form>

    @if($entities->hasPages())
    <div class="mt-8">{{ $entities->onEachSide(1)->links() }}</div>
    @endif

</div>
@endsection
