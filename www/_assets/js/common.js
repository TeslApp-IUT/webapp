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

  // Password Toggle
  document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      const allPasswordWrappers = document.querySelectorAll('.password-wrapper, .input-wrapper');
      const allPasswordInputs = [];

      allPasswordWrappers.forEach(function (wrapper) {
        const input =
          wrapper.querySelector('input[type="password"]') ||
          wrapper.querySelector('input[type="text"][id*="assword"]') ||
          wrapper.querySelector('input[type="text"][name*="password"]');
        if (input) {
          allPasswordInputs.push(input);
        }
      });

      if (allPasswordInputs.length === 0) return;

      const shouldShow = allPasswordInputs[0].type === 'password';
      allPasswordInputs.forEach(function (input) {
        if (shouldShow) {
          input.type = 'text';
        } else {
          input.type = 'password';
        }
      });

      document.querySelectorAll('.password-toggle').forEach(function (btn) {
        if (shouldShow) {
          btn.setAttribute('aria-label', 'Masquer le mot de passe');
        } else {
          btn.setAttribute('aria-label', 'Afficher le mot de passe');
        }
      });
    });
  });

  // Password Requirements & Strength Validation
  const passwordInput =
    document.getElementById('password') || document.getElementById('newPassword');
  const feedbackContainer = document.getElementById('passwordFeedback');
  const requirementsContainer = document.getElementById('passwordRequirements');
  const strengthFill = document.getElementById('passwordStrengthFill');
  const strengthLabel = document.getElementById('passwordStrengthLabel');

  if (passwordInput && feedbackContainer) {
    const requirements = {
      length: (pwd) => pwd.length >= 8,
      uppercase: (pwd) => /[A-Z]/.test(pwd),
      lowercase: (pwd) => /[a-z]/.test(pwd),
      number: (pwd) => /[0-9]/.test(pwd),
      special: (pwd) => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd),
    };

    const strengthLevels = {
      0: { label: 'Très faible', class: 'weak', width: '10%' },
      1: { label: 'Faible', class: 'weak', width: '25%' },
      2: { label: 'Moyen', class: 'medium', width: '50%' },
      3: { label: 'Moyen', class: 'medium', width: '65%' },
      4: { label: 'Fort', class: 'strong', width: '85%' },
      5: { label: 'Très fort', class: 'strong', width: '100%' },
    };

    passwordInput.addEventListener('input', function () {
      const password = this.value;
      let validCount = 0;

      // Show/hide feedback based on input
      if (password.length > 0) {
        feedbackContainer.classList.add('visible');
      } else {
        feedbackContainer.classList.remove('visible');
      }

      // Validate each requirement
      Object.keys(requirements).forEach(function (req) {
        const isValid = requirements[req](password);
        if (isValid) validCount++;

        if (requirementsContainer) {
          const element = requirementsContainer.querySelector('[data-requirement="' + req + '"]');
          if (element) {
            if (isValid) {
              element.classList.add('valid');
            } else {
              element.classList.remove('valid');
            }
          }
        }
      });

      // Update strength indicator
      if (strengthFill && strengthLabel) {
        const level = strengthLevels[validCount];

        // Remove previous classes
        strengthFill.classList.remove('weak', 'medium', 'strong');
        strengthLabel.classList.remove('weak', 'medium', 'strong');

        // Add new classes
        strengthFill.classList.add(level.class);
        strengthLabel.classList.add(level.class);
        strengthFill.style.width = level.width;
        strengthLabel.textContent = level.label;
      }
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCommon);
} else {
  initCommon();
}
