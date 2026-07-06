<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostBenefit;

class JpJobPostBenefitApiController extends Controller
{
    public function index(JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('view', $jobPost);

        $benefits = $jobPost->benefits()->with('benefit:id,icon,category')->get();

        return response()->json($benefits->map(fn ($b) => [
            'id'           => $b->id,
            'benefit_id'   => $b->benefit_id,
            'benefit_name' => $b->benefit_name,
            'description'  => $b->description,
            'sort_order'   => $b->sort_order,
            'icon'         => $b->benefit?->icon,
            'category'     => $b->benefit?->category,
        ]));
    }

    public function store(Request $request, JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('update', $jobPost);

        $data = $request->validate([
            'benefit_name' => 'required|string|max:150',
            'benefit_id'   => 'nullable|integer|exists:jp_benefit_masters,id',
            'description'  => 'nullable|string|max:300',
        ]);

        $data['sort_order'] = $jobPost->benefits()->max('sort_order') + 1;

        $benefit = $jobPost->benefits()->create($data);
        $benefit->load('benefit:id,icon,category');

        return response()->json([
            'id'           => $benefit->id,
            'benefit_id'   => $benefit->benefit_id,
            'benefit_name' => $benefit->benefit_name,
            'description'  => $benefit->description,
            'sort_order'   => $benefit->sort_order,
            'icon'         => $benefit->benefit?->icon,
            'category'     => $benefit->benefit?->category,
        ], 201);
    }

    public function destroy(JpJobPost $jobPost, JpJobPostBenefit $benefit): JsonResponse
    {
        $this->authorize('update', $jobPost);
        abort_if($benefit->job_post_id !== $jobPost->id, 404);

        $benefit->delete();

        return response()->json(['message' => 'Đã xóa phúc lợi.']);
    }
}
