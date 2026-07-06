<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

class IncrementSemVerAction
{
    use AsAction;

    /** @param 'major'|'minor'|'patch' $level */
    public function handle(string $version, string $level = 'minor'): string
    {
        [$major, $minor, $patch] = array_pad(array_map('intval', explode('.', $version)), 3, 0);

        return match ($level) {
            'major' => ($major + 1) . '.0.0',
            'patch' => "{$major}.{$minor}." . ($patch + 1),
            default => "{$major}." . ($minor + 1) . '.0',
        };
    }
}
