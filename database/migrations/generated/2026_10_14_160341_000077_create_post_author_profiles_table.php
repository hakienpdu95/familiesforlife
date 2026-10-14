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
        if (Schema::hasTable('post_author_profiles')) {
            return;
        }

        Schema::create('post_author_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('pen_name', 120)->nullable();
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_author_profiles');
    }
};