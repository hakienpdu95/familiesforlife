<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assessment\Enums\VerificationStatus;
use Modules\Assessment\Models\IdentityVerification;

class IdentityVerificationController extends Controller
{
    // ── GET /passport/verify ─────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $user = $request->user();

        $verifications = IdentityVerification::where('user_id', $user->id)
            ->where('status', VerificationStatus::Verified->value)
            ->orderByDesc('verified_at')
            ->get();

        return view('assessment::passport.verify.index', compact(
            'user', 'verifications'
        ));
    }
}
