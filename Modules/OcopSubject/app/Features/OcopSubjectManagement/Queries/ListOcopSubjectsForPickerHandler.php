<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\OcopSubject\Models\OcopSubject;

class ListOcopSubjectsForPickerHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        return OcopSubject::query()
            ->where(fn ($q) => $q->where('is_active', true)
                ->when($query->includeId, fn ($w) => $w->orWhere('id', $query->includeId)))
            ->orderBy('name')
            ->get(['id', 'name', 'tax_code', 'organization_type', 'address', 'ward_name', 'province_name'])
            ->map(fn (OcopSubject $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'tax_code' => $p->tax_code,
                'type' => $p->organization_type->label(),
                'address' => $p->fullAddress(),
            ]);
    }
}
