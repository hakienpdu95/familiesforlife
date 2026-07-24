<?php

namespace Modules\RealEstate\Features\ListingManagement\Http;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Approval\Actions\ApproveAction;
use Modules\Approval\Actions\ArchiveAction;
use Modules\Approval\Actions\PublishAction;
use Modules\Approval\Actions\RejectAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Approval\Exceptions\InvalidTransitionException;
use Modules\RealEstate\Enums\ApartmentSubtype;
use Modules\RealEstate\Enums\CompassDirection;
use Modules\RealEstate\Enums\HouseSubtype;
use Modules\RealEstate\Enums\InteriorStatus;
use Modules\RealEstate\Enums\LegalStatus;
use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Enums\UsageStatus;
use Modules\RealEstate\Features\ListingManagement\Actions\CreateRealEstateListingAction;
use Modules\RealEstate\Features\ListingManagement\Actions\DeleteRealEstateListingAction;
use Modules\RealEstate\Features\ListingManagement\Actions\ReorderGalleryMediaAction;
use Modules\RealEstate\Features\ListingManagement\Actions\UpdateRealEstateListingAction;
use Modules\RealEstate\Features\ListingManagement\Data\RealEstateListingData;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * spec/RealEstateForSale_Technical_Specification.md §7.1/§8 — CRUD của Organization + workflow
 * duyệt (copy đúng cấu trúc ProductAdminController, KHÔNG viết lại state machine riêng).
 */
class RealEstateListingAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RealEstateListing::class, 'listing');
    }

    public function index(): View
    {
        // Danh sách thật lấy qua Tabulator (remote pagination/sort/filter) — xem
        // RealEstateListingApiController, cùng pattern ProductAdminController::index().
        return view('realestate::admin.listings.index');
    }

    public function create(): View
    {
        return view('realestate::admin.listings.create');
    }

    public function store(Request $request, CreateRealEstateListingAction $action): RedirectResponse
    {
        $data    = RealEstateListingData::from($this->validated($request));
        $listing = $action->handle($data);

        return redirect()->route('backend.real-estate.edit', $listing)
            ->with('success', "Đã tạo tin \"{$listing->title}\" (bản nháp) — hoàn thiện rồi bấm \"Gửi duyệt\".");
    }

    public function edit(RealEstateListing $listing): string
    {
        // platform_content_moderator (organization_id=null) không có TenantContext trỏ tới tổ
        // chức của $listing — bọc để approvalSubject/badge duyệt resolve đúng (cùng nguyên nhân
        // ProductAdminController::edit(), xem docblock đó).
        return TenantContext::runForOrganization(
            $listing->organization,
            fn () => view('realestate::admin.listings.edit', ['listing' => $listing])->render(),
        );
    }

    public function update(Request $request, RealEstateListing $listing, UpdateRealEstateListingAction $action): RedirectResponse
    {
        abort_if($request->user()?->isPlatformViewer(), 403);

        $data = RealEstateListingData::from($this->validated($request, $listing));

        try {
            $action->handle($listing, $data);
        } catch (InvalidTransitionException $e) {
            return back()->withInput()->with('error', 'Không thể sửa nội dung tin đã lưu trữ. Các trường khác đã được lưu — chỉ phần nội dung bị bỏ qua.');
        }

        return redirect()->route('backend.real-estate.index')->with('success', 'Cập nhật tin bất động sản thành công.');
    }

    public function destroy(RealEstateListing $listing, DeleteRealEstateListingAction $action): RedirectResponse
    {
        $title = $listing->title;
        $action->handle($listing);

        return redirect()->route('backend.real-estate.index')->with('success', "Đã xoá tin \"{$title}\".");
    }

    public function reorderGallery(Request $request, RealEstateListing $listing, ReorderGalleryMediaAction $action): RedirectResponse
    {
        $this->authorize('update', $listing);

        $uuids = $request->validate(['uuids' => ['required', 'array', 'max:6'], 'uuids.*' => ['string', 'uuid']])['uuids'];
        $action->handle($listing, $uuids);

        return back()->with('success', 'Đã sắp lại thứ tự ảnh.');
    }

    // ── Approval workflow — copy đúng cấu trúc ProductAdminController (§5.5/§6 spec Bán) ──

    public function submitApproval(RealEstateListing $listing, SubmitForApprovalAction $action): RedirectResponse
    {
        $this->authorize('submitForApproval', $listing);

        return $this->runApprovalTransition($listing, fn () => $action->handle($listing), 'Đã gửi tin để chờ duyệt.');
    }

    public function approveContent(RealEstateListing $listing, ApproveAction $action): RedirectResponse
    {
        $this->authorize('approve', $listing);

        return $this->runApprovalTransition($listing, fn () => $action->handle($listing), 'Đã duyệt nội dung tin.');
    }

    public function rejectContent(Request $request, RealEstateListing $listing, RejectAction $action): RedirectResponse
    {
        $this->authorize('reject', $listing);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10']])['reason'];

        return $this->runApprovalTransition($listing, fn () => $action->handle($listing, $reason), 'Đã từ chối duyệt.');
    }

    public function publishContent(RealEstateListing $listing, PublishAction $action): RedirectResponse
    {
        $this->authorize('publishApproval', $listing);

        return $this->runApprovalTransition($listing, fn () => $action->handle($listing), 'Đã xuất bản tin bất động sản.');
    }

    public function archiveContent(RealEstateListing $listing, ArchiveAction $action): RedirectResponse
    {
        $this->authorize('archiveApproval', $listing);

        return $this->runApprovalTransition($listing, fn () => $action->handle($listing), 'Đã lưu trữ tin.');
    }

    private function runApprovalTransition(RealEstateListing $listing, \Closure $callback, string $successMessage): RedirectResponse
    {
        try {
            TenantContext::runForOrganization($listing->organization, $callback);
        } catch (InvalidTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $successMessage);
    }

    // ── Validation (§5.3/§5.4 spec Bán) ─────────────────────────────────────

    private function validated(Request $request, ?RealEstateListing $listing = null): array
    {
        $listingType   = $request->input('listing_type');
        $isSale        = $listingType === ListingType::Sale->value;
        $isRent        = $listingType === ListingType::Rent->value;
        $isNegotiable  = $request->boolean('is_price_negotiable');

        $priceRule        = $isSale && ! $isNegotiable ? ['required'] : ['nullable'];
        $monthlyRentRule  = $isRent && ! $isNegotiable ? ['required'] : ['nullable'];

        return $request->validate([
            'listing_type'   => ['required', Rule::in(array_column(ListingType::cases(), 'value'))],
            'property_type'  => [
                'required',
                function ($attribute, $value, $fail) use ($listingType) {
                    $type = ListingType::tryFrom((string) $listingType);
                    $valid = $type ? array_column(\Modules\RealEstate\Enums\PropertyType::validFor($type), 'value') : [];
                    if (! in_array($value, $valid, true)) {
                        $fail('Loại hình không hợp lệ với loại tin đã chọn.');
                    }
                },
            ],
            'title'            => ['required', 'string', 'max:250'],
            'slug'             => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('real_estate_listings', 'slug')->ignore($listing?->id),
            ],
            'description'      => ['nullable', 'string'],
            'address_detail'   => ['nullable', 'string', 'max:255'],
            'province_code'    => ['required', 'exists:provinces,province_code'],
            'ward_code'        => ['required', 'exists:wards,ward_code'],
            'area'             => ['required', 'numeric', 'min:1'],
            'bedrooms'         => ['nullable', 'integer', 'min:0'],
            'bathrooms'        => ['nullable', 'integer', 'min:0'],
            'floors'           => ['nullable', 'integer', 'min:1'],
            'interior_status'  => ['nullable', Rule::in(array_column(InteriorStatus::cases(), 'value'))],
            'is_price_negotiable' => ['boolean'],

            'price'       => [...$priceRule, 'numeric', 'min:0'],
            'is_urgent'   => ['boolean'],
            'urgent_days' => ['nullable', 'integer', 'min:1'],

            'monthly_rent'         => [...$monthlyRentRule, 'numeric', 'min:0'],
            'deposit'              => ['nullable', 'numeric', 'min:0'],
            'rental_period_months' => ['nullable', 'integer', 'min:3'],

            'house_subtype'     => ['nullable', Rule::in(array_column(HouseSubtype::cases(), 'value'))],
            'apartment_subtype' => ['nullable', Rule::in(array_column(ApartmentSubtype::cases(), 'value'))],
            'width'             => ['nullable', 'numeric', 'min:0'],
            'length'            => ['nullable', 'numeric', 'min:0'],
            'land_area'         => ['nullable', 'numeric', 'min:0'],
            'usable_area'       => ['nullable', 'numeric', 'min:0'],
            'net_area'          => ['nullable', 'numeric', 'min:0'],
            'legal_status'      => ['nullable', Rule::in(array_column(LegalStatus::cases(), 'value'))],
            'direction'         => ['nullable', Rule::in(array_column(CompassDirection::cases(), 'value'))],
            'balcony_direction' => ['nullable', Rule::in(array_column(CompassDirection::cases(), 'value'))],
            'project_name'      => ['nullable', 'string', 'max:150'],
            'apartment_address' => ['nullable', 'string', 'max:150'],
            'usage_status'      => ['nullable', Rule::in(array_column(UsageStatus::cases(), 'value'))],
            'front_road_width'      => ['nullable', 'numeric', 'min:0'],
            'current_rental_income' => ['nullable', 'numeric', 'min:0'],
            'management_fee'        => ['nullable', 'numeric', 'min:0'],

            'is_featured' => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],

            'gallery_media_uuids'   => ['nullable', 'array', 'max:6'],
            'gallery_media_uuids.*' => ['string', 'uuid'],
        ], [
            'listing_type.required'       => 'Vui lòng chọn loại tin (Bán hoặc Thuê).',
            'listing_type.in'             => 'Loại tin không hợp lệ.',
            'property_type.required'      => 'Vui lòng chọn loại hình bất động sản.',
            'title.required'               => 'Vui lòng nhập tiêu đề tin.',
            'title.max'                    => 'Tiêu đề không được vượt quá :max ký tự.',
            'slug.regex'                   => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
            'slug.unique'                  => 'Slug này đã được dùng cho tin khác, vui lòng chọn slug khác.',
            'slug.max'                     => 'Slug không được vượt quá :max ký tự.',
            'address_detail.max'           => 'Địa chỉ không được vượt quá :max ký tự.',
            'province_code.required'       => 'Vui lòng chọn Tỉnh/Thành phố.',
            'province_code.exists'         => 'Tỉnh/Thành phố không hợp lệ.',
            'ward_code.required'           => 'Vui lòng chọn Phường/Xã.',
            'ward_code.exists'             => 'Phường/Xã không hợp lệ.',
            'area.required'                => 'Vui lòng nhập diện tích.',
            'area.numeric'                 => 'Diện tích phải là số.',
            'area.min'                     => 'Diện tích phải lớn hơn 0.',
            'price.required'               => 'Vui lòng nhập giá bán (hoặc chọn "Giá thoả thuận").',
            'price.numeric'                => 'Giá bán phải là số.',
            'monthly_rent.required'        => 'Vui lòng nhập giá thuê/tháng (hoặc chọn "Giá thoả thuận").',
            'monthly_rent.numeric'         => 'Giá thuê/tháng phải là số.',
            'rental_period_months.min'     => 'Thời hạn thuê tối thiểu :min tháng.',
            'gallery_media_uuids.max'      => 'Tối đa :max ảnh cho mỗi tin.',
        ]);
    }
}
