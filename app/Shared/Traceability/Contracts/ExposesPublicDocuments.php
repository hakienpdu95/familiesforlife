<?php

namespace App\Shared\Traceability\Contracts;

interface ExposesPublicDocuments
{
    /** @return string[] collection hồ sơ được phép mở xem từ trang QR khi đối tượng đang công khai */
    public function publicDocumentCollections(): array;
}
