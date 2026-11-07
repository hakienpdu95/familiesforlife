<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Shared\Traceability\Models\QrReport;
use App\Shared\Traceability\Models\QrScan;
use App\Shared\Traceability\Notifications\QrReportSubmittedNotification;
use App\Shared\Traceability\TraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TraceQrController extends Controller
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python|headless/i';

    public function __invoke(Request $request, TraceabilityService $traceability, string $qrCode): View
    {
        $identity = $traceability->resolveQrCode($qrCode);

        $this->recordScan($request, $identity->id);

        $page = $traceability->present($identity);

        return view($page['view'], [
            'brand' => config('app.site_name'),
            'reportReasons' => QrReport::REASONS,
            ...$page,
        ]);
    }

    public function report(Request $request, TraceabilityService $traceability, string $qrCode): RedirectResponse
    {
        $identity = $traceability->resolveQrCode($qrCode);

        if (filled($request->input('website'))) {
            return redirect()->route('traceability.qr', $qrCode)->with('report_sent', true);
        }

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(QrReport::REASONS))],
            'content' => ['required', 'string', 'min:10', 'max:1000'],
            'contact' => ['nullable', 'string', 'max:120'],
        ], [
            'reason.required' => 'Vui lòng chọn lý do phản ánh.',
            'content.required' => 'Vui lòng mô tả nội dung phản ánh.',
            'content.min' => 'Nội dung phản ánh cần tối thiểu :min ký tự.',
            'content.max' => 'Nội dung phản ánh tối đa :max ký tự.',
            'contact.max' => 'Thông tin liên hệ tối đa :max ký tự.',
        ]);

        $report = QrReport::create([
            ...$data,
            'qr_identity_id' => $identity->id,
            'visitor_hash' => QrReport::visitorHash($request->ip()),
            'status' => 'new',
        ]);

        $this->notifyReviewers($report->setRelation('identity', $identity), $traceability->present($identity)['title'] ?? '');

        return redirect()->to(route('traceability.qr', $qrCode).'#phan-anh')->with('report_sent', true);
    }

    private function notifyReviewers(QrReport $report, string $targetLabel): void
    {
        $reviewers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                RoleEnum::SUPER_ADMIN->value,
                RoleEnum::CONTENT_MODERATOR->value,
                RoleEnum::CONTENT_HEAD->value,
                RoleEnum::OPS->value,
            ]))
            ->get();

        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new QrReportSubmittedNotification($report, $targetLabel));
        }
    }

    private function recordScan(Request $request, int $identityId): void
    {
        $userAgent = (string) $request->userAgent();

        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent)) {
            return;
        }

        $hash = QrReport::visitorHash($request->ip());

        dispatch(function () use ($identityId, $hash, $userAgent): void {
            $recent = QrScan::query()
                ->where('qr_identity_id', $identityId)
                ->where('visitor_hash', $hash)
                ->where('scanned_at', '>=', now()->subMinutes(QrScan::DEDUPE_MINUTES))
                ->exists();

            if (! $recent) {
                QrScan::create([
                    'qr_identity_id' => $identityId,
                    'visitor_hash' => $hash,
                    'user_agent' => mb_substr($userAgent, 0, 255),
                    'scanned_at' => now(),
                ]);
            }
        })->afterResponse();
    }
}
