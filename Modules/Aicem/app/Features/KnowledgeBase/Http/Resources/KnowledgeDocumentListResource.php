<?php

namespace Modules\Aicem\Features\KnowledgeBase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/aicem/knowledge-documents — xem KnowledgeDocumentApiController. */
class KnowledgeDocumentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'type'         => $this->type,
            'subject_type' => $this->subject_type,

            'scope_count' => $this->scope ? count($this->scope) : null,
            'scope_match' => $this->scope ? $this->scope_match->value : null,

            'priority'        => $this->priority,
            'current_version' => $this->current_version,
            'creator_name'    => $this->creator?->name,

            'edit_url'    => route('backend.aicem.knowledge-documents.edit', $this->resource),
            'destroy_url' => route('backend.aicem.knowledge-documents.destroy', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
