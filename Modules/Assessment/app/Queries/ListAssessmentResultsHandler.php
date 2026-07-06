<?php

namespace Modules\Assessment\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Assessment\Models\AssessmentResult;

class ListAssessmentResultsHandler
{
    public function handle(ListAssessmentResultsQuery $query): LengthAwarePaginator
    {
        return AssessmentResult::where('assessment_code', $query->assessmentCode)
            ->when($query->bandCode, fn ($q) => $q->where('maturity_level', $query->bandCode))
            ->when($query->scoreMin !== null, fn ($q) => $q->where('overall_score', '>=', $query->scoreMin))
            ->when($query->scoreMax !== null, fn ($q) => $q->where('overall_score', '<=', $query->scoreMax))
            ->with(['classification'])
            ->orderBy($query->sortField, $query->sortDir)
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
