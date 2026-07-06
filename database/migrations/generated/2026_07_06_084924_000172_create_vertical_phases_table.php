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
        if (Schema::hasTable('vertical_phases')) {
            return;
        }

        Schema::create('vertical_phases', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('vertical_template_id')->constrained('vertical_templates')->cascadeOnDelete()->comment('FK -> vertical_templates');
            $table->string('key', 50)->comment('Machine key của phase — vd: draft, surveying');
            $table->string('label', 100)->comment('Nhãn hiển thị phase');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Thứ tự hiển thị');
            $table->boolean('is_initial')->default(false)->comment('Phase khởi tạo mặc định — thay hardcode \'draft\'');
            $table->boolean('auto_assign_data_collection')->default(false)->comment('Tự động gán khảo sát thu thập dữ liệu khi vào phase này — thay hardcode \'surveying\'');
            

            // Indexes
            $table->unique(['vertical_template_id', 'key'], 'uq_vertical_phase_key');
            $table->index(['vertical_template_id', 'sort_order'], 'idx_vertical_phase_sort');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('vertical_phases');
    }
};