<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            if (! Schema::hasColumn('provinces', 'slug')) {
                $table->string('slug', 255)->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $cols = array_filter(['slug'], fn ($c) => Schema::hasColumn('provinces', $c));
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
