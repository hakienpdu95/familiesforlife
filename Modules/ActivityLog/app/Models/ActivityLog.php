<?php

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\ActivityLog\Enums\LogLevel;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    protected $table = 'activity_log';

    protected $casts = [
        'level'      => LogLevel::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function contexts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLogContext::class, 'log_id');
    }

    public function http(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ActivityLogHttp::class, 'log_id');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getContextMapAttribute(): array
    {
        return $this->contexts
            ->mapWithKeys(fn($c) => [$c->key_name => $c->typedValue()])
            ->all();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeForOrganization(Builder $q, int $orgId): Builder
    {
        // Bao gồm cả rows không có org (system/legacy logs)
        return $q->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'));
    }

    public function scopeModule(Builder $q, string $module): Builder
    {
        return $q->where('module', $module);
    }

    public function scopeAction(Builder $q, string $action): Builder
    {
        return $q->where('action', $action);
    }

    public function scopeLevel(Builder $q, int $min): Builder
    {
        return $q->where('level', '>=', $min);
    }

    /**
     * Lọc theo subject — dùng FQCN (get_class) để khớp với giá trị lưu trong DB.
     */
    public function scopeForSubject(Builder $q, Model $model): Builder
    {
        return $q->where('subject_type', get_class($model))
                 ->where('subject_id', $model->getKey());
    }
}
