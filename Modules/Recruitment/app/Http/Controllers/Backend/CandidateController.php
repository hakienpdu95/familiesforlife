<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Recruitment\Actions\Backend\StoreCandidateAction;
use Modules\Recruitment\Data\Requests\StoreCandidateData;
use Modules\Recruitment\Enums\CandidateSource;
use Modules\Recruitment\Enums\CandidateStatus;
use Modules\Recruitment\Models\RcCandidate;

class CandidateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RcCandidate::class, 'candidate');
    }

    public function index(): View
    {
        $statuses = collect(CandidateStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $sources = collect(CandidateSource::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('recruitment::candidates.index', compact('statuses', 'sources'));
    }

    public function create(): View
    {
        $sources = collect(CandidateSource::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $users = User::query()->orderBy('name')->get(['id', 'name']);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('recruitment::candidates.create', compact('sources', 'users', 'organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, StoreCandidateAction $action): RedirectResponse
    {
        $data = StoreCandidateData::validateAndCreate($request->all());
        $candidate = $action->handle($data);

        return redirect()
            ->route('backend.recruitment.candidates.show', $candidate)
            ->with('success', "Đã thêm ứng viên {$candidate->full_name}");
    }

    public function show(RcCandidate $candidate): View
    {
        $candidate->load([
            'applications.currentStage',
            'createdBy',
            'notes.createdBy',
            'attachments.uploadedBy',
        ]);

        $noteTypes   = collect(\Modules\Recruitment\Enums\NoteType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $fileTypes   = collect(\Modules\Recruitment\Enums\AttachmentFileType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        return view('recruitment::candidates.show', compact('candidate', 'noteTypes', 'fileTypes'));
    }

    public function edit(RcCandidate $candidate): View
    {
        $sources = collect(CandidateSource::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $users = User::query()->orderBy('name')->get(['id', 'name']);

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('recruitment::candidates.edit', compact('candidate', 'sources', 'users', 'organizations', 'orgLocked'));
    }

    public function update(Request $request, RcCandidate $candidate, StoreCandidateAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'full_name'        => ['required', 'string', 'max:150'],
            'email'            => ['required', 'email', 'max:150'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'current_title'    => ['nullable', 'string', 'max:150'],
            'current_company'  => ['nullable', 'string', 'max:150'],
            'years_experience' => ['nullable', 'integer', 'min:0'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'date_of_birth'    => ['nullable', 'date'],
            'skills'           => ['nullable', 'string'],
            'source'           => ['required', 'string'],
            'referred_by'      => ['nullable', 'integer', 'exists:users,id'],
            'linkedin_url'     => ['nullable', 'url', 'max:300'],
            'portfolio_url'    => ['nullable', 'url', 'max:300'],
        ]);

        $candidate->update(array_merge($validated, ['updated_by' => auth()->id()]));

        return redirect()
            ->route('backend.recruitment.candidates.show', $candidate)
            ->with('success', 'Đã cập nhật thông tin ứng viên');
    }

    public function destroy(RcCandidate $candidate): RedirectResponse
    {
        $candidate->delete();

        return redirect()
            ->route('backend.recruitment.candidates.index')
            ->with('success', 'Đã xóa ứng viên');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }
}
