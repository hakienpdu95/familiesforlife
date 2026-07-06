<?php

namespace Modules\WorkflowAutomation\Services;

use Modules\WorkflowAutomation\Core\WorkflowDispatcher;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowEntityState;
use Modules\WorkflowAutomation\Models\WorkflowEntityStateLog;
use Modules\WorkflowAutomation\Models\WorkflowEntityTransition;

/**
 * State Machine service (Model B — §9.3).
 *
 * Usage:
 *   $service->performTransition($entity, $transitionKey, $actorRole, $actorId, $comment);
 *   $service->setStateDirectly($entityType, $entityId, $stateKey, $actorId, $orgId);
 *
 * Entities wanting state machine support should expose:
 *   - getWorkflowState(): ?string   → current state_key
 *   - setWorkflowState(string $key) → persist state to the entity record
 */
class WorkflowEntityStateService
{
    public function __construct(private readonly WorkflowDispatcher $dispatcher) {}

    /**
     * Perform a defined transition on an entity.
     * Validates allowed_roles, from/to states, logs history, fires automation.
     */
    public function performTransition(
        object  $entity,
        string  $transitionKey,
        string  $actorRole,
        ?int    $actorId,
        int     $orgId,
        ?string $comment = null,
    ): void {
        $entityType = class_basename($entity);

        $transition = WorkflowEntityTransition::where('organization_id', $orgId)
            ->where('entity_type', $entityType)
            ->where('transition_key', $transitionKey)
            ->first();

        if (!$transition) {
            throw new \RuntimeException("Transition '{$transitionKey}' not found for {$entityType}.");
        }

        if (!$transition->isAllowedForRole($actorRole)) {
            throw new \RuntimeException("Role '{$actorRole}' is not allowed to perform '{$transitionKey}'.");
        }

        // Validate from_state (NULL = any state is allowed)
        if ($transition->from_state_id !== null) {
            $fromState = WorkflowEntityState::find($transition->from_state_id);
            $currentStateKey = method_exists($entity, 'getWorkflowState')
                ? $entity->getWorkflowState()
                : ($entity->workflow_state ?? null);

            if ($fromState && $currentStateKey !== $fromState->state_key) {
                throw new \RuntimeException(
                    "Entity is in state '{$currentStateKey}', but transition requires '{$fromState->state_key}'."
                );
            }
        }

        $toState = WorkflowEntityState::find($transition->to_state_id);
        if (!$toState) {
            throw new \RuntimeException("Target state #{$transition->to_state_id} not found.");
        }

        $fromStateKey = method_exists($entity, 'getWorkflowState')
            ? $entity->getWorkflowState()
            : ($entity->workflow_state ?? null);

        // Update entity state
        if (method_exists($entity, 'setWorkflowState')) {
            $entity->setWorkflowState($toState->state_key);
        } else {
            $entity->workflow_state = $toState->state_key;
            $entity->save();
        }

        // Log the transition
        $logEntry = WorkflowEntityStateLog::create([
            'organization_id'       => $orgId,
            'entity_type'           => $entityType,
            'entity_id'             => $entity->id,
            'from_state_key'        => $fromStateKey,
            'to_state_key'          => $toState->state_key,
            'transition_key'        => $transitionKey,
            'actor_id'              => $actorId,
            'comment'               => $comment,
            'triggered_execution_id'=> null,
            'created_at'            => now(),
        ]);

        // Fire automation workflow if configured
        if ($transition->triggers_workflow_id) {
            $triggerPayload = TriggerPayload::forStateChange(
                organizationId: $orgId,
                entityType:     $entityType,
                entityId:       $entity->id,
                fromState:      $fromStateKey,
                toState:        $toState->state_key,
                transitionKey:  $transitionKey,
                comment:        $comment,
                actorId:        $actorId,
            );

            $this->dispatcher->fire($triggerPayload, $transition->triggers_workflow_id);
        }
    }

    /**
     * Directly set entity state (used by subject.state_set executor, bypasses transition rules).
     * Logs as system-initiated change (actor_id = null if not provided).
     */
    public function setStateDirectly(
        string $entityType,
        int    $entityId,
        string $stateKey,
        ?int   $actorId,
        int    $orgId,
    ): void {
        $state = WorkflowEntityState::where('organization_id', $orgId)
            ->where('entity_type', $entityType)
            ->where('state_key', $stateKey)
            ->first();

        if (!$state) {
            throw new \RuntimeException("State '{$stateKey}' not found for {$entityType} in org #{$orgId}.");
        }

        WorkflowEntityStateLog::create([
            'organization_id'       => $orgId,
            'entity_type'           => $entityType,
            'entity_id'             => $entityId,
            'from_state_key'        => null,
            'to_state_key'          => $stateKey,
            'transition_key'        => null,
            'actor_id'              => $actorId,
            'comment'               => null,
            'triggered_execution_id'=> null,
            'created_at'            => now(),
        ]);
    }

    /**
     * Get available transitions from current state for a given role.
     */
    public function getAvailableTransitions(string $entityType, string $currentStateKey, string $role, int $orgId): array
    {
        $currentState = WorkflowEntityState::where('organization_id', $orgId)
            ->where('entity_type', $entityType)
            ->where('state_key', $currentStateKey)
            ->first();

        $query = WorkflowEntityTransition::where('organization_id', $orgId)
            ->where('entity_type', $entityType)
            ->orderBy('sort_order');

        if ($currentState) {
            $query->where(function ($q) use ($currentState) {
                $q->whereNull('from_state_id')
                  ->orWhere('from_state_id', $currentState->id);
            });
        }

        return $query->get()
            ->filter(fn ($t) => $t->isAllowedForRole($role))
            ->values()
            ->all();
    }
}
