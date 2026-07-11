<?php

namespace Modules\Post\Features\ArticleAuthoring\Exceptions;

class CannotDeleteMainLocaleException extends \RuntimeException
{
    public function __construct(string $locale)
    {
        parent::__construct("Không thể xoá bản dịch \"{$locale}\" vì đang là ngôn ngữ chính — hãy đổi ngôn ngữ chính trước.");
    }
}
