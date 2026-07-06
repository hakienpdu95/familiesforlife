<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Generates breadcrumb trails from the current route name.
 *
 * Algorithm:
 *  1. Strip skip_root_segments (e.g. 'backend') from the route name.
 *  2. Split remaining segments. The last segment is a CRUD action when it
 *     matches ['index','show','create','edit','store','update','destroy'].
 *  3. Walk each resource segment, linking to its .index route when available.
 *  4. For show/edit actions, append the model's display name from route params.
 *  5. Append 'Tạo mới' / 'Chỉnh sửa' for create/edit action labels.
 */
class Breadcrumbs
{
    /** Actions that consume a route model binding for the previous resource. */
    private const MODEL_ACTIONS = ['show', 'edit', 'update', 'destroy'];

    /** All CRUD action names (last segment gets special treatment). */
    private const CRUD_ACTIONS = ['index', 'show', 'create', 'edit', 'store', 'update', 'destroy'];

    public function generate(): array
    {
        $route = request()->route();
        if (! $route) {
            return [];
        }

        $routeName = $route->getName() ?? '';

        $homeCrumb = [
            'label'  => 'Trang chủ',
            'url'    => route('backend.dashboard'),
            'active' => false,
        ];

        if (! $routeName || $routeName === 'backend.dashboard') {
            return [array_merge($homeCrumb, ['active' => true])];
        }

        $crumbs = [$homeCrumb];

        // Strip configured root prefixes (e.g. 'backend')
        $skipRoots    = (array) config('breadcrumbs.skip_root_segments', ['backend']);
        $parts        = explode('.', $routeName);
        $routePrefix  = '';

        foreach ($skipRoots as $skip) {
            if (! empty($parts) && $parts[0] === $skip) {
                $routePrefix = $routePrefix ? $routePrefix . '.' . $skip : $skip;
                array_shift($parts);
            }
        }

        if (empty($parts)) {
            return [array_merge($homeCrumb, ['active' => true])];
        }

        $labels     = config('breadcrumbs.segments', []);
        $modelAttrs = config('breadcrumbs.model_name_attributes', ['full_name', 'name', 'title', 'subject', 'label']);
        $params     = $route->parameters();

        // Classify last segment as CRUD action or just a named page
        $lastPart      = end($parts);
        $isAction      = in_array($lastPart, self::CRUD_ACTIONS, true);
        $resourceParts = $isAction ? array_slice($parts, 0, -1) : $parts;
        $actionPart    = $isAction ? $lastPart : null;

        $currentPrefix = $routePrefix;
        $count         = count($resourceParts);

        foreach ($resourceParts as $idx => $part) {
            $currentPrefix = $currentPrefix ? $currentPrefix . '.' . $part : $part;
            $isLast        = ($idx === $count - 1);
            $label         = $labels[$part] ?? Str::title(str_replace(['-', '_'], ' ', $part));

            if (! $isLast) {
                // Intermediate segment – link to its .index route if available
                $indexRoute = $currentPrefix . '.index';
                $url        = Route::has($indexRoute) ? $this->safeRoute($indexRoute, $params) : null;
                $crumbs[]   = ['label' => $label, 'url' => $url, 'active' => false];
                continue;
            }

            // ── Last resource segment ─────────────────────────────────────

            $needsModel = $actionPart && in_array($actionPart, self::MODEL_ACTIONS, true);

            if ($needsModel) {
                // Link the resource list, then show model display name
                $indexRoute = $currentPrefix . '.index';
                if (Route::has($indexRoute)) {
                    $crumbs[] = ['label' => $label, 'url' => $this->safeRoute($indexRoute, $params), 'active' => false];
                }
                $modelName    = $this->extractModelName($params, $modelAttrs) ?? $label;
                $modelIsLast  = ! in_array($actionPart, ['edit', 'update'], true);
                $crumbs[]     = ['label' => $modelName, 'url' => null, 'active' => $modelIsLast];

            } elseif ($actionPart === 'create') {
                // Link the resource list, add 'Tạo mới' after the loop
                $indexRoute = $currentPrefix . '.index';
                if (Route::has($indexRoute)) {
                    $crumbs[] = ['label' => $label, 'url' => $this->safeRoute($indexRoute, $params), 'active' => false];
                } else {
                    $crumbs[] = ['label' => $label, 'url' => null, 'active' => false];
                }

            } else {
                // Terminal page (index or named leaf page with no action)
                $crumbs[] = ['label' => $label, 'url' => null, 'active' => ($actionPart === null)];
            }
        }

        // Append action label for create / edit (not for index/show/destroy)
        if ($actionPart !== null
            && array_key_exists($actionPart, $labels)
            && $labels[$actionPart] !== null
        ) {
            $crumbs[] = ['label' => $labels[$actionPart], 'url' => null, 'active' => true];
        }

        // Guarantee the last crumb is always marked active
        if (! empty($crumbs)) {
            $crumbs[count($crumbs) - 1]['active'] = true;
        }

        return $crumbs;
    }

    private function safeRoute(string $name, array $currentParams): ?string
    {
        try {
            $routeObj = Route::getRoutes()->getByName($name);
            if (! $routeObj) {
                return null;
            }
            // Only pass params the target route actually declares, avoiding extra query-string pollution
            $needed = array_intersect_key($currentParams, array_flip($routeObj->parameterNames()));
            return route($name, $needed);
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractModelName(array $params, array $attrs): ?string
    {
        foreach (array_reverse($params) as $param) {
            if (! is_object($param)) {
                continue;
            }
            foreach ($attrs as $attr) {
                $value = $param->{$attr} ?? null;
                if ($value) {
                    return (string) $value;
                }
            }
        }

        return null;
    }
}
