<?php
/**
 * Popup callback view — no chrome, no layout.
 * Sends one postMessage to the opener (auth.php) then closes itself.
 *
 * Expected PHP variables:
 *   $status  string  'success' | 'error'
 *   $error   string  error code, only used when $status === 'error'
 */
$status ??= 'error';
$error  ??= 'unknown_error';

$message = $status === 'success'
    ? ['success' => true]
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
