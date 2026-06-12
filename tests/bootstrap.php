<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap: wires the hand-written autoloader so application and
 * test classes (Teslapp\*) resolve under PHPUnit. PHPUnit's own classes are
 * loaded by vendor/autoload.php, required by the phpunit binary itself.
 */

require_once __DIR__ . '/../private/config/autoloader.php';
