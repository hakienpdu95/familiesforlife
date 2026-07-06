<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\BlueprintVersionDiff;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * So sánh 2 version theo từng nhóm outcomes/capabilities/workflows/checklists,
 * khớp theo `code` (định danh ổn định xuyên suốt Clone) — Chương 6 A04.3.
 */
class CompareBlueprintVersionsAction
{
    use AsAction;

    public function handle(BlueprintVersion $from, BlueprintVersion $to): BlueprintVersionDiff
    {
        $from->loadMissing(['outcomes', 'capabilities.workflows.phases.checklists']);
        $to->loadMissing(['outcomes', 'capabilities.workflows.phases.checklists']);

        $added = [];
        $removed = [];
        $changed = [];

        foreach ([
            'outcomes'     => [$from->outcomes, $to->outcomes],
            'capabilities' => [$from->capabilities, $to->capabilities],
            'workflows'    => [$from->capabilities->flatMap->workflows, $to->capabilities->flatMap->workflows],
            'checklists'   => [
                $from->capabilities->flatMap->workflows->flatMap->phases->flatMap->checklists,
                $to->capabilities->flatMap->workflows->flatMap->phases->flatMap->checklists,
            ],
        ] as $group => [$fromItems, $toItems]) {
            [$added[$group], $removed[$group], $changed[$group]] = $this->diffByCode($fromItems, $toItems);
        }

        return new BlueprintVersionDiff(added: $added, removed: $removed, changed: $changed);
    }

    /** @return array{0: array, 1: array, 2: array} [added, removed, changed] */
    private function diffByCode(Collection $fromItems, Collection $toItems): array
    {
        // toBase() — quan trọng: Eloquent\Collection::except() loại trừ theo model key
        // (id), không phải theo key của keyBy(); phải hạ về Support\Collection thường
        // để except() so khớp đúng theo "code".
        $fromByCode = $fromItems->keyBy('code')->toBase();
        $toByCode   = $toItems->keyBy('code')->toBase();

        $added = $toByCode->except($fromByCode->keys()->all())
            ->map(fn ($item) => ['code' => $item->code, 'name' => $item->name])
            ->values()->all();

        $removed = $fromByCode->except($toByCode->keys()->all())
            ->map(fn ($item) => ['code' => $item->code, 'name' => $item->name])
            ->values()->all();

        $changed = [];
        foreach ($fromByCode->keys()->intersect($toByCode->keys()) as $code) {
            $before = $fromByCode[$code];
            $after  = $toByCode[$code];

            if ($before->name !== $after->name || ($before->description ?? null) !== ($after->description ?? null)) {
                $changed[] = [
                    'code'   => $code,
                    'before' => ['name' => $before->name, 'description' => $before->description ?? null],
                    'after'  => ['name' => $after->name, 'description' => $after->description ?? null],
                ];
            }
        }

        return [$added, $removed, $changed];
    }
}
