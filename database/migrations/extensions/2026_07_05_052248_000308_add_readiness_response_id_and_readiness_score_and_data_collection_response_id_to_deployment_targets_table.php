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
        Schema::table('deployment_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_targets', 'readiness_response_id')) {
                $table->foreignId('readiness_response_id')->nullable()->constrained('survey_responses')->nullOnDelete();
            }
            if (!Schema::hasColumn('deployment_targets', 'readiness_score')) {
                $table->unsignedTinyInteger('readiness_score')->nullable()->after('readiness_response_id')->comment('0-100 readiness score; null = not assessed yet');
            }
            if (!Schema::hasColumn('deployment_targets', 'data_collection_response_id')) {
                $table->foreignId('data_collection_response_id')->nullable()->constrained('survey_responses')->nullOnDelete()->after('readiness_score');
            }
            if (!Schema::hasColumn('deployment_targets', 'deployment_id')) {
                $table->foreignId('deployment_id')->nullable()->constrained('deployments')->nullOnDelete()->after('data_collection_response_id')->comment('Deployment Engine (spec Phần 4) — pin runtime vào lần deploy đã tạo ra nó');
            }
            if (!Schema::hasColumn('deployment_targets', 'blueprint_version_id')) {
                $table->foreignId('blueprint_version_id')->nullable()->constrained('blueprint_versions')->nullOnDelete()->after('deployment_id')->comment('Deployment Engine (spec Phần 4) — pin runtime vào đúng Blueprint Version đã deploy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deployment_targets', function (Blueprint $table) {
            if (Schema::hasColumn('deployment_targets', 'readiness_response_id')) $table->dropForeign(['readiness_response_id']);
            if (Schema::hasColumn('deployment_targets', 'data_collection_response_id')) $table->dropForeign(['data_collection_response_id']);
            if (Schema::hasColumn('deployment_targets', 'deployment_id')) $table->dropForeign(['deployment_id']);
            if (Schema::hasColumn('deployment_targets', 'blueprint_version_id')) $table->dropForeign(['blueprint_version_id']);
            $cols = array_filter(['readiness_response_id', 'readiness_score', 'data_collection_response_id', 'deployment_id', 'blueprint_version_id'], fn($c) => Schema::hasColumn('deployment_targets', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};