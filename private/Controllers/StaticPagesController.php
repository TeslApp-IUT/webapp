<?php

declare(strict_types=1);

namespace Teslapp\Controllers;

/**
 * Static Pages Controller
 *
 * This controller handles the display of all static pages in the application
 * that do not require any special processing or authentication.
 *
 * Note: View templates are located in private/Views/static/ and are
 * rendered using the factored layout in private/Views/layout.php.
 */
final class StaticPagesController
{
    /**
     * Displays the home page
     *
     * @return void
     */
    public function home(): void
    {
        require_once __DIR__ . '/../Views/static/home.php';
    }

    /**
     * Displays the site map
     *
     * @return void
     */
    public function sitemap(): void
    {
        require_once __DIR__ . '/../Views/static/sitemap.php';
    }

    /**
     * Displays the legal notice
     *
     * @return void
     */
    public function legal(): void
    {
        require_once __DIR__ . '/../Views/static/legal.php';
    }

    /**
     * Displays the privacy policy
     *
     * @return void
     */
    public function privacy(): void
    {
        require_once __DIR__ . '/../Views/static/privacy.php';
    }

    /**
     * Displays the 404 error page
     *
     * @return void
     */
    public function notFound(): void
    {
        require_once __DIR__ . '/../Views/static/not-found.php';
    }
}
