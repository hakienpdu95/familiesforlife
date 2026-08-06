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
        if (Schema::hasTable('survey_field_rows')) {
            return;
        }

        Schema::create('survey_field_rows', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('field_id')->constrained('survey_fields')->cascadeOnDelete();
            $table->string('row_key', 100);
            $table->string('label', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            

            // Indexes
            $table->unique(['field_id', 'row_key']);
            $table->index(['field_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_field_rows');
    }
};