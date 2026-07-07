<?php

namespace Modules\Post\Features\ArticleAuthoring\Exceptions;

class ProductBlockValidationException extends \RuntimeException
{
    /** @param string[] $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' | ', $errors));
    }
}
