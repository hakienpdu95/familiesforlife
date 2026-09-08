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
        if (Schema::hasTable('persona_conditions')) {
            return;
        }

        Schema::create('persona_conditions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('target_type', 30)->comment('domain | section | overall | signal_flag');
            $table->string('target_code', 100)->comment('domain_code | section_code | \"overall\" | flag_code');
            $table->string('operator', 5)->comment('< | <= | = | >= | >');
            $table->decimal('threshold_value', 5, 2)->nullable()->comment('Ngưỡng cho domain/section/overall');
            $table->boolean('flag_value')->nullable()->comment('Giá trị mong đợi cho signal_flag');
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index('persona_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_conditions');
    }
};