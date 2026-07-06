<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Assessment\Models\PassportEntry;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class PublicPassportController extends Controller
{
    /**
     * GET /p/{token} — Public profile page (no auth required)
     * token = share_token (link_only) or uuid (public)
     */
    public function show(string $token): View|Response
    {
        $entry = $this->resolveEntry($token);

        $entry->load(['domainScores', 'certifications', 'impactHighlights', 'sandboxSummaries']);

        $noindex = ($entry->visibility === 'link_only');

        return response()->view('assessment::passport.public', compact('entry', 'noindex'));
    }

    /**
     * GET /p/{token}/pdf — Public PDF (no auth required)
     */
    public function pdf(string $token)
    {
        $entry = $this->resolveEntry($token);

        $entry->load(['domainScores', 'certifications', 'impactHighlights', 'sandboxSummaries']);

        $filename = 'Passport_' . preg_replace('/[^A-Za-z0-9]/', '_', $entry->source_org_name ?? 'export') . '_' . substr($entry->uuid, 0, 8) . '.pdf';

        return Pdf::view('assessment::pdf.passport-entry', compact('entry'))
            ->format(Format::A4)
            ->download($filename);
    }

    /**
     * GET /passport/{passport}/pdf — Personal PDF (auth + owner via PassportController)
     * Called from PassportController but lives here for PDF logic.
     */
    public function personalPdf(PassportEntry $passport, Request $request)
    {
        $this->authorize('view', $passport);

        $passport->load(['domainScores', 'certifications', 'impactHighlights', 'sandboxSummaries']);

        $filename = 'Passport_' . preg_replace('/[^A-Za-z0-9]/', '_', $passport->source_org_name ?? 'export') . '_' . substr($passport->uuid, 0, 8) . '.pdf';

        return Pdf::view('assessment::pdf.passport-entry', [
            'entry'      => $passport,
            'showNote'   => true,
            'ownerName'  => $request->user()->name,
        ])
            ->format(Format::A4)
            ->download($filename);
    }

    // ─────────────────────────────────────────────────────────────────

    private function resolveEntry(string $token): PassportEntry
    {
        // Try share_token first (link_only), then uuid (public)
        $entry = PassportEntry::where('share_token', $token)
            ->orWhere('uuid', $token)
            ->first();

        abort_if(!$entry, 404);

        if ($entry->visibility === 'link_only') {
            abort_if($entry->share_token !== $token, 404);

            if ($entry->share_token_expires_at && $entry->share_token_expires_at->isPast()) {
                abort(410, 'Link chia sẻ này đã hết hạn.');
            }
        }

        abort_if($entry->visibility === 'private', 404);

        return $entry;
    }
}
