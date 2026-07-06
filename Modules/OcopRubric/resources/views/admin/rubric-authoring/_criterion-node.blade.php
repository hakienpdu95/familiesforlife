@php $isDraft = $isDraft ?? false; @endphp
<li class="border border-base-200 rounded-lg p-3 mb-2 bg-base-100">
    <div class="flex items-start justify-between gap-2 flex-wrap">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono text-xs badge badge-ghost">{{ $node->code }}</span>
                <span class="font-medium text-sm">{{ $node->label }}</span>
                <span class="badge badge-sm {{ $node->is_scorable ? 'badge-info' : 'badge-outline' }}">
                    {{ $node->is_scorable ? 'Tiêu chí lá' : 'Mục' }} — {{ $node->max_score }}đ
                </span>
            </div>
            @if ($node->requirement_note)
            <p class="text-xs text-base-content/50 mt-1 italic">{{ $node->requirement_note }}</p>
            @endif
        </div>

        @if ($isDraft)
        <div class="flex gap-1 shrink-0">
            <form method="POST" action="{{ route('ocop_rubric.admin.criteria.move', [$node, 'up']) }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-xs" title="Lên">↑</button>
            </form>
            <form method="POST" action="{{ route('ocop_rubric.admin.criteria.move', [$node, 'down']) }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-xs" title="Xuống">↓</button>
            </form>
            <details class="dropdown dropdown-end">
                <summary class="btn btn-ghost btn-xs">Sửa</summary>
                <div class="dropdown-content z-10 card card-compact w-80 p-3 shadow bg-base-100 border border-base-200">
                    <form method="POST" action="{{ route('ocop_rubric.admin.criteria.update', $node) }}" class="flex flex-col gap-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="rubric_section_id" value="{{ $node->rubric_section_id }}">
                        <input type="hidden" name="parent_id" value="{{ $node->parent_id }}">
                        <input type="text" name="code" value="{{ $node->code }}" class="input input-bordered input-xs w-full" placeholder="Mã (VD 1.1)" required>
                        <input type="text" name="label" value="{{ $node->label }}" class="input input-bordered input-xs w-full" placeholder="Nhãn" required>
                        <input type="number" step="0.01" name="max_score" value="{{ $node->max_score }}" class="input input-bordered input-xs w-full" placeholder="Điểm tối đa" required>
                        <textarea name="requirement_note" class="textarea textarea-bordered textarea-xs w-full" placeholder="Ghi chú (tùy chọn)">{{ $node->requirement_note }}</textarea>
                        <label class="label cursor-pointer gap-2 justify-start">
                            <input type="checkbox" name="is_scorable" value="1" class="checkbox checkbox-xs" @checked($node->is_scorable)>
                            <span class="label-text text-xs">Là tiêu chí lá (có phương án chọn)</span>
                        </label>
                        <button type="submit" class="btn btn-primary btn-xs w-full">Lưu</button>
                    </form>
                </div>
            </details>
            <form method="POST" action="{{ route('ocop_rubric.admin.criteria.destroy', $node) }}"
                  onsubmit="return confirm('Xóa tiêu chí này và toàn bộ con/phương án bên trong?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
            </form>
        </div>
        @endif
    </div>

    @if ($node->is_scorable)
        <div class="mt-2 pl-4 border-l-2 border-base-200">
            <table class="table table-xs">
                <tbody>
                @foreach ($node->options as $opt)
                <tr>
                    <td class="text-xs">{{ $opt->label }}</td>
                    <td class="text-xs text-right font-mono w-16">{{ $opt->points }}đ</td>
                    @if ($isDraft)
                    <td class="w-28">
                        <div class="flex gap-1 justify-end">
                            <details class="dropdown dropdown-end">
                                <summary class="btn btn-ghost btn-xs">Sửa</summary>
                                <div class="dropdown-content z-10 card card-compact w-72 p-3 shadow bg-base-100 border border-base-200">
                                    <form method="POST" action="{{ route('ocop_rubric.admin.options.update', $opt) }}" class="flex flex-col gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="criterion_id" value="{{ $node->id }}">
                                        <input type="text" name="label" value="{{ $opt->label }}" class="input input-bordered input-xs w-full" required>
                                        <input type="number" step="0.01" name="points" value="{{ $opt->points }}" class="input input-bordered input-xs w-full" required>
                                        <button type="submit" class="btn btn-primary btn-xs w-full">Lưu</button>
                                    </form>
                                </div>
                            </details>
                            <form method="POST" action="{{ route('ocop_rubric.admin.options.destroy', $opt) }}"
                                  onsubmit="return confirm('Xóa phương án này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @endforeach
                </tbody>
            </table>

            @if ($isDraft)
            <details class="mt-1">
                <summary class="text-xs link link-primary cursor-pointer">+ Thêm phương án</summary>
                <form method="POST" action="{{ route('ocop_rubric.admin.options.store') }}" class="flex gap-2 mt-2">
                    @csrf
                    <input type="hidden" name="criterion_id" value="{{ $node->id }}">
                    <input type="text" name="label" placeholder="Nhãn phương án" class="input input-bordered input-xs flex-1" required>
                    <input type="number" step="0.01" name="points" placeholder="Điểm" class="input input-bordered input-xs w-20" required>
                    <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                </form>
            </details>
            @endif
        </div>
    @else
        <ul class="mt-2 pl-4 border-l-2 border-base-200">
            @foreach ($node->childrenRecursive as $child)
                @include('ocoprubric::admin.rubric-authoring._criterion-node', ['node' => $child, 'isDraft' => $isDraft])
            @endforeach
        </ul>

        @if ($isDraft)
        <details class="mt-2">
            <summary class="text-xs link link-primary cursor-pointer">+ Thêm tiêu chí con</summary>
            <form method="POST" action="{{ route('ocop_rubric.admin.criteria.store') }}" class="flex flex-col gap-2 mt-2 max-w-md">
                @csrf
                <input type="hidden" name="rubric_section_id" value="{{ $node->rubric_section_id }}">
                <input type="hidden" name="parent_id" value="{{ $node->id }}">
                <input type="text" name="code" placeholder="Mã (VD: {{ $node->code }}.1)" class="input input-bordered input-xs w-full" required>
                <input type="text" name="label" placeholder="Nhãn" class="input input-bordered input-xs w-full" required>
                <input type="number" step="0.01" name="max_score" placeholder="Điểm tối đa" class="input input-bordered input-xs w-full" required>
                <label class="label cursor-pointer gap-2 justify-start">
                    <input type="checkbox" name="is_scorable" value="1" class="checkbox checkbox-xs">
                    <span class="label-text text-xs">Là tiêu chí lá (có phương án chọn)</span>
                </label>
                <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
            </form>
        </details>
        @endif
    @endif
</li>
