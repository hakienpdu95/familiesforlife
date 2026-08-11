<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pension_support_tiers')) {
            return;
        }

        Schema::create('pension_support_tiers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('period_id')->constrained('pension_parameter_periods')->cascadeOnDelete();
            $table->string('group_key');
            $table->decimal('support_percent', 5, 2);
            $table->timestamps();

            // Indexes
            $table->unique(['period_id', 'group_key']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('pension_support_tiers');
    }
};
