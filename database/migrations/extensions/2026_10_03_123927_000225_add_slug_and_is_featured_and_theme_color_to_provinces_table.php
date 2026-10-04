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
        });
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $cols = array_filter(['slug', 'is_featured', 'theme_color'], fn($c) => Schema::hasColumn('provinces', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};