<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_calendar_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('content_calendar_entries', 'funnel_stage')) {
                $table->string('funnel_stage', 10)->nullable();
            }
            if (! Schema::hasIndex('content_calendar_entries', 'cc_entries_category_funnel_idx')) {
                $table->index(['post_category_id', 'funnel_stage'], 'cc_entries_category_funnel_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_calendar_entries', function (Blueprint $table) {
            $cols = array_filter(['funnel_stage'], fn ($c) => Schema::hasColumn('content_calendar_entries', $c));
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
