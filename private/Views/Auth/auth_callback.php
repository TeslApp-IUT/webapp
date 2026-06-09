<?php
/**
 * Popup callback view — no chrome, no layout.
 * Sends one postMessage to the opener (auth.php) then closes itself.
 *
 * Expected PHP variables:
 *   $status    string       'success' | 'error'
 *   $error     string       error code, only used when $status === 'error'
 *   $redirect  string|null  where the opener should send the MAIN window on success
 *                           (defaults to the app once unset; set to /auth/signup for new users)
 */
$status ??= 'error';
$error  ??= 'unknown_error';
$redirect ??= null;

$message = $status === 'success'
    ? ['success' => true, 'redirect' => $redirect]
    : ['success' => false, 'error' => $error];
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>TeslApp</title></head>
<body>
<div id="auth-data" data-message="<?= htmlspecialchars(json_encode($message), ENT_QUOTES) ?>" hidden></div>
<script src="/_assets/js/auth_callback.js"></script>
</body>
</html>
