<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_step_headers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('step_id');
            $table->string('header_key', 128);
            $table->string('header_value', 500);
            $table->index('step_id');
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_step_headers'); }
};
