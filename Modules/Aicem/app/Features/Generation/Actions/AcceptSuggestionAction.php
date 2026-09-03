<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Contracts\AicemSubjectResolver;
use Modules\Aicem\Enums\SuggestionStatus;
use Modules\Aicem\Features\Generation\Exceptions\SuggestionAlreadyDecidedException;
use Modules\Aicem\Features\Generation\Exceptions\SuggestionStaleException;
use Modules\Aicem\Models\AicemSuggestion;

/**
 * Action DUY NHẤT được phép ghi suggestion đã accept vào subject thật (qua resolver → action gốc
 * của module chỉđịnh). Bắt buộc chạy guard staleness trong transaction có lockForUpdate trên
 * subject TRƯỚC khi ghi — spec/AICEM_Technical_Specification.md mục 9.1/10.
 */
class AcceptSuggestionAction
{
    use AsAction;

    public function handle(AicemSuggestion $suggestion, int $userId): AicemSuggestion
    {
        // QUAN TRỌNG: khi phát hiện stale, transaction vẫn PHẢI commit để trạng thái `stale` được
        // lưu lại (mục 9.1) — nếu throw ngay trong DB::transaction(), Laravel rollback toàn bộ,
        // xoá luôn chính cái update(['status' => Stale]) vừa gọi. Do đó transaction luôn return
        // bình thường (commit), lỗi được ném RA NGOÀI sau khi đã commit, qua biến tham chiếu.
        $staleMessage = null;

        $result = DB::transaction(function () use ($suggestion, $userId, &$staleMessage) {
            /** @var AicemSuggestion $suggestion */
            $suggestion = AicemSuggestion::query()->lockForUpdate()->findOrFail($suggestion->id);

            if (! in_array($suggestion->status, [SuggestionStatus::Pending, SuggestionStatus::Stale], true)) {
                throw new SuggestionAlreadyDecidedException(
                    "Đề xuất đã được quyết định trước đó ({$suggestion->status->label()})."
                );
            }

            $run          = $suggestion->generationRun;
            $registry     = config("aicem_subjects.{$run->subject_type}", []);
            $subjectClass = $registry['model'];

            $subject = $subjectClass::query()->lockForUpdate()->findOrFail($run->subject_id);

            /** @var AicemSubjectResolver $resolver */
            $resolver = app($registry['resolver']);

            if ($suggestion->block_id !== null) {
                $block         = collect($resolver->blocks($subject))->keyBy('block_id')->get($suggestion->block_id);
                $editableTypes = $registry['block_editable_types'] ?? [];

                if (! $block || ! in_array($block['type'], $editableTypes, true)) {
                    $suggestion->update(['status' => SuggestionStatus::Stale]);
                    $staleMessage = 'Block đã bị xoá/thay đổi, hãy chạy lại AI để có đề xuất mới.';

                    return $suggestion;
                }

                $currentText = (string) $block['body'];
            } else {
                $fields      = $resolver->fields($subject);
                $currentText = (string) ($fields[$suggestion->field] ?? '');
            }

            if ($currentText !== $suggestion->original_text) {
                $suggestion->update(['status' => SuggestionStatus::Stale]);
                $staleMessage = 'Nội dung đã thay đổi từ lúc AI phân tích (giá trị hiện tại: "'
                    . \Illuminate\Support\Str::limit($currentText, 100) . '"). Không thể tự ghi đè — '
                    . 'hãy chạy lại AI để có đề xuất mới dựa trên nội dung hiện tại.';

                return $suggestion;
            }

            if ($suggestion->field !== null) {
                $resolver->applyFieldSuggestion($subject, $suggestion->field, $suggestion->suggested_text, $userId);
            } else {
                $resolver->applyBlockSuggestion($subject, $suggestion->block_id, $suggestion->suggested_text, $userId);
            }

            $suggestion->update([
                'status'     => SuggestionStatus::Accepted,
                'decided_by' => $userId,
                'decided_at' => now(),
            ]);

            return $suggestion;
        });

        if ($staleMessage !== null) {
            throw new SuggestionStaleException($staleMessage);
        }

        return $result;
    }
}
