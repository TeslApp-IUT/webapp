// vehicle-actions.js — vehicle commands (issue #26).
//
// Each command tile carries a data-action matching a route segment (lock,
// unlock, honk, flash, trunk-front, ...). Clicking it POSTs to
// /dashboard/<vehicleId>/<action> with the CSRF token read from the
// <meta name="csrf-token"> tag. The vehicleId is read from
// <meta name="vehicle-id"> injected by the control page view.
//
// The tile content (SVG pictogram + label) is preserved: instead of rewriting
// button.textContent, the in-flight state is shown via an .is-loading class and
// the shared [data-action-feedback] element. A re-entry guard (the .is-loading
// check) prevents double submits without using the disabled attribute, which is
// reserved for the "coming soon" placeholder tiles.

function initVehicleActions() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta?.getAttribute('content') ?? '';
  const vehicleMeta = document.querySelector('meta[name="vehicle-id"]');
  const vehicleId = vehicleMeta?.getAttribute('content') ?? '';
  const feedback = document.querySelector('[data-action-feedback]');
  const buttons = document.querySelectorAll('.vehicle-commands [data-action]');

  buttons.forEach((button) => {
    button.addEventListener('click', async () => {
      const action = button.dataset.action;
      if (!action || button.classList.contains('is-loading')) {
        return;
      }

      button.classList.add('is-loading');
      setFeedback(feedback, 'Envoi de la commande…', '');

      try {
        const response = await fetch('/dashboard/' + vehicleId + '/' + action, {
          method: 'POST',
          redirect: 'manual',
          headers: {
            'X-CSRF-Token': csrfToken,
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        setFeedback(feedback, 'Commande envoyée.', 'success');
      } catch {
        setFeedback(feedback, 'Échec de la commande, réessayez.', 'error');
      } finally {
        button.classList.remove('is-loading');
      }
    });
  });
}

function setFeedback(element, message, state) {
  if (!element) {
    return;
  }
  element.textContent = message;
  element.dataset.state = state;
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initVehicleActions);
} else {
  initVehicleActions();
}
