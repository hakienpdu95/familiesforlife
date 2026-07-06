<?php
namespace Modules\WorkflowAutomation\Data;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class TriggerPayload extends Data
{
    public function __construct(
        public readonly string  $triggerType,
        public readonly string  $sourceModule,
        public readonly ?int    $organizationId,
        public readonly ?int    $actorId,
        public readonly ?string $actorEmail,
        public readonly ?string $actorName,
        public readonly ?string $actorRole,
        public readonly ?string $subjectType,
        public readonly ?int    $subjectId,
        public readonly ?string $subjectLabel,
        public readonly array   $extra = [],
        /** Snapshot of the subject model's attributes, addressable via `subject.attr.<field>`. */
        public readonly array   $subjectAttributes = [],
        public readonly string  $requestId = '',
        public readonly \DateTimeImmutable $firedAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Generic, module-agnostic factory. Builds a payload from any subject (Eloquent model
     * or plain object) plus arbitrary extra context. Organization is taken from the current
     * tenant (falling back to the subject's organization_id), and the actor from auth().
     *
     * Any field can be forced through $overrides (e.g. ['actorEmail' => $respondentRef]).
     */
    public static function make(
        string  $triggerType,
        string  $sourceModule,
        ?object $subject = null,
        array   $extra = [],
        array   $overrides = [],
    ): self {
        $subjectType  = null;
        $subjectId    = null;
        $subjectLabel = null;
        $subjectAttrs = [];
        $subjectOrgId = null;

        if ($subject instanceof Model) {
            $subjectType  = class_basename($subject);
            $subjectId    = is_numeric($subject->getKey()) ? (int) $subject->getKey() : null;
            $subjectLabel = "{$subjectType} #{$subject->getKey()}";
            $subjectAttrs = $subject->attributesToArray();
            $subjectOrgId = $subjectAttrs['organization_id'] ?? null;
        }

        $user = auth()->user();

        $defaults = [
            'triggerType'       => $triggerType,
            'sourceModule'      => $sourceModule,
            'organizationId'    => TenantContext::isSet()
                                    ? TenantContext::getOrganizationId()
                                    : ($subjectOrgId !== null ? (int) $subjectOrgId : null),
            'actorId'           => auth()->id(),
            'actorEmail'        => $user?->email,
            'actorName'         => $user?->name,
            'actorRole'         => ($user && method_exists($user, 'getRoleNames'))
                                    ? $user->getRoleNames()->first()
                                    : null,
            'subjectType'       => $subjectType,
            'subjectId'         => $subjectId,
            'subjectLabel'      => $subjectLabel,
            'extra'             => $extra,
            'subjectAttributes' => $subjectAttrs,
            'requestId'         => request()->header('X-Request-Id') ?? (string) \Str::uuid(),
        ];

        $args = array_merge($defaults, $overrides);

        return new self(...$args);
    }

    public function resolve(string $field): mixed
    {
        return match (true) {
            $field === 'trigger.type'                => $this->triggerType,
            $field === 'trigger.module'              => $this->sourceModule,
            $field === 'actor.id'                    => $this->actorId,
            $field === 'actor.email'                 => $this->actorEmail,
            $field === 'actor.name'                  => $this->actorName,
            $field === 'actor.role'                  => $this->actorRole,
            $field === 'subject.type'                => $this->subjectType,
            $field === 'subject.id'                  => $this->subjectId,
            $field === 'subject.label'               => $this->subjectLabel,
            str_starts_with($field, 'subject.attr.') => $this->subjectAttributes[substr($field, 13)] ?? null,
            str_starts_with($field, 'extra.')        => $this->extra[substr($field, 6)] ?? null,
            default                                  => null,
        };
    }

    public function render(string $template): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function ($m) {
            $val = $this->resolve($m[1]);
            return $val !== null ? (string) $val : $m[0];
        }, $template);
    }

    /**
     * Factory for State Machine transition events (§9.3).
     */
    public static function forStateChange(
        int     $organizationId,
        string  $entityType,
        int     $entityId,
        ?string $fromState,
        string  $toState,
        string  $transitionKey,
        ?string $comment = null,
        ?int    $actorId = null,
    ): self {
        return new self(
            triggerType:       'entity.state_changed',
            sourceModule:      'Core',
            organizationId:    $organizationId,
            actorId:           $actorId,
            actorEmail:        null,
            actorName:         null,
            actorRole:         null,
            subjectType:       $entityType,
            subjectId:         $entityId,
            subjectLabel:      "{$entityType} #{$entityId}",
            extra: [
                'entity_type'    => $entityType,
                'entity_id'      => $entityId,
                'from_state'     => $fromState,
                'to_state'       => $toState,
                'transition_key' => $transitionKey,
                'comment'        => $comment,
            ],
            requestId: (string) \Str::uuid(),
        );
    }

    /** Compact snapshot stored on the execution row for traceability. */
    public function toContext(): array
    {
        return array_filter([
            'extra'              => $this->extra,
            'subject_attributes' => $this->subjectAttributes,
            'actor_name'         => $this->actorName,
            'actor_email'        => $this->actorEmail,
            'subject_label'      => $this->subjectLabel,
        ], fn ($v) => $v !== null && $v !== []);
    }
}
