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
        if (Schema::hasTable('surveys')) {
            return;
        }

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('title', 255)->comment('Tiêu đề khảo sát');
            $table->string('slug', 160)->unique()->comment('Slug URL — unique');
            $table->unsignedTinyInteger('status')->default(0)->index()->comment('0=draft 1=active 2=closed');
            $table->unsignedSmallInteger('version')->default(1)->comment('Phiên bản khảo sát');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('created_at');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};