<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpScreeningChoice;
use Modules\JobPosting\Models\JpScreeningQuestion;

class JpScreeningQuestionApiController extends Controller
{
    public function index(JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('view', $jobPost);

        $questions = $jobPost->screeningQuestions()->with('choices')->get();

        return response()->json($questions->map(fn ($q) => $this->formatQuestion($q)));
    }

    public function store(Request $request, JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('update', $jobPost);

        if ($jobPost->screeningQuestions()->count() >= 10) {
            return response()->json(['message' => 'Tối đa 10 câu hỏi mỗi tin tuyển dụng.'], 422);
        }

        $data = $request->validate([
            'question_text'       => 'required|string|max:500',
            'question_type'       => 'required|in:yes_no,short_text,long_text,number,single_choice,multiple_choice,file_upload',
            'is_required'         => 'boolean',
            'is_disqualifying'    => 'boolean',
            'disqualify_if_answer'=> 'nullable|string|max:100',
            'placeholder'         => 'nullable|string|max:200',
            'max_length'          => 'nullable|integer|min:1|max:10000',
            'choices'             => 'nullable|array|max:10',
            'choices.*.choice_text'    => 'required_with:choices|string|max:200',
            'choices.*.is_disqualifying' => 'boolean',
        ]);

        $data['sort_order'] = $jobPost->screeningQuestions()->max('sort_order') + 1;

        $question = $jobPost->screeningQuestions()->create(
            collect($data)->except('choices')->all()
        );

        if (!empty($data['choices'])) {
            foreach ($data['choices'] as $i => $choice) {
                $question->choices()->create([
                    'choice_text'      => $choice['choice_text'],
                    'is_disqualifying' => $choice['is_disqualifying'] ?? false,
                    'sort_order'       => $i,
                ]);
            }
        }

        $question->load('choices');

        return response()->json($this->formatQuestion($question), 201);
    }

    public function update(Request $request, JpJobPost $jobPost, JpScreeningQuestion $question): JsonResponse
    {
        $this->authorize('update', $jobPost);
        abort_if($question->job_post_id !== $jobPost->id, 404);

        $data = $request->validate([
            'question_text'       => 'required|string|max:500',
            'question_type'       => 'required|in:yes_no,short_text,long_text,number,single_choice,multiple_choice,file_upload',
            'is_required'         => 'boolean',
            'is_disqualifying'    => 'boolean',
            'disqualify_if_answer'=> 'nullable|string|max:100',
            'placeholder'         => 'nullable|string|max:200',
            'max_length'          => 'nullable|integer|min:1|max:10000',
            'choices'             => 'nullable|array|max:10',
            'choices.*.id'             => 'nullable|integer',
            'choices.*.choice_text'    => 'required_with:choices|string|max:200',
            'choices.*.is_disqualifying' => 'boolean',
        ]);

        $question->update(collect($data)->except('choices')->all());

        if (isset($data['choices'])) {
            $keepIds = collect($data['choices'])->pluck('id')->filter()->all();
            $question->choices()->whereNotIn('id', $keepIds)->delete();

            foreach ($data['choices'] as $i => $choice) {
                if (!empty($choice['id'])) {
                    JpScreeningChoice::where('id', $choice['id'])->update([
                        'choice_text'      => $choice['choice_text'],
                        'is_disqualifying' => $choice['is_disqualifying'] ?? false,
                        'sort_order'       => $i,
                    ]);
                } else {
                    $question->choices()->create([
                        'choice_text'      => $choice['choice_text'],
                        'is_disqualifying' => $choice['is_disqualifying'] ?? false,
                        'sort_order'       => $i,
                    ]);
                }
            }
        }

        $question->load('choices');

        return response()->json($this->formatQuestion($question));
    }

    public function destroy(JpJobPost $jobPost, JpScreeningQuestion $question): JsonResponse
    {
        $this->authorize('update', $jobPost);
        abort_if($question->job_post_id !== $jobPost->id, 404);

        $question->delete();

        return response()->json(['message' => 'Đã xóa câu hỏi.']);
    }

    public function reorder(Request $request, JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('update', $jobPost);

        $data = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);

        foreach ($data['ids'] as $i => $id) {
            JpScreeningQuestion::where('id', $id)->where('job_post_id', $jobPost->id)->update(['sort_order' => $i]);
        }

        return response()->json(['message' => 'Đã sắp xếp lại câu hỏi.']);
    }

    private function formatQuestion(JpScreeningQuestion $q): array
    {
        return [
            'id'                  => $q->id,
            'question_text'       => $q->question_text,
            'question_type'       => $q->question_type->value,
            'question_type_label' => $q->question_type->label(),
            'has_choices'         => $q->question_type->hasChoices(),
            'is_required'         => $q->is_required,
            'is_disqualifying'    => $q->is_disqualifying,
            'disqualify_if_answer'=> $q->disqualify_if_answer,
            'placeholder'         => $q->placeholder,
            'max_length'          => $q->max_length,
            'sort_order'          => $q->sort_order,
            'choices'             => $q->choices->map(fn ($c) => [
                'id'               => $c->id,
                'choice_text'      => $c->choice_text,
                'is_disqualifying' => $c->is_disqualifying,
                'sort_order'       => $c->sort_order,
            ])->all(),
        ];
    }
}
