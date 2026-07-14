<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Features\EventModeration\Data\EventData;
use Modules\Event\Models\Event;

/**
 * Staff tạo thẳng sự kiện trong dashboard (không qua form public — spec §4: quan hệ
 * EventSubmission optional, KHÔNG tạo dòng nào ở đây). Vẫn khởi tạo `status = Submitted`
 * (KHÔNG bỏ qua bước duyệt dù ai tạo) — giữ đúng 1 state machine duy nhất cho mọi nguồn dữ
 * liệu, tránh đặc cách "staff tạo thì tự động Published" phá vỡ nguyên tắc tách viết/duyệt
 * (spec §6.1, §3.1).
 */
class CreateEventAction
{
    use AsAction;

    public function __construct(private readonly BuildEventAttributesAction $buildAttributes) {}

    public function handle(EventData $data): Event
    {
        return Event::create([
            ...$this->buildAttributes->handle($data),
            'slug'        => $this->uniqueSlug($data->title),
            'poster_path' => $data->poster_path,
            'status'      => EventStatus::Submitted,
            'created_by'  => auth()->id(),
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 2;

        while (Event::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
