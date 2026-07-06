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
        if (Schema::hasTable('assessment_config_snapshots')) {
            return;
        }

        Schema::create('assessment_config_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 60)->index();
            $table->unsignedInteger('version');
            $table->boolean('has_scoring')->default(false);
            $table->string('aggregation_model', 30)->nullable();
            $table->string('classification_type', 30)->nullable();
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->string('label_pass', 60)->nullable();
            $table->string('label_fail', 60)->nullable();
            $table->string('created_by', 255)->nullable();
            $table->text('change_note')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['assessment_code', 'version']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_config_snapshots');
    }
};