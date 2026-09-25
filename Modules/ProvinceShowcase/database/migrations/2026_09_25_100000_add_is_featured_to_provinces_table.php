<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            if (! Schema::hasColumn('provinces', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->index();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('provinces', 'is_featured')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->dropIndex(['is_featured']);
                $table->dropColumn('is_featured');
            });
        }
    }
};
