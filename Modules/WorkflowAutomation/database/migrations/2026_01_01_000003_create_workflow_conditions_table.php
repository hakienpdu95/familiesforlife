<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->string('field', 128);
            $table->string('operator', 32);
            $table->string('value', 500)->nullable();
            $table->tinyInteger('value_type')->unsigned()->default(1);
            $table->timestamp('created_at')->nullable();
            $table->index(['workflow_id', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_conditions'); }
};
