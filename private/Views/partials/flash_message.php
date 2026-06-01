<?php
/**
 * Partial : messages flash (erreurs, succès, info).
 *
 * Les variables sont fournies par le layout via Flash::consume() (consommation
 * unique : lues puis supprimées de la session).
 *
 * Note : styles inline conservés pour l'instant (autorisés par la CSP
 * `style-src 'unsafe-inline'`) ; migration vers des classes CSS au thème Tesla
 * prévue quand le flash sera réellement affiché (LOT auth).
 *
 * @var array<int, string> $errors  Messages d'erreur
 * @var string|null        $success Message de succès
 * @var string|null        $info    Message d'information
 */
?>
<?php if (!empty($errors)): ?>
    <div class="errors" role="alert" style="background:#ffecec; border:1px solid #ffb3b3; color:#a40000; padding:.75rem; border-radius:10px; margin-bottom:1rem;">
        <ul style="margin:0; padding-left:1.2rem;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="success" role="status" style="background:#e9ffe9; border:1px solid #9ed99e; color:#136b13; padding:.75rem; border-radius:10px; margin-bottom:1rem;">
        <?= e($success) ?>
    </div>
<?php endif; ?>

<?php if (!empty($info)): ?>
    <div class="info" role="status" style="background:#eef3ff; border:1px solid #b3c7ff; color:#1a3a8a; padding:.75rem; border-radius:10px; margin-bottom:1rem;">
        <?= e($info) ?>
    </div>
<?php endif; ?>
