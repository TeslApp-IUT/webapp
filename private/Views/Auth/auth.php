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

  <div class="w-dvw h-dvh flex flex-col items-center justify-center">
    <div class="bg-gray-700 border border-gray-600 p-4 rounded-xl">
      <button id="auth-button" class="btn-primary bg-white! text-black! !font-normal">
        <img src="/_assets/images/tesla_logo_gray.png" alt="logo Tesla">
        <span>Se connecter avec Tesla</span>
      </button>
    </div>
  </div>

  <script>
    document.getElementById('auth-button').addEventListener('click', () => {
      const currentLocation = new URL(window.location.href);


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
    });
  </script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
