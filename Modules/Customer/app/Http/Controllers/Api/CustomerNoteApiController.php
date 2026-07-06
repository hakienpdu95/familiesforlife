<?php

namespace Modules\Customer\Http\Controllers\Api;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Actions\Notes\DestroyNoteAction;
use Modules\Customer\Actions\Notes\StoreNoteAction;
use Modules\Customer\Actions\Notes\TogglePinNoteAction;
use Modules\Customer\Actions\Notes\UpdateNoteAction;
use Modules\Customer\Data\Requests\StoreNoteData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerNote;

class CustomerNoteApiController extends Controller
{
    public function store(
        Request $request,
        Customer $customer,
        StoreNoteAction $action,
    ): JsonResponse {
        $this->authorize('view', $customer);

        $data = StoreNoteData::validateAndCreate($request->all());
        $note = $action->handle($customer, $data);

        return response()->json(['ok' => true, 'note' => $note], 201);
    }

    public function update(
        Request $request,
        Customer $customer,
        CustomerNote $note,
        UpdateNoteAction $action,
    ): JsonResponse {
        $this->assertNoteOwnership($customer, $note);

        $data        = StoreNoteData::validateAndCreate($request->all());
        $updatedNote = $action->handle($note, $data);

        return response()->json(['ok' => true, 'note' => $updatedNote]);
    }

    public function destroy(
        Request $request,
        Customer $customer,
        CustomerNote $note,
        DestroyNoteAction $action,
    ): JsonResponse {
        $this->assertNoteOwnership($customer, $note);

        $action->handle($note);

        return response()->json(['ok' => true]);
    }

    public function togglePin(
        Request $request,
        Customer $customer,
        CustomerNote $note,
        TogglePinNoteAction $action,
    ): JsonResponse {
        $this->authorize('view', $customer);
        abort_unless($note->customer_id === $customer->id, 404);

        $updatedNote = $action->handle($note);

        return response()->json(['ok' => true, 'is_pinned' => $updatedNote->is_pinned]);
    }

    private function assertNoteOwnership(Customer $customer, CustomerNote $note): void
    {
        abort_unless($note->customer_id === $customer->id, 404);

        $user     = auth()->user();
        $isAuthor = (int) $note->author_id === $user->id;
        $canEdit  = $user->can(PermissionEnum::CUSTOMERS_EDIT->value);

        abort_unless($isAuthor || $canEdit, 403);
    }
}
