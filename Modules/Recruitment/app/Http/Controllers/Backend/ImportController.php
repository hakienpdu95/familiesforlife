<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Recruitment\Actions\Backend\ImportFromMarketplaceAction;

class ImportController extends Controller
{
    public function fromMarketplace(Request $request, ImportFromMarketplaceAction $action): JsonResponse
    {
        $this->authorize('create', \Modules\Recruitment\Models\RcApplication::class);

        $validated = $request->validate([
            'full_name'          => ['required', 'string', 'max:150'],
            'email'              => ['required', 'email', 'max:150'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'current_title'      => ['nullable', 'string', 'max:150'],
            'current_company'    => ['nullable', 'string', 'max:150'],
            'years_experience'   => ['nullable', 'integer', 'min:0'],
            'skills'             => ['nullable', 'string'],
            'linkedin_url'       => ['nullable', 'url', 'max:300'],
            'mkt_applicant_id'   => ['required', 'string', 'size:36'],
            'mkt_application_id' => ['required', 'string', 'size:36'],
            'jp_job_post_id'     => ['nullable', 'string', 'size:36'],
            'cover_letter'       => ['nullable', 'string'],
            'expected_salary'    => ['nullable', 'numeric', 'min:0'],
        ]);

        $application = $action->handle($validated);

        return response()->json([
            'message'        => 'Đã import ứng viên từ Marketplace thành công',
            'application_id' => $application->id,
            'candidate_id'   => $application->candidate_id,
        ], 201);
    }
}
