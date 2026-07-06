<?php

namespace Modules\Customer\Http\Controllers\Api;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Actions\LogActivityAction;
use Modules\Customer\Data\CustomerActivityData;
use Modules\Customer\Data\Requests\StoreActivityData;
use Modules\Customer\Enums\CustomerActivityType;
use Modules\Customer\Models\Customer;

class CustomerActivityApiController extends Controller
{
    public function store(
        Request $request,
        Customer $customer,
        LogActivityAction $action,
    ): JsonResponse {
        $this->authorize('view', $customer);
        abort_unless($request->user()->can(PermissionEnum::CUSTOMERS_EDIT->value), 403);

        $typeValues = collect(CustomerActivityType::cases())->map(fn ($c) => $c->value)->all();
        $request->merge(['type' => (int) $request->input('type')]);
        $request->validate([
            'type' => 'required|integer|in:' . implode(',', $typeValues),
        ]);

        $data = StoreActivityData::validateAndCreate($request->all());
        $user = $request->user();

        $activity = $action->handle(new CustomerActivityData(
            customerId:      $customer->id,
            orgId:           $customer->organization_id,
            type:            $data->type,
            title:           $data->title,
            description:     $data->description,
            outcome:         $data->outcome,
            scheduledAt:     $data->scheduled_at,
            completedAt:     $data->completed_at ?? now()->toDateTimeString(),
            durationMinutes: $data->duration_minutes,
            actorId:         $user->id,
            actorName:       $user->name,
        ));

        return response()->json(['ok' => true, 'activity' => $activity], 201);
    }
}
