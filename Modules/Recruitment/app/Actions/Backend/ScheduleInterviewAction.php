<?php

namespace Modules\Recruitment\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcInterview;
use Modules\Recruitment\Models\RcInterviewPanelist;

class ScheduleInterviewAction
{
    use AsAction;

    /**
     * @param  array{
     *   stage_id: int,
     *   interview_type: string,
     *   title: ?string,
     *   scheduled_at: string,
     *   duration_minutes: int,
     *   location: ?string,
     *   meeting_url: ?string,
     *   meeting_id: ?string,
     *   interviewer_note: ?string,
     *   panelists: array<array{user_id: int, role: string}>,
     * } $data
     */
    public function handle(RcApplication $application, array $data): RcInterview
    {
        // BR-RC-004: không tạo interview khi application rejected/withdrawn
        if (in_array($application->status?->value, ['rejected', 'withdrawn', 'hired'])) {
            abort(422, 'Không thể tạo lịch phỏng vấn cho đơn đã kết thúc');
        }

        $interview = RcInterview::create([
            'application_id'   => $application->id,
            'stage_id'         => $data['stage_id'],
            'interview_type'   => $data['interview_type'],
            'title'            => $data['title'] ?? null,
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'location'         => $data['location'] ?? null,
            'meeting_url'      => $data['meeting_url'] ?? null,
            'meeting_id'       => $data['meeting_id'] ?? null,
            'interviewer_note' => $data['interviewer_note'] ?? null,
            'status'           => 'scheduled',
        ]);

        // Thêm panelists — BR-RC-004: phải là user nội bộ (validated ở controller)
        foreach ($data['panelists'] ?? [] as $p) {
            RcInterviewPanelist::create([
                'interview_id'    => $interview->id,
                'user_id'         => $p['user_id'],
                'role'            => $p['role'] ?? 'interviewer',
                'response_status' => 'pending',
            ]);
        }

        return $interview;
    }
}
