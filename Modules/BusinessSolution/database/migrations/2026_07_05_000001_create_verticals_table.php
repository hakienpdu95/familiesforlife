<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verticals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();      // agriculture, insurance, workforce, education...
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('status', 20)->default('active'); // VerticalStatus: active|inactive
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verticals');
    }
};
