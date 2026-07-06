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
        if (Schema::hasTable('deployment_logs')) {
            return;
        }

        Schema::create('deployment_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->string('step', 100);
            $table->text('message')->nullable();
            $table->string('level', 20)->default('info');
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['deployment_id', 'created_at']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_logs');
    }
};