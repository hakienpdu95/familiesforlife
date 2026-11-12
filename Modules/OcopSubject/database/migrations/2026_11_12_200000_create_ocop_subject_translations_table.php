<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** spec/I18n_Specification.md §5.3 — bản dịch chủ thể OCOP; cột lẻ ocop_subjects.name_en chuyển sang ocop_subject_translations(locale=en) rồi bị xoá. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_subject_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocop_subject_id')->constrained('ocop_subjects')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 255)->nullable();
            $table->longText('story')->nullable();
            $table->timestamps();

            $table->unique(['ocop_subject_id', 'locale'], 'uq_ocop_subject_translations_locale');
        });

        if (! Schema::hasColumn('ocop_subjects', 'name_en')) {
            return;
        }

        DB::transaction(function (): void {
            $now = now();

            DB::table('ocop_subjects')
                ->whereNotNull('name_en')
                ->whereRaw("TRIM(name_en) <> ''")
                ->orderBy('id')
                ->select(['id', 'name_en'])
                ->chunkById(500, function ($subjects) use ($now): void {
                    DB::table('ocop_subject_translations')->insert($subjects->map(fn ($subject) => [
                        'ocop_subject_id' => $subject->id,
                        'locale' => 'en',
                        'name' => trim($subject->name_en),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all());
                });
        });

        $moved = DB::table('ocop_subject_translations')->where('locale', 'en')->whereNotNull('name')->count();
        $source = DB::table('ocop_subjects')->whereNotNull('name_en')->whereRaw("TRIM(name_en) <> ''")->count();

        if ($moved !== $source) {
            throw new RuntimeException("ocop_subjects.name_en: chuyển {$moved}/{$source} dòng — dừng, không xoá cột.");
        }

        Schema::table('ocop_subjects', function (Blueprint $table) {
            $table->dropColumn('name_en');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ocop_subjects', 'name_en')) {
            Schema::table('ocop_subjects', function (Blueprint $table) {
                $table->string('name_en', 255)->nullable()->after('name');
            });
        }

        DB::table('ocop_subject_translations')
            ->where('locale', 'en')
            ->whereNotNull('name')
            ->orderBy('id')
            ->each(fn ($translation) => DB::table('ocop_subjects')
                ->where('id', $translation->ocop_subject_id)
                ->update(['name_en' => $translation->name]));

        Schema::dropIfExists('ocop_subject_translations');
    }
};
