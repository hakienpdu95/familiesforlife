<?php

namespace Modules\N8n\Features\ConnectionManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\N8n\Features\ConnectionManagement\Actions\CreateN8nConnectionAction;
use Modules\N8n\Features\ConnectionManagement\Actions\DeleteN8nConnectionAction;
use Modules\N8n\Features\ConnectionManagement\Actions\RestoreN8nConnectionAction;
use Modules\N8n\Features\ConnectionManagement\Actions\RotateN8nConnectionSecretAction;
use Modules\N8n\Features\ConnectionManagement\Actions\UpdateN8nConnectionAction;
use Modules\N8n\Features\ConnectionManagement\Data\N8nConnectionData;
use Modules\N8n\Features\ConnectionManagement\Http\Requests\RotateN8nConnectionRequest;
use Modules\N8n\Features\ConnectionManagement\Http\Requests\StoreN8nConnectionRequest;
use Modules\N8n\Features\ConnectionManagement\Http\Requests\UpdateN8nConnectionRequest;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §6 — admin CRUD, gate bằng Platform Roles
 * (KHÔNG middleware 'tenant', đúng mẫu dashboard/platform-users/dashboard/subscription/admin).
 * `index` xem được bởi platform_ops HOẶC platform_viewer (super-admin bypass qua Gate::before);
 * mọi hành động ghi (store/update/destroy/restore/rotate) gate bằng `can:manage-n8n` ở route.
 */
class N8nConnectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            abort_unless($user?->isPlatformOps() || $user?->isPlatformViewer() || $user?->hasRole('super-admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        return view('n8n::connections.index');
    }

    public function create(): View
    {
        return view('n8n::connections.create');
    }

    public function store(StoreN8nConnectionRequest $request, CreateN8nConnectionAction $action): RedirectResponse
    {
        $data = N8nConnectionData::from($request->toData());
        $connection = $action->handle($data, $request->user()->id);

        // §3.2 — hiển thị plaintext 1 LẦN DUY NHẤT ngay sau khi tạo.
        return redirect()->route('backend.n8n.connections.index')->with('success', 'Đã tạo kết nối n8n mới.')->with('n8n_reveal', [
            'connection_id' => $connection->id,
            'inbound_token' => $connection->inbound_token,
            'inbound_secret' => $connection->inbound_secret,
        ]);
    }

    public function edit(N8nConnection $connection): View
    {
        return view('n8n::connections.edit', compact('connection'));
    }

    public function update(UpdateN8nConnectionRequest $request, N8nConnection $connection, UpdateN8nConnectionAction $action): RedirectResponse
    {
        $data = N8nConnectionData::from($request->toData());
        $action->handle($connection, $data, $request->user()->id);

        return redirect()->route('backend.n8n.connections.index')->with('success', 'Đã cập nhật kết nối.');
    }

    public function destroy(N8nConnection $connection, DeleteN8nConnectionAction $action): RedirectResponse
    {
        $action->handle($connection);

        return redirect()->route('backend.n8n.connections.index')->with('success', 'Đã xoá kết nối (mềm) — tên vẫn được giữ, không thể tái sử dụng cho kết nối mới.');
    }

    public function restore(string $connection, RestoreN8nConnectionAction $action): RedirectResponse
    {
        // Route model binding chuẩn KHÔNG dùng được ở đây — global scope của SoftDeletes loại
        // trừ bản ghi đã xoá mềm, đúng là đối tượng route này cần thao tác tới.
        $model = N8nConnection::withTrashed()->where('uuid', $connection)->firstOrFail();
        $action->handle($model);

        return redirect()->route('backend.n8n.connections.index')->with('success', "Đã khôi phục kết nối \"{$model->name}\".");
    }

    public function rotate(RotateN8nConnectionRequest $request, N8nConnection $connection, RotateN8nConnectionSecretAction $action): JsonResponse
    {
        $rotated = $action->handle(
            connection: $connection,
            rotateInboundToken: $request->boolean('rotate_inbound_token'),
            rotateInboundSecret: $request->boolean('rotate_inbound_secret'),
            rotateOutboundSecret: $request->boolean('rotate_outbound_secret'),
            updatedBy: $request->user()->id,
        );

        // §3.2 — trả CHỈ giá trị VỪA xoay, không trả lại field không liên quan.
        return response()->json(['rotated' => $rotated]);
    }
}
