<?php

declare(strict_types=1);

/**
 * Hand-written PSR-4 autoloader for Tesla App.
 *
 * Replaces the Composer-generated autoloader as the class-loading mechanism
 * for all project code (course requirement: the autoloader must be written by
 * the team — see MMN cm3-poo slide 31 and cm4-php-avance slides 18-19).
 * Composer is kept only to install the dev toolchain (PHPUnit, PHPStan).
 *
 * Maps a namespace prefix to a base directory, then resolves the remaining
 * namespace segments to a file path (PSR-4). Paths are built from __DIR__ so
 * this file is self-contained (no dependency on config.php / BASE_PATH).
 */

/**
 * Resolves a fully qualified class name to its file and loads it.
 *
 * On the first matching prefix, strips it, converts the remaining namespace
 * separators to directory separators and appends ".php". A miss is a silent
 * no-op so other autoloaders on the SPL queue (e.g. PHPUnit's own, in dev)
 * still get a turn.
 */
function teslapp_autoload(string $class): void
{
    /** @var array<string, string> $prefixes PSR-4 prefix => base directory */
    $prefixes = [
        'Teslapp\\Controllers\\' => __DIR__ . '/../Controllers/',
        'Teslapp\\Models\\' => __DIR__ . '/../Models/',
        'Teslapp\\Utils\\' => __DIR__ . '/../Utils/',
        'Teslapp\\Tests\\' => __DIR__ . '/../../tests/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }

        // First matching prefix wins; a missing file surfaces as a regular
        // "class not found" error at the call site (PSR-4 behaviour).
        return;
    }
}

spl_autoload_register('teslapp_autoload');

// Global helpers (e(), toLocalTime()) are plain functions, not classes, so
// they cannot be autoloaded: load them unconditionally (replaces the former
// composer.json "files" entry).
require_once __DIR__ . '/../Utils/Functions.php';
