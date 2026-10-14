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
        if (Schema::hasTable('customer_meta')) {
            return;
        }

        Schema::create('customer_meta', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('definition_id');
            $table->string('val_string', 1000)->nullable();
            $table->bigInteger('val_integer')->nullable();
            $table->decimal('val_decimal', 18, 4)->nullable();
            $table->boolean('val_boolean')->nullable();
            $table->date('val_date')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['customer_id', 'definition_id'], 'uq_cmeta_customer_def');
            $table->index('customer_id', 'idx_cmeta_customer');
            $table->index('definition_id', 'idx_cmeta_def');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_meta');
    }
};