<?php
namespace Modules\Customer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Http\Resources\CustomerListResource;
use Modules\Customer\Models\Customer;
use Modules\Customer\Queries\ListCustomersHandler;
use Modules\Customer\Queries\ListCustomersQuery;

class CustomerApiController extends Controller
{
    public function index(Request $request, ListCustomersHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        // Không gắn với 1 org cố định (super-admin) → được phép xem xuyên tổ chức.
        // Tính ở server, không tin cờ client gửi lên.
        $crossOrgCapable = $request->user()->organization_id === null;

        $query = new ListCustomersQuery(
            page:            max(1, $request->integer('page', 1)),
            perPage:         min(100, max(5, $request->integer('size', 25))),
            sortField:       $sortField,
            sortDir:         $sortDir,
            search:          $request->input('search') ?: null,
            type:            $request->filled('type')       ? (int) $request->input('type')        : null,
            stage:           $request->filled('stage')      ? (int) $request->input('stage')       : null,
            sourceId:        $request->filled('source_id')  ? (int) $request->input('source_id')   : null,
            assignedTo:      $request->filled('assigned_to')? (int) $request->input('assigned_to') : null,
            province:        $request->input('province_code') ?: null,
            tagId:           $request->filled('tag_id')     ? (int) $request->input('tag_id')      : null,
            dateFrom:        $request->input('date_from') ?: null,
            dateTo:          $request->input('date_to')   ?: null,
            crossOrgCapable: $crossOrgCapable,
            organizationId:  $crossOrgCapable && $request->filled('organization_id')
                ? (int) $request->input('organization_id') : null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => CustomerListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $term = $request->input('q', '');
        $results = Customer::query()
            ->select('id', 'display_name', 'primary_email', 'primary_phone', 'customer_type')
            ->when($term, fn ($q) => $q->where('display_name', 'like', '%' . $term . '%'))
            ->orderBy('display_name')
            ->limit(20)
            ->get();

        return response()->json($results->map(fn ($c) => [
            'id'    => $c->id,
            'text'  => $c->display_name,
            'email' => $c->primary_email,
            'phone' => $c->primary_phone,
        ]));
    }
}
