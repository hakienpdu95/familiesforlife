<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('definition_id');
            $table->string('val_string', 1000)->nullable();
            $table->bigInteger('val_integer')->nullable();
            $table->decimal('val_decimal', 18, 4)->nullable();
            $table->boolean('val_boolean')->nullable();
            $table->date('val_date')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'definition_id'], 'uq_cmeta_customer_def');
            $table->index('customer_id', 'idx_cmeta_customer');
            $table->index('definition_id', 'idx_cmeta_def');

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('definition_id')->references('id')->on('customer_field_definitions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_meta');
    }
};
