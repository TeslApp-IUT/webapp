<!--
    Partial : Header invité

    En-tête affiché aux visiteurs non connectés :
    - Logo TeslApp
    - Navigation principale
    - Connexion via « Login with Tesla »
    - Menu mobile responsive
-->
<header class="header">
    <div class="container">
        <!-- Logo TeslApp -->
        <a href="/site/home" class="logo">
            <img src="/_assets/images/Logo.svg" alt="TeslApp">
        </a>

        <!-- Navigation principale -->
        <nav class="nav">
            <a href="/site/home" class="nav-link">Accueil</a>
            <a href="/site/sitemap" class="nav-link">Plan du site</a>
            <a href="/site/legal" class="nav-link">Mentions légales</a>
        </nav>

        <!-- Action : connexion Tesla -->
        <div class="header-actions">
            <a href="/auth/tesla/login" class="btn-primary">Se connecter avec Tesla</a>
        </div>

        <!-- Bouton du menu mobile -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Ouvrir le menu" aria-expanded="false">
            <span class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
    </div>
</header>

<!-- Menu mobile -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <a href="/site/home" class="logo">
            <img src="/_assets/images/Logo.svg" alt="TeslApp">
        </a>
        <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Fermer le menu">
            <img src="/_assets/images/croix-light.svg" alt="" aria-hidden="true">
        </button>
    </div>

    <div class="mobile-menu-content">
        <div class="mobile-menu-section-title">NAVIGATION</div>
        <nav class="mobile-menu-nav">
            <a href="/site/home" class="mobile-menu-link">Accueil</a>
            <a href="/site/sitemap" class="mobile-menu-link">Plan du site</a>
            <a href="/site/legal" class="mobile-menu-link">Mentions légales</a>
        </nav>

        <div class="mobile-menu-actions">
            <a href="/auth/tesla/login" class="btn-primary">Se connecter avec Tesla</a>
        </div>
    </div>
</div>
