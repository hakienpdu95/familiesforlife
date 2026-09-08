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
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            }
            if (!Schema::hasColumn('assessments', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('organization_id')->comment('Thời gian xóa mềm');
            }
            if (!Schema::hasColumn('assessments', 'source_type')) {
                $table->string('source_type', 30)->default('standalone')->after('deleted_at')->comment('global_template|survey_linked|lead_scoring|standalone');
            }
            if (!Schema::hasColumn('assessments', 'source_ref')) {
                $table->string('source_ref', 255)->nullable()->after('source_type')->comment('slug/code của entity nguồn — VD: survey slug nếu survey_linked');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'organization_id')) $table->dropForeign(['organization_id']);
            $cols = array_filter(['organization_id', 'deleted_at', 'source_type', 'source_ref'], fn($c) => Schema::hasColumn('assessments', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};