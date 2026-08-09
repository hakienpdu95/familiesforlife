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
        Schema::table('video_idea_extractor_layer2_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('video_idea_extractor_layer2_runs', 'kind')) {
                $table->string('kind', 30)->default('layer2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('video_idea_extractor_layer2_runs', function (Blueprint $table) {
            $cols = array_filter(['kind'], fn($c) => Schema::hasColumn('video_idea_extractor_layer2_runs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};