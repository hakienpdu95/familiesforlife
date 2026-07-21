<?php

namespace Modules\ContentBrief\Policies;

use App\Models\User;
use Modules\ContentBrief\Models\ContentBrief;

/**
 * spec/ContentBrief_Technical_Specification.md §5 — Lớp A: content_brief.view (CEO/Ops/
 * Marketing/Admin), content_brief.manage (soạn thảo — Marketing/Admin), content_brief.approve
 * (duyệt/từ chối — CEO/Ops/Admin). "generate" dùng chung ability update (content_brief.manage).
 */
class ContentBriefPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('content_brief.view');
    }

    public function view(User $user, ContentBrief $brief): bool
    {
        return $user->can('content_brief.view');
    }

    public function create(User $user): bool
    {
        return $user->can('content_brief.manage');
    }

    public function update(User $user, ContentBrief $brief): bool
    {
        return $user->can('content_brief.manage');
    }

    public function delete(User $user, ContentBrief $brief): bool
    {
        return $user->can('content_brief.manage');
    }

    public function approve(User $user, ContentBrief $brief): bool
    {
        return $user->can('content_brief.approve');
    }
}
