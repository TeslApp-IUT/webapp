<?php
/**
 * Layout principal — squelette HTML factorisé (R4.01 chap 1 §5.4).
 *
 * Une vue remplit un tampon de sortie (ob_start) puis expose les variables
 * suivantes avant de faire `require __DIR__ . '/../layout.php';` :
 *   $title       string    titre de la page
 *   $content     string    corps de la page (HTML déjà rendu)
 *   $description string    meta description (optionnel)
 *   $extraCss    string[]  feuilles CSS supplémentaires, sans extension (optionnel)
 *   $extraJs     string[]  scripts supplémentaires, sans extension (optionnel)
 *   $bodyClass   string    classe(s) du <body> (optionnel)
 *   $headExtra   string    fragment HTML injecté en fin de <head>, ex. JSON-LD (optionnel)
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

    <?php require __DIR__ . '/partials/header_guest.php'; ?>

    <main>
        <?php
        // Messages flash : consommés une fois depuis la session, puis supprimés.
        $errors = \Teslapp\Utils\Flash::consume('errors', []);
        $success = \Teslapp\Utils\Flash::consume('success');
        $info = \Teslapp\Utils\Flash::consume('info');
        require __DIR__ . '/partials/flash_message.php';
        ?>
        <?= $content ?? '' ?>
    </main>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="/_assets/js/common.js" defer></script>
    <?php foreach (($extraJs ?? []) as $js): ?>
        <script src="/_assets/js/<?= e($js) ?>.js" defer></script>
    <?php endforeach; ?>
</body>
</html>
