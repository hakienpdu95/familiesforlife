<?php

namespace App\Actions;

use DateTimeImmutable;
use DateTimeZone;
use Lorisleiva\Actions\Concerns\AsAction;

class BuildTimezoneOptionsAction
{
    use AsAction;

    public function handle(): array
    {
        $now = new DateTimeImmutable('now');
        $groups = [];

        foreach (timezone_identifiers_list() as $identifier) {
            $offset = (new DateTimeZone($identifier))->getOffset($now);
            $region = str_contains($identifier, '/') ? strstr($identifier, '/', true) : 'UTC';

            $groups[$region][] = [
                'value' => $identifier,
                'city' => str_replace(['/', '_'], [' / ', ' '], str_contains($identifier, '/') ? substr($identifier, strlen($region) + 1) : $identifier),
                'offset' => $offset,
                'utc' => $this->formatOffset($offset),
            ];
        }

        foreach ($groups as &$items) {
            usort($items, fn ($a, $b) => [$a['offset'], $a['city']] <=> [$b['offset'], $b['city']]);
        }

        ksort($groups);

        return $groups;
    }

    private function formatOffset(int $seconds): string
    {
        $sign = $seconds < 0 ? '-' : '+';
        $seconds = abs($seconds);

        return sprintf('UTC%s%02d:%02d', $sign, intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }
}
