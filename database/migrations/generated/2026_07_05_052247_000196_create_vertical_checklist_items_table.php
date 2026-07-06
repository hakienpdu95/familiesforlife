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
        if (Schema::hasTable('vertical_checklist_items')) {
            return;
        }

        Schema::create('vertical_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('vertical_phase_id')->constrained('vertical_phases')->cascadeOnDelete()->comment('FK -> vertical_phases');
            $table->string('key', 100)->comment('Machine key của checklist item');
            $table->string('label', 255)->comment('Nhãn hiển thị checklist item');
            $table->boolean('is_required')->default(true)->comment('Bắt buộc hoàn thành');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Thứ tự hiển thị');
            

            // Indexes
            $table->unique(['vertical_phase_id', 'key'], 'uq_vertical_checklist_key');
            $table->index(['vertical_phase_id', 'sort_order'], 'idx_vertical_checklist_sort');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('vertical_checklist_items');
    }
};