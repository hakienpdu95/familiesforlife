<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Enums\Visibility;
use Modules\JobPosting\Http\Resources\JpJobPostPublicResource;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpScreeningQuestion;
use Modules\JobPosting\Models\RcApplication;
use Modules\JobPosting\Models\RcCandidate;
use Modules\JobPosting\Services\JpJobPostStatService;

class JpCareerApiController extends Controller
{
    public function __construct(private readonly JpJobPostStatService $statService) {}
    // GET /api/careers/{org_slug}/jobs
    public function index(Request $request, string $orgSlug): JsonResponse
    {
        $org = Organization::where('slug', $orgSlug)->firstOrFail();

        $query = JpJobPost::withoutTenant()
            ->where('organization_id', $org->id)
            ->where('status', JobPostStatus::Published->value)
            ->where('visibility', Visibility::Public->value)
            ->where('publish_to_career_page', true)
            ->with(['department', 'skills', 'benefits'])
            ->orderByDesc('published_at');

        if ($request->has('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }
        if ($request->has('work_arrangement')) {
            $query->where('work_arrangement', $request->input('work_arrangement'));
        }
        if ($request->has('experience_level')) {
            $query->where('experience_level', $request->input('experience_level'));
        }
        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->input('city') . '%');
        }

        $posts = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data'     => JpJobPostPublicResource::collection($posts->items()),
            'meta'     => [
                'total'        => $posts->total(),
                'per_page'     => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

    // GET /api/careers/{org_slug}/jobs/{slug}
    public function show(string $orgSlug, string $slug): JsonResponse
    {
        $org = Organization::where('slug', $orgSlug)->firstOrFail();

        $post = JpJobPost::withoutTenant()
            ->where('organization_id', $org->id)
            ->where('slug', $slug)
            ->where('status', JobPostStatus::Published->value)
            ->where('publish_to_career_page', true)
            ->with(['department', 'skills', 'benefits'])
            ->firstOrFail();

        $this->statService->recordView($post, 'career_page');

        return response()->json(new JpJobPostPublicResource($post));
    }

    // GET /api/careers/{org_slug}/jobs/{slug}/questions
    public function questions(string $orgSlug, string $slug): JsonResponse
    {
        $org = Organization::where('slug', $orgSlug)->firstOrFail();

        $post = JpJobPost::withoutTenant()
            ->where('organization_id', $org->id)
            ->where('slug', $slug)
            ->where('status', JobPostStatus::Published->value)
            ->where('publish_to_career_page', true)
            ->firstOrFail();

        $questions = JpScreeningQuestion::where('job_post_id', $post->id)
            ->with('choices')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($q) => [
                'uuid'                => $q->uuid,
                'question_text'       => $q->question_text,
                'question_type'       => $q->question_type?->value,
                'is_required'         => $q->is_required,
                'is_disqualifying'    => $q->is_disqualifying,
                'placeholder'         => $q->placeholder,
                'max_length'          => $q->max_length,
                'choices'             => $q->choices->map(fn ($c) => [
                    'uuid'             => $c->uuid,
                    'choice_text'      => $c->choice_text,
                    'is_disqualifying' => $c->is_disqualifying,
                ]),
            ]);

        return response()->json(['data' => $questions]);
    }

    // POST /api/careers/{org_slug}/jobs/{slug}/apply
    public function apply(Request $request, string $orgSlug, string $slug): JsonResponse
    {
        $org = Organization::where('slug', $orgSlug)->firstOrFail();

        $post = JpJobPost::withoutTenant()
            ->where('organization_id', $org->id)
            ->where('slug', $slug)
            ->where('status', JobPostStatus::Published->value)
            ->where('allow_direct_apply', true)
            ->where('publish_to_career_page', true)
            ->firstOrFail();

        $request->validate([
            'full_name'    => 'required|string|max:200',
            'email'        => 'required|email|max:150',
            'phone'        => 'nullable|string|max:30',
            'resume_url'   => 'nullable|url|max:500',
            'portfolio_url'=> 'nullable|url|max:500',
            'cover_letter' => 'nullable|string|max:5000',
            'answers'      => 'nullable|array',
        ]);

        // Upsert candidate (org-scoped, keyed by email)
        $candidate = RcCandidate::firstOrCreate(
            ['org_id' => $org->id, 'email' => $request->input('email')],
            [
                'uuid'      => Str::uuid()->toString(),
                'full_name' => $request->input('full_name'),
                'phone'     => $request->input('phone'),
                'source'    => 'career_page',
            ]
        );

        // Update contact info if changed
        $candidate->fill(array_filter([
            'full_name'     => $request->input('full_name'),
            'phone'         => $request->input('phone'),
            'resume_url'    => $request->input('resume_url'),
            'portfolio_url' => $request->input('portfolio_url'),
        ]))->save();

        // Prevent duplicate application for same post
        $exists = RcApplication::where('jp_job_post_id', $post->uuid)
            ->where('candidate_id', $candidate->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Bạn đã ứng tuyển vị trí này rồi.'], 422);
        }

        // Check disqualifying answers
        $answers      = $request->input('answers', []);
        $disqualified = $this->checkDisqualifying($post, $answers);

        $application = RcApplication::create([
            'uuid'          => Str::uuid()->toString(),
            'jp_job_post_id'=> $post->uuid,
            'candidate_id'  => $candidate->id,
            'org_id'        => $org->id,
            'apply_source'  => 'career_page',
            'cover_letter'  => $request->input('cover_letter'),
            'answers'       => $answers ?: null,
            'disqualified'  => $disqualified,
        ]);

        // Increment denormalized counter and daily stat grain
        JpJobPost::where('id', $post->id)->increment('application_count');
        $this->statService->recordApply($post, 'career_page');

        return response()->json([
            'message'        => 'Đã gửi đơn ứng tuyển thành công.',
            'application_id' => $application->uuid,
        ], 201);
    }

    private function checkDisqualifying(JpJobPost $post, array $answers): bool
    {
        $questions = JpScreeningQuestion::where('job_post_id', $post->id)
            ->where('is_disqualifying', true)
            ->with('choices')
            ->get();

        foreach ($questions as $question) {
            $answer = $answers[$question->uuid] ?? null;
            if ($answer === null) {
                continue;
            }

            if ($question->disqualify_if_answer !== null && strtolower((string) $answer) === strtolower($question->disqualify_if_answer)) {
                return true;
            }

            // Check disqualifying choices
            foreach ($question->choices as $choice) {
                if ($choice->is_disqualifying) {
                    $selected = is_array($answer) ? $answer : [$answer];
                    if (in_array($choice->uuid, $selected)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
