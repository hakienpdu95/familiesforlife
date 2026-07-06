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
        if (Schema::hasTable('job_title_domain_requirements')) {
            return;
        }

        Schema::create('job_title_domain_requirements', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('job_title_id');
            $table->string('domain_code', 20);
            $table->decimal('required_score', 5, 2)->default(40.0);
            $table->tinyInteger('is_critical')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['job_title_id', 'domain_code']);
            $table->index('organization_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('job_title_domain_requirements');
    }
};