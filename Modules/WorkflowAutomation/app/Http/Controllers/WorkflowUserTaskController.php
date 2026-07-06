<?php

namespace Modules\WorkflowAutomation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\WorkflowAutomation\Actions\ResumeWorkflowAction;
use Modules\WorkflowAutomation\Models\WorkflowUserTask;

/**
 * Human-in-the-loop respond endpoint (§9.2 — Mô hình C).
 *
 * Routes:
 *   GET  /workflow/tasks/{token}           → show task for review
 *   POST /workflow/tasks/{token}/respond   → submit decision / form
 */
class WorkflowUserTaskController extends Controller
{
    public function show(string $token)
    {
        $task = WorkflowUserTask::where('task_token', $token)->firstOrFail();

        // Expired check
        if ($task->due_at && now()->isAfter($task->due_at) && $task->status === WorkflowUserTask::STATUS_PENDING) {
            $task->update(['status' => WorkflowUserTask::STATUS_EXPIRED]);
        }

        return view('workflowautomation::user-tasks.show', compact('task'));
    }

    public function respond(Request $request, string $token)
    {
        $task = WorkflowUserTask::where('task_token', $token)
            ->where('status', WorkflowUserTask::STATUS_PENDING)
            ->firstOrFail();

        $allowed = $task->allowed_decisions ?? ['approve', 'reject'];

        $validated = $request->validate([
            'decision'      => ['required', 'string', 'in:' . implode(',', $allowed)],
            'comment'       => ['nullable', 'string', 'max:500'],
            'form_response' => ['nullable', 'array'],
        ]);

        $isApproved = in_array($validated['decision'], ['approve', 'complete', 'publish'], true);
        $newStatus  = $isApproved
            ? WorkflowUserTask::STATUS_COMPLETED
            : WorkflowUserTask::STATUS_REJECTED;

        $task->update([
            'status'        => $newStatus,
            'decision'      => $validated['decision'],
            'comment'       => $validated['comment'] ?? null,
            'form_response' => isset($validated['form_response'])
                ? json_encode($validated['form_response'], JSON_UNESCAPED_UNICODE)
                : null,
            'completed_by'  => auth()->id(),
            'completed_at'  => now(),
        ]);

        // Dispatch resume job (§9.2 step 3)
        ResumeWorkflowAction::dispatch(
            executionId:  $task->execution_id,
            decision:     $validated['decision'],
            formResponse: $validated['form_response'] ?? null,
            completedBy:  auth()->id(),
            comment:      $validated['comment'] ?? null,
        )->onQueue('workflows');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Phản hồi đã được ghi nhận.']);
        }

        return redirect()->back()->with('success', 'Phản hồi đã được ghi nhận. Quy trình sẽ tiếp tục tự động.');
    }

    public function myTasks(Request $request)
    {
        $userId = auth()->id();
        $orgId  = \App\Shared\Tenancy\TenantContext::getOrganizationId();

        $userRoles = auth()->user()?->getRoleNames()?->toArray() ?? [];

        $tasks = WorkflowUserTask::where('organization_id', $orgId)
            ->where('status', WorkflowUserTask::STATUS_PENDING)
            ->where(function ($q) use ($userId, $userRoles) {
                $q->where('assignee_id', $userId)
                  ->orWhereIn('assignee_role', $userRoles);
            })
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($tasks->items());
        }

        return view('workflowautomation::user-tasks.index', compact('tasks'));
    }
}
