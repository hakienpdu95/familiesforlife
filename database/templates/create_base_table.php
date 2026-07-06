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
        if (Schema::hasTable('__TABLE_NAME__')) {
            return;
        }

        Schema::create('__TABLE_NAME__', function (Blueprint $table) {
            __FIELDS__
            __INDEXES__
        });

        __INITIAL_DATA__
    }

    public function down(): void
    {
        Schema::dropIfExists('__TABLE_NAME__');
    }
};