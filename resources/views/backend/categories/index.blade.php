@extends('layouts.backend')
@section('title','Danh mục')
@section('breadcrumb')
<nav class="breadcrumb-nav"><a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span><span class="current">Danh mục</span></nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div><h1 class="text-2xl font-bold text-base-content">Danh mục</h1></div>
    <a href="{{ route('backend.categories.create') }}" class="btn btn-primary btn-sm gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Thêm danh mục</a>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h2 class="font-bold text-base-content mb-4">Tất cả danh mục</h2>
            <div class="overflow-x-auto"><table class="table table-sm"><thead><tr class="text-xs text-base-content/50 uppercase bg-base-50"><th>Tên</th><th class="text-center">SP</th><th>Trạng thái</th><th></th></tr></thead>
            <tbody>
            @php $cats=[['name'=>'Điện thoại','slug'=>'dien-thoai','count'=>48,'status'=>true,'children'=>3],['name'=>'Laptop','slug'=>'laptop','count'=>32,'status'=>true,'children'=>2],['name'=>'Phụ kiện','slug'=>'phu-kien','count'=>94,'status'=>true,'children'=>5],['name'=>'Âm thanh','slug'=>'am-thanh','count'=>27,'status'=>true,'children'=>2],['name'=>'Tablet','slug'=>'tablet','count'=>15,'status'=>false,'children'=>0]]; @endphp
            @foreach($cats as $c)
            <tr class="border-b border-base-100 last:border-0 hover">
                <td><div><p class="font-medium text-sm">{{ $c['name'] }}</p><p class="text-xs text-base-content/40">{{ $c['slug'] }} @if($c['children']) · {{ $c['children'] }} danh mục con @endif</p></div></td>
                <td class="text-center"><span class="badge badge-ghost badge-sm">{{ $c['count'] }}</span></td>
                <td><input type="checkbox" class="toggle toggle-success toggle-sm" {{ $c['status'] ? 'checked' : '' }}></td>
                <td><div class="flex gap-1"><a href="#" class="btn btn-ghost btn-xs btn-circle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a><button class="btn btn-ghost btn-xs btn-circle text-error"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div></td>
            </tr>
            @endforeach
            </tbody></table></div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h2 class="font-bold text-base-content mb-4">Thêm nhanh</h2>
            <form action="{{ route('backend.categories.store') }}" method="POST">@csrf
            <div class="space-y-3">
                <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Tên danh mục <span class="text-error">*</span></span></label><input type="text" name="name" class="input input-bordered" placeholder="VD: Điện thoại" required></div>
                <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Slug</span></label><input type="text" name="slug" class="input input-bordered" placeholder="tự động tạo từ tên"></div>
                <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Danh mục cha</span></label><select name="parent_id" class="select select-bordered"><option value="">-- Không có --</option><option>Điện thoại</option><option>Laptop</option></select></div>
                <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Mô tả</span></label><textarea name="description" class="textarea textarea-bordered h-20"></textarea></div>
                <div class="flex justify-end"><button type="submit" class="btn btn-primary gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Lưu danh mục</button></div>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection
