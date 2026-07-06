<?php
namespace Modules\Customer\Queries;

use App\Shared\Contracts\QueryInterface;

class ListCustomersQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page        = 1,
        public readonly int     $perPage     = 25,
        public readonly string  $sortField   = 'created_at',
        public readonly string  $sortDir     = 'desc',
        public readonly ?string $search      = null,
        public readonly ?int    $type        = null,
        public readonly ?int    $stage       = null,
        public readonly ?int    $sourceId    = null,
        public readonly ?int    $assignedTo  = null,
        public readonly ?string $province    = null,
        public readonly ?int    $tagId       = null,
        public readonly ?string $dateFrom    = null,
        public readonly ?string $dateTo      = null,
        // true chỉ khi user hiện tại không gắn với 1 org cố định (super-admin) —
        // xem CustomerApiController::index(). Không bao giờ tin trực tiếp từ request.
        public readonly bool    $crossOrgCapable = false,
        public readonly ?int    $organizationId  = null,
    ) {}
}
