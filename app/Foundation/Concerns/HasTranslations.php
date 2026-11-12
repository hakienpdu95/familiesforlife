<?php

namespace App\Foundation\Concerns;

use App\Support\I18n\PortalLocale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * spec/I18n_Specification.md §5.3 — cột gốc = bản `vi`, bản locale khác nằm ở bảng
 * `{model}_translations`. Model khai báo `translatable()` và `translationModel()`.
 */
trait HasTranslations
{
    abstract public static function translatable(): array;

    abstract public static function translationModel(): string;

    public static function bootHasTranslations(): void
    {
        static::addGlobalScope('portal_translation', function (Builder $builder): void {
            $portal = app(PortalLocale::class);

            if ($portal->active() && ! $portal->isDefault()) {
                $builder->with(['translations' => fn ($q) => $q->where('locale', $portal->current())]);
            }
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(static::translationModel(), $this->translationForeignKey());
    }

    public function translationForeignKey(): string
    {
        return $this->getForeignKey();
    }

    public function translationFor(string $locale): ?Model
    {
        if ($locale === PortalLocale::default()) {
            return null;
        }

        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    public function hasTranslation(string $locale, ?array $required = null): bool
    {
        $translation = $this->translationFor($locale);

        if (! $translation) {
            return false;
        }

        foreach ($required ?? [static::translatable()[0]] as $attribute) {
            if (blank($translation->getAttribute($attribute))) {
                return false;
            }
        }

        return true;
    }

    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        if (! in_array($key, static::translatable(), true)) {
            return $value;
        }

        $portal = app(PortalLocale::class);
        if (! $portal->active() || $portal->isDefault() || ! $this->exists) {
            return $value;
        }

        $translated = $this->translationFor($portal->current())?->getAttribute($key);

        return filled($translated) ? $translated : $value;
    }

    public function originalAttribute(string $key): mixed
    {
        return parent::getAttributeValue($key);
    }
}
