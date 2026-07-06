<?php
namespace Modules\Customer\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Customer\Models\Customer;

class ListCustomersHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['display_name', 'lifecycle_stage', 'last_activity_at', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListCustomersQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        // crossOrgCapable → bỏ qua OrganizationScope (super-admin xem tất cả tổ chức),
        // ngược lại giữ nguyên scope mặc định (khoá vào org hiện tại của user).
        $q = $query->crossOrgCapable
            ? Customer::withoutTenant()->select('customers.*')
            : Customer::query()->select('customers.*');

        $q->with(['source:id,label,icon', 'assignee:id,name', 'tags:id,name,color']);

        if ($query->crossOrgCapable) {
            $q->with('organization:id,name');
            if ($query->organizationId !== null) {
                $q->where('customers.organization_id', $query->organizationId);
            }
        }

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('customers.display_name', 'like', $term)
                    ->orWhere('customers.primary_email', 'like', $term)
                    ->orWhere('customers.primary_phone', 'like', $term)
                    ->orWhere('customers.company_name', 'like', $term)
                    ->orWhere('customers.tax_code', 'like', $term);
            });
        }

        if ($query->type !== null)       $q->where('customers.customer_type', $query->type);
        if ($query->stage !== null)      $q->where('customers.lifecycle_stage', $query->stage);
        if ($query->sourceId !== null)   $q->where('customers.source_id', $query->sourceId);
        if ($query->assignedTo !== null) $q->where('customers.assigned_to', $query->assignedTo);
        if ($query->province !== null && $query->province !== '') {
            $q->where('customers.province_code', $query->province);
        }
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('customers.created_at', '>=', $query->dateFrom);
        }
        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('customers.created_at', '<=', $query->dateTo);
        }
        if ($query->tagId !== null) {
            $q->whereHas('tags', fn ($t) => $t->where('customer_tags.id', $query->tagId));
        }

        $q->orderBy('customers.' . $sortField, $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
