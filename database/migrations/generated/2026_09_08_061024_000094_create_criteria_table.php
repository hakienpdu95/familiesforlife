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
        if (Schema::hasTable('criteria')) {
            return;
        }

        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->string('type', 20);
            $table->string('unit', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_comparable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};