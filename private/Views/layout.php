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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($description ?? '') ?>">
    <title><?= e($title ?? 'TeslApp') ?></title>
    <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/_assets/css/styles.css">
    <?php foreach (($extraCss ?? []) as $css): ?>
        <link rel="stylesheet" href="/_assets/css/<?= e($css) ?>.css">
    <?php endforeach; ?>
    <?= $headExtra ?? '' ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">

    <a class="skip-link" href="#main">Aller au contenu principal</a>
    <?php
    /**
     * Header variant: 'guest' (default) or 'user' (a view sets $header before include).
     * Strict whitelist -> prevents any arbitrary file inclusion through $header.
     **/
    $headerVariant = $header ?? 'guest';
    if (!in_array($headerVariant, ['guest', 'user'], true)) {
      $headerVariant = 'guest';
    }
    require_once __DIR__ . '/partials/header_' . $headerVariant . '.php';
    ?>
    <main id="main">
        <?php
        // Messages flash : consommés une fois depuis la session, puis supprimés.
        $errors = \Teslapp\Utils\Flash::consume('errors', []);
        $success = \Teslapp\Utils\Flash::consume('success');
        $info = \Teslapp\Utils\Flash::consume('info');
        require_once __DIR__ . '/partials/flash_message.php';
        ?>
        <?= $content ?? '' ?>
    </main>

    <?php require_once __DIR__ . '/partials/footer.php'; ?>

    <script src="/_assets/js/common.js" defer></script>
    <?php foreach (($extraJs ?? []) as $js): ?>
        <script src="/_assets/js/<?= e($js) ?>.js" defer></script>
    <?php endforeach; ?>
</body>
</html>