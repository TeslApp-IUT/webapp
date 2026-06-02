function initCommon() {
  // Mobile Menu Toggle
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileMenuClose = document.getElementById('mobileMenuClose');

  if (mobileMenuToggle && mobileMenu && mobileMenuClose) {
    const openMenu = function () {
      mobileMenu.classList.add('active');
      document.body.style.overflow = 'hidden';
      mobileMenuToggle.setAttribute('aria-expanded', 'true');
    };

    const closeMenu = function () {
      mobileMenu.classList.remove('active');
      document.body.style.overflow = '';
      mobileMenuToggle.setAttribute('aria-expanded', 'false');
    };

    mobileMenuToggle.addEventListener('click', openMenu);
    mobileMenuClose.addEventListener('click', closeMenu);

    const mobileMenuLinks = mobileMenu.querySelectorAll(
      '.mobile-menu-link, .mobile-menu-actions a',
    );
    mobileMenuLinks.forEach((link) => {
      link.addEventListener('click', closeMenu);
    });

    // Fermeture au clavier (Échap) lorsque le menu est ouvert — accessibilité.
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
        closeMenu();
      }
    });
  }

  // Active Nav Link
  const currentPath = globalThis.location.pathname;
  const navLinks = document.querySelectorAll('.nav-link, .mobile-menu-link');
  navLinks.forEach(function (link) {
    link.classList.remove('active');
    const linkPath = link.getAttribute('href');
    if (
      (linkPath && currentPath === linkPath) ||
      (linkPath === '/site/home' && (currentPath === '/' || currentPath === '/site/home'))
    ) {
      link.classList.add('active');
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCommon);
} else {
  initCommon();
}
