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
        if (Schema::hasTable('verticals')) {
            return;
        }

        Schema::create('verticals', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('status', 20)->default('active');
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