const ERRORS = {
  missing_code: "Code d'autorisation manquant.",
  invalid_state: 'Échec de la vérification de sécurité. Veuillez réessayer.',
  token_exchange_failed: "L'échange du token a échoué. Veuillez réessayer.",
};

function switchTo(nextId) {
  document.querySelectorAll('#auth-button span').forEach((span) => {
    span.className = span.id === nextId ? 'visible' : 'hidden';
  });
}

function showError(code) {
  const el = document.getElementById('auth-error');
  el.textContent = ERRORS[code] ?? "Une erreur inattendue s'est produite.";
  el.classList.remove('hidden');
}

function hideError() {
  document.getElementById('auth-error').classList.add('hidden');
}

let closedCheckInterval = null;
let authCompleted = false;

window.addEventListener('message', (event) => {
  if (event.origin !== globalThis.location.origin) return;
  if (typeof event.data?.success !== 'boolean') return;

  authCompleted = true;
  clearInterval(closedCheckInterval);

  if (event.data.success) {
    // New users are sent to the signup form (event.data.redirect).
    // Returning users go back to the page they came from (redirectURI query param),
    // or fall back to /dashboard.
    const raw = new URLSearchParams(globalThis.location.search).get('redirectURI') ?? '';
    const redirectUri = raw.startsWith('/') && !raw.startsWith('//') ? raw : '/dashboard';
    globalThis.location.href = event.data.redirect ?? redirectUri;
  } else {
    showError(event.data.error);
    switchTo('button-retry');
  }
});

document.getElementById('auth-button').addEventListener('click', () => {
  authCompleted = false;
  hideError();

  const href = document.getElementById('auth-button').dataset.href;
  const windowProxy = window.open(
    href,
    '_blank',
    'popup=true,width=500,height=700,top=100,left=50',
  );

  if (windowProxy === null) {
    switchTo('button-retry');
    return;
  }

  switchTo('button-pending');

  closedCheckInterval = setInterval(() => {
    if (windowProxy.closed && !authCompleted) {
      clearInterval(closedCheckInterval);
      switchTo('button-retry');
    }
  }, 500);
});
