<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Assessment\Models\PassportEntry;
use Modules\Assessment\Models\WorkforceProfile;

class PassportController extends Controller
{
    /**
     * GET /passport — Personal Dashboard
     * Accessible by both free and org_member users (§6.6)
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $entries = PassportEntry::where('user_id', $user->id)
            ->orderByDesc('snapshot_at')
            ->with(['domainScores', 'certifications', 'sandboxSummaries'])
            ->get();

        // Current in-progress chapter: the workforce profile in the user's active org
        $currentProfile = null;
        if ($user->isOrgMember() && $user->current_org_id) {
            $currentProfile = WorkforceProfile::withoutTenant()
                ->where('user_id', $user->id)
                ->where('organization_id', $user->current_org_id)
                ->with('organization')
                ->first();
        }

        return view('assessment::passport.index', compact('user', 'entries', 'currentProfile'));
    }

    /**
     * GET /passport/{uuid} — Detail of one passport entry
     */
    public function show(Request $request, PassportEntry $passport): View
    {
        $this->authorize('view', $passport);

        $passport->load([
            'domainScores',
            'certifications',
            'impactHighlights',
            'sandboxSummaries',
        ]);

        return view('assessment::passport.show', [
            'entry' => $passport,
            'user'  => $request->user(),
        ]);
    }

    /**
     * PUT /passport/{uuid}/note — Update personal note (mutable field)
     */
    public function updateNote(Request $request, PassportEntry $passport): RedirectResponse
    {
        $this->authorize('update', $passport);

        $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        // Use direct query to bypass immutability observer (note is mutable)
        PassportEntry::where('id', $passport->id)
            ->update(['personal_note' => $request->input('note')]);

        return back()->with('success', 'Ghi chú đã được lưu.');
    }

    /**
     * PUT /passport/{uuid}/visibility — Change visibility (mutable field)
     */
    public function updateVisibility(Request $request, PassportEntry $passport): RedirectResponse
    {
        $this->authorize('update', $passport);

        $request->validate([
            'visibility' => ['required', 'in:private,link_only,public'],
        ]);

        $data = ['visibility' => $request->input('visibility')];

        if ($request->input('visibility') === 'link_only' && !$passport->share_token) {
            $data['share_token']            = Str::random(48);
            $data['share_token_expires_at'] = now()->addYear();
        }

        if ($request->input('visibility') === 'private') {
            $data['share_token']            = null;
            $data['share_token_expires_at'] = null;
        }

        PassportEntry::where('id', $passport->id)->update($data);

        return back()->with('success', 'Quyền riêng tư đã được cập nhật.');
    }

    /**
     * POST /passport/{uuid}/share — Generate share link
     */
    public function generateShareLink(Request $request, PassportEntry $passport): RedirectResponse
    {
        $this->authorize('update', $passport);

        PassportEntry::where('id', $passport->id)->update([
            'visibility'             => 'link_only',
            'share_token'            => Str::random(48),
            'share_token_expires_at' => now()->addYear(),
        ]);

        return back()->with('success', 'Link chia sẻ đã được tạo.');
    }

    /**
     * DELETE /passport/{uuid}/share — Revoke share link
     */
    public function revokeShareLink(Request $request, PassportEntry $passport): RedirectResponse
    {
        $this->authorize('update', $passport);

        PassportEntry::where('id', $passport->id)->update([
            'visibility'             => 'private',
            'share_token'            => null,
            'share_token_expires_at' => null,
        ]);

        return back()->with('success', 'Link chia sẻ đã được thu hồi.');
    }

    /**
     * GET /passport/current — Redirect đến workforce profile nếu đang là org_member
     */
    public function current(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isOrgMember()) {
            return redirect()->route('backend.workforce.me');
        }

        return redirect()->route('passport.index');
    }
}
