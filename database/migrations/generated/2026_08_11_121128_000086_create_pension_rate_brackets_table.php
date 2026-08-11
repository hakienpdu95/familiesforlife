<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pension_rate_brackets')) {
            return;
        }

        Schema::create('pension_rate_brackets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('gender');
            $table->unsignedTinyInteger('min_years_for_base_rate');
            $table->decimal('base_rate_percent', 5, 2);
            $table->decimal('increment_percent_per_year', 5, 2);
            $table->decimal('max_rate_percent', 5, 2)->default(75.00);
            $table->date('effective_from');
            $table->string('source_document');
            $table->text('notes')->nullable();
            $table->timestamps();

        });

    }

    public function down(): void
    {
        Schema::dropIfExists('pension_rate_brackets');
    }
};
