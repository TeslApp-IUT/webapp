const message = JSON.parse(document.getElementById('auth-data').dataset.message);

if (window.opener) {
  window.opener.postMessage(message, window.location.origin);
}

window.close();
