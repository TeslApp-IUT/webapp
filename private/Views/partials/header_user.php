<?php
/**
 * Partial: Logged-in User Header.
 *
 * Header displayed to authenticated users:
 * - TeslApp logo
 * - Extended navigation (adds the dashboard link)
 * - Profile button showing the email initial (placeholder until the auth lot
 *   wires the user session)
 * - Logout button (POST + CSRF, prepared for the future /auth/logout route)
 * - Responsive mobile menu (same IDs as header_guest so common.js drives it)
 *
 * Selected by layout.php when the view sets `$header = 'user'`.
 *
 * @var string|null $userEmail Optional email passed by the view; falls back to the
 *                             session (filled by the auth lot) then to '' (neutral avatar).
 **/

/* Email source: explicit view variable -> session (future auth) -> '' (neutral empty avatar). */
$userEmail = $userEmail ?? ($_SESSION['user']['email'] ?? '');
$userInitial = $userEmail !== '' ? mb_strtoupper(mb_substr($userEmail, 0, 1)) : '';
?>
<header class="header">
    <div class="container">
        <!-- TeslApp Logo -->
        <a href="/" class="logo">
            <img src="/_assets/images/Logo.svg" alt="TeslApp">
        </a>

        <!-- Main navigation -->
        <nav class="nav" aria-label="navigation principale">
            <a href="/" class="nav-link">Accueil</a>
            <a href="/sitemap" class="nav-link">Plan du site</a>
            <a href="/legal" class="nav-link">Mentions légales</a>
            <a href="/vehicle/select" class="nav-link">Tableau de bord</a>
        </nav>

        <!-- Header actions: logout + profile -->
        <div class="header-actions">
            <!-- Logout (POST + CSRF). The /auth/logout route is wired in the auth lot. -->
            <form method="post" action="/auth/logout" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn-icon logout-icon" aria-label="Se déconnecter">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </form>

            <!-- Profile (email initial; a full profile menu will come with the auth lot) -->
            <button type="button" class="btn-profile" aria-label="Profil utilisateur">
                <span class="profile-initials"><?= e($userInitial) ?></span>
            </button>
        </div>

        <!-- Mobile menu button -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobileMenu">
            <span class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
    </div>
</header>

<!-- Mobile menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <a href="/" class="logo">
            <img src="/_assets/images/Logo.svg" alt="TeslApp">
        </a>
        <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Fermer le menu">
            <img src="/_assets/images/croix-light.svg" alt="" aria-hidden="true">
        </button>
    </div>

    <div class="mobile-menu-content">
        <div class="mobile-menu-section-title">NAVIGATION</div>
        <nav class="mobile-menu-nav" aria-label="navigation">
            <a href="/dashboard/overview" class="mobile-menu-link">Tableau de bord</a>
            <a href="/" class="mobile-menu-link">Accueil</a>
            <a href="/sitemap" class="mobile-menu-link">Plan du site</a>
            <a href="/legal" class="mobile-menu-link">Mentions légales</a>
        </nav>

        <div class="mobile-menu-actions">
            <form method="post" action="/auth/logout" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</div>