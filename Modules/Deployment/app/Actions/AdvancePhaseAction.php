<?php

namespace Modules\Deployment\Actions;

use App\Foundation\VerticalDefinition;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Notifications\PhaseAdvancedNotification;

class AdvancePhaseAction
{
    use AsAction;

    public function handle(DeploymentTarget $target, VerticalDefinition $vertical): void
    {
        $phases     = $vertical->phases();
        $currentIdx = array_search($target->current_phase, $phases, true);

        if ($currentIdx === false) {
            throw new \RuntimeException("Phase '{$target->current_phase}' không hợp lệ cho vertical này.");
        }

        if ($currentIdx >= count($phases) - 1) {
            throw new \RuntimeException('Target đã ở phase cuối, không thể tiến lên.');
        }

        $requiredPending = $target->checklistItems()
            ->where('phase', $target->current_phase)
            ->where('is_required', true)
            ->where('is_done', false)
            ->count();

        if ($requiredPending > 0) {
            throw new \RuntimeException(
                "Còn {$requiredPending} mục checklist bắt buộc chưa hoàn thành trong phase hiện tại."
            );
        }

        $fromPhase = $target->current_phase;
        $nextPhase = $phases[$currentIdx + 1];
        $target->update(['current_phase' => $nextPhase]);

        // Auto-create a task in the linked project for the new phase
        (new CreatePhaseTaskAction)->handle($target, $nextPhase);

        // Phase-specific automations
        if ($vertical->autoAssignsDataCollection($nextPhase)) {
            (new AssignDataCollectionSurveyAction)->handle($target->fresh());
        }

        // Notify the assigned employee (if any) and the current user
        $this->notify($target->fresh(), $fromPhase, $nextPhase);
    }

    private function notify(DeploymentTarget $target, string $from, string $to): void
    {
        $notification = new PhaseAdvancedNotification($target, $from, $to);
        $notified     = collect();

        if ($target->assignedEmployee?->user_id) {
            $user = User::find($target->assignedEmployee->user_id);
            if ($user) {
                $user->notify($notification);
                $notified->push($user->id);
            }
        }

        // Also notify the actor if different
        $actorId = auth()->id();
        if ($actorId && ! $notified->contains($actorId)) {
            auth()->user()?->notify($notification);
        }
    }
}
