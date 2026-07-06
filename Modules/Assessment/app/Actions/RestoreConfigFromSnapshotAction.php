<?php

namespace Modules\Assessment\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\AssessmentConfigSnapshot;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\PassFailConfig;
use Modules\Assessment\Models\Persona;
use Modules\Assessment\Models\PersonaCondition;
use Modules\Assessment\Models\PainPointRule;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Assessment\Models\RoadmapMilestone;
use Modules\Assessment\Models\RoadmapPhase;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoreRule;
use Modules\Assessment\Models\ScoreRuleNumericRange;
use Modules\Assessment\Models\ScoreRuleOption;

/**
 * Restores a scoring config to the state captured in a given snapshot version.
 * Runs in a single transaction. After restore, creates a new snapshot so the
 * rollback itself is versioned and auditable.
 */
class RestoreConfigFromSnapshotAction
{
    use AsAction;

    public function __construct(
        private readonly CreateConfigSnapshotAction $createSnapshot,
    ) {}

    public function handle(AssessmentConfigSnapshot $snapshot, ?string $restoredBy = null): AssessmentConfigSnapshot
    {
        $code = $snapshot->assessment_code;

        // Eager-load all snapshot relations before the transaction
        $snapshot->loadMissing([
            'domains',
            'bands',
            'rules.options',
            'rules.ranges',
            'personas.conditions',
            'painPoints',
            'recommendations',
            'roadmapPhases.milestones',
        ]);

        DB::transaction(function () use ($snapshot, $code, $restoredBy) {
            // 1 — Assessment settings
            Assessment::updateOrCreate(
                ['assessment_code' => $code],
                [
                    'has_scoring'         => $snapshot->has_scoring,
                    'aggregation_model'   => $snapshot->aggregation_model,
                    'classification_type' => $snapshot->classification_type,
                    'is_active'           => true,
                ]
            );

            // 2 — Pass-fail
            if ($snapshot->passing_score !== null) {
                PassFailConfig::updateOrCreate(
                    ['assessment_code' => $code],
                    [
                        'passing_score' => $snapshot->passing_score,
                        'label_pass'    => $snapshot->label_pass ?? 'Đạt',
                        'label_fail'    => $snapshot->label_fail ?? 'Không đạt',
                    ]
                );
            }

            // 3 — Domains
            AssessmentDomain::where('assessment_code', $code)->delete();
            foreach ($snapshot->domains as $d) {
                AssessmentDomain::create([
                    'assessment_code' => $code,
                    'domain_code'     => $d->domain_code,
                    'label'           => $d->label,
                    'weight'          => $d->weight,
                    'min_score'       => $d->min_score,
                    'max_score'       => $d->max_score,
                    'sort_order'      => $d->sort_order,
                    'is_active'       => true,
                ]);
            }

            // 4 — Bands
            ScoreBand::where('assessment_code', $code)->delete();
            foreach ($snapshot->bands as $b) {
                ScoreBand::create([
                    'assessment_code' => $code,
                    'band_code'       => $b->band_code,
                    'label'           => $b->label,
                    'description'     => $b->description,
                    'min_score'       => $b->min_score,
                    'max_score'       => $b->max_score,
                    'default_min'     => $b->min_score,
                    'default_max'     => $b->max_score,
                    'sort_order'      => $b->sort_order,
                ]);
            }

            // 5 — Rules + options + ranges
            ScoreRule::where('assessment_code', $code)->each(function ($rule) {
                $rule->options()->delete();
                $rule->numericRanges()->delete();
                $rule->delete();
            });

            foreach ($snapshot->rules as $sr) {
                $rule = ScoreRule::create([
                    'assessment_code'       => $code,
                    'field_key'             => $sr->field_key,
                    'domain_code'           => $sr->domain_code,
                    'feature_code'          => $sr->feature_code ?? $sr->field_key,
                    'signal_flag'           => $sr->signal_flag,
                    'score_if_true'         => $sr->score_if_true,
                    'score_if_false'        => $sr->score_if_false,
                    'question_scoring_type' => $sr->question_scoring_type,
                    'condition_type'        => $sr->condition_type,
                    'min_score_cap'         => $sr->min_score_cap,
                    'max_score_cap'         => $sr->max_score_cap,
                    'is_active'             => true,
                ]);

                foreach ($sr->options as $o) {
                    ScoreRuleOption::create([
                        'rule_id'      => $rule->id,
                        'option_value' => $o->option_value,
                        'option_label' => $o->option_label,
                        'score'        => $o->score,
                        'signal_flag'  => $o->signal_flag,
                        'sort_order'   => $o->sort_order,
                    ]);
                }

                foreach ($sr->ranges as $nr) {
                    ScoreRuleNumericRange::create([
                        'rule_id'     => $rule->id,
                        'min_value'   => $nr->min_value,
                        'max_value'   => $nr->max_value,
                        'score'       => $nr->score,
                        'signal_flag' => $nr->signal_flag,
                        'sort_order'  => $nr->sort_order,
                    ]);
                }
            }

            // 6 — Personas + conditions
            foreach (Persona::where('assessment_code', $code)->get() as $p) {
                $p->conditions()->delete();
                $p->delete();
            }
            foreach ($snapshot->personas as $sp) {
                $persona = Persona::create([
                    'assessment_code' => $code,
                    'persona_code'    => $sp->persona_code,
                    'label'           => $sp->label,
                    'description'     => $sp->description,
                    'sort_order'      => $sp->sort_order,
                ]);
                foreach ($sp->conditions as $sc) {
                    PersonaCondition::create([
                        'persona_id'      => $persona->id,
                        'target_type'     => $sc->target_type,
                        'target_code'     => $sc->target_code,
                        'operator'        => $sc->operator,
                        'threshold_value' => $sc->threshold_value,
                        'flag_value'      => $sc->flag_value !== null ? (bool) $sc->flag_value : null,
                        'sort_order'      => $sc->sort_order,
                    ]);
                }
            }

            // 7 — Pain points
            PainPointRule::where('assessment_code', $code)->delete();
            foreach ($snapshot->painPoints as $pp) {
                PainPointRule::create([
                    'assessment_code' => $code,
                    'pain_point_code' => $pp->pain_point_code,
                    'label'           => $pp->label,
                    'required_flags'  => $pp->required_flags,
                    'is_active'       => true,
                ]);
            }

            // 8 — Recommendations
            RecommendationRule::where('assessment_code', $code)->delete();
            foreach ($snapshot->recommendations as $r) {
                RecommendationRule::create([
                    'assessment_code'     => $code,
                    'recommendation_code' => $r->recommendation_code,
                    'label'               => $r->label,
                    'description'         => $r->description,
                    'trigger_domain'      => $r->trigger_domain,
                    'threshold_score'     => $r->threshold_score,
                    'priority'            => $r->priority,
                    'is_active'           => true,
                ]);
            }

            // 9 — Roadmap phases + milestones
            foreach (RoadmapPhase::where('assessment_code', $code)->get() as $phase) {
                $phase->milestones()->delete();
                $phase->delete();
            }
            foreach ($snapshot->roadmapPhases as $sp) {
                $phase = RoadmapPhase::create([
                    'assessment_code' => $code,
                    'band_code'       => $sp->band_code,
                    'maturity_level'  => $sp->band_code,
                    'phase_code'      => $sp->phase_code,
                    'title'           => $sp->title,
                    'description'     => $sp->description,
                    'duration_weeks'  => $sp->duration_weeks,
                    'sort_order'      => $sp->sort_order,
                ]);
                foreach ($sp->milestones as $sm) {
                    RoadmapMilestone::create([
                        'phase_id'   => $phase->id,
                        'title'      => $sm->title,
                        'sort_order' => $sm->sort_order,
                    ]);
                }
            }
        });

        // Create new snapshot after rollback — this version records the restore event
        return $this->createSnapshot->handle(
            $code,
            $restoredBy,
            "Rolled back to version {$snapshot->version}",
        );
    }
}
