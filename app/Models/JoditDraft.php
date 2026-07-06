<?php

namespace App\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasTenantMedia;
use Spatie\MediaLibrary\HasMedia;

/**
 * Temporary holder for images uploaded via Jodit editor before content is saved.
 *
 * Lifecycle:
 *  1. User pastes/inserts image in Jodit → POST /api/media/jodit-upload
 *  2. Media created with model_type = JoditDraft, model_id = draft.id
 *  3. User saves content → MediaUploadService::reassociateOrphans() moves media to real entity
 *  4. Artisan media:cleanup-orphans deletes JoditDraft media older than 72h (last_touched_at)
 */
class JoditDraft extends TenantAwareModel implements HasMedia
{
    use HasTenantMedia;

    protected $table = 'jodit_drafts';

    protected $fillable = ['organization_id', 'user_id', 'context_type', 'context_id'];
}
