// command-forms.js — submit feedback for the Tesla command forms (battery & AC pages).
//
// Sending a command can take ~20s when the server wakes a sleeping vehicle
// (wake-on-demand retries). While a POST is in flight: block any further form
// submission on the page (each in-flight command keeps a PHP-FPM worker busy),
// and show the waiting state on the clicked button. Scoped to <main> so the
// header forms (logout, impersonation) keep their default behaviour.

(function () {
  let inFlight = false;

  function markBusy(submitter) {
    submitter.disabled = true;
    submitter.setAttribute('aria-busy', 'true');
    submitter.title = 'L’envoi peut prendre ~20 s si le véhicule doit être réveillé.';

    // Keep icon-only buttons intact; only relabel plain text buttons/inputs.
    if (submitter instanceof HTMLInputElement) {
      submitter.dataset.originalLabel = submitter.value;
      submitter.value = 'Envoi en cours…';
    } else if (!submitter.querySelector('svg') && submitter.textContent.trim() !== '') {
      submitter.dataset.originalLabel = submitter.textContent;
      submitter.textContent = 'Envoi en cours…';
    }
  }

  function restoreBusy(button) {
    button.disabled = false;
    button.removeAttribute('aria-busy');
    button.removeAttribute('title');

    const label = button.dataset.originalLabel;
    if (label !== undefined) {
      if (button instanceof HTMLInputElement) {
        button.value = label;
      } else {
        button.textContent = label;
      }
      delete button.dataset.originalLabel;
    }
  }

  function initCommandForms() {
    document.querySelectorAll('main form[method="post"]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (inFlight) {
          event.preventDefault();
          return;
        }
        inFlight = true;

        const submitter = event.submitter;
        if (!submitter) {
          return;
        }
        // Defer the visual lock: disabling the submitter synchronously inside
        // the submit handler would drop its name/value from the POST body.
        setTimeout(() => markBusy(submitter), 0);
      });
    });

    // A page restored from the bfcache (browser back button) resumes with the
    // in-flight lock and a disabled button; reset so the forms work again.
    window.addEventListener('pageshow', (event) => {
      if (!event.persisted) {
        return;
      }
      inFlight = false;
      document.querySelectorAll('main form[method="post"] [aria-busy="true"]').forEach(restoreBusy);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCommandForms);
  } else {
    initCommandForms();
  }
})();
