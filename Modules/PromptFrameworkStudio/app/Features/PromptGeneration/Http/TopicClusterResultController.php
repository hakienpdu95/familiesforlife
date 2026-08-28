<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\PushTopicClusterItemsToContentOutlinesAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\SaveTopicClusterAiResultAction;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;
use Modules\PromptFrameworkStudio\Models\TopicClusterResult;

/**
 * (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — màn hình "dán kết quả AI → duyệt
 * từng mục (checkbox) → đẩy sang ContentOutlines" cho prompt dùng framework `topiccluster`. CHỈ hợp
 * lệ với framework này (`abortUnlessTopicCluster()`) — các framework khác không có khái niệm
 * Pillar/Cluster để duyệt/đẩy.
 *
 * Route `push` gác THÊM permission `content_outlines.use` (ngoài `prompt_framework_studio.use` đã
 * gác ở group route cha) — hành động này TẠO bản ghi ở module khác, người chỉ có quyền dùng Prompt
 * Studio không mặc nhiên có quyền tạo Content Outline (xem routes/web.php).
 */
class TopicClusterResultController extends Controller
{
    public function show(GeneratedPrompt $prompt): View
    {
        $this->abortUnlessTopicCluster($prompt);

        return view('promptframeworkstudio::prompts.topic_cluster_result', [
            'prompt' => $prompt,
            'result' => TopicClusterResult::where('generated_prompt_id', $prompt->id)->first(),
        ]);
    }

    public function save(Request $request, GeneratedPrompt $prompt, SaveTopicClusterAiResultAction $save): RedirectResponse
    {
        $this->abortUnlessTopicCluster($prompt);

        $validated = $request->validate([
            'ai_result_raw' => ['required', 'string', 'max:100000'],
        ]);

        $result = $save->handle($prompt, $validated['ai_result_raw'], $request->user()->id);

        return redirect()->route('backend.promptstudio.prompts.topic-cluster-result.show', $prompt)
            ->with($result->structured['pillar'] === null
                ? ['error' => 'Không tìm thấy khối "PILLAR: ... | ..." trong nội dung đã dán — kiểm tra bạn đã dán ĐỦ phần AI trả về (kể cả khối mã ở cuối), hoặc yêu cầu AI trả lời lại đúng định dạng.']
                : ['success' => 'Đã phân tích kết quả — duyệt từng mục bên dưới trước khi đẩy sang Content Outlines.']);
    }

    public function push(Request $request, GeneratedPrompt $prompt, PushTopicClusterItemsToContentOutlinesAction $push): RedirectResponse
    {
        $this->abortUnlessTopicCluster($prompt);

        $validated = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['string'],
        ]);

        $result = TopicClusterResult::where('generated_prompt_id', $prompt->id)->firstOrFail();

        $push->handle($result, $validated['selected'], $request->user()->id);

        return redirect()->route('backend.promptstudio.prompts.topic-cluster-result.show', $prompt)
            ->with('success', 'Đã đẩy các mục đã chọn sang Content Outlines.');
    }

    private function abortUnlessTopicCluster(GeneratedPrompt $prompt): void
    {
        abort_unless($prompt->framework_key === 'topiccluster', 404);
    }
}
