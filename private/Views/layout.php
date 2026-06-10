<?php
/**
 * Main layout — factored HTML skeleton (R4.01 chap 1 §5.4).
 *
 * A view fills an output buffer (ob_start) then exposes the following
 * variables before calling `require_once __DIR__ . '/../layout.php';` :
 *   $title       string    page title
 *   $content     string    page body (already rendered HTML)
 *   $description string    meta description (optional)
 *   $extraCss    string[]  additional CSS files, without extension (optional)
 *   $extraJs     string[]  additional scripts, without extension (optional)
 *   $bodyClass   string    <body> class(es) (optional)
 *   $headExtra   string    HTML fragment injected at the end of <head>, e.g. JSON-LD (optional)
 *   $header      string    'guest' (default) or 'user' — selects the header partial (optional)
 */

use Teslapp\Utils\Flash;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($description ?? '') ?>">
  <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
  <title><?= e($title ?? 'TeslApp') ?></title>
  <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="/_assets/css/styles.css">
  <?php foreach (($extraCss ?? []) as $css): ?>
    <link rel="stylesheet" href="/_assets/css/<?= e($css) ?>.css">
  <?php endforeach; ?>
  <?= $headExtra ?? '' ?>
</head>
<?php $impersonating = isset($_SESSION['real_user_id']); ?>
<body class="<?= e(trim(($bodyClass ?? '') . ($impersonating ? ' impersonating' : ''))) ?>">

  <a class="skip-link" href="#main">Aller au contenu principal</a>
  <?php if ($impersonating): ?>
    <div class="sticky top-0 inset-x-0 z-[1001] flex items-center justify-between bg-amber-500/95 px-4 py-2 text-sm text-black backdrop-blur-sm">
      <span>Mode délégué — vous naviguez en tant que <strong><?= htmlspecialchars($_SESSION['user_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></span>
      <form method="post" action="/auth/impersonate/stop" class="inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="ml-4 font-semibold underline underline-offset-2 hover:opacity-75 cursor-pointer">Arrêter</button>
      </form>
    </div>
  <?php endif; ?>
  <?php
  if (empty($noChrome)) {
    /**
     * Header variant: 'guest' (default) or 'user' (a view sets $header before include).
     * Strict whitelist -> prevents any arbitrary file inclusion through $header.
     **/
    $headerVariant = ($_SESSION['logged_in'] ?? false) === true ? 'user' : 'guest';
    require_once __DIR__ . '/partials/header_' . $headerVariant . '.php';
  }
  ?>
  <main id="main">
    <?php
    // Flash messages: displayed once during the session, then deleted.
    $errors = Flash::consume('errors', []);
    $success = Flash::consume('success');
    $info = Flash::consume('info');
    require_once __DIR__ . '/partials/flash_message.php';
    ?>
    <?= $content ?? '' ?>
  </main>

  <?php
  if (empty($noChrome)) {
    require_once __DIR__ . '/partials/footer.php';
  }
  ?>

  <script src="/_assets/js/common.js" defer></script>
  <?php foreach (($extraJs ?? []) as $js): ?>
    <script src="/_assets/js/<?= e($js) ?>.js" defer></script>
  <?php endforeach; ?>

</body>
</html>
