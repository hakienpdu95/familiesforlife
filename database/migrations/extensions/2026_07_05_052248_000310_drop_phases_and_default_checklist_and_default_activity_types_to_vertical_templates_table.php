<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vertical_templates', function (Blueprint $table) {
            if (Schema::hasIndex('vertical_templates', 'vertical_templates_code_unique')) {
                $table->dropUnique('vertical_templates_code_unique');
            }
            $cols = array_filter(['phases', 'default_checklist', 'default_activity_types', 'default_legal_doc_types', 'default_hierarchy', 'export_adapter'], fn($c) => Schema::hasColumn('vertical_templates', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }

    public function down(): void
    {
        Schema::table('vertical_templates', function (Blueprint $table) {
            // TODO: $table->json('phases')->...; // add lại 'phases'
            // TODO: $table->json('default_checklist')->...; // add lại 'default_checklist'
            // TODO: $table->json('default_activity_types')->...; // add lại 'default_activity_types'
            // TODO: $table->json('default_legal_doc_types')->...; // add lại 'default_legal_doc_types'
            // TODO: $table->json('default_hierarchy')->...; // add lại 'default_hierarchy'
            // TODO: $table->string('export_adapter')->...; // add lại 'export_adapter'
        });
    }
};