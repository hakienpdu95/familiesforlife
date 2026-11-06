<?php

use App\Support\Username;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 30)->nullable()->unique()->after('email')->comment('Tên đăng nhập: a-z 0-9 . _ - (lowercase)');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('username')->comment('Số điện thoại (0xxxxxxxxx)');
            }
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable()->after('phone')->comment('Chức danh');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('department', 100)->nullable()->comment('Phòng ban')->change();

            $drop = array_values(array_filter(['branch_id', 'department_id'], fn ($c) => Schema::hasColumn('users', $c)));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        DB::table('users')->whereNull('username')->orderBy('id')->select('id', 'email')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['username' => Username::suggest($user->email, $user->id)]);
        });
    }

    public function down(): void {}
};
