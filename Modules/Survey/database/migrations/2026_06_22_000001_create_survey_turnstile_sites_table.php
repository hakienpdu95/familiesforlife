<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_turnstile_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('site_key');
            $table->text('secret_key_encrypted');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_turnstile_sites');
    }
};
