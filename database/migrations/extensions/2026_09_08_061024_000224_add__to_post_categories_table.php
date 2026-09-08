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
        Schema::table('post_categories', function (Blueprint $table) {
            if (!Schema::hasIndex('post_categories', 'uq_post_cat_slug')) {
                $table->unique('slug', 'uq_post_cat_slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_categories', function (Blueprint $table) {
            $cols = array_filter([], fn($c) => Schema::hasColumn('post_categories', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};