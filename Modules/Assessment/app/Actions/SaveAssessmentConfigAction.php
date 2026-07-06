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

class SaveAssessmentConfigAction
{
    use AsAction;

    public function handle(
        Assessment $assessment,
        array $data,
        ?string $createdBy = null,
        ?string $changeNote = null,
    ): AssessmentConfigSnapshot {
        $code = $assessment->assessment_code;

        DB::transaction(function () use ($assessment, $code, $data) {

            // 1 — Assessment hub
            $assessment->update([
                'has_scoring'         => $data['assessment']['has_scoring'],
                'aggregation_model'   => $data['assessment']['aggregation_model'],
                'classification_type' => $data['assessment']['classification_type'],
            ]);
            $assessment->increment('version');

            // 2 — Domains
            AssessmentDomain::where('assessment_code', $code)->delete();
            foreach ($data['domains'] ?? [] as $idx => $d) {
                AssessmentDomain::create([
                    'assessment_code' => $code,
                    'domain_code'     => $d['domain_code'],
                    'label'           => $d['label'] ?? $d['domain_code'],
                    'weight'          => (float) $d['weight'],
                    'min_score'       => (int) $d['min_score'],
                    'max_score'       => (int) $d['max_score'],
                    'sort_order'      => $idx + 1,
                    'is_active'       => true,
                ]);
            }

            // 3 — Score rules
            $incomingKeys = collect($data['rules'] ?? [])->pluck('field_key')->all();
            ScoreRule::where('assessment_code', $code)
                ->whereNotIn('field_key', $incomingKeys)
                ->delete();

            foreach ($data['rules'] ?? [] as $r) {
                $type = $r['question_scoring_type'] ?? 'none';
                $rule = ScoreRule::updateOrCreate(
                    ['assessment_code' => $code, 'field_key' => $r['field_key']],
                    [
                        'feature_code'          => ($r['feature_code'] ?? '') ?: $r['field_key'],
                        'domain_code'           => $r['domain_code'] ?? null,
                        'signal_flag'           => $r['signal_flag'] ?? null,
                        'score_if_true'         => (int) ($r['score_if_true'] ?? 0),
                        'score_if_false'        => (int) ($r['score_if_false'] ?? 0),
                        'question_scoring_type' => $type,
                        'condition_type'        => $type,
                        'min_score_cap'         => isset($r['min_score_cap']) && $r['min_score_cap'] !== '' ? (int) $r['min_score_cap'] : null,
                        'max_score_cap'         => isset($r['max_score_cap']) && $r['max_score_cap'] !== '' ? (int) $r['max_score_cap'] : null,
                        'is_active'             => true,
                    ]
                );

                $rule->options()->delete();
                foreach ($r['options'] ?? [] as $oIdx => $o) {
                    ScoreRuleOption::create([
                        'rule_id'      => $rule->id,
                        'option_value' => $o['option_value'],
                        // option_label là NOT NULL với default('') ở DB — ?? null trước đây ghi
                        // thẳng NULL vào INSERT (đè default), vẫn vỡ constraint. '' mới đúng ý default.
                        'option_label' => $o['option_label'] ?? '',
                        'score'        => (int) ($o['score'] ?? 0),
                        'signal_flag'  => $o['signal_flag'] ?? null,
                        'sort_order'   => $oIdx + 1,
                    ]);
                }

                $rule->numericRanges()->delete();
                foreach ($r['ranges'] ?? [] as $rIdx => $nr) {
                    ScoreRuleNumericRange::create([
                        'rule_id'     => $rule->id,
                        'min_value'   => ($nr['min_value'] !== '' && $nr['min_value'] !== null) ? (float) $nr['min_value'] : null,
                        'max_value'   => ($nr['max_value'] !== '' && $nr['max_value'] !== null) ? (float) $nr['max_value'] : null,
                        'score'       => (int) ($nr['score'] ?? 0),
                        'signal_flag' => $nr['signal_flag'] ?? null,
                        'sort_order'  => $rIdx + 1,
                    ]);
                }
            }

            // 4 — Score bands
            ScoreBand::where('assessment_code', $code)->delete();
            foreach ($data['bands'] ?? [] as $idx => $b) {
                $min = (float) $b['min_score'];
                $max = (float) $b['max_score'];
                ScoreBand::create([
                    'assessment_code' => $code,
                    'band_code'       => $b['band_code'],
                    'label'           => $b['label'],
                    'description'     => $b['description'] ?? null,
                    'min_score'       => $min,
                    'max_score'       => $max,
                    'default_min'     => $min,
                    'default_max'     => $max,
                    'sort_order'      => $idx + 1,
                ]);
            }

            // 5 — Pass-fail config
            if (!empty($data['pass_fail'])) {
                PassFailConfig::updateOrCreate(
                    ['assessment_code' => $code],
                    [
                        'passing_score' => (float) ($data['pass_fail']['passing_score'] ?? 70),
                        'label_pass'    => $data['pass_fail']['label_pass'] ?? 'Đạt',
                        'label_fail'    => $data['pass_fail']['label_fail'] ?? 'Không đạt',
                    ]
                );
            }

            // 6 — Personas + conditions
            foreach (Persona::where('assessment_code', $code)->get() as $p) {
                $p->conditions()->delete();
                $p->delete();
            }
            foreach ($data['personas'] ?? [] as $idx => $p) {
                $persona = Persona::create([
                    'assessment_code' => $code,
                    'persona_code'    => $p['persona_code'],
                    'label'           => $p['label'],
                    'description'     => $p['description'] ?? null,
                    'sort_order'      => $idx + 1,
                ]);
                foreach ($p['conditions'] ?? [] as $cIdx => $c) {
                    PersonaCondition::create([
                        'persona_id'      => $persona->id,
                        'target_type'     => $c['target_type'],
                        'target_code'     => $c['target_code'] ?? null,
                        'operator'        => $c['operator'],
                        'threshold_value' => ($c['threshold_value'] !== '' && $c['threshold_value'] !== null) ? (float) $c['threshold_value'] : null,
                        'flag_value'      => isset($c['flag_value']) ? (bool) $c['flag_value'] : null,
                        'sort_order'      => $cIdx + 1,
                    ]);
                }
            }

            // 7 — Pain point rules
            PainPointRule::where('assessment_code', $code)->delete();
            foreach ($data['pain_points'] ?? [] as $pp) {
                PainPointRule::create([
                    'assessment_code' => $code,
                    'pain_point_code' => $pp['pain_point_code'],
                    'label'           => $pp['label'] ?? null,
                    'required_flags'  => $pp['required_flags'],
                    'is_active'       => true,
                ]);
            }

            // 8 — Recommendation rules
            RecommendationRule::where('assessment_code', $code)->delete();
            foreach ($data['recommendations'] ?? [] as $idx => $rec) {
                RecommendationRule::create([
                    'assessment_code'     => $code,
                    'recommendation_code' => $rec['recommendation_code'],
                    'label'               => $rec['label'] ?? null,
                    'description'         => $rec['description'] ?? null,
                    'trigger_domain'      => $rec['trigger_domain'],
                    'threshold_score'     => (float) ($rec['threshold_score'] ?? 50),
                    'priority'            => $idx + 1,
                    'is_active'           => true,
                ]);
            }

            // 9 — Roadmap phases + milestones
            foreach (RoadmapPhase::where('assessment_code', $code)->get() as $phase) {
                $phase->milestones()->delete();
                $phase->delete();
            }
            foreach ($data['roadmap'] ?? [] as $bandCode => $phases) {
                foreach ($phases as $idx => $ph) {
                    $phase = RoadmapPhase::create([
                        'assessment_code' => $code,
                        'band_code'       => $bandCode,
                        'maturity_level'  => $bandCode,
                        'phase_code'      => $ph['phase_code'],
                        'title'           => $ph['title'],
                        'description'     => $ph['description'] ?? null,
                        'duration_weeks'  => ($ph['duration_weeks'] ?? '') !== '' ? (int) $ph['duration_weeks'] : null,
                        'sort_order'      => $idx + 1,
                    ]);
                    foreach ($ph['milestones'] ?? [] as $mIdx => $milestone) {
                        RoadmapMilestone::create([
                            'phase_id'   => $phase->id,
                            'title'      => is_array($milestone) ? ($milestone['title'] ?? '') : $milestone,
                            'sort_order' => $mIdx + 1,
                        ]);
                    }
                }
            }
        });

        return app(CreateConfigSnapshotAction::class)->handle($code, $createdBy, $changeNote);
    }
}
