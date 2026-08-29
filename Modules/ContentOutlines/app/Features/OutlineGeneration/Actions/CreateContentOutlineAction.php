<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentOutlines\Features\Concerns\ResolvesCategoryContext;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;
use Modules\ContentOutlines\Models\ContentOutline;

class CreateContentOutlineAction
{
    use AsAction;
    use ResolvesCategoryContext;

    public function __construct(private readonly BuildContentOutlinePromptAction $buildPrompt) {}

    public function handle(ContentOutlineInputData $input, int $createdBy): ContentOutline
    {
        $foundation = $this->resolveFoundation($input->post_category_id);
        $existingArticleTitles = $this->resolveExistingArticleTitles($input->post_category_id);
        $prompt = $this->buildPrompt->handle($input, $foundation, $existingArticleTitles);

        return ContentOutline::create([
            'label' => $input->label ?: $input->topic,
            'topic' => $input->topic,
            'target_keyword' => $input->target_keyword,
            'secondary_keywords' => $input->secondary_keywords,
            'search_intent' => $input->search_intent,
            'post_category_id' => $input->post_category_id,
            'target_audience' => $input->target_audience,
            'job_to_be_done' => $input->job_to_be_done,
            'reader_emotional_state' => $input->reader_emotional_state,
            'content_goal' => $input->content_goal,
            'cta_url' => $input->cta_url,
            'tone_style' => $input->tone_style,
            'competitor_urls' => $input->competitor_urls,
            'desired_word_count' => $input->desired_word_count,
            'language' => $input->language,
            'outline_depth' => $input->outline_depth,
            'content_role' => $input->content_role,
            'additional_notes' => $input->additional_notes,
            'generated_prompt' => $prompt,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
