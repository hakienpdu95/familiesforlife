<?php

namespace Modules\Assessment\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\AssessmentConfigSnapshot;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\PassFailConfig;
use Modules\Assessment\Models\Persona;
use Modules\Assessment\Models\PainPointRule;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Assessment\Models\RoadmapPhase;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoreRule;

/**
 * Creates an immutable snapshot of the current scoring config for the given assessment_code.
 * Called inside the saveConfig transaction — must be called AFTER all config rows are committed.
 * Returns the new snapshot instance.
 */
class CreateConfigSnapshotAction
{
    use AsAction;

    public function handle(string $assessmentCode, ?string $createdBy = null, ?string $changeNote = null): AssessmentConfigSnapshot
    {
        $nextVersion = (AssessmentConfigSnapshot::where('assessment_code', $assessmentCode)->max('version') ?? 0) + 1;

        $assessment = Assessment::where('assessment_code', $assessmentCode)->first();
        $passFail   = PassFailConfig::where('assessment_code', $assessmentCode)->first();

        // 1. Header
        $snapshot = AssessmentConfigSnapshot::create([
            'assessment_code'     => $assessmentCode,
            'version'             => $nextVersion,
            'has_scoring'         => $assessment?->has_scoring ?? false,
            'aggregation_model'   => $assessment?->aggregation_model,
            'classification_type' => $assessment?->classification_type,
            'passing_score'       => $passFail?->passing_score,
            'label_pass'          => $passFail?->label_pass,
            'label_fail'          => $passFail?->label_fail,
            'created_by'          => $createdBy,
            'change_note'         => $changeNote,
        ]);

        $sid = $snapshot->id;

        // 2. Domains
        $domains = AssessmentDomain::where('assessment_code', $assessmentCode)->orderBy('sort_order')->get();
        if ($domains->isNotEmpty()) {
            DB::table('snapshot_domains')->insert(
                $domains->map(fn ($d) => [
                    'snapshot_id' => $sid,
                    'domain_code' => $d->domain_code,
                    'label'       => $d->label,
                    'weight'      => $d->weight,
                    'min_score'   => $d->min_score,
                    'max_score'   => $d->max_score,
                    'sort_order'  => $d->sort_order,
                ])->all()
            );
        }

        // 3. Bands
        $bands = ScoreBand::where('assessment_code', $assessmentCode)->orderBy('sort_order')->get();
        if ($bands->isNotEmpty()) {
            DB::table('snapshot_bands')->insert(
                $bands->map(fn ($b) => [
                    'snapshot_id' => $sid,
                    'band_code'   => $b->band_code,
                    'label'       => $b->label,
                    'description' => $b->description,
                    'min_score'   => $b->min_score,
                    'max_score'   => $b->max_score,
                    'sort_order'  => $b->sort_order,
                ])->all()
            );
        }

        // 4. Rules + options + ranges
        $rules = ScoreRule::where('assessment_code', $assessmentCode)
            ->with(['options' => fn ($q) => $q->orderBy('sort_order'), 'numericRanges' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        foreach ($rules as $rule) {
            $snapRuleId = DB::table('snapshot_rules')->insertGetId([
                'snapshot_id'           => $sid,
                'field_key'             => $rule->field_key,
                'domain_code'           => $rule->domain_code,
                'feature_code'          => $rule->feature_code,
                'signal_flag'           => $rule->signal_flag,
                'score_if_true'         => $rule->score_if_true,
                'score_if_false'        => $rule->score_if_false,
                'question_scoring_type' => $rule->question_scoring_type ?? $rule->condition_type ?? 'none',
                'condition_type'        => $rule->condition_type ?? $rule->question_scoring_type ?? 'none',
                'min_score_cap'         => $rule->min_score_cap,
                'max_score_cap'         => $rule->max_score_cap,
            ]);

            if ($rule->options->isNotEmpty()) {
                DB::table('snapshot_rule_options')->insert(
                    $rule->options->map(fn ($o, $i) => [
                        'snapshot_rule_id' => $snapRuleId,
                        'option_value'     => $o->option_value,
                        'option_label'     => $o->option_label,
                        'score'            => $o->score,
                        'signal_flag'      => $o->signal_flag,
                        'sort_order'       => $o->sort_order ?? ($i + 1),
                    ])->all()
                );
            }

            if ($rule->numericRanges->isNotEmpty()) {
                DB::table('snapshot_rule_ranges')->insert(
                    $rule->numericRanges->map(fn ($nr, $i) => [
                        'snapshot_rule_id' => $snapRuleId,
                        'min_value'        => $nr->min_value,
                        'max_value'        => $nr->max_value,
                        'score'            => $nr->score,
                        'signal_flag'      => $nr->signal_flag,
                        'sort_order'       => $nr->sort_order ?? ($i + 1),
                    ])->all()
                );
            }
        }

        // 5. Personas + conditions
        $personas = Persona::where('assessment_code', $assessmentCode)
            ->with(['conditions' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        foreach ($personas as $idx => $persona) {
            $snapPersonaId = DB::table('snapshot_personas')->insertGetId([
                'snapshot_id'  => $sid,
                'persona_code' => $persona->persona_code,
                'label'        => $persona->label,
                'description'  => $persona->description,
                'sort_order'   => $persona->sort_order ?? ($idx + 1),
            ]);

            if ($persona->conditions->isNotEmpty()) {
                DB::table('snapshot_persona_conditions')->insert(
                    $persona->conditions->map(fn ($c, $i) => [
                        'snapshot_persona_id' => $snapPersonaId,
                        'target_type'         => $c->target_type,
                        'target_code'         => $c->target_code,
                        'operator'            => $c->operator,
                        'threshold_value'     => $c->threshold_value,
                        'flag_value'          => isset($c->flag_value) ? (int) $c->flag_value : null,
                        'sort_order'          => $c->sort_order ?? ($i + 1),
                    ])->all()
                );
            }
        }

        // 6. Pain points
        $painPoints = PainPointRule::where('assessment_code', $assessmentCode)->get();
        if ($painPoints->isNotEmpty()) {
            DB::table('snapshot_pain_points')->insert(
                $painPoints->map(fn ($pp) => [
                    'snapshot_id'     => $sid,
                    'pain_point_code' => $pp->pain_point_code,
                    'label'           => $pp->label,
                    'required_flags'  => $pp->required_flags,
                ])->all()
            );
        }

        // 7. Recommendations
        $recs = RecommendationRule::where('assessment_code', $assessmentCode)->orderBy('priority')->get();
        if ($recs->isNotEmpty()) {
            DB::table('snapshot_recommendations')->insert(
                $recs->map(fn ($r) => [
                    'snapshot_id'         => $sid,
                    'recommendation_code' => $r->recommendation_code,
                    'label'               => $r->label,
                    'description'         => $r->description,
                    'trigger_domain'      => $r->trigger_domain,
                    'threshold_score'     => $r->threshold_score,
                    'priority'            => $r->priority,
                ])->all()
            );
        }

        // 8. Roadmap phases + milestones
        $phases = RoadmapPhase::where('assessment_code', $assessmentCode)
            ->with(['milestones' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        foreach ($phases as $phase) {
            $snapPhaseId = DB::table('snapshot_roadmap_phases')->insertGetId([
                'snapshot_id'    => $sid,
                'band_code'      => $phase->band_code,
                'phase_code'     => $phase->phase_code,
                'title'          => $phase->title,
                'description'    => $phase->description,
                'duration_weeks' => $phase->duration_weeks,
                'sort_order'     => $phase->sort_order,
            ]);

            if ($phase->milestones->isNotEmpty()) {
                DB::table('snapshot_roadmap_milestones')->insert(
                    $phase->milestones->map(fn ($m, $i) => [
                        'snapshot_phase_id' => $snapPhaseId,
                        'title'             => $m->title,
                        'sort_order'        => $m->sort_order ?? ($i + 1),
                    ])->all()
                );
            }
        }

        return $snapshot;
    }
}
