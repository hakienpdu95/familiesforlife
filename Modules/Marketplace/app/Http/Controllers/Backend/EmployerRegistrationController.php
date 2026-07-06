<?php

namespace Modules\Marketplace\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Marketplace\Actions\Backend\RegisterEmployerAction;
use Modules\Marketplace\Data\Requests\RegisterEmployerData;

class EmployerRegistrationController extends Controller
{
    public function create(): View
    {
        return view('marketplace::employer.register');
    }

    public function store(Request $request, RegisterEmployerAction $action): RedirectResponse
    {
        $data   = RegisterEmployerData::validateAndCreate($request->all());
        $result = $action->handle($data);

        return redirect()
            ->route('marketplace.employer.status')
            ->with('success', 'Đăng ký thành công! Chúng tôi sẽ xem xét và thông báo qua email trong 1-2 ngày làm việc.');
    }

    public function status(): View
    {
        return view('marketplace::employer.status');
    }
}
