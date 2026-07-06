<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Assessment\Events\MaturityLevelChanged;
use Modules\Employee\Models\Employee;

class UpdateEmployeeDigitalCompetencyListener
{
    public function handle(AssessmentCompleted $event): void
    {
        if ($event->result->assessment_code !== 'TDWCF') {
            return;
        }

        $result = $event->result;

        if ($result->subject_type !== Employee::class) {
            return;
        }

        $employee = Employee::find($result->subject_id);

        if (! $employee) {
            return;
        }

        $oldLevel = $employee->digital_maturity_level;

        $employee->update([
            'digital_competency_score'      => $result->overall_score,
            'digital_maturity_level'        => $result->maturity_level,
            'latest_assessment_result_id'   => $result->id,
            'last_assessed_at'              => $result->calculated_at ?? now(),
        ]);

        if ($oldLevel !== $result->maturity_level && $result->maturity_level) {
            event(new MaturityLevelChanged($employee, (string) $oldLevel, $result->maturity_level));
        }
    }
}
