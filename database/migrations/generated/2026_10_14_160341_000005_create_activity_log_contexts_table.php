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
        if (Schema::hasTable('activity_log_contexts')) {
            return;
        }

        Schema::create('activity_log_contexts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('log_id');
            $table->string('key_name', 64);
            $table->unsignedTinyInteger('value_type')->default(1)->comment('1=string 2=integer 3=decimal 4=boolean 5=datetime');
            $table->string('val_string', 500)->nullable();
            $table->bigInteger('val_integer')->nullable();
            $table->decimal('val_decimal', 20, 6)->nullable();
            $table->boolean('val_boolean')->nullable();
            $table->dateTime('val_datetime')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index('log_id', 'idx_log');
            $table->index(['key_name', 'val_integer'], 'idx_key_integer');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_contexts');
    }
};