<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pension_usage_logs')) {
            return;
        }

        Schema::create('pension_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->enum('gender', ['male', 'female']);
            $table->boolean('has_mandatory_history')->default(false);
            $table->boolean('uses_support_group')->default(false);
            $table->string('eligibility_branch', 1);
            $table->boolean('eligible_by_years')->default(false);
            $table->unsignedSmallInteger('years_accumulated');
            $table->unsignedSmallInteger('years_required');
            $table->timestamp('created_at')->nullable();

        });

    }

    public function down(): void
    {
        Schema::dropIfExists('pension_usage_logs');
    }
};
