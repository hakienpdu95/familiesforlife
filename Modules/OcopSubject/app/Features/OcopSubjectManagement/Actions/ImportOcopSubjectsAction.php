<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopSubject\Models\OcopSubject;
use Normalizer;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportOcopSubjectsAction
{
    use AsAction;

    private const WARD_PREFIX_REGEX = '/^(phường|xã|thị trấn|đặc khu)\s+/u';

    public function handle(string $path, string $provinceCode): array
    {
        $provinceName = Province::where('province_code', $provinceCode)->value('name');
        [$wardsByName, $wardsByShortName] = $this->wardLookup($provinceCode);

        $existing = OcopSubject::where('province_code', $provinceCode)
            ->get(['name', 'ward_code'])
            ->mapWithKeys(fn (OcopSubject $s) => [$this->key($s->name, $s->ward_code) => true])
            ->all();

        $rows = (new FastExcel)->withoutHeaders()->import($path);

        $created = 0;
        $duplicates = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $provinceCode, $provinceName, $wardsByName, $wardsByShortName, &$existing, &$created, &$duplicates, &$errors): void {
            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $rowNumber = $index + 1;
                $name = $this->clean($row[0] ?? '');
                $wardInput = $this->clean($row[1] ?? '');

                if ($name === '' && $wardInput === '') {
                    continue;
                }

                if ($name === '') {
                    $errors[] = "Dòng {$rowNumber}: thiếu tên cơ sở sản xuất.";

                    continue;
                }

                $normalized = $this->normalize($wardInput);
                $ward = $wardsByName[$normalized] ?? $wardsByShortName[$this->stripPrefix($normalized)] ?? null;

                if (! $ward) {
                    $errors[] = "Dòng {$rowNumber}: không tìm thấy phường/xã \"{$wardInput}\" thuộc {$provinceName} ({$name}).";

                    continue;
                }

                $key = $this->key($name, $ward->ward_code);
                if (isset($existing[$key])) {
                    $duplicates++;

                    continue;
                }

                OcopSubject::create([
                    'name' => Str::limit($name, 255, ''),
                    'province_code' => $provinceCode,
                    'province_name' => $provinceName,
                    'ward_code' => $ward->ward_code,
                    'ward_name' => $ward->name,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);

                $existing[$key] = true;
                $created++;
            }
        });

        return compact('created', 'duplicates', 'errors');
    }

    private function wardLookup(string $provinceCode): array
    {
        $byName = [];
        $byShortName = [];

        Ward::where('province_code', $provinceCode)
            ->get(['ward_code', 'name'])
            ->each(function (Ward $ward) use (&$byName, &$byShortName): void {
                $normalized = $this->normalize($ward->name);
                $byName[$normalized] = $ward;

                $short = $this->stripPrefix($normalized);
                $byShortName[$short] = isset($byShortName[$short]) ? false : $ward;
            });

        return [$byName, array_filter($byShortName)];
    }

    private function clean(mixed $value): string
    {
        $value = Normalizer::normalize((string) $value, Normalizer::FORM_C) ?: (string) $value;

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private function normalize(string $value): string
    {
        return mb_strtolower($this->clean($value));
    }

    private function stripPrefix(string $normalized): string
    {
        return preg_replace(self::WARD_PREFIX_REGEX, '', $normalized);
    }

    private function key(string $name, ?string $wardCode): string
    {
        return $this->normalize($name).'|'.$wardCode;
    }
}
