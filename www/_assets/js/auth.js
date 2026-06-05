const ERRORS = {
  missing_code: "Code d'autorisation manquant.",
  token_exchange_failed: "L'échange du token a échoué. Veuillez réessayer.",
};

function switchTo(nextId) {
  document.querySelectorAll('#auth-button span').forEach(span => {
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
  if (event.origin !== window.location.origin) return;
  if (typeof event.data?.success !== 'boolean') return;

  authCompleted = true;
  clearInterval(closedCheckInterval);

  if (event.data.success) {
    window.location.href = '/vehicle/dashboard';
  } else {
    showError(event.data.error);
    switchTo('button-retry');
  }
});

document.getElementById('auth-button').addEventListener('click', () => {
  authCompleted = false;
  hideError();

  const href = document.getElementById('auth-button').dataset.href;
  const windowProxy = window.open(href, '_blank', 'popup=true,width=500,height=700,top=100,left=50');

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
