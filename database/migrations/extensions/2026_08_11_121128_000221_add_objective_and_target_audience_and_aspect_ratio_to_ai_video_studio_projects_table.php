<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_video_studio_projects', 'objective')) {
                $table->text('objective')->nullable();
            }
            if (! Schema::hasColumn('ai_video_studio_projects', 'target_audience')) {
                $table->text('target_audience')->nullable()->after('objective');
            }
            if (! Schema::hasColumn('ai_video_studio_projects', 'aspect_ratio')) {
                $table->string('aspect_ratio', 10)->nullable()->after('target_audience');
            }
            if (! Schema::hasColumn('ai_video_studio_projects', 'reference_image_url')) {
                $table->text('reference_image_url')->nullable()->after('aspect_ratio');
            }
            if (! Schema::hasColumn('ai_video_studio_projects', 'resolution')) {
                $table->string('resolution', 10)->nullable()->after('reference_image_url');
            }
            if (! Schema::hasColumn('ai_video_studio_projects', 'video_type')) {
                $table->string('video_type', 20)->nullable()->after('resolution');
            }
            if (! Schema::hasColumn('ai_video_studio_projects', 'core_message')) {
                $table->text('core_message')->nullable()->after('video_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $cols = array_filter(['objective', 'target_audience', 'aspect_ratio', 'reference_image_url', 'resolution', 'video_type', 'core_message'], fn ($c) => Schema::hasColumn('ai_video_studio_projects', $c));
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
