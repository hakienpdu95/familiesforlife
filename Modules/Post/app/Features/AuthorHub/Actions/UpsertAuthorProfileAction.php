<?php

namespace Modules\Post\Features\AuthorHub\Actions;

use App\Models\User;
use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\AuthorHub\Data\AuthorProfileData;
use Modules\Post\Models\PostAuthorProfile;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §6.1 — lần đầu tác giả lưu card "Hồ
 * sơ tác giả công khai" ở /auth/profile → tự tạo PostAuthorProfile (slug = slugFor($user)),
 * các lần lưu sau chỉ update. `is_public` giữ đúng default cột DB (`true`, §3.2) khi tạo mới —
 * KHÔNG có nhánh giá trị khác nhau theo account_type/organization_id của user (§0). Slug KHÔNG
 * đổi lại ở các lần update sau (đã là URL công khai — đổi bút danh không phá link cũ).
 */
class UpsertAuthorProfileAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    public function handle(User $user, AuthorProfileData $data): PostAuthorProfile
    {
        $profile = PostAuthorProfile::firstOrNew(['user_id' => $user->id]);

        if (! $profile->exists) {
            $profile->slug = PostAuthorProfile::slugFor($user, $data->pen_name);
        }

        $profile->user_id      = $user->id;
        $profile->pen_name     = $data->pen_name;
        $profile->bio          = $data->bio;
        $profile->job_title    = $data->job_title;
        $profile->credentials  = $data->credentials;
        $profile->social_links = $data->social_links;
        $profile->is_public    = $data->is_public;
        $profile->save();

        // §2.6 — form tạo mới chưa có profile.id lúc FilePond upload avatar, ảnh tạm gắn ở
        // FilePondDraft; "nhận" vào profile thật vừa tạo/cập nhật ngay đây (idempotent nếu
        // avatar đã được đính trực tiếp qua X-Context-Id ở lần sửa sau — xem MediaUploadController).
        if ($data->avatar_media_uuid) {
            $this->mediaUpload->reassociateFilePondDrafts($profile, [$data->avatar_media_uuid], 'avatar');
        }

        return $profile;
    }
}
