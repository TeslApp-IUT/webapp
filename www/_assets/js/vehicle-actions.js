// vehicle-actions.js — vehicle commands (issue #26).
//
// Each dashboard action button carries a data-action matching a route segment
// (lock, unlock, honk, flash, trunk-front, ...). Clicking it POSTs to
// /vehicle/<action> with the CSRF token read from the <meta name="csrf-token">
// tag. The VIN is resolved server-side from the session (selected_vin), so it is
// never sent from the browser.

function initVehicleActions() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta?.getAttribute('content') ?? '';
  const feedback = document.querySelector('[data-action-feedback]');
  const buttons = document.querySelectorAll('.dashboard-actions [data-action]');

  buttons.forEach((button) => {
    button.addEventListener('click', async () => {
      const action = button.dataset.action;
      if (!action) {
        return;
      }

      const originalLabel = button.textContent;
      button.disabled = true;
      button.textContent = 'Envoi…';
      setFeedback(feedback, '', '');

      try {
        const response = await fetch('/vehicle/' + action, {
          method: 'POST',
          headers: {
            'X-CSRF-Token': csrfToken,
            Accept: 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        setFeedback(feedback, 'Commande envoyée.', 'success');
      } catch {
        setFeedback(feedback, 'Échec de la commande, réessayez.', 'error');
      } finally {
        button.disabled = false;
        button.textContent = originalLabel;
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
