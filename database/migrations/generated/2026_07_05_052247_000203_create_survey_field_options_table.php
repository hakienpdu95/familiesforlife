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
        if (Schema::hasTable('survey_field_options')) {
            return;
        }

        Schema::create('survey_field_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('field_id')->constrained('survey_fields')->cascadeOnDelete()->comment('FK -> survey_fields');
            $table->string('option_value', 150)->comment('Giá trị machine: chatgpt');
            $table->string('label', 300)->comment('Nhãn hiển thị: ChatGPT');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Thứ tự hiển thị');
            $table->boolean('is_other')->default(false)->comment('Lựa chọn Khác — cho phép nhập tay');
            $table->timestamps();
            

            // Indexes
            $table->index(['field_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_field_options');
    }
};