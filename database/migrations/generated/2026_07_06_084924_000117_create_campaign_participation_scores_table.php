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
        if (Schema::hasTable('campaign_participation_scores')) {
            return;
        }

        Schema::create('campaign_participation_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('participation_id');
            $table->string('domain_code', 10);
            $table->decimal('score', 5, 2);
            

            // Indexes
            $table->unique(['participation_id', 'domain_code'], 'cps_part_domain_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_participation_scores');
    }
};