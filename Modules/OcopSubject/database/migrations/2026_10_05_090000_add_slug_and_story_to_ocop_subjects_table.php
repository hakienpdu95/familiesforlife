<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ocop_subjects', 'slug') && Schema::hasColumn('ocop_subjects', 'story')) {
            return;
        }

        Schema::table('ocop_subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('ocop_subjects', 'slug')) {
                $table->string('slug', 255)->nullable()->after(Schema::hasColumn('ocop_subjects', 'name_en') ? 'name_en' : 'name');
            }
            if (! Schema::hasColumn('ocop_subjects', 'story')) {
                $table->longText('story')->nullable()->after('website');
            }
        });

        $used = [];
        DB::table('ocop_subjects')->orderBy('id')->get(['id', 'name'])->each(function ($row) use (&$used) {
            $base = Str::slug($row->name) ?: 'chu-the';
            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $used[$slug] = true;

            DB::table('ocop_subjects')->where('id', $row->id)->update(['slug' => $slug]);
        });

        Schema::table('ocop_subjects', function (Blueprint $table) {
            $table->string('slug', 255)->nullable(false)->change();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('ocop_subjects', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'story']);
        });
    }
};
