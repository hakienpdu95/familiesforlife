<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['slogan', 'description', 'cover_image', 'tvc_video_url', 'vr360_map_url', 'highlight_tags'];

    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            if (! Schema::hasColumn('provinces', 'slogan')) {
                $table->string('slogan', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('cover_image', 255)->nullable();
                $table->string('tvc_video_url', 500)->nullable();
                $table->string('vr360_map_url', 500)->nullable();
                $table->json('highlight_tags')->nullable();
            }
        });
    }

    public function down(): void
    {
        $existing = array_values(array_filter(self::COLUMNS, fn (string $c) => Schema::hasColumn('provinces', $c)));

        if ($existing) {
            Schema::table('provinces', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
