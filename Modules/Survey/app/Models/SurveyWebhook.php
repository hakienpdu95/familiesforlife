<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyWebhook extends Model
{
    protected $fillable = [
        'survey_id',
        'url',
        'secret',
        'events',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = ['secret'];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /** @return string[] */
    public function eventList(): array
    {
        if (empty($this->events)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $this->events)));
    }

    public function listensTo(string $event): bool
    {
        $list = $this->eventList();
        return empty($list) || in_array($event, $list, true);
    }
}
