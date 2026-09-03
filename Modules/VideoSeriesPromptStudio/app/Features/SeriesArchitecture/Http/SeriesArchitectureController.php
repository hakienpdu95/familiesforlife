<?php

namespace Modules\VideoSeriesPromptStudio\Features\SeriesArchitecture\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ContentFoundation\Actions\ListCategoryFoundationsAction;
use Modules\Post\Models\PostCategory;
use Modules\VideoSeriesPromptStudio\Features\SeriesArchitecture\Actions\CreateVideoSeriesPromptAction;
use Modules\VideoSeriesPromptStudio\Models\VideoSeriesPrompt;

/**
 * KHÔNG gọi AI Provider trong app — cùng nguyên tắc
 * Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\PromptGenerationController: chỉ
 * ghép prompt + lưu lại, người dùng tự copy sang ChatGPT/Claude.
 */
class SeriesArchitectureController extends Controller
{
    public function index(): View
    {
        $promptList = VideoSeriesPrompt::query()
            ->with('category')
            ->latest()
            ->paginate(20);

        return view('videoseriespromptstudio::index', [
            'promptList' => $promptList,
        ]);
    }

    public function create(ListCategoryFoundationsAction $listCategoryFoundations): View
    {
        return view('videoseriespromptstudio::create', [
            'categoryFoundations' => $listCategoryFoundations->handle(withFoundationDetails: false),
        ]);
    }

    public function store(Request $request, CreateVideoSeriesPromptAction $createPrompt): RedirectResponse
    {
        $minEpisodes = (int) config('video_series_prompt_studio.content_arc.min_episode_count', 5);
        $maxEpisodes = (int) config('video_series_prompt_studio.content_arc.max_episode_count', 10);

        $platformKeys = array_keys(config('video_series_prompt_studio.platform.options', []));

        $data = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'series_topic' => ['required', 'string', 'max:255'],
            'pov' => ['nullable', 'string', 'max:500'],
            'business_goal' => ['nullable', 'string', 'max:2000'],
            'episode_count' => ['required', 'integer', "min:{$minEpisodes}", "max:{$maxEpisodes}"],
            'platform' => ['required', 'string', 'in:'.implode(',', $platformKeys)],
            'post_category_uuid' => ['nullable', 'string', 'uuid', 'exists:post_categories,uuid'],
        ]);

        $categoryId = isset($data['post_category_uuid'])
            ? PostCategory::where('uuid', $data['post_category_uuid'])->value('id')
            : null;

        $prompt = $createPrompt->handle(
            label: $data['label'],
            seriesTopic: $data['series_topic'],
            pov: $data['pov'] ?? null,
            businessGoal: $data['business_goal'] ?? null,
            episodeCount: $data['episode_count'],
            platform: $data['platform'],
            postCategoryId: $categoryId,
            createdBy: $request->user()->id,
        );

        return redirect()->route('backend.videoseriespromptstudio.show', $prompt)
            ->with('success', 'Đã sinh prompt — sao chép bên dưới để dùng.');
    }

    public function show(VideoSeriesPrompt $prompt): View
    {
        $prompt->load('category', 'createdBy');

        return view('videoseriespromptstudio::show', [
            'prompt' => $prompt,
        ]);
    }

    public function destroy(VideoSeriesPrompt $prompt): RedirectResponse
    {
        $prompt->delete();

        return redirect()->route('backend.videoseriespromptstudio.index')
            ->with('success', 'Đã xoá prompt.');
    }
}
