<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Models\OcopSubject;
use Normalizer;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportOcopProductsAction
{
    use AsAction;

    private const HEADER_SCAN_LIMIT = 20;

    private const COLUMNS = [
        'product' => ['tên sản phẩm', 'tên sản phẩm ocop'],
        'subject' => ['tên cơ sở sản xuất', 'tên chủ thể', 'chủ thể', 'cơ sở sản xuất'],
        'star' => ['hạng sao', 'số sao', 'xếp hạng'],
    ];

    private array $slugs = [];

    public function handle(string $path, string $provinceCode): array
    {
        $rows = (new FastExcel)->withoutHeaders()->import($path)->values();

        [$headerIndex, $columns] = $this->locateHeader($rows);
        if ($headerIndex === null) {
            return ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => ['Không tìm thấy dòng tiêu đề có các cột "Tên sản phẩm" và "Tên cơ sở sản xuất".']];
        }

        $subjects = $this->subjectLookup($provinceCode);
        $products = OcopProduct::whereIn('ocop_subject_id', collect($subjects)->filter()->pluck('id'))
            ->get()
            ->keyBy(fn (OcopProduct $p) => $this->productKey($p->ocop_subject_id, $p->name));
        $this->slugs = array_flip(OcopProduct::withTrashed()->pluck('slug')->all());

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $headerIndex, $columns, $subjects, $products, &$created, &$updated, &$unchanged, &$errors): void {
            foreach ($rows->slice($headerIndex + 1) as $index => $row) {
                $rowNumber = $index + 1;
                $name = Str::limit($this->clean($row[$columns['product']] ?? ''), 150, '');
                $subjectName = $this->clean($row[$columns['subject']] ?? '');
                $star = isset($columns['star']) ? $this->parseStar($row[$columns['star']] ?? null) : null;

                if ($name === '' && $subjectName === '') {
                    continue;
                }

                if ($name === '' || $subjectName === '') {
                    $errors[] = "Dòng {$rowNumber}: thiếu ".($name === '' ? 'tên sản phẩm' : 'tên cơ sở sản xuất').'.';

                    continue;
                }

                $subject = $subjects[$this->normalize($subjectName)] ?? null;
                if ($subject === null) {
                    $errors[] = "Dòng {$rowNumber}: không tìm thấy chủ thể \"{$subjectName}\" thuộc tỉnh/thành đã chọn ({$name}).";

                    continue;
                }
                if ($subject === false) {
                    $errors[] = "Dòng {$rowNumber}: có nhiều chủ thể cùng tên \"{$subjectName}\" trong tỉnh/thành, cần gán thủ công ({$name}).";

                    continue;
                }

                $key = $this->productKey($subject->id, $name);
                $product = $products->get($key);

                if ($product) {
                    $product->fill(['star_rating' => $star ?? $product->star_rating, 'updated_by' => auth()->id()]);
                    if ($product->isDirty('star_rating')) {
                        $product->save();
                        $updated++;
                    } else {
                        $unchanged++;
                    }

                    continue;
                }

                $products->put($key, OcopProduct::create([
                    'name' => $name,
                    'slug' => $this->uniqueSlug($name),
                    'star_rating' => $star,
                    ...SyncOcopSubjectSnapshotAction::attributesFor($subject),
                    'created_by' => auth()->id(),
                ]));
                $created++;
            }
        });

        return compact('created', 'updated', 'unchanged', 'errors');
    }

    private function locateHeader(Collection $rows): array
    {
        foreach ($rows->take(self::HEADER_SCAN_LIMIT) as $index => $row) {
            $columns = [];
            foreach ($row as $col => $cell) {
                $label = $this->normalize($cell);
                foreach (self::COLUMNS as $field => $aliases) {
                    if (! isset($columns[$field]) && in_array($label, $aliases, true)) {
                        $columns[$field] = $col;
                    }
                }
            }

            if (isset($columns['product'], $columns['subject'])) {
                return [$index, $columns];
            }
        }

        return [null, []];
    }

    private function subjectLookup(string $provinceCode): array
    {
        $lookup = [];

        OcopSubject::where('province_code', $provinceCode)
            ->get(['id', 'name', 'address', 'province_code', 'province_name', 'ward_code', 'ward_name'])
            ->each(function (OcopSubject $subject) use (&$lookup): void {
                $key = $this->normalize($subject->name);
                $lookup[$key] = isset($lookup[$key]) ? false : $subject;
            });

        return $lookup;
    }

    private function parseStar(mixed $value): ?int
    {
        if (! preg_match('/\d+/', (string) $value, $m)) {
            return null;
        }

        $star = (int) $m[0];

        return in_array($star, [3, 4, 5], true) ? $star : null;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'san-pham-ocop';
        $slug = $base;
        $i = 2;

        while (isset($this->slugs[$slug])) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        $this->slugs[$slug] = true;

        return $slug;
    }

    private function productKey(int $subjectId, string $name): string
    {
        return $subjectId.'|'.$this->normalize($name);
    }

    private function clean(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = Normalizer::normalize((string) $value, Normalizer::FORM_C) ?: (string) $value;

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private function normalize(mixed $value): string
    {
        return mb_strtolower($this->clean($value));
    }
}
