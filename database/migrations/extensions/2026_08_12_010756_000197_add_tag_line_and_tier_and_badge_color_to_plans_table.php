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
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'tag_line')) {
                $table->string('tag_line', 120)->nullable();
            }
            if (!Schema::hasColumn('plans', 'tier')) {
                $table->string('tier', 32)->default('growth')->after('tag_line');
            }
            if (!Schema::hasColumn('plans', 'badge_color')) {
                $table->string('badge_color', 64)->nullable()->after('tier');
            }
            if (!Schema::hasColumn('plans', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('badge_color');
            }
            if (!Schema::hasColumn('plans', 'annual_price')) {
                $table->decimal('annual_price', 15, 2)->nullable()->after('is_public');
            }
            if (!Schema::hasColumn('plans', 'currency_local')) {
                $table->string('currency_local', 10)->default('VND')->after('annual_price');
            }
            if (!Schema::hasColumn('plans', 'price_local')) {
                $table->decimal('price_local', 15, 2)->nullable()->after('currency_local');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $cols = array_filter(['tag_line', 'tier', 'badge_color', 'is_public', 'annual_price', 'currency_local', 'price_local'], fn($c) => Schema::hasColumn('plans', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};