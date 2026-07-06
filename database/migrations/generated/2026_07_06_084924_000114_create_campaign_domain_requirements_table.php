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
        if (Schema::hasTable('campaign_domain_requirements')) {
            return;
        }

        Schema::create('campaign_domain_requirements', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('campaign_id');
            $table->string('domain_code', 10);
            $table->decimal('min_score', 5, 2)->default(0);
            $table->tinyInteger('is_required')->default(1);
            

            // Indexes
            $table->unique(['campaign_id', 'domain_code'], 'cdr_campaign_domain_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_domain_requirements');
    }
};