<?php

namespace Modules\RealEstate\Features\ListingManagement\Actions;

use App\Services\Media\MediaUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\RealEstate\Features\ListingManagement\Actions\Concerns\BuildsListingAttributes;
use Modules\RealEstate\Features\ListingManagement\Data\RealEstateListingData;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * spec/RealEstateForSale_Technical_Specification.md §5.5 — Organization tạo tin ở trạng thái
 * Draft (HasApproval::bootHasApproval() tự tạo ApprovalSubject), KHÔNG tự động submit duyệt
 * ngay như Product — tin BĐS có nhiều field/ảnh nên Organization cần chỉnh sửa/hoàn thiện
 * trước, tự bấm "Gửi duyệt" riêng (route submit-approval, §7.1) khi sẵn sàng.
 */
class CreateRealEstateListingAction
{
    use AsAction;
    use BuildsListingAttributes;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    public function handle(RealEstateListingData $data): RealEstateListing
    {
        return DB::transaction(function () use ($data) {
            $listing = RealEstateListing::create(array_merge($this->buildAttributes($data), [
                'slug'       => $this->uniqueSlug($data->title),
                'created_by' => auth()->id(),
            ]));

            if (! empty($data->gallery_media_uuids)) {
                $this->mediaUpload->reassociateFilePondDrafts($listing, $data->gallery_media_uuids, 'real_estate_gallery');
            }

            return $listing;
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 2;

        while (RealEstateListing::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
