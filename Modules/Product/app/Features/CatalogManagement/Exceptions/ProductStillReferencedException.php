<?php

namespace Modules\Product\Features\CatalogManagement\Exceptions;

class ProductStillReferencedException extends \RuntimeException
{
    public function __construct(public readonly int $usedInArticlesCount)
    {
        parent::__construct(
            __('Không thể xoá sản phẩm này — đang được :count bài viết tham chiếu. Hãy rà soát/thay thế trước, hoặc chuyển trạng thái sang "Ngừng kinh doanh" thay vì xoá.', ['count' => $usedInArticlesCount])
        );
    }
}
