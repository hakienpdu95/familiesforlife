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
        if (Schema::hasTable('ai_settings')) {
            return;
        }

        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->text('provider_config')->nullable()->comment('BYOK: provider + model + API key (encrypted cast), NULL = dùng config(ai.default)');
            $table->decimal('monthly_budget_usd', 10, 2)->nullable()->comment('Hạn mức chi phí AI mỗi tháng (USD), NULL = không giới hạn');
            $table->json('rate_limit_override')->nullable()->comment('Override config(aicem.rate_limit), NULL = dùng mặc định');
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};