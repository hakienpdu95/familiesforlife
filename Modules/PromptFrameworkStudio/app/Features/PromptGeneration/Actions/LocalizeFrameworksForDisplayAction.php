<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

class LocalizeFrameworksForDisplayAction
{
    use AsAction;

    public function handle(?array $frameworks = null): array
    {
        $frameworks ??= config('prompt_framework_studio.frameworks', []);

        return array_map(fn (array $framework) => $this->localize($framework), $frameworks);
    }

    public function localize(array $framework): array
    {
        $framework['ui'] = [
            'name' => __($framework['name'] ?? ''),
            'description' => __($framework['description'] ?? ''),
            'best_for' => __($framework['best_for'] ?? ''),
            'example' => array_map(fn ($value) => is_string($value) ? __($value) : $value, $framework['example'] ?? []),
        ];

        $framework['fields'] = array_map(function (array $field) {
            $field['ui'] = [
                'label' => __($field['label'] ?? ''),
                'hint' => isset($field['hint']) ? __($field['hint']) : null,
                'tip' => isset($field['tip']) ? __($field['tip']) : null,
                'options' => array_map(fn (string $label) => __($label), $field['options'] ?? []),
            ];

            return $field;
        }, $framework['fields'] ?? []);

        return $framework;
    }
}
