<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // spec §8.2/§6.1 — 4 dòng/giai đoạn (poor_household/near_poor_household/
        // ethnic_minority/other), mỗi dòng 1 tỷ lệ hỗ trợ nhà nước (k).
        Schema::create('pension_support_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('pension_parameter_periods')->cascadeOnDelete();
            $table->string('group_key');
            $table->decimal('support_percent', 5, 2);
            $table->timestamps();

            $table->unique(['period_id', 'group_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pension_support_tiers');
    }
};
