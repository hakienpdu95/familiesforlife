<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_prompts', function (Blueprint $table) {
            if (! Schema::hasColumn('generated_prompts', 'post_category_id')) {
                $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('generated_prompts', function (Blueprint $table) {
            if (Schema::hasColumn('generated_prompts', 'post_category_id')) {
                $table->dropForeign(['post_category_id']);
            }
            $cols = array_filter(['post_category_id'], fn ($c) => Schema::hasColumn('generated_prompts', $c));
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
