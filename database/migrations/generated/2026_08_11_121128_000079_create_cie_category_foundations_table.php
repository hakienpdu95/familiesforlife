<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cie_category_foundations')) {
            return;
        }

        Schema::create('cie_category_foundations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->text('core_focus')->nullable();
            $table->text('unique_angle')->nullable();
            $table->text('content_goals')->nullable();
            $table->string('audience', 500)->nullable();
            $table->string('constraints', 500)->nullable();
            $table->text('style_sample')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

        });

    }

    public function down(): void
    {
        Schema::dropIfExists('cie_category_foundations');
    }
};
