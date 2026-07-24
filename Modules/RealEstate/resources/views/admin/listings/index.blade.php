@extends('layouts.backend')
@section('title', 'Bất động sản')

@section('content')
<div>
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type }} mb-4 text-sm"><span>{{ session($type) }}</span></div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Bất động sản</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tin rao bán/thuê nhà đất của tổ chức bạn</p>
        </div>
        @can('create', \Modules\RealEstate\Models\RealEstateListing::class)
        <a href="{{ route('backend.real-estate.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Đăng tin mới
        </a>
        @endcan
    </div>

    <div class="join mb-4">
        @foreach(['' => 'Tất cả', 'sale' => 'Bán', 'rent' => 'Thuê'] as $value => $label)
        <a href="{{ request()->fullUrlWithQuery(['listing_type' => $value ?: null, 'page' => null]) }}"
           class="btn btn-sm join-item {{ request('listing_type', '') === $value ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th class="text-center">Giá</th>
                        <th class="text-center">Trạng thái duyệt</th>
                        <th class="text-center">Ngày tạo</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($listings as $listing)
                    @php $status = $listing->approvalStatus(); @endphp
                    <tr class="hover">
                        <td class="max-w-xs truncate">{{ $listing->title }}</td>
                        <td>{{ $listing->listing_type->label() }} · {{ $listing->property_type->label() }}</td>
                        <td class="text-center font-mono text-sm">{{ $listing->display_price }}</td>
                        <td class="text-center">
                            @if($status)
                            <span class="badge badge-sm {{ $status->badgeClass() }}">{{ $status->label() }}</span>
                            @endif
                        </td>
                        <td class="text-center text-xs text-base-content/50">{{ $listing->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('backend.real-estate.edit', $listing) }}" class="btn btn-sm btn-ghost">Sửa</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-base-content/40">Chưa có tin nào — bấm "Đăng tin mới" để bắt đầu.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($listings->hasPages())
    <div class="pt-6 flex justify-center">
        {{ $listings->onEachSide(1)->links() }}
    </div>
    @endif
</div>
@endsection
