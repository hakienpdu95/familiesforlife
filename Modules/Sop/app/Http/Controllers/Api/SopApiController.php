<?php

namespace Modules\Sop\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sop\Http\Resources\SopProcessListResource;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Queries\ListSopProcessesHandler;
use Modules\Sop\Queries\ListSopProcessesQuery;
use Spatie\Permission\Models\Role;

class SopApiController extends Controller
{
    public function approved(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SopProcess::class);

        $q = trim($request->input('q', ''));

        $sops = SopProcess::approved()
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%");
            }))
            ->orderBy('code')
            ->limit(30)
            ->get(['id', 'uuid', 'code', 'title']);

        return response()->json($sops->map(fn ($s) => [
            'id'    => $s->id,
            'uuid'  => $s->uuid,
            'code'  => $s->code,
            'title' => $s->title,
            'label' => "{$s->code} — {$s->title}",
        ]));
    }

    public function usersSearch(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        $users = User::when($q, fn ($query) => $query->where(function ($sub) use ($q) {
            $sub->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        }))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email']);

        return response()->json($users->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->name,
            'meta' => $u->email,
        ]));
    }

    public function roles(): JsonResponse
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return response()->json($roles->map(fn ($r) => [
            'id'   => $r->id,
            'name' => $r->name,
            'meta' => 'Vai trò',
        ]));
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SopProcess::class);

        $q         = trim($request->input('q', ''));
        $excludeId = $request->integer('exclude');

        $sops = SopProcess::when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%");
            }))
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('code')
            ->limit(20)
            ->get(['id', 'uuid', 'code', 'title', 'status']);

        return response()->json($sops->map(fn ($s) => [
            'id'     => $s->id,
            'uuid'   => $s->uuid,
            'code'   => $s->code,
            'title'  => $s->title,
            'status' => $s->status?->value,
            'label'  => "{$s->code} — {$s->title}",
        ]));
    }

    public function index(Request $request, ListSopProcessesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', SopProcess::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListSopProcessesQuery(
            page:         max(1, $request->integer('page', 1)),
            perPage:      min(100, max(5, $request->integer('size', 25))),
            sortField:    $sortField,
            sortDir:      $sortDir,
            search:       $request->input('search'),
            status:       $request->input('status'),
            type:         $request->input('type'),
            departmentId: $request->integer('department_id') ?: null,
            branchId:     $request->integer('branch_id') ?: null,
            ownerId:      $request->integer('owner_id') ?: null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => SopProcessListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
