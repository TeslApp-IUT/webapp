<?php
/**
 * Partial : messages flash (erreurs, succès, info).
 *
 * Les variables sont fournies par le layout via Flash::consume() (consommation
 * unique : lues puis supprimées de la session). Le style vit dans styles.css
 * (classes .flash / .flash--error / .flash--success / .flash--info) — aucun
 * style inline, afin de préserver une CSP stricte (style-src 'self').
 *
 * @var array<int, string> $errors  Messages d'erreur
 * @var string|null        $success Message de succès
 * @var string|null        $info    Message d'information
 */
?>
<?php if (!empty($errors)): ?>
    <div class="flash flash--error" role="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="flash flash--success" role="status"><?= e($success) ?></div>
<?php endif; ?>

<?php if (!empty($info)): ?>
    <div class="flash flash--info" role="status"><?= e($info) ?></div>
<?php endif; ?>
