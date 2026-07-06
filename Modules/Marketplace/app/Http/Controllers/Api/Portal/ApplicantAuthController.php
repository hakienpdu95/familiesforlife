<?php
namespace Modules\Marketplace\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Marketplace\Actions\Portal\RegisterApplicantAction;
use Modules\Marketplace\Data\Requests\LoginApplicantData;
use Modules\Marketplace\Data\Requests\RegisterApplicantData;
use Modules\Marketplace\Http\Resources\MktApplicantResource;
use Modules\Marketplace\Models\MktApplicant;

class ApplicantAuthController extends Controller
{
    public function register(Request $request, RegisterApplicantAction $action): JsonResponse
    {
        $data      = RegisterApplicantData::validateAndCreate($request->all());
        $applicant = $action->handle($data);

        Auth::guard('marketplace')->login($applicant);

        return response()->json([
            'message'   => 'Đăng ký thành công.',
            'applicant' => new MktApplicantResource($applicant),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = LoginApplicantData::validateAndCreate($request->all());

        $applicant = MktApplicant::where('email', $data->email)->first();

        if (! $applicant || ! Hash::check($data->password, $applicant->password_hash)) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng.'], 422);
        }

        if ($applicant->status->value === 'suspended') {
            return response()->json(['message' => 'Tài khoản đã bị đình chỉ.'], 403);
        }

        Auth::guard('marketplace')->login($applicant, $data->remember);

        return response()->json([
            'message'   => 'Đăng nhập thành công.',
            'applicant' => new MktApplicantResource($applicant),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('marketplace')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }
}
