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
        if (Schema::hasTable('lead_meta')) {
            return;
        }

        Schema::create('lead_meta', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('lead_id');
            $table->string('key_name', 64);
            $table->unsignedTinyInteger('value_type')->default(1);
            $table->string('val_string', 500)->nullable();
            $table->bigInteger('val_integer')->nullable();
            $table->decimal('val_decimal', 20, 6)->nullable();
            $table->boolean('val_boolean')->nullable();
            $table->dateTime('val_datetime')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['lead_id', 'key_name'], 'uq_meta_lead_key');
            $table->index(['key_name', 'val_integer'], 'idx_meta_key_integer');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_meta');
    }
};