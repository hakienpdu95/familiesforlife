<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log_contexts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('log_id');
            $table->string('key_name', 64);
            $table->unsignedTinyInteger('value_type')->default(1)
                  ->comment('1=string 2=integer 3=decimal 4=boolean 5=datetime');
            $table->string('val_string', 500)->nullable();
            $table->bigInteger('val_integer')->nullable();
            $table->decimal('val_decimal', 20, 6)->nullable();
            $table->boolean('val_boolean')->nullable();
            $table->dateTime('val_datetime')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('log_id',                   'idx_log');
            $table->index(['key_name', 'val_integer'], 'idx_key_integer');
        });

        // Prefix index — MySQL only (Blueprint không hỗ trợ prefix index)
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE activity_log_contexts ADD INDEX idx_key_string (key_name, val_string(64))'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_contexts');
    }
};
