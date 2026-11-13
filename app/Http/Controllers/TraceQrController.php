<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Shared\Traceability\Actions\RecordQrScanAction;
use App\Shared\Traceability\Models\QrReport;
use App\Shared\Traceability\Notifications\QrReportSubmittedNotification;
use App\Shared\Traceability\TraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraceQrController extends Controller
{
    public function __invoke(Request $request, TraceabilityService $traceability, string $qrCode): View
    {
        $identity = $traceability->resolveQrCode($qrCode);

        $traceability->localize($identity);
        $scan = RecordQrScanAction::run($request, $identity->id);

        $page = $traceability->present($identity);

        return view($page['view'], [
            'brand' => config('app.site_name'),
            'reportReasons' => array_map('__', QrReport::REASONS),
            'reportHints' => array_map(fn (array $hints) => array_map('__', $hints), QrReport::REASON_HINTS),
            'scan' => $scan,
            ...$page,
        ]);
    }

    public function document(TraceabilityService $traceability, string $qrCode, string $media): StreamedResponse
    {
        $file = $traceability->publicDocument($traceability->resolveQrCode($qrCode), $media);

        abort_if(! $file || ! Storage::disk($file->disk)->exists($file->getPathRelativeToRoot()), 404);

        $extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
        $name = Str::slug(pathinfo($file->name ?: $file->file_name, PATHINFO_FILENAME)) ?: 'ho-so';

        return Storage::disk($file->disk)->response($file->getPathRelativeToRoot(), $name.($extension ? '.'.$extension : ''), [
            'Content-Type' => $file->mime_type,
            'Cache-Control' => 'private, max-age=600',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function report(Request $request, TraceabilityService $traceability, string $qrCode): RedirectResponse
    {
        $identity = $traceability->resolveQrCode($qrCode);

        if (filled($request->input('website'))) {
            return redirect()->to(route('traceability.qr', $qrCode).'#phan-anh')->with('report_sent', ['ref' => null, 'reason' => null, 'contact' => false]);
        }

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(QrReport::REASONS))],
            'content' => ['required', 'string', 'min:10', 'max:1000'],
            'contact' => ['nullable', 'string', 'max:120'],
        ], [
            'reason.required' => __('Vui lòng chọn lý do phản ánh.'),
            'content.required' => __('Vui lòng mô tả nội dung phản ánh.'),
            'content.min' => __('Nội dung phản ánh cần tối thiểu :min ký tự.'),
            'content.max' => __('Nội dung phản ánh tối đa :max ký tự.'),
            'contact.max' => __('Thông tin liên hệ tối đa :max ký tự.'),
        ]);

        $report = QrReport::create([
            ...$data,
            'qr_identity_id' => $identity->id,
            'visitor_hash' => QrReport::visitorHash($request->ip()),
            'status' => 'new',
        ]);

        $this->notifyReviewers($report->setRelation('identity', $identity), $traceability->present($identity)['title'] ?? '');

        return redirect()->to(route('traceability.qr', $qrCode).'#phan-anh')->with('report_sent', [
            'ref' => $report->ref(),
            'reason' => $report->reasonLabel(),
            'reason_key' => $report->reason,
            'contact' => filled($report->contact),
        ]);
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
}
