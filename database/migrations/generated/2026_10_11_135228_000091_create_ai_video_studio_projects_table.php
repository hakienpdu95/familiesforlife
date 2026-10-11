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
        if (Schema::hasTable('ai_video_studio_projects')) {
            return;
        }

        Schema::create('ai_video_studio_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->text('default_subject')->nullable();
            $table->text('default_style')->nullable();
            $table->text('default_constraints')->nullable();
            $table->string('status', 10)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            

            // Indexes
            $table->index('status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_studio_projects');
    }
};