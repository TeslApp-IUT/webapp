function initCommon() {
  // Mobile Menu Toggle
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileMenuClose = document.getElementById('mobileMenuClose');

  if (mobileMenuToggle && mobileMenu && mobileMenuClose) {
    mobileMenuToggle.addEventListener('click', function () {
      mobileMenu.classList.add('active');
      document.body.style.overflow = 'hidden';
    });

    mobileMenuClose.addEventListener('click', function () {
      mobileMenu.classList.remove('active');
      document.body.style.overflow = '';
    });

    const mobileMenuLinks = mobileMenu.querySelectorAll(
      '.mobile-menu-link, .mobile-menu-actions a',
    );
    mobileMenuLinks.forEach((link) => {
      link.addEventListener('click', function () {
        mobileMenu.classList.remove('active');
        document.body.style.overflow = '';
      });
    });
  }

  // Active Nav Link
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.nav-link, .mobile-menu-link');
  navLinks.forEach(function (link) {
    link.classList.remove('active');
    const linkPath = link.getAttribute('href');
    if (linkPath && currentPath === linkPath) {
      link.classList.add('active');
    } else if (linkPath === '/site/home' && (currentPath === '/' || currentPath === '/site/home')) {
      link.classList.add('active');
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCommon);
} else {
  initCommon();
}
