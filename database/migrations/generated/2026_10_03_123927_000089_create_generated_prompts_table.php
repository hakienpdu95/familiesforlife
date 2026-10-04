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
        if (Schema::hasTable('generated_prompts')) {
            return;
        }

        Schema::create('generated_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('framework_key', 30);
            $table->string('label', 150);
            $table->json('field_values');
            $table->longText('rendered_prompt');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            

            // Indexes
            $table->index('framework_key');
            $table->index('created_at');
            $table->index('label');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_prompts');
    }
};