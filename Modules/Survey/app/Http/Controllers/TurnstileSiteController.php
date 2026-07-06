<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveyTurnstileSite;

/**
 * Quản lý các "Turnstile site" — mỗi site = 1 widget Cloudflare Turnstile
 * của 1 domain bên ngoài (vd thuchocvn.vn). Nhiều survey dùng chung 1 site,
 * nên đây là màn hình quản trị riêng (không lồng trong từng survey).
 */
class TurnstileSiteController extends Controller
{
    public function index()
    {
        $this->authorize('survey.manage_tokens');

        $sites = SurveyTurnstileSite::withCount('surveys')->orderBy('name')->get();

        return view('survey::turnstile-sites.index', compact('sites'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'site_key'   => ['required', 'string', 'max:255'],
            'secret_key' => ['required', 'string', 'max:255'],
        ]);

        $site = SurveyTurnstileSite::create([
            'name'                 => $data['name'],
            'site_key'             => $data['site_key'],
            'secret_key_encrypted' => Crypt::encryptString($data['secret_key']),
            'is_active'            => true,
        ]);

        ActivityLogger::info('Survey', 'turnstile_site_created', $site, ['name' => $site->name]);

        return response()->json([
            'success' => true,
            'site'    => $this->payload($site),
            'message' => "Site \"{$site->name}\" đã được tạo.",
        ]);
    }

    public function update(Request $request, SurveyTurnstileSite $turnstileSite): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'site_key'   => ['required', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name'      => $data['name'],
            'site_key'  => $data['site_key'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $turnstileSite->is_active,
        ];

        if (!empty($data['secret_key'])) {
            $payload['secret_key_encrypted'] = Crypt::encryptString($data['secret_key']);
        }

        $turnstileSite->update($payload);

        ActivityLogger::info('Survey', 'turnstile_site_updated', $turnstileSite, ['name' => $turnstileSite->name]);

        return response()->json([
            'success' => true,
            'site'    => $this->payload($turnstileSite),
            'message' => "Site \"{$turnstileSite->name}\" đã được cập nhật.",
        ]);
    }

    public function reveal(SurveyTurnstileSite $turnstileSite): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        $secret = $turnstileSite->secretKey();

        if ($secret === null) {
            return response()->json(['error' => 'Không thể giải mã secret key.'], 422);
        }

        return response()->json(['plain' => $secret]);
    }

    public function destroy(SurveyTurnstileSite $turnstileSite): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        if ($turnstileSite->surveys()->exists()) {
            return response()->json([
                'error' => 'Không thể xóa — vẫn còn survey đang gắn với site này. Gỡ gán khỏi survey trước.',
            ], 422);
        }

        $name = $turnstileSite->name;
        $turnstileSite->delete();

        ActivityLogger::warning('Survey', 'turnstile_site_deleted', null, ['name' => $name]);

        return response()->json(['success' => true, 'message' => "Site \"{$name}\" đã bị xóa."]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function payload(SurveyTurnstileSite $site): array
    {
        return [
            'id'            => $site->id,
            'name'          => $site->name,
            'site_key'      => $site->site_key,
            'is_active'     => $site->is_active,
            'surveys_count' => $site->surveys_count ?? $site->surveys()->count(),
            'created_at'    => $site->created_at->format('d/m/Y H:i'),
        ];
    }
}
