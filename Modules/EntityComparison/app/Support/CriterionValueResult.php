<?php

namespace Modules\EntityComparison\Support;

use Illuminate\Support\Collection;
use LogicException;
use Modules\EntityComparison\Models\CriterionOption;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §4/§11.1 — Value Object trả về bởi
 * CriterionValueResolver::read(). KHÔNG để code gọi nơi khác tự
 * `if ($criterion->type === CriterionType::MultiSelect)` — dùng isMultiple()/asScalar()/
 * asOptions() thay thế.
 */
final readonly class CriterionValueResult
{
    private function __construct(
        private mixed $scalar,
        private ?Collection $options = null,
    ) {}

    /** string|float|bool|\Illuminate\Support\Carbon|array{min:float,max:float}|\Modules\EntityComparison\Models\CriterionOption|null */
    public static function scalar(mixed $value): self
    {
        return new self($value);
    }

    /** @param  Collection<int, CriterionOption>  $options */
    public static function multiple(Collection $options): self
    {
        return new self(null, $options);
    }

    public function isMultiple(): bool
    {
        return $this->options !== null;
    }

    public function asScalar(): mixed
    {
        if ($this->isMultiple()) {
            throw new LogicException('CriterionValueResult::asScalar() gọi trên kết quả multi_select — dùng asOptions() thay.');
        }

        return $this->scalar;
    }

    /** @return Collection<int, CriterionOption> */
    public function asOptions(): Collection
    {
        if (! $this->isMultiple()) {
            throw new LogicException('CriterionValueResult::asOptions() gọi trên kết quả scalar — dùng asScalar() thay.');
        }

        return $this->options;
    }

    public function isEmpty(): bool
    {
        return $this->isMultiple() ? $this->options->isEmpty() : $this->scalar === null;
    }
}
