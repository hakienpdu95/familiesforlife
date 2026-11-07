<?php

namespace App\Shared\Traceability;

use App\Shared\Traceability\Contracts\TracePresenter;
use Illuminate\Database\Eloquent\Relations\Relation;

class TraceabilityRegistry
{
    /** @var array<class-string, class-string<TracePresenter>> */
    private array $presenters = [];

    /**
     * @param  class-string  $modelClass
     * @param  class-string<TracePresenter>  $presenterClass
     */
    public function register(string $modelClass, string $presenterClass): void
    {
        $this->presenters[$modelClass] = $presenterClass;
    }

    /** @return array<class-string, class-string<TracePresenter>> */
    public function all(): array
    {
        return $this->presenters;
    }

    public function presenterForMorphType(string $morphType): ?TracePresenter
    {
        $class = Relation::getMorphedModel($morphType) ?? $morphType;

        return $this->presenterForClass($class);
    }

    public function presenterForClass(string $class): ?TracePresenter
    {
        $presenter = $this->presenters[$class] ?? null;

        return $presenter ? app($presenter) : null;
    }
}
