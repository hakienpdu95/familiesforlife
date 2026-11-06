<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORPHAN_TABLES = [
        'activity_log_alert_rules', 'cie_category_foundations', 'cie_foundation_categories', 'assessment_config_snapshots', 'assessment_domains', 'assessment_results',
        'assessments', 'content_brief_generations', 'content_brief_versions', 'content_briefs',
        'customer_activities', 'customer_field_definitions', 'customer_meta', 'customer_notes',
        'customer_tag_map', 'customer_tags', 'customers', 'maturity_levels', 'organization_settings',
        'pain_point_rules', 'pass_fail_configs', 'persona_conditions', 'personas',
        'process_approval_flow_steps', 'process_approval_flows', 'process_approval_statuses', 'process_approvals',
        'recommendation_rules', 'result_classifications', 'result_domain_scores', 'result_pain_points',
        'result_question_scores', 'result_question_selected_options', 'result_recommendations',
        'result_roadmap_phases', 'result_signal_flags', 'roadmap_milestones', 'roadmap_phases', 'score_bands',
        'score_rule_numeric_ranges', 'score_rule_options', 'score_rules', 'scoring_feedback', 'snapshot_bands',
        'snapshot_domains', 'snapshot_pain_points', 'snapshot_persona_conditions', 'snapshot_personas',
        'snapshot_recommendations', 'snapshot_roadmap_milestones', 'snapshot_roadmap_phases',
        'snapshot_rule_options', 'snapshot_rule_ranges', 'snapshot_rules', 'submission_behavior_log',
        'survey_answers', 'survey_drafts', 'survey_field_conditions', 'survey_field_options', 'survey_field_rows',
        'survey_fields', 'survey_responses', 'survey_sections', 'survey_tokens', 'survey_turnstile_sites',
        'survey_webhooks', 'surveys', 'workflow_conditions', 'workflow_entity_state_logs', 'workflow_entity_states',
        'workflow_entity_transitions', 'workflow_execution_steps', 'workflow_executions', 'workflow_input_fields',
        'workflow_step_groups', 'workflow_step_headers', 'workflow_steps', 'workflow_templates',
        'workflow_trigger_params', 'workflow_user_tasks', 'workflow_variables', 'workflows',
    ];

    public function up(): void
    {
        $this->deleteOrganizationApprovalSubjects();
        $this->keepSingleAicemWorkflowSet();
        $this->dropSpatieTeamColumns();

        Schema::disableForeignKeyConstraints();

        foreach (self::ORPHAN_TABLES as $table) {
            Schema::dropIfExists($table);
        }

        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if ($table !== 'organizations' && Schema::hasColumn($table, 'organization_id')) {
                $this->dropOrganizationColumn($table);
            }
        }

        if (Schema::hasColumn('users', 'current_org_id')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('current_org_id'));
        }

        Schema::dropIfExists('organizations');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void {}

    private function deleteOrganizationApprovalSubjects(): void
    {
        if (! Schema::hasTable('approval_subjects')) {
            return;
        }

        $ids = DB::table('approval_subjects')->where('subject_type', 'organization')->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('approval_logs')->whereIn('approval_subject_id', $ids)->delete();
            DB::table('approval_subjects')->whereIn('id', $ids)->delete();
        }
    }

    private function keepSingleAicemWorkflowSet(): void
    {
        if (! Schema::hasTable('aicem_workflows') || ! Schema::hasColumn('aicem_workflows', 'organization_id')) {
            return;
        }

        $keepOrgId = Schema::hasTable('organizations')
            ? DB::table('organizations')->where('slug', 'platform-editorial')->value('id')
            : null;
        $keepOrgId ??= DB::table('aicem_workflows')->min('organization_id');

        if ($keepOrgId === null) {
            return;
        }

        $workflowIds = DB::table('aicem_workflows')->where('organization_id', '!=', $keepOrgId)->pluck('id');

        DB::table('aicem_generation_runs')->whereIn('workflow_id', $workflowIds)->delete();
        DB::table('aicem_workflows')->whereIn('id', $workflowIds)->delete();
        DB::table('aicem_context_templates')->where('organization_id', '!=', $keepOrgId)->delete();
    }

    private function dropSpatieTeamColumns(): void
    {
        $tables = config('permission.table_names');

        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $key => $pivotKey) {
            $table = $tables[$key];

            if (! Schema::hasColumn($table, 'organization_id')) {
                continue;
            }

            $primary = "{$table}_".($key === 'model_has_roles' ? 'role' : 'permission').'_model_type_primary';

            Schema::table($table, function (Blueprint $t) use ($table) {
                $this->dropIndexesOn($t, $table, 'organization_id');
                $t->dropColumn('organization_id');
            });

            Schema::table($table, fn (Blueprint $t) => $t->primary([$pivotKey, 'model_id', 'model_type'], $primary));
        }

        if (Schema::hasColumn($tables['roles'], 'organization_id')) {
            Schema::table($tables['roles'], function (Blueprint $t) use ($tables) {
                $this->dropIndexesOn($t, $tables['roles'], 'organization_id');
                $t->dropColumn('organization_id');
            });

            Schema::table($tables['roles'], fn (Blueprint $t) => $t->unique(['name', 'guard_name']));
        }
    }

    private function dropOrganizationColumn(string $table): void
    {
        $recreate = collect(Schema::getIndexes($table))
            ->filter(fn (array $index) => ! $index['primary'] && in_array('organization_id', $index['columns'], true))
            ->map(fn (array $index) => [
                'name' => $this->stripOrgFromName($index['name']),
                'columns' => array_values(array_diff($index['columns'], ['organization_id'])),
                'unique' => $index['unique'],
            ])
            ->filter(fn (array $index) => $index['columns'] !== []);

        Schema::table($table, function (Blueprint $t) use ($table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if ($foreignKey['columns'] === ['organization_id']) {
                    $t->dropForeign($foreignKey['name']);
                }
            }
        });

        Schema::table($table, function (Blueprint $t) use ($table) {
            $this->dropIndexesOn($t, $table, 'organization_id');
            $t->dropColumn('organization_id');
        });

        $existing = collect(Schema::getIndexes($table));

        foreach ($recreate as $index) {
            $duplicate = $existing->contains(fn (array $i) => $i['columns'] === $index['columns'] && $i['unique'] === $index['unique']);

            if ($duplicate || $existing->contains(fn (array $i) => $i['name'] === $index['name'])) {
                continue;
            }

            Schema::table($table, fn (Blueprint $t) => $index['unique']
                ? $t->unique($index['columns'], $index['name'])
                : $t->index($index['columns'], $index['name']));

            $existing = collect(Schema::getIndexes($table));
        }
    }

    private function dropIndexesOn(Blueprint $t, string $table, string $column): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (! $index['primary'] && in_array($column, $index['columns'], true)) {
                $index['unique'] ? $t->dropUnique($index['name']) : $t->dropIndex($index['name']);
            }
        }
    }

    private function stripOrgFromName(string $name): string
    {
        $name = str_replace(['_organization_id', 'organization_id_'], '', $name);

        return preg_replace(['/_org_/', '/_org(?=_|$)/', '/(?<=_)org_/'], ['_', '', ''], $name);
    }
};
