<?php

namespace Modules\User\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\User\Actions\ActivateAccountAction;
use Modules\User\Actions\CreateAccountAction;
use Modules\User\Actions\DeactivateAccountAction;
use Modules\User\Actions\ListAccountsAction;
use Modules\User\Actions\SendPasswordSetupLinkAction;
use Modules\User\Actions\UpdateAccountAction;
use Modules\User\Data\CreateAccountData;
use Modules\User\Data\UpdateAccountData;
use Modules\User\Http\Resources\AccountListResource;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('user::index', ['labels' => RoleEnum::labels()]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'role' => ['nullable', 'string', 'in:'.implode(',', array_keys(RoleEnum::labels()))],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $sortRaw = $request->input('sort.0');

        $users = ListAccountsAction::run(
            $validated['search'] ?? null,
            $validated['role'] ?? null,
            $validated['status'] ?? null,
            is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name',
            is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc',
            (int) ($validated['size'] ?? 25),
            (int) ($validated['page'] ?? 1),
        );

        return response()->json([
            'data' => AccountListResource::collection($users->items()),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('user::create', ['labels' => RoleEnum::labels(assignableOnly: true)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = CreateAccountAction::run(CreateAccountData::validateAndCreate($request->all()));

        return redirect()->route('backend.users.index')
            ->with('success', "Đã tạo tài khoản {$user->email} và gửi email đặt mật khẩu.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('user::edit', [
            'account' => $user,
            'labels' => RoleEnum::labels(assignableOnly: true),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        UpdateAccountAction::run($user, UpdateAccountData::validateAndCreate($request->all()));

        return redirect()->route('backend.users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        DeactivateAccountAction::run($user);

        return redirect()->route('backend.users.index')->with('success', 'Đã vô hiệu hoá tài khoản.');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('activate', $user);

        ActivateAccountAction::run($user);

        return redirect()->route('backend.users.index')->with('success', 'Đã kích hoạt lại tài khoản.');
    }

    public function sendPasswordResetLink(User $user): RedirectResponse
    {
        $this->authorize('sendPasswordResetLink', $user);

        SendPasswordSetupLinkAction::run($user);

        return back()->with('success', "Đã gửi link đặt lại mật khẩu tới {$user->email}.");
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
        ])['ids'];

        $actor = $request->user();

        $deactivated = User::query()->whereIn('id', $ids)->get()
            ->filter(fn (User $user) => $actor->can('deactivate', $user))
            ->each(fn (User $user) => DeactivateAccountAction::run($user, 'bulk'));

        $skipped = count($ids) - $deactivated->count();

        return redirect()->route('backend.users.index')->with('success',
            "Đã vô hiệu hoá {$deactivated->count()} tài khoản.".($skipped > 0 ? " Bỏ qua {$skipped} tài khoản không đủ điều kiện." : ''));
    }
}
