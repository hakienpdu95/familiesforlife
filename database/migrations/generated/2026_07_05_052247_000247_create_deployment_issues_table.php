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
        if (Schema::hasTable('deployment_issues')) {
            return;
        }

        Schema::create('deployment_issues', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deployment_target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('severity', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            

            // Indexes
            $table->index(['deployment_target_id', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_issues');
    }
};