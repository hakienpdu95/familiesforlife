<?php

namespace Modules\Post\Features\AuthorHub\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Post\Features\AuthorHub\Actions\UpsertAuthorProfileAction;
use Modules\Post\Features\AuthorHub\Data\AuthorProfileData;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §6.1 — card "Hồ sơ tác giả công khai"
 * ở `/auth/profile` (Modules/Auth). Controller thuộc Modules/Post (chủ sở hữu
 * PostAuthorProfile) — URI đặt dưới `auth/profile/*` chỉ vì tiện UX (cùng trang), KHÔNG có
 * nghĩa PostAuthorProfile thuộc Modules/Auth.
 */
class AuthorProfileSelfController extends Controller
{
    public function update(Request $request, UpsertAuthorProfileAction $action): RedirectResponse
    {
        $user = $request->user();

        // §6.1 — chỉ tài khoản isPlatform() mới có card này (§0 v1.2, loại marketing/Lớp B);
        // chặn thêm ở đây phòng request thủ công bỏ qua điều kiện hiển thị ở view.
        abort_unless($user->isPlatform(), 403);

        // §6.1 — bag riêng ('authorProfile') vì `/auth/profile` có nhiều form khác cùng trang
        // (Fortify updateProfileInformation/updatePassword cũng dùng bag riêng của chúng) —
        // tránh lỗi validate của form này đè lên các form khác khi redirect back.
        $validated = $request->validateWithBag('authorProfile', [
            'pen_name'               => ['nullable', 'string', 'max:120'],
            'bio'                    => ['nullable', 'string', 'max:500'],
            'job_title'              => ['nullable', 'string', 'max:150'],
            'credentials'            => ['nullable', 'string', 'max:255'],
            'social_links'           => ['nullable', 'array'],
            'social_links.facebook'  => ['nullable', 'url', 'max:255'],
            'social_links.x'         => ['nullable', 'url', 'max:255'],
            'social_links.linkedin'  => ['nullable', 'url', 'max:255'],
            'social_links.website'   => ['nullable', 'url', 'max:255'],
            'is_public'              => ['boolean'],
            'avatar_media_uuid'      => ['nullable', 'string', 'uuid'],
        ]);

        $socialLinks = array_filter($validated['social_links'] ?? []);

        $action->handle($user, AuthorProfileData::from([
            'pen_name'          => $validated['pen_name'] ?? null,
            'bio'               => $validated['bio'] ?? null,
            'job_title'         => $validated['job_title'] ?? null,
            'credentials'       => $validated['credentials'] ?? null,
            'social_links'      => $socialLinks ?: null,
            'is_public'         => $request->boolean('is_public'),
            'avatar_media_uuid' => $validated['avatar_media_uuid'] ?? null,
        ]));

        return redirect()->route('auth.profile')->with('status', 'author-profile-updated');
    }
}
