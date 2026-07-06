<?php

namespace App\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasTenantMedia;
use Spatie\MediaLibrary\HasMedia;

/**
 * Temporary holder for files uploaded via FilePond before the form is saved.
 *
 * Completely separate from JoditDraft — FilePond uploads NEVER touch JoditDraft.
 *
 * Lifecycle:
 *  1. User picks a file in FilePond → POST /api/v1/media/upload
 *  2. Media created with model_type = FilePondDraft, model_id = draft.id
 *  3. User submits form → MediaUploadService::reassociateFilePondDrafts() moves media to real entity
 *  4. Artisan media:cleanup-orphans deletes FilePondDraft media older than TTL (filepond_orphan_ttl_hours)
 *
 * For edit forms (X-Context-Id provided), media is attached directly to the entity
 * on upload — no FilePondDraft is created.
 */
class FilePondDraft extends TenantAwareModel implements HasMedia
{
    use HasTenantMedia;

    protected $table = 'filepond_drafts';

    protected $fillable = ['organization_id', 'user_id', 'context_type', 'context_id'];
}
