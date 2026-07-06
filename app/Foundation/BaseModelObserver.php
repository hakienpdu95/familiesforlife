<?php

namespace App\Foundation;

use Illuminate\Database\Eloquent\Model;
use Modules\ActivityLog\Core\ActivityLogger;

/**
 * Base observer — ghi ActivityLog chuẩn cho mọi model.
 * Subclass override module(), resourceCode(), và *Context() để thêm context riêng.
 */
abstract class BaseModelObserver
{
    abstract protected function module(): string;

    abstract protected function resourceCode(): string;

    protected function subjectLabel(Model $m): ?string
    {
        if (method_exists($m, 'getActivityLabel')) {
            return $m->getActivityLabel();
        }

        return $m->name ?? $m->title ?? $m->label ?? "#{$m->getKey()}";
    }

    protected function createdContext(Model $m): array
    {
        return ['organization_id' => $m->organization_id ?? null];
    }

    protected function updatedContext(Model $m): array
    {
        return ['organization_id' => $m->organization_id ?? null];
    }

    protected function deletedContext(Model $m): array
    {
        return ['organization_id' => $m->organization_id ?? null];
    }

    public function created(Model $model): void
    {
        ActivityLogger::info(
            $this->module(),
            $this->resourceCode() . '.created',
            $model,
            $this->createdContext($model),
        );
    }

    public function updated(Model $model): void
    {
        if (empty($model->getChanges())) {
            return;
        }

        ActivityLogger::info(
            $this->module(),
            $this->resourceCode() . '.updated',
            $model,
            $this->updatedContext($model),
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLogger::warning(
            $this->module(),
            $this->resourceCode() . '.deleted',
            $model,
            $this->deletedContext($model),
        );
    }
}
