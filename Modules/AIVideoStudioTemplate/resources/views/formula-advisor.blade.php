@extends('layouts.backend')
@section('title', 'Sinh Master Prompt kịch bản — AI Video Studio')

{{-- spec/AIVideoStudioTemplate_Technical_Specification.md §13 (v1.25). Thuần GET — mọi form trên
     trang này submit lại chính URL qua query string, không ghi DB, không CSRF. Từ v1.25, trang này
     KHÔNG còn tự chọn công thức (FormulaDecisionTree đã xoá) — chỉ render 1 Master Prompt để copy
     sang AI ngoài (ChatGPT/Claude); AI ngoài mới là nơi chọn công thức + viết kịch bản. --}}
@php
    /** @var \Illuminate\Support\Collection|array $catalog */
    $formulaLabels = \Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject::VIDEO_FORMULAS;
    $tierLabels = ['S' => 'Tier S — ưu tiên cao nhất', 'A' => 'Tier A', 'B' => 'Tier B', 'C' => 'Tier C', 'D' => 'Tier D'];
    $grouped = collect($catalog)->groupBy('tier');
@endphp

@section('content')

<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sinh Master Prompt Kịch bản</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Nhập chủ đề/sản phẩm bất kỳ → sinh 1 prompt hoàn chỉnh để dán sang AI ngoài (ChatGPT/Claude) — AI sẽ tư vấn công thức và viết kịch bản.</p>
    </div>
    <a href="{{ route('backend.aivideostudiotemplate.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Danh sách project
    </a>
</div>

<div class="alert alert-warning py-2.5 px-4 mb-5 text-xs">
    <span>Prompt này chỉ tổ chức lại cây quyết định + ranh giới nội dung để AI ngoài tham khảo — kết quả AI trả về (công thức chọn/kịch bản) vẫn cần biên tập viên đọc lại trước khi đăng. Với sản phẩm Mẹ &amp; Bé, ranh giới pháp lý dựa trên Nghị định 100/2014/NĐ-CP và quy định quảng cáo thực phẩm chức năng/thiết bị y tế hiện hành — kiểm tra văn bản mới nhất trước khi triển khai quy mô lớn.</span>
</div>

{{-- ── 1. Form sinh Master Prompt ───────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <h2 class="text-sm font-semibold flex items-center gap-2"><span class="badge badge-primary badge-sm">1</span> Nhập chủ đề/sản phẩm</h2>

        <form method="GET" action="{{ route('backend.aivideostudiotemplate.formula-advisor') }}" class="space-y-3 mt-2 max-w-2xl">
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-sm font-medium">Chủ đề/Sản phẩm <span class="text-error">*</span></span></label>
                <input type="text" name="topic" value="{{ $topic }}" required maxlength="255"
                       placeholder="VD: Máy hút sữa đôi / Nồi chiên không dầu / Áo khoác gió nam"
                       class="input input-bordered input-sm w-full">
            </div>
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-sm font-medium">Mô tả bổ sung</span></label>
                <textarea name="description" rows="3" class="textarea textarea-bordered textarea-sm w-full"
                          placeholder="VD: Giá 1,9 triệu, đối tượng mẹ đi làm trở lại sau sinh, ưu điểm hút êm và nhanh">{{ $description }}</textarea>
            </div>
            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-2 py-0">
                    <input type="checkbox" name="is_mother_baby" value="1" class="checkbox checkbox-sm" @checked($isMotherBaby)>
                    <span class="label-text text-sm">Đây là sản phẩm ngách Mẹ &amp; Bé (chèn thêm ranh giới pháp lý riêng của ngách này vào prompt)</span>
                </label>
            </div>
            <div class="card-actions">
                <button type="submit" class="btn btn-primary btn-sm">Sinh Master Prompt</button>
                @if($topic !== '')
                <a href="{{ route('backend.aivideostudiotemplate.formula-advisor') }}" class="btn btn-ghost btn-sm">Đặt lại</a>
                @endif
            </div>
        </form>

        @if($masterPrompt)
        <div class="mt-4">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-sm font-semibold">Master Prompt — copy và dán sang ChatGPT/Claude</span>
                <button type="button" class="btn btn-primary btn-xs" onclick="aivsCopy('aivsMasterPrompt', this)">Copy</button>
            </div>
            <textarea id="aivsMasterPrompt" readonly rows="16"
                      class="textarea textarea-bordered w-full font-mono text-xs leading-relaxed">{{ $masterPrompt }}</textarea>
        </div>
        @endif
    </div>
</div>

{{-- ── 2. Tra cứu catalog Mẹ & Bé — dùng làm ngữ cảnh gợi ý (KHÔNG quyết định thay) ────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <h2 class="text-sm font-semibold flex items-center gap-2"><span class="badge badge-primary badge-sm">2</span> Tra cứu 36 sản phẩm Mẹ &amp; Bé đã có ghi chú sẵn <span class="badge badge-ghost badge-sm font-normal">Không bắt buộc</span></h2>
        <p class="text-xs text-base-content/40 mt-0.5 mb-3">Đây là dữ liệu tham khảo từ ma trận nội bộ, KHÔNG còn tự quyết định công thức — bấm "Dùng làm ngữ cảnh" để chèn ghi chú/cảnh báo đã biết vào Mô tả bổ sung ở trên, giúp Master Prompt cụ thể hơn.</p>

        <form method="GET" action="{{ route('backend.aivideostudiotemplate.formula-advisor') }}" class="flex gap-2 mb-3 max-w-md">
            <input type="text" name="q" value="{{ $search }}" placeholder="Tìm tên sản phẩm... VD: bỉm, máy hút sữa"
                   class="input input-bordered input-sm w-full">
            <button type="submit" class="btn btn-primary btn-sm">Tìm</button>
            @if($search)
            <a href="{{ route('backend.aivideostudiotemplate.formula-advisor') }}" class="btn btn-ghost btn-sm">Xoá</a>
            @endif
        </form>

        @if($selected)
        {{-- ── Panel chi tiết sản phẩm đã chọn ── --}}
        <div class="border border-primary/30 bg-primary/5 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="font-bold text-base">{{ $selected['name'] }} <span class="badge badge-ghost badge-sm ml-1">{{ $tierLabels[$selected['tier']] ?? $selected['tier'] }}</span></h3>
                <a href="{{ route('backend.aivideostudiotemplate.formula-advisor', ['q' => $search]) }}" class="text-xs text-base-content/40 hover:text-base-content">✕ Đóng</a>
            </div>

            @if(!empty($selected['multi_version']))
            <div class="alert alert-info py-2 px-3 text-xs mt-2">
                <span>Sản phẩm này nên tách thành nhiều phiên bản video riêng theo từng chân dung — không có 1 công thức "chính" duy nhất.</span>
            </div>
            @endif

            <p class="text-xs text-base-content/40 mt-2">Công thức từng dùng trong ma trận nội bộ (THAM KHẢO — AI ngoài có thể chọn khác):</p>
            <div class="flex flex-wrap gap-1.5 mt-1">
                @if($selected['primary'])
                <span class="badge badge-outline">{{ $formulaLabels[$selected['primary']] ?? $selected['primary'] }}</span>
                @endif
                @foreach($selected['secondary'] as $sec)
                <span class="badge badge-outline badge-ghost">{{ $formulaLabels[$sec] ?? $sec }}</span>
                @endforeach
                @foreach($selected['forbid_formulas'] ?? [] as $forbidden)
                <span class="badge badge-error gap-1">🚫 {{ $formulaLabels[$forbidden] ?? $forbidden }}</span>
                @endforeach
            </div>

            <p class="text-sm mt-2">{{ $selected['note'] }}</p>
            <p class="text-xs text-base-content/50 mt-1">Chân dung khán giả (mã tham khảo — cần tài liệu "Bộ Chân dung Người Review" để diễn giải đầy đủ): <b>{{ $selected['personas'] }}</b></p>

            @if($selected['warning'])
            <div class="alert alert-error py-2 px-3 text-xs mt-2">
                <span>{{ $selected['warning'] }}</span>
            </div>
            @endif

            @if(!empty($selected['sample_script']))
            <details class="mt-3">
                <summary class="cursor-pointer text-xs font-semibold text-primary">📝 Xem kịch bản mẫu — {{ $selected['sample_script']['title'] }}</summary>
                <div class="prose prose-sm max-w-none mt-2 text-sm">{!! \Illuminate\Support\Str::markdown($selected['sample_script']['body']) !!}</div>
            </details>
            @endif

            @php
                $selectedContext = trim($selected['note'].' (Chân dung tham khảo: '.$selected['personas'].($selected['warning'] ? '; '.$selected['warning'] : '').')');
            @endphp
            <div class="card-actions justify-end mt-3">
                <a href="{{ route('backend.aivideostudiotemplate.formula-advisor', [
                        'topic' => $selected['name'],
                        'description' => $selectedContext,
                        'is_mother_baby' => 1,
                    ]) }}" class="btn btn-primary btn-sm">Dùng làm ngữ cảnh cho Master Prompt</a>
            </div>
        </div>
        @endif

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Công thức từng dùng (tham khảo)</th>
                        <th>Chân dung</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grouped as $tier => $items)
                    <tr class="bg-base-200/50"><td colspan="4" class="font-semibold text-xs">{{ $tierLabels[$tier] ?? $tier }}</td></tr>
                    @foreach($items as $slug => $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>
                            @if($item['primary'])
                            <span class="badge badge-outline badge-sm">{{ $formulaLabels[$item['primary']] ?? $item['primary'] }}</span>
                            @else
                            <span class="text-xs text-base-content/40">— đa phiên bản —</span>
                            @endif
                        </td>
                        <td class="text-xs text-base-content/60">{{ $item['personas'] }}</td>
                        <td><a href="{{ route('backend.aivideostudiotemplate.formula-advisor', ['product' => $slug, 'q' => $search]) }}" class="link link-primary text-xs">Xem chi tiết</a></td>
                    </tr>
                    @endforeach
                    @empty
                    <tr><td colspan="4" class="text-center text-sm text-base-content/40 py-4">Không tìm thấy sản phẩm khớp từ khoá.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── 3. Tham khảo — nhóm cấm/hạn chế, ghép sai, ngân sách (chỉ áp dụng ngành Mẹ & Bé) ───── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-sm font-semibold">🔴 Nhóm bị cấm/hạn chế quảng cáo <span class="badge badge-ghost badge-sm font-normal">Chỉ áp dụng ngành Mẹ &amp; Bé</span></h2>
            <div class="overflow-x-auto mt-2">
                <table class="table table-xs">
                    <thead><tr><th>Sản phẩm</th><th>Công thức duy nhất</th><th>Ghi chú</th></tr></thead>
                    <tbody>
                        @foreach($bannedCategories as $cat)
                        <tr>
                            <td class="font-medium">{{ $cat['name'] }}</td>
                            <td>
                                @if($cat['no_affiliate'])
                                <span class="badge badge-error badge-sm">Không làm affiliate</span>
                                @else
                                <span class="badge badge-sm">{{ $formulaLabels[$cat['allowed_formula']] ?? $cat['allowed_formula'] }} (bỏ CTA)</span>
                                @endif
                            </td>
                            <td class="text-xs text-base-content/60">{{ $cat['note'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-sm font-semibold">❌ Bảng cấm ghép công thức × sản phẩm <span class="badge badge-ghost badge-sm font-normal">Chỉ áp dụng ngành Mẹ &amp; Bé</span></h2>
            <div class="overflow-x-auto mt-2">
                <table class="table table-xs">
                    <thead><tr><th>Ghép sai</th><th>Hậu quả</th></tr></thead>
                    <tbody>
                        @foreach($forbiddenCombos as $combo)
                        <tr>
                            <td class="font-medium">{{ $combo['pair'] }}</td>
                            <td class="text-xs text-base-content/60">{{ $combo['consequence'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="card bg-base-100 shadow-sm border border-base-200 mt-5">
    <div class="card-body">
        <h2 class="text-sm font-semibold">Phân bổ ngân sách sản xuất theo công thức <span class="badge badge-ghost badge-sm font-normal">Chỉ áp dụng ngành Mẹ &amp; Bé</span></h2>
        <p class="text-xs text-base-content/40 mt-0.5 mb-2">Gợi ý tỷ lệ thời gian nên dành cho mỗi công thức khi lập kế hoạch nội dung hàng tháng.</p>
        <div class="overflow-x-auto">
            <table class="table table-xs">
                <thead><tr><th>Công thức</th><th>Thời gian sản xuất</th><th>Tỷ lệ nên chiếm</th><th>Vai trò trong phễu</th></tr></thead>
                <tbody>
                    @foreach($productionBudget as $key => $b)
                    <tr>
                        <td class="font-medium">{{ $formulaLabels[$key] ?? $key }}</td>
                        <td class="text-xs">{{ $b['time'] }}</td>
                        <td><span class="badge badge-sm">{{ $b['share'] }}</span></td>
                        <td class="text-xs text-base-content/60">{{ $b['role'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @vite(['Modules/AIVideoStudioTemplate/resources/assets/js/aivideostudiotemplate.js'], 'build/backend')
@endpush
