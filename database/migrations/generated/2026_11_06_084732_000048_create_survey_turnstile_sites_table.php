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
        if (Schema::hasTable('survey_turnstile_sites')) {
            return;
        }

        Schema::create('survey_turnstile_sites', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
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