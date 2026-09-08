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
        Schema::table('content_foundations', function (Blueprint $table) {
            if (!Schema::hasColumn('content_foundations', 'audience_behavior')) {
                $table->text('audience_behavior')->nullable();
            }
            if (!Schema::hasColumn('content_foundations', 'product_service_docs')) {
                $table->text('product_service_docs')->nullable()->after('audience_behavior');
            }
            if (!Schema::hasColumn('content_foundations', 'best_example_content')) {
                $table->text('best_example_content')->nullable()->after('product_service_docs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_foundations', function (Blueprint $table) {
            $cols = array_filter(['audience_behavior', 'product_service_docs', 'best_example_content'], fn($c) => Schema::hasColumn('content_foundations', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};