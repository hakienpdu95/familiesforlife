<?php
namespace Modules\Marketplace\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Data\Requests\StoreApplicantExperienceData;
use Modules\Marketplace\Data\Requests\StoreApplicantPortfolioData;
use Modules\Marketplace\Data\Requests\StoreApplicantSkillData;
use Modules\Marketplace\Data\Requests\UpdateApplicantProfileData;
use Modules\Marketplace\Http\Resources\MktApplicantExperienceResource;
use Modules\Marketplace\Http\Resources\MktApplicantPortfolioResource;
use Modules\Marketplace\Http\Resources\MktApplicantResource;
use Modules\Marketplace\Http\Resources\MktApplicantSkillResource;
use Modules\Marketplace\Models\MktApplicantExperience;
use Modules\Marketplace\Models\MktApplicantPortfolio;
use Modules\Marketplace\Models\MktApplicantSkill;

class ApplicantProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $applicant = $request->user('marketplace');
        $applicant->load(['skills', 'experiences', 'portfolios']);

        return response()->json(new MktApplicantResource($applicant));
    }

    public function update(Request $request): JsonResponse
    {
        $applicant = $request->user('marketplace');
        $data      = UpdateApplicantProfileData::validateAndCreate($request->all());

        $updates = array_filter([
            'display_name'         => $data->display_name,
            'headline'             => $data->headline,
            'bio'                  => $data->bio,
            'phone'                => $data->phone,
            'location'             => $data->location,
            'website_url'          => $data->website_url,
            'linkedin_url'         => $data->linkedin_url,
            'years_experience'     => $data->years_experience,
            'expected_salary_min'  => $data->expected_salary_min,
            'expected_salary_max'  => $data->expected_salary_max,
            'salary_currency'      => $data->salary_currency,
            'availability'         => $data->availability?->value,
            'is_profile_public'    => $data->is_profile_public,
            'is_email_public'      => $data->is_email_public,
        ], fn($v) => $v !== null);

        // Always include booleans (they may be false)
        $updates['is_profile_public'] = $data->is_profile_public;
        $updates['is_email_public']   = $data->is_email_public;

        $applicant->update($updates);

        return response()->json(new MktApplicantResource($applicant->fresh(['skills', 'experiences', 'portfolios'])));
    }

    public function addSkill(Request $request): JsonResponse
    {
        $applicant = $request->user('marketplace');
        $data      = StoreApplicantSkillData::validateAndCreate($request->all());

        $skill = MktApplicantSkill::create([
            'applicant_id'      => $applicant->id,
            'skill_name'        => $data->skill_name,
            'proficiency_level' => $data->proficiency_level->value,
            'years_used'        => $data->years_used,
            'sort_order'        => $data->sort_order,
        ]);

        return response()->json(new MktApplicantSkillResource($skill), 201);
    }

    public function addExperience(Request $request): JsonResponse
    {
        $applicant = $request->user('marketplace');
        $data      = StoreApplicantExperienceData::validateAndCreate($request->all());

        $experience = MktApplicantExperience::create([
            'applicant_id' => $applicant->id,
            'company_name' => $data->company_name,
            'title'        => $data->title,
            'description'  => $data->description,
            'start_month'  => $data->start_month,
            'start_year'   => $data->start_year,
            'end_month'    => $data->end_month,
            'end_year'     => $data->end_year,
            'is_current'   => $data->is_current,
            'sort_order'   => $data->sort_order,
        ]);

        return response()->json(new MktApplicantExperienceResource($experience), 201);
    }

    public function addPortfolio(Request $request): JsonResponse
    {
        $applicant = $request->user('marketplace');
        $data      = StoreApplicantPortfolioData::validateAndCreate($request->all());

        $portfolio = MktApplicantPortfolio::create([
            'applicant_id'   => $applicant->id,
            'title'          => $data->title,
            'description'    => $data->description,
            'project_url'    => $data->project_url,
            'thumbnail_url'  => $data->thumbnail_url,
            'tech_stack'     => $data->tech_stack,
            'completed_year' => $data->completed_year,
            'sort_order'     => $data->sort_order,
        ]);

        return response()->json(new MktApplicantPortfolioResource($portfolio), 201);
    }
}
