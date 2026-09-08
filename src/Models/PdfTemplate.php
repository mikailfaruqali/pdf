<?php

declare(strict_types=1);

namespace PDF\Models;

use Illuminate\Support\Facades\DB;
use Throwable;

class PdfTemplate
{
    public static function resolveOptionsForView(string $view, ?string $locale = NULL): array
    {
        $resolvedLocale = $locale ?? (function_exists('app') ? app()->getLocale() : 'en');

        try {
            $rows = DB::table('pdf_templates')
                ->whereIn('view', [$view, '*', 'all'])
                ->whereIn('locale', [$resolvedLocale, '*', 'all'])
                ->get(['view', 'locale', 'options']);
        } catch (Throwable) {
            return [];
        }

        if ($rows->isEmpty()) {
            return [];
        }

        $decode = function ($row): array {
            if (! $row) {
                return [];
            }

            $raw = $row->options;
            if (is_array($raw)) {
                return $raw;
            }

            $decoded = is_string($raw) ? json_decode($raw, TRUE) : NULL;

            return is_array($decoded) ? $decoded : [];
        };

        $globalAll = $decode($rows->first(fn ($r): bool => in_array($r->view, ['*', 'all'], TRUE) && in_array($r->locale, ['*', 'all'], TRUE)));
        $globalLoc = $decode($rows->first(fn ($r): bool => in_array($r->view, ['*', 'all'], TRUE) && $r->locale === $resolvedLocale));
        $viewAll = $decode($rows->first(fn ($r): bool => $r->view === $view && in_array($r->locale, ['*', 'all'], TRUE)));
        $viewLoc = $decode($rows->first(fn ($r): bool => $r->view === $view && $r->locale === $resolvedLocale));

        return array_merge($globalAll, $globalLoc, $viewAll, $viewLoc);
    }
}
