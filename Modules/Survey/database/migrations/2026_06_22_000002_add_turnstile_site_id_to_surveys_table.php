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
        Schema::table('surveys', function (Blueprint $table) {
            $table->foreignId('turnstile_site_id')
                ->nullable()
                ->after('allow_multiple_responses')
                ->constrained('survey_turnstile_sites')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('turnstile_site_id');
        });
    }
};
