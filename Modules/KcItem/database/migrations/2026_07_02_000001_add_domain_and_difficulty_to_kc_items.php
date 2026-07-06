<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kc_items', function (Blueprint $table) {
            $table->string('domain_code', 10)->nullable()->after('category_id');
            $table->unsignedTinyInteger('difficulty')->nullable()->after('domain_code');
            $table->index('domain_code');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::table('kc_items', function (Blueprint $table) {
            $table->dropIndex(['domain_code']);
            $table->dropIndex(['difficulty']);
            $table->dropColumn(['domain_code', 'difficulty']);
        });
    }
};
