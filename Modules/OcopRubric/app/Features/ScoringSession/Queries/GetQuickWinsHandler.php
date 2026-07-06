<?php

namespace Modules\OcopRubric\Features\ScoringSession\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopScoringSession;

class GetQuickWinsHandler implements QueryHandlerInterface
{
    /** @return array{criterion_label:string, current_points:float, best_points:float, gain:float}[] */
    public function handle(QueryInterface $query): array
    {
        /** @var GetQuickWinsQuery $query */
        $session = OcopScoringSession::with([
            'rubricVersion.sections.criteria' => fn ($q) => $q->where('is_scorable', true)->with('options'),
            'answers',
        ])->findOrFail($query->sessionId);

        $answered = $session->answers->keyBy('criterion_id');
        $wins = [];

        foreach ($session->rubricVersion->sections as $section) {
            foreach ($section->criteria as $criterion) {
                $current = (float) ($answered[$criterion->id]->points_awarded ?? 0);
                $best = (float) ($criterion->options->max('points') ?? 0);
                $gain = round($best - $current, 2);

                if ($gain > 0) {
                    $wins[] = [
                        'criterion_label' => $criterion->label,
                        'current_points'  => $current,
                        'best_points'     => $best,
                        'gain'            => $gain,
                    ];
                }
            }
        }

        usort($wins, fn ($a, $b) => $b['gain'] <=> $a['gain']);

        return array_slice($wins, 0, $query->limit ?? 5);
    }
}
