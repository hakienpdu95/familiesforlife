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
        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_video_studio_shots', 'model_tool')) {
                $table->string('model_tool', 150)->nullable();
            }
            if (!Schema::hasColumn('ai_video_studio_shots', 'qc_notes')) {
                $table->text('qc_notes')->nullable()->after('model_tool');
            }
            if (!Schema::hasColumn('ai_video_studio_shots', 'mood')) {
                $table->text('mood')->nullable()->after('qc_notes');
            }
            if (!Schema::hasColumn('ai_video_studio_shots', 'duration_seconds')) {
                $table->unsignedSmallInteger('duration_seconds')->nullable()->after('mood');
            }
            if (!Schema::hasColumn('ai_video_studio_shots', 'audio_direction')) {
                $table->text('audio_direction')->nullable()->after('duration_seconds');
            }
            if (!Schema::hasColumn('ai_video_studio_shots', 'reference_assets')) {
                $table->text('reference_assets')->nullable()->after('audio_direction');
            }
            if (!Schema::hasColumn('ai_video_studio_shots', 'cta_text')) {
                $table->string('cta_text', 200)->nullable()->after('reference_assets');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $cols = array_filter(['model_tool', 'qc_notes', 'mood', 'duration_seconds', 'audio_direction', 'reference_assets', 'cta_text'], fn($c) => Schema::hasColumn('ai_video_studio_shots', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};