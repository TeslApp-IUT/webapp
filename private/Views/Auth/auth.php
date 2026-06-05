<?php
$BASE_TESLA_URL = 'https://auth.tesla.com/oauth2/v3/authorize';

$params = [
  'response_type' => 'code',
  'client_id' => getenv('CLIENT_ID'),
  'scope' => 'openid offline_access user_data vehicle_device_data vehicle_location vehicle_cmds vehicle_charging_cmds vehicle_specs',
  'require_requested_scopes' => true,
  'prompt_missing_scopes' => true
];

$href = $BASE_TESLA_URL . '?' . http_build_query($params);

$title = 'Authentification — TeslApp';
$description = "Authentification en cours avec Tesla";
$noChrome = true;
ob_start();
?>
  <!--
  <script>
    window.onload = () => {
      const currentLocation = new URL(window.location.href);

      async function checkCookie() {
        const res = await fetch(window.location.href, {
          method: 'POST',
        });

        if (res.status === 200) {
          const redirectUri = new URL(currentLocation.origin);

          redirectUri.pathname = currentLocation.searchParams.get('redirect') || '/';

          window.location.href = redirectUri.toString();
        }
      }

      const redirectUri = currentLocation.origin + '/callback_auth';
      const teslaUrl = new URL('<?php echo $href ?>');
      teslaUrl.searchParams.set('redirect_uri', redirectUri.toString());
      const windowProxy = window.open(teslaUrl.href, '_blank', 'popup=true,width=500,height=700,top=100,left=50');

      const closedCheckInterval = setInterval(() => {
        const text = document.querySelector('#replaceable-text');

        if (windowProxy === null) {
          text.innerHTML = 'La fenêtre n\'a pas pu être ouverte.'
          return;
        }
        if (!windowProxy.closed) {
          text.innerHTML = 'Authentification en cours dans une nouvelle fenêtre...'
          return;
        }
        if (windowProxy.closed) {
          clearInterval(closedCheckInterval);
          text.innerHTML = 'La fenêtre a été fermée.'
        }
      }, 500);

      setInterval(checkCookie, 2000);
    };
  </script>
!-->

<link rel="stylesheet" href="/_assets/css/login_with_tesla.css">
  <div class="w-dvw h-dvh flex flex-col items-center justify-center">
    <div class="flex flex-col items-center justify-center bg-[#1A1A1A] border border-[#4A4A4A] p-8 rounded-xl gap-5">
      <img src="/_assets/images/Logo.svg" width="325">
      <div class="border-l-3 border-l-blue-600/50 bg-blue-600/30 p-2 rounded-lg text-gray-300 text-sm">
        Vous allez vous connecter à l'aide de votre compte Tesla.
        <br/>Veuillez cliquer sur le bouton ci-dessous afin d'être redirigé.
      </div>
      <button id="auth-button" class="btn-primary hover:bg-gray-300! active:scale-95 transition-all! bg-white! text-black! font-normal!">
        <img src="/_assets/images/tesla_logo_gray.png" alt="logo Tesla">
        <span id="button-connect" class="visible">Se connecter avec Tesla</span>
        <span id="button-retry" class="hidden">Essayer à nouveau</span>
        <span id="button-pending" class="hidden">Authentification...</span>
      </button>
    </div>
  </div>

  <script>
    function switchTo(nextId) {
      document.querySelectorAll('#auth-button span').forEach(span => {
        span.className = span.id === nextId ? 'visible' : 'hidden';
      });
    }

    document.getElementById('auth-button').addEventListener('click', () => {
      const currentLocation = new URL(window.location.href);

      const redirectUri = currentLocation.origin + '/callback_auth';
      const teslaUrl = new URL('<?php echo $href ?>');
      teslaUrl.searchParams.set('redirect_uri', redirectUri.toString());
      const windowProxy = window.open(teslaUrl.href, '_blank', 'popup=true,width=500,height=700,top=100,left=50');

      if (windowProxy === null) {
        switchTo('button-retry');
        return;
      }

      switchTo('button-pending');

      const closedCheckInterval = setInterval(() => {
        if (windowProxy.closed) {
          clearInterval(closedCheckInterval);
          switchTo('button-retry');
        }
      }, 500);
    });
  </script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';


