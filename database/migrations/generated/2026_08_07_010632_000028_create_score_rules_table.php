<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('score_rules')) {
            return;
        }

        Schema::create('score_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50);
            $table->string('field_key', 100)->comment('Khớp với survey_fields.field_key');
            $table->string('domain_code', 50)->comment('Khớp với assessment_domains.domain_code');
            $table->string('signal_flag', 100)->nullable()->comment('e.g. HAS_CRM — nullable nếu không emit flag');
            $table->integer('score_if_true')->default(0)->comment('Dùng khi condition_type = boolean');
            $table->integer('score_if_false')->default(0)->comment('Dùng khi condition_type = boolean');
            $table->string('condition_type', 20)->default('boolean')->comment('boolean | single_choice | multi_choice');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            

            // Indexes
            $table->unique(['assessment_code', 'field_key', 'domain_code'], 'uq_score_rule');
            $table->index(['assessment_code', 'domain_code']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('score_rules');
    }
};