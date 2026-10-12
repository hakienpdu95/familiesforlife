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
        Schema::table('provinces', function (Blueprint $table) {
            if (!Schema::hasColumn('provinces', 'slug')) {
                $table->string('slug', 255)->nullable()->unique();
            }
            if (!Schema::hasColumn('provinces', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->index()->after('slug');
            }
            if (!Schema::hasColumn('provinces', 'theme_color')) {
                $table->string('theme_color', 7)->nullable()->after('is_featured');
            }
            if (!Schema::hasColumn('provinces', 'slogan')) {
                $table->string('slogan', 255)->nullable()->after('theme_color');
            }
            if (!Schema::hasColumn('provinces', 'description')) {
                $table->text('description')->nullable()->after('slogan');
            }
            if (!Schema::hasColumn('provinces', 'cover_image')) {
                $table->string('cover_image', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('provinces', 'tvc_video_url')) {
                $table->string('tvc_video_url', 500)->nullable()->after('cover_image');
            }
            if (!Schema::hasColumn('provinces', 'vr360_map_url')) {
                $table->string('vr360_map_url', 500)->nullable()->after('tvc_video_url');
            }
            if (!Schema::hasColumn('provinces', 'highlight_tags')) {
                $table->json('highlight_tags')->nullable()->after('vr360_map_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $cols = array_filter(['slug', 'is_featured', 'theme_color', 'slogan', 'description', 'cover_image', 'tvc_video_url', 'vr360_map_url', 'highlight_tags'], fn($c) => Schema::hasColumn('provinces', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};