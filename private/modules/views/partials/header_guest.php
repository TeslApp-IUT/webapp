<!-- 
    Partial : Header Invité
    
    En-tête affiché pour les utilisateurs non connectés.
    Contient :
    - Logo MedBoard
    - Menu de navigation principal
    - Bouton de changement de thème
    - Boutons de connexion et d'inscription
    - Menu mobile responsive
-->

<!-- En-tête principal -->
<header class="header">
    <div class="container">
        <!-- Logo MedBoard -->
        <a href="/site/home" class="logo">
            <img src="/_assets/images/LogoMedBoard.svg" alt="MedBoard" class="logo-light">
            <img src="/_assets/images/LogoMedBoardDarkMode.svg" alt="MedBoard" class="logo-dark">
        </a>

        <!-- Navigation principale -->
        <nav class="nav">
            <a href="/site/home" class="nav-link">Accueil</a>
            <a href="/site/sitemap" class="nav-link">Plan du site</a>
            <a href="/site/legal" class="nav-link">Mentions légales</a>
        </nav>

        <!-- Actions du header -->
        <div class="header-actions">
            <!-- Bouton de changement de thème -->
            <button class="btn-icon" id="themeToggle" aria-label="Changer le thème">
                <img src="/_assets/images/lune.svg" alt="" class="moon-icon" aria-hidden="true">
                <img src="/_assets/images/soleil.svg" alt="" class="sun-icon" aria-hidden="true">
            </button>
            <!-- Bouton de connexion -->
            <a href="/auth/login" class="btn-text">Se connecter</a>
            <!-- Bouton d'inscription -->
            <a href="/auth/register" class="btn-primary">Créer un compte</a>
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
    <!-- En-tête du menu mobile -->
    <div class="mobile-menu-header">
        <a href="/site/home" class="logo">
            <img src="/_assets/images/LogoMedBoard.svg" alt="MedBoard" class="logo-light">
            <img src="/_assets/images/LogoMedBoardDarkMode.svg" alt="MedBoard" class="logo-dark">
        </a>
        <!-- Bouton de fermeture -->
        <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Fermer le menu">
            <img src="/_assets/images/croix-light.svg" alt="" class="close-icon-light" aria-hidden="true">
            <img src="/_assets/images/croix-dark.svg" alt="" class="close-icon-dark" aria-hidden="true">
        </button>
    </div>

    <!-- Contenu du menu mobile -->
    <div class="mobile-menu-content">
        <!-- Section navigation -->
        <div class="mobile-menu-section-title">NAVIGATION</div>
        <nav class="mobile-menu-nav">
            <a href="/site/home" class="mobile-menu-link">Accueil</a>
            <a href="/site/sitemap" class="mobile-menu-link">Plan du site</a>
            <a href="/site/legal" class="mobile-menu-link">Mentions légales</a>
        </nav>

        <!-- Section changement de thème -->
        <div class="mobile-menu-theme">
            <span class="mobile-menu-theme-label">Thème</span>
            <button class="mobile-theme-toggle" id="mobileThemeToggle" aria-label="Changer le thème">
                <img src="/_assets/images/lune.svg" alt="" class="mobile-moon-icon" aria-hidden="true">
                <img src="/_assets/images/soleil.svg" alt="" class="mobile-sun-icon" aria-hidden="true">
                <span class="mobile-theme-text">Sombre</span>
            </button>
        </div>

        <!-- Actions du menu mobile -->
        <div class="mobile-menu-actions">
            <a href="/auth/login" class="btn-text">Se connecter</a>
            <a href="/auth/register" class="btn-primary">Créer un compte</a>
        </div>
    </div>
</div>
