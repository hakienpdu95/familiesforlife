@extends('layouts.backend')
@section('title', 'Tạo Issue')

@section('content')
<div class="max-w-2xl">
    <div class="mb-5">
        <h1 class="text-2xl font-bold">Tạo Issue</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $vertical->label() }}</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-4">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <form method="POST"
                  action="{{ route('deployment.issues.store', ['vertical' => $vertical->code()]) }}">
                @csrf

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Đối tượng triển khai <span class="text-error">*</span></span></label>
                    <select name="deployment_target_id"
                            class="select select-bordered @error('deployment_target_id') select-error @enderror"
                            x-data x-on:change="
                                document.querySelector('[name=project_id]').value =
                                $el.options[$el.selectedIndex].dataset.project
                            ">
                        <option value="">— Chọn {{ $vertical->targetLabel() }} —</option>
                        @foreach($targets as $t)
                        <option value="{{ $t->id }}"
                                data-project="{{ $t->project_id }}"
                                @selected(old('deployment_target_id', $selectedTarget?->id) == $t->id)>
                            {{ $t->targetOrganization?->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('deployment_target_id')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <input type="hidden" name="project_id" value="{{ old('project_id', $selectedTarget?->project_id) }}">

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Tiêu đề <span class="text-error">*</span></span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="input input-bordered @error('title') input-error @enderror">
                    @error('title')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Mô tả</span></label>
                    <textarea name="description" rows="4"
                              class="textarea textarea-bordered">{{ old('description') }}</textarea>
                </div>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Mức độ nghiêm trọng <span class="text-error">*</span></span></label>
                    <select name="severity" class="select select-bordered @error('severity') select-error @enderror">
                        @foreach($severities as $s)
                        <option value="{{ $s->value }}" @selected(old('severity') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('severity')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                @if($issueTypes)
                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Loại issue</span></label>
                    <select name="issue_type" class="select select-bordered @error('issue_type') select-error @enderror">
                        <option value="">— Không phân loại —</option>
                        @foreach($issueTypes as $code => $label)
                        <option value="{{ $code }}" @selected(old('issue_type') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-base-content/40 mt-1">
                        Danh mục do tổ chức tự cấu hình — chỉnh tại trang Cấu hình vertical.
                    </p>
                    @error('issue_type')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                @endif

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Chi tiết mức độ</span></label>
                    <textarea name="severity_detail" rows="3"
                              placeholder="Vd: diện tích nhiễm ~10%, đã phun VBIO 2 lần..."
                              class="textarea textarea-bordered @error('severity_detail') textarea-error @enderror">{{ old('severity_detail') }}</textarea>
                    @error('severity_detail')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Người phụ trách</span></label>
                    <select name="owner_id" class="select select-bordered @error('owner_id') select-error @enderror">
                        <option value="">— Chưa chỉ định —</option>
                        @foreach($owners as $o)
                        <option value="{{ $o->id }}" @selected(old('owner_id') == $o->id)>{{ $o->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-base-content/40 mt-1">Sẽ nhận thông báo khi issue được tạo.</p>
                    @error('owner_id')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Tạo Issue</button>
                    <a href="{{ route('deployment.issues.index', ['vertical' => $vertical->code()]) }}"
                       class="btn btn-ghost">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
