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
        if (Schema::hasTable('workflows')) {
            return;
        }

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('name', 191);
            $table->string('description', 500)->nullable();
            $table->string('trigger_type', 64);
            $table->unsignedTinyInteger('condition_match')->default(1);
            $table->unsignedTinyInteger('cooldown_type')->default(0);
            $table->boolean('is_active')->default(false);
            $table->unsignedTinyInteger('priority')->default(5);
            $table->unsignedInteger('run_count')->default(0);
            $table->dateTime('last_run_at')->nullable();
            $table->unsignedTinyInteger('last_run_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['organization_id', 'trigger_type', 'is_active'], 'idx_org_trigger');
            $table->index(['organization_id', 'is_active', 'priority'], 'idx_org_priority');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};