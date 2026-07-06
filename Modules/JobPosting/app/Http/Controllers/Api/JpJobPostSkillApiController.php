<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostSkill;

class JpJobPostSkillApiController extends Controller
{
    public function index(JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('view', $jobPost);

        $skills = $jobPost->skills()->with('skill:id,category')->get();

        return response()->json($skills->map(fn ($s) => [
            'id'                => $s->id,
            'skill_id'          => $s->skill_id,
            'skill_name'        => $s->skill_name,
            'skill_category'    => $s->skill?->category,
            'requirement_level' => $s->requirement_level->value,
            'requirement_label' => $s->requirement_level->label(),
            'proficiency'       => $s->proficiency?->value,
            'proficiency_label' => $s->proficiency?->label(),
            'min_years'         => $s->min_years,
            'sort_order'        => $s->sort_order,
        ]));
    }

    public function store(Request $request, JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('update', $jobPost);

        $data = $request->validate([
            'skill_name'        => 'required|string|max:100',
            'skill_id'          => 'nullable|integer|exists:jp_skill_masters,id',
            'requirement_level' => 'required|in:required,preferred,nice_to_have',
            'proficiency'       => 'nullable|in:beginner,intermediate,advanced,expert',
            'min_years'         => 'nullable|integer|min:0|max:50',
        ]);

        if ($jobPost->skills()->count() >= 20) {
            return response()->json(['message' => 'Tối đa 20 kỹ năng mỗi tin tuyển dụng.'], 422);
        }

        $data['sort_order'] = $jobPost->skills()->max('sort_order') + 1;

        $skill = $jobPost->skills()->create($data);

        return response()->json([
            'id'                => $skill->id,
            'skill_id'          => $skill->skill_id,
            'skill_name'        => $skill->skill_name,
            'requirement_level' => $skill->requirement_level->value,
            'requirement_label' => $skill->requirement_level->label(),
            'proficiency'       => $skill->proficiency?->value,
            'proficiency_label' => $skill->proficiency?->label(),
            'min_years'         => $skill->min_years,
            'sort_order'        => $skill->sort_order,
        ], 201);
    }

    public function update(Request $request, JpJobPost $jobPost, JpJobPostSkill $skill): JsonResponse
    {
        $this->authorize('update', $jobPost);
        abort_if($skill->job_post_id !== $jobPost->id, 404);

        $data = $request->validate([
            'skill_name'        => 'required|string|max:100',
            'skill_id'          => 'nullable|integer|exists:jp_skill_masters,id',
            'requirement_level' => 'required|in:required,preferred,nice_to_have',
            'proficiency'       => 'nullable|in:beginner,intermediate,advanced,expert',
            'min_years'         => 'nullable|integer|min:0|max:50',
        ]);

        $skill->update($data);

        return response()->json([
            'id'                => $skill->id,
            'skill_id'          => $skill->skill_id,
            'skill_name'        => $skill->skill_name,
            'requirement_level' => $skill->requirement_level->value,
            'requirement_label' => $skill->requirement_level->label(),
            'proficiency'       => $skill->proficiency?->value,
            'proficiency_label' => $skill->proficiency?->label(),
            'min_years'         => $skill->min_years,
            'sort_order'        => $skill->sort_order,
        ]);
    }

    public function destroy(JpJobPost $jobPost, JpJobPostSkill $skill): JsonResponse
    {
        $this->authorize('update', $jobPost);
        abort_if($skill->job_post_id !== $jobPost->id, 404);

        $skill->delete();

        return response()->json(['message' => 'Đã xóa kỹ năng.']);
    }
}
