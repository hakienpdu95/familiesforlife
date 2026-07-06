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
        if (Schema::hasTable('blueprint_ai_capabilities')) {
            return;
        }

        Schema::create('blueprint_ai_capabilities', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('checklist_id')->nullable()->constrained('blueprint_checklists')->nullOnDelete();
            $table->string('capability_code', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('ai_agent_id')->nullable();
            $table->unsignedBigInteger('ai_prompt_id')->nullable();
            $table->string('trigger_event', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_ai_capabilities');
    }
};