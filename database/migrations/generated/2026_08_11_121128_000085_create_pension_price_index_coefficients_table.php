<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pension_price_index_coefficients')) {
            return;
        }

        Schema::create('pension_price_index_coefficients', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedSmallInteger('settlement_year');
            $table->unsignedSmallInteger('contribution_year');
            $table->decimal('coefficient', 4, 2);
            $table->string('source_document');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->unique(['settlement_year', 'contribution_year'], 'pension_price_index_coef_year_unique');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('pension_price_index_coefficients');
    }
};
