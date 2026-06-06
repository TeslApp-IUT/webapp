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
<script>
  (function () {
    const message = <?php try {
      echo json_encode($message, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
      echo '{}';
    } ?>;

    if (window.opener) {
      window.opener.postMessage(message, window.location.origin);
    }

    window.close();
  })();
</script>
