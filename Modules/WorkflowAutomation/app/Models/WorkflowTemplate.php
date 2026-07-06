<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'category', 'icon', 'color', 'tags',
        'template_config', 'trigger_type', 'is_public', 'author_org_id',
        'version', 'usage_count', 'rating', 'preview_description',
    ];

    protected $casts = [
        'is_public'       => 'boolean',
        'tags'            => 'array',
        'template_config' => 'array',
        'rating'          => 'float',
    ];
}
