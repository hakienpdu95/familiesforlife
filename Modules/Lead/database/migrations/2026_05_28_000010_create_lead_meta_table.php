<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('key_name', 64);
            // 1=string 2=integer 3=decimal 4=boolean 5=datetime
            $table->unsignedTinyInteger('value_type')->default(1);
            $table->string('val_string', 500)->nullable();
            $table->bigInteger('val_integer')->nullable();
            $table->decimal('val_decimal', 20, 6)->nullable();
            $table->boolean('val_boolean')->nullable();
            $table->dateTime('val_datetime')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'key_name'], 'uq_meta_lead_key');
            $table->index(['key_name', DB::raw('val_string(64)')], 'idx_meta_key_string');
            $table->index(['key_name', 'val_integer'], 'idx_meta_key_integer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_meta');
    }
};
