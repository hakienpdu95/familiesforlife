<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Bus\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Assessment\Models\Assessment;
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

use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyResponse;
use Modules\Assessment\Actions\CreateConfigSnapshotAction;
use Modules\Assessment\Actions\RestoreConfigFromSnapshotAction;
use Modules\Assessment\Actions\SaveAssessmentConfigAction;
use Modules\Assessment\Jobs\RunAssessmentJob;
use Modules\Assessment\Models\AssessmentConfigSnapshot;
use Spatie\Activitylog\Models\Activity;

class AssessmentConfigController extends Controller
{

    // ── Trang chính wizard ────────────────────────────────────────────────────

    public function index(Assessment $assessment): View
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');

        return view('assessment::config.index', ['assessmentCode' => $code,
            
            'assessmentCode' => $code,
        ]);
    }

    // ── GET config ────────────────────────────────────────────────────────────

    public function getConfig(Assessment $assessment): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');



        $domains = AssessmentDomain::where('assessment_code', $code)
            ->orderBy('sort_order')->get();

        $rules = ScoreRule::where('assessment_code', $code)
            ->with(['options', 'numericRanges'])
            ->get();

        $bands          = ScoreBand::forAssessment($code)->ordered()->get();
        $passFailConfig = PassFailConfig::where('assessment_code', $code)->first();
        $personas       = Persona::forAssessment($code)->with('conditions')->get();
        $painPoints     = PainPointRule::where('assessment_code', $code)->get();
        $recommendations = RecommendationRule::where('assessment_code', $code)->orderBy('priority')->get();
        $roadmapRaw = RoadmapPhase::where('assessment_code', $code)
            ->with('milestones')
            ->orderBy('sort_order')
            ->get();

        // Group by band_code (fallback to maturity_level for old data)
        $roadmap = [];
        foreach ($roadmapRaw as $phase) {
            $key = $phase->band_code ?? $phase->maturity_level ?? '__unknown';
            $roadmap[$key][] = [
                'id'             => $phase->id,
                'phase_code'     => $phase->phase_code,
                'title'          => $phase->title,
                'description'    => $phase->description,
                'duration_weeks' => $phase->duration_weeks,
                'sort_order'     => $phase->sort_order,
                'milestones'     => $phase->milestones->map(fn ($m) => [
                    'id'         => $m->id,
                    'title'      => $m->title,
                    'sort_order' => $m->sort_order,
                ])->values(),
            ];
        }

        return response()->json([
            'assessment'     => $assessment ? [
                'has_scoring'         => $assessment->has_scoring,
                'aggregation_model'   => $assessment->aggregation_model,
                'classification_type' => $assessment->classification_type,
                'version'             => $assessment->version,
            ] : null,
            'domains'         => $domains->values(),
            'rules'           => $rules->map(fn ($r) => $this->serializeRule($r))->values(),
            'bands'           => $bands->values(),
            'pass_fail'       => $passFailConfig,
            'personas'        => $personas->map(fn ($p) => [
                'id'           => $p->id,
                'persona_code' => $p->persona_code,
                'label'        => $p->label,
                'description'  => $p->description,
                'sort_order'   => $p->sort_order,
                'conditions'   => $p->conditions->values(),
            ])->values(),
            'pain_points'     => $painPoints->values(),
            'recommendations' => $recommendations->values(),
            'roadmap'         => $roadmap,
        ]);
    }

    // ── PUT config (transaction) ──────────────────────────────────────────────

    public function saveConfig(Request $request, Assessment $assessment): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');

        $data = $request->validate([
            'assessment.has_scoring'         => 'required|boolean',
            'assessment.aggregation_model'   => 'required|in:flat_sum,weighted_domain,sectioned',
            'assessment.classification_type' => 'required|in:none,score_band,pass_fail,persona_match',
            'domains'                        => 'array|max:50',
            'domains.*.domain_code'          => 'required|string|max:60|regex:/^[a-z0-9_]+$/',
            'domains.*.label'                => 'nullable|string|max:120',
            'domains.*.weight'               => 'required|numeric|min:0|max:1',
            'domains.*.min_score'            => 'required|numeric',
            'domains.*.max_score'            => 'required|numeric',
            'rules'                          => 'array|max:200',
            'rules.*.field_key'              => 'required|string|max:100',
            // score_rules.domain_code là NOT NULL ở DB (bắt buộc với mọi aggregation model,
            // không riêng weighted_domain) — thiếu rule này trước đây cho phép domain_code
            // rỗng lọt xuống DB, vỡ constraint với lỗi SQL khó hiểu thay vì validation message.
            'rules.*.domain_code'            => 'required|string|max:50',
            'rules.*.question_scoring_type'  => 'required|in:none,boolean,single_choice,multi_choice,numeric_range',
            // score_rule_options.option_value là NOT NULL ở DB — chỉ áp dụng khi rule có options
            // (single_choice/multi_choice), nhưng validate luôn cho mọi entry có mặt thì an toàn
            // hơn (mảng rỗng/không gửi options thì rule này không chạy, không ảnh hưởng rule khác).
            'rules.*.options.*.option_value' => 'required|string|max:100',
            'bands'                          => 'array|max:20',
            'bands.*.band_code'              => 'required|string|max:60|regex:/^[A-Za-z0-9_]+$/',
            'bands.*.label'                  => 'required|string|max:120',
            'bands.*.min_score'              => 'required|numeric|min:0|max:100',
            'bands.*.max_score'              => 'required|numeric|min:0|max:100',
            'pass_fail'                      => 'nullable|array',
            'pass_fail.passing_score'        => 'nullable|numeric|min:0|max:100',
            // personas/persona_conditions — toàn bộ cột bên dưới là NOT NULL ở DB,
            // Action không có fallback nên thiếu field nào là crash SQL field đó.
            'personas'                            => 'array|max:20',
            'personas.*.persona_code'             => 'required|string|max:100',
            'personas.*.label'                    => 'required|string|max:255',
            'personas.*.conditions.*.target_type' => 'required|string|max:30',
            'personas.*.conditions.*.target_code' => 'required|string|max:100',
            'personas.*.conditions.*.operator'    => 'required|string|max:5',
            'pain_points'                    => 'array|max:50',
            'pain_points.*.pain_point_code'  => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            // pain_point_rules.label là NOT NULL ở DB — đây chính là cột gây lỗi SQL vừa rồi.
            'pain_points.*.label'            => 'required|string|max:255',
            'pain_points.*.required_flags'   => 'required|string|max:500',
            // recommendation_rules — recommendation_code/label/trigger_domain đều NOT NULL ở DB.
            'recommendations'                          => 'array|max:50',
            'recommendations.*.recommendation_code'    => 'required|string|max:100',
            'recommendations.*.label'                  => 'required|string|max:255',
            'recommendations.*.trigger_domain'         => 'required|string|max:50',
            // roadmap_phases.title là NOT NULL ở DB — roadmap key theo band_code (dynamic key)
            // nên dùng wildcard 2 cấp 'roadmap.*.*'.
            'roadmap'                        => 'array|max:20',
            'roadmap.*.*.phase_code'         => 'required|string|max:100',
            'roadmap.*.*.title'              => 'required|string|max:255',
        ]);

        // Domain uniqueness check
        $domainCodes = collect($data['domains'] ?? [])->pluck('domain_code');
        if ($domainCodes->count() !== $domainCodes->unique()->count()) {
            return response()->json(['message' => 'domain_code bị trùng lặp trong danh sách domains.'], 422);
        }

        // Band uniqueness check
        $bandCodes = collect($data['bands'] ?? [])->pluck('band_code');
        if ($bandCodes->count() !== $bandCodes->unique()->count()) {
            return response()->json(['message' => 'band_code bị trùng lặp trong danh sách bands.'], 422);
        }

        // Domain min < max check
        foreach ($data['domains'] ?? [] as $d) {
            if ((float) $d['min_score'] >= (float) $d['max_score']) {
                return response()->json(['message' => "Domain '{$d['domain_code']}': min_score phải nhỏ hơn max_score."], 422);
            }
        }

        $snapshot = SaveAssessmentConfigAction::run(
            $assessment,
            $data,
            auth()->user()?->email,
            $request->input('change_note'),
        );

        activity('scoring_config')
            ->performedOn($assessment)
            ->causedBy(auth()->user())
            ->event('config_saved')
            ->withProperties([
                'assessment_code'   => $code,
                'version'           => $snapshot->version,
                'change_note'       => $request->input('change_note'),
                'domains_count'     => count($data['domains'] ?? []),
                'rules_count'       => count($data['rules'] ?? []),
                'bands_count'       => count($data['bands'] ?? []),
                'personas_count'    => count($data['personas'] ?? []),
                'pain_points_count' => count($data['pain_points'] ?? []),
                'recs_count'        => count($data['recommendations'] ?? []),
            ])
            ->log('Đã lưu & kích hoạt scoring config');

        return response()->json([
            'success'          => true,
            'message'          => 'Scoring đã được lưu và kích hoạt.',
            'snapshot_version' => $snapshot->version,
        ]);
    }

    // ── POST rollback to snapshot version ────────────────────────────────────

    public function rollback(Request $request, Assessment $assessment, int $version): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');


        $snapshot = AssessmentConfigSnapshot::where('assessment_code', $code)
            ->where('version', $version)
            ->firstOrFail();

        $newSnapshot = app(RestoreConfigFromSnapshotAction::class)->handle(
            $snapshot,
            auth()->user()?->email,
        );

        activity('scoring_config')
            ->performedOn($survey)
            ->causedBy(auth()->user())
            ->event('config_rollback')
            ->withProperties([
                'assessment_code'  => $code,
                'restored_version' => $version,
                'new_version'      => $newSnapshot->version,
            ])
            ->log("Đã rollback scoring config về v{$version}");

        return response()->json([
            'success'          => true,
            'message'          => "Đã khôi phục config về version {$version}. Version hiện tại: {$newSnapshot->version}.",
            'restored_version' => $version,
            'new_version'      => $newSnapshot->version,
        ]);
    }

    // ── GET snapshot list ─────────────────────────────────────────────────────

    public function snapshots(Assessment $assessment): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');


        $snapshots = AssessmentConfigSnapshot::where('assessment_code', $code)
            ->orderBy('version', 'desc')
            ->get(['id', 'version', 'created_by', 'change_note', 'created_at']);

        return response()->json(['snapshots' => $snapshots]);
    }

    // ── POST reprocess all responses ──────────────────────────────────────────

    public function reprocessAll(Request $request, Assessment $assessment): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');

        if (! $code) {
            return response()->json(['message' => 'Assessment code không hợp lệ.'], 422);
        }

        // RunAssessmentJob nhận ScoringSubjectInterface (model đã hydrate), không phải id
        // — phải load model thật, không thể pluck('id') rồi truyền thẳng int vào job.
        $responses = SurveyResponse::whereHas("survey", fn ($q) => $q->where("assessment_code", $code))
            ->complete()
            ->get();

        if ($responses->isEmpty()) {
            return response()->json(['message' => 'Không có response nào để tính lại.', 'total' => 0]);
        }

        $jobs = $responses->map(fn ($response) => new RunAssessmentJob($response, force: true))->all();

        $batch = Bus::batch($jobs)
            ->name("reprocess-assessment-{$assessment->id}")
            ->onQueue('low')
            ->allowFailures()
            ->dispatch();

        activity('scoring_config')
            ->performedOn($assessment)
            ->causedBy(auth()->user())
            ->event('reprocess_all')
            ->withProperties([
                'total_responses' => $responses->count(),
                'batch_id'        => $batch->id,
            ])
            ->log("Đã tính lại {$responses->count()} responses");

        return response()->json([
            'batch_id' => $batch->id,
            'total'    => count($jobs),
            'message'  => count($jobs) . ' responses đã được đưa vào hàng đợi tính lại điểm.',
        ]);
    }

    // ── GET batch progress ─────────────────────────────────────────────────────

    public function getBatchStatus(Request $request, Assessment $assessment, string $batchId): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');

        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return response()->json(['message' => 'Batch không tìm thấy.'], 404);
        }

        return response()->json([
            'id'             => $batch->id,
            'name'           => $batch->name,
            'total'          => $batch->totalJobs,
            'processed'      => $batch->processedJobs(),
            'failed'         => $batch->failedJobs,
            'progress'       => $batch->progress(),
            'finished'       => $batch->finished(),
            'cancelled'      => $batch->cancelled(),
        ]);
    }

    // ── GET fields ────────────────────────────────────────────────────────────

    public function getFields(Assessment $assessment): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');



        $existingRules = ScoreRule::where('assessment_code', $code)
            ->with(['options', 'numericRanges'])
            ->get()
            ->keyBy('field_key');

        // Cross-module read: load SurveyFields cho assessment này qua survey join
        $fields = \Modules\Survey\Models\SurveyField::whereHas(
                'survey', fn ($q) => $q->where('assessment_code', $code)
            )
            ->with(['section', 'options'])
            ->ordered()
            ->get()
            ->map(function ($f) use ($existingRules) {
                $rule = $existingRules->get($f->field_key);
                return [
                    'id'              => $f->id,
                    'field_key'       => $f->field_key,
                    'label'           => $f->label,
                    'field_type'      => $f->field_type->value,
                    'field_type_label' => $f->field_type->label(),
                    'is_choice'       => $f->field_type->isChoice(),
                    'is_active'       => $f->is_active,
                    'section'         => $f->section?->title,
                    'field_options'   => $f->field_type->isChoice()
                        ? $f->options->map(fn ($o) => [
                            'value' => $o->option_value,
                            'label' => $o->label,
                        ])->values()
                        : [],
                    'rule'            => $rule ? $this->serializeRule($rule) : null,
                ];
            });

        return response()->json(['fields' => $fields]);
    }

    // ── GET flags ─────────────────────────────────────────────────────────────

    public function getFlags(Assessment $assessment): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');



        $ruleFlags = ScoreRule::where('assessment_code', $code)
            ->whereNotNull('signal_flag')
            ->pluck('signal_flag');

        $optionFlags = DB::table('score_rule_options')
            ->join('score_rules', 'score_rules.id', '=', 'score_rule_options.rule_id')
            ->where('score_rules.assessment_code', $code)
            ->whereNotNull('score_rule_options.signal_flag')
            ->pluck('score_rule_options.signal_flag');

        $rangeFlags = DB::table('score_rule_numeric_ranges')
            ->join('score_rules', 'score_rules.id', '=', 'score_rule_numeric_ranges.rule_id')
            ->where('score_rules.assessment_code', $code)
            ->whereNotNull('score_rule_numeric_ranges.signal_flag')
            ->pluck('score_rule_numeric_ranges.signal_flag');

        $flags = $ruleFlags->merge($optionFlags)->merge($rangeFlags)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return response()->json(['flags' => $flags]);
    }

    // ── POST validate ─────────────────────────────────────────────────────────

    public function validateConfig(Request $request, Assessment $assessment): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.config');

        $data   = $request->json()->all();
        $errors = [];

        $aggModel = $data['assessment']['aggregation_model'] ?? '';

        if ($aggModel === 'weighted_domain') {
            $weightSum = collect($data['domains'] ?? [])->sum('weight');
            if (abs($weightSum - 1.0) > 0.01) {
                $errors[] = 'Tổng weight domains phải = 1.00 (hiện tại: ' . round($weightSum, 3) . ')';
            }
            foreach ($data['domains'] ?? [] as $d) {
                if ((int) ($d['min_score'] ?? 0) >= (int) ($d['max_score'] ?? 0)) {
                    $errors[] = "Domain '{$d['domain_code']}': min_score >= max_score";
                }
            }
        }

        if (($data['assessment']['classification_type'] ?? '') === 'score_band') {
            $bands = collect($data['bands'] ?? [])->sortBy(fn ($b) => (float) $b['min_score'])->values();
            for ($i = 1; $i < $bands->count(); $i++) {
                $prev    = $bands[$i - 1];
                $curr    = $bands[$i];
                $prevMax = (float) $prev['max_score'];
                $currMin = (float) $curr['min_score'];
                if (abs($currMin - $prevMax) > 1.01) {
                    $errors[] = "Bands có gap: {$prev['band_code']} kết thúc tại {$prevMax}, {$curr['band_code']} bắt đầu từ {$currMin}";
                }
                if ($curr['max_score'] <= $curr['min_score']) {
                    $errors[] = "Band '{$curr['band_code']}': max_score phải lớn hơn min_score";
                }
            }
        }

        // C.5 — Score rule constraints
        foreach ($data['rules'] ?? [] as $r) {
            $type   = $r['question_scoring_type'] ?? 'none';
            $fKey   = $r['field_key'] ?? '?';
            $optCnt = count($r['options'] ?? []);

            // score_rules.domain_code là NOT NULL ở DB cho mọi rule — thiếu domain
            // sẽ vỡ constraint khi save, báo sớm ở đây thay vì lỗi SQL khó hiểu.
            if (empty($r['domain_code'])) {
                $errors[] = "Câu '{$fKey}': chưa chọn domain (bắt buộc).";
            }

            if (in_array($type, ['multi_choice', 'single_choice'], true) && $optCnt < 2) {
                $errors[] = "Câu '{$fKey}': {$type} cần tối thiểu 2 options (hiện có {$optCnt}).";
            }

            if ($type === 'multi_choice') {
                $maxCap = $r['max_score_cap'] ?? null;
                if ($maxCap === null || $maxCap === '') {
                    $errors[] = "Câu '{$fKey}': multi_choice bắt buộc khai báo max_score_cap.";
                }
                $minCap = $r['min_score_cap'] ?? null;
                if ($maxCap !== null && $maxCap !== '' && $minCap !== null && $minCap !== '' && (int) $minCap >= (int) $maxCap) {
                    $errors[] = "Câu '{$fKey}': min_score_cap ({$minCap}) phải nhỏ hơn max_score_cap ({$maxCap}).";
                }
            }

            if ($type === 'numeric_range') {
                $ranges = collect($r['ranges'] ?? [])
                    ->filter(fn ($nr) => ($nr['min_value'] ?? '') !== '' && ($nr['max_value'] ?? '') !== '')
                    ->sortBy(fn ($nr) => (float) $nr['min_value'])
                    ->values();

                for ($i = 1; $i < $ranges->count(); $i++) {
                    $prev = $ranges[$i - 1];
                    $curr = $ranges[$i];
                    if ((float) $curr['min_value'] < (float) $prev['max_value']) {
                        $errors[] = "Câu '{$fKey}': numeric_range overlap [{$prev['min_value']}–{$prev['max_value']}] và [{$curr['min_value']}–{$curr['max_value']}].";
                    }
                }
            }
        }

        return response()->json(['valid' => empty($errors), 'errors' => $errors]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function deriveCode(string $code): string
    {
        return str_replace('-', '_', $survey->slug);
    }

    private function serializeRule(ScoreRule $rule): array
    {
        return [
            'id'                    => $rule->id,
            'field_key'             => $rule->field_key,
            'feature_code'          => $rule->feature_code,
            'domain_code'           => $rule->domain_code,
            'signal_flag'           => $rule->signal_flag,
            'score_if_true'         => $rule->score_if_true,
            'score_if_false'        => $rule->score_if_false,
            'question_scoring_type' => $rule->question_scoring_type ?? $rule->condition_type,
            'min_score_cap'         => $rule->min_score_cap,
            'max_score_cap'         => $rule->max_score_cap,
            'options'               => $rule->options->values(),
            'ranges'                => $rule->numericRanges->values(),
        ];
    }
}
