<?php
/**
 * Partial : Messages Flash
 * 
 * Affiche les messages d'erreur et de succès issus de la session.
 * Utilisé pour informer l'utilisateur du résultat de ses actions.
 * 
 * Variables attendues :
 * @var array|null $errors Liste des messages d'erreur à afficher
 * @var string|null $success Message de succès à afficher
 */
?>

<?php 
/**
 * Affichage des erreurs
 * Structure : liste non ordonnée avec un message par erreur
 */
if (!empty($errors)): 
?>
    <div class="errors" role="alert" style="background:#ffecec; border:1px solid #ffb3b3; color:#a40000; padding:.75rem; border-radius:10px; margin-bottom:1rem;">
        <ul style="margin:0; padding-left: 1.2rem;">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php 
/**
 * Affichage du message de succès
 * Affiché uniquement si la variable $success contient une valeur
 */
if (!empty($success)): 
?>
    <div class="success" role="status" style="background:#e9ffe9; border:1px solid #9ed99e; color:#136b13; padding:.75rem; border-radius:10px; margin-bottom:1rem;">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>