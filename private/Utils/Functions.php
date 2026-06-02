<?php

declare(strict_types=1);

/**
 * Global utility functions for Tesla App.
 *
 * Loaded via Composer autoload (key "files"), so available everywhere
 * without `use`. Intended only for very short cross-functional helpers (escape sequences, etc.);
 * all structured logic must go through a PSR-4 class in Teslapp\Utils.
 */
if (!function_exists('e')) {
    /**
     * Escapes a string for safe insertion into HTML (anti-XSS).
     *
     * Flags: ENT_QUOTES (escapes " and ' — safe in attributes),
     * ENT_SUBSTITUTE (invalid UTF-8 → U+FFFD instead of an empty string),
     * ENT_HTML5 (HTML5 entity set).
     *
     * @param string|null $value Value to escape (null treated as '')
     * @return string The escaped value
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
