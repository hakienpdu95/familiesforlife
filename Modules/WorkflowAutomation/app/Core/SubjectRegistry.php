<?php

namespace Modules\WorkflowAutomation\Core;

use Modules\WorkflowAutomation\Contracts\WorkflowSubject;
use Modules\WorkflowAutomation\Data\TriggerPayload;

final class SubjectRegistry
{
    private array $subjects = [];

    public function register(string $type, string $fqcn, string $label, array $updatableFields): void
    {
        $this->subjects[$type] = compact('fqcn', 'label', 'updatableFields');
    }

    public function get(string $type): ?array
    {
        return $this->subjects[$type] ?? null;
    }

    public function all(): array
    {
        return $this->subjects;
    }

    public function resolve(string $type, TriggerPayload $payload): ?object
    {
        $config = $this->get($type);
        if (!$config) return null;

        $class = $config['fqcn'];
        if (!class_exists($class)) return null;

        if (in_array(WorkflowSubject::class, class_implements($class), true)) {
            return $class::resolveFromPayload($payload);
        }

        return $class::find($payload->subjectId);
    }
}
