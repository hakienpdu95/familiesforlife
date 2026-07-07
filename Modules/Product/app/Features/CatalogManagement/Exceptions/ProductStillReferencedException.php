<?php

namespace Modules\Product\Features\CatalogManagement\Exceptions;

class ProductStillReferencedException extends \RuntimeException
{
    public function __construct(public readonly int $usedInArticlesCount)
    {
        parent::__construct(
            "Không thể xoá sản phẩm này — đang được {$usedInArticlesCount} bài viết tham chiếu. "
            . 'Hãy rà soát/thay thế trước, hoặc chuyển trạng thái sang "Ngừng kinh doanh" thay vì xoá.'
        );
    }
}
