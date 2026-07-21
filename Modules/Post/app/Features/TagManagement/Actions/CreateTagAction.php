<?php

namespace Modules\Post\Features\TagManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\TagManagement\Data\TagData;
use Modules\Post\Models\PostTag;

class CreateTagAction
{
    use AsAction;

    public function handle(TagData $data): PostTag
    {
        return PostTag::create([
            'name' => $data->name,
            'slug' => $this->uniqueSlug($data->name),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (PostTag::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
