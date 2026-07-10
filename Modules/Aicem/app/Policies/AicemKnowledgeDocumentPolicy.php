<?php

namespace Modules\Aicem\Policies;

use App\Models\User;
use Modules\Aicem\Models\AicemKnowledgeDocument;

/**
 * aicem.view = xem read-only (CEO, Ops); aicem.config_prompt = sửa knowledge base/template/workflow
 * (AI_Operator, System Admin) — spec/AICEM_Technical_Specification.md mục 12.
 */
class AicemKnowledgeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('aicem.view') || $user->can('aicem.config_prompt');
    }

    public function view(User $user, AicemKnowledgeDocument $document): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('aicem.config_prompt');
    }

    public function update(User $user, AicemKnowledgeDocument $document): bool
    {
        return $user->can('aicem.config_prompt');
    }

    public function delete(User $user, AicemKnowledgeDocument $document): bool
    {
        return $user->can('aicem.config_prompt');
    }

    /** Rollback về 1 version lịch sử — cùng quyền với sửa. */
    public function rollback(User $user, AicemKnowledgeDocument $document): bool
    {
        return $user->can('aicem.config_prompt');
    }
}
