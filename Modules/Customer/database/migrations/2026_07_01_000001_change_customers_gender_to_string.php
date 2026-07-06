<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customers.gender được migrate ban đầu là tinyInteger, nhưng form
 * (_form.blade.php) và show.blade.php luôn dùng mã chữ 'M'/'F'/'O'.
 * Insert 'M' vào cột tinyInteger gây lỗi "Incorrect integer value" trên
 * MySQL strict mode — đổi cột sang string(1) cho khớp dữ liệu thực tế.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('gender', 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->tinyInteger('gender')->nullable()->change();
        });
    }
};
