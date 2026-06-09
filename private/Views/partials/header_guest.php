<!--
    Partial: Guest Header

    Header displayed to visitors who are not logged in:
    - TeslApp logo
    - Main navigation
    - Log in via "Login with Tesla"
    - Responsive mobile menu
-->
<header class="header">
  <div class="container">
    <!-- TeslApp Logo -->
    <a href="/" class="logo">
      <img src="/_assets/images/Logo.svg" alt="TeslApp">
    </a>

    <!-- Main navigation -->
    <nav class="nav" aria-label="navigation principale">
      <a href="/site/home" class="nav-link">Accueil</a>
      <a href="/site/sitemap" class="nav-link">Plan du site</a>
      <a href="/site/legal" class="nav-link">Mentions légales</a>
    </nav>

    <!-- Action: Tesla connection -->
    <div class="header-actions">
      <?php require __DIR__ . '/../partials/login_with_tesla.php'; ?>
    </div>

    <!-- Mobile Menu Button -->
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
      <a href="/site/home" class="mobile-menu-link">Accueil</a>
      <a href="/site/sitemap" class="mobile-menu-link">Plan du site</a>
      <a href="/site/legal" class="mobile-menu-link">Mentions légales</a>
    </nav>

    <div class="mobile-menu-actions">
      <?php require __DIR__ . '/../partials/login_with_tesla.php'; ?>
    </div>
  </div>
</div>
