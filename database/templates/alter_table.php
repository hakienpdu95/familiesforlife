<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('__TABLE_NAME__', function (Blueprint $table) {
            __UP_BODY__
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE_NAME__', function (Blueprint $table) {
            __DOWN_BODY__
        });
    }
};