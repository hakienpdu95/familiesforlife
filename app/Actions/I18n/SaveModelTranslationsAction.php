<?php

namespace App\Actions\I18n;

use App\Support\I18n\PortalLocale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;

/** spec/I18n_Specification.md §5.7 — lưu tab bản dịch; toàn bộ trường rỗng = xoá dòng dịch. */
class SaveModelTranslationsAction
{
    use AsAction;

    public static function rules(array $fieldRules): array
    {
        $rules = ['translations' => ['nullable', 'array']];

        foreach (PortalLocale::prefixed() as $locale) {
            foreach ($fieldRules as $field => $fieldRule) {
                $rules["translations.{$locale}.{$field}"] = $fieldRule;
            }
        }

        return $rules;
    }

    public function handle(Model $model, ?array $input): void
    {
        foreach (PortalLocale::prefixed() as $locale) {
            $data = Arr::only($input[$locale] ?? [], $model::translatable());
            $data = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $data);

            if (collect($data)->filter(fn ($value) => filled($value))->isEmpty()) {
                $model->translations()->where('locale', $locale)->delete();

                continue;
            }

            $model->translations()->updateOrCreate(['locale' => $locale], $data);
        }
    }
}
