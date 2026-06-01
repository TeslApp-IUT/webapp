<?php
$title = 'Erreur 404 - TeslApp';
$description = "Page non trouvée - La page que vous recherchez n'existe pas ou a été déplacée";
$bodyClass = 'error-page-body';
$headExtra = '<meta name="robots" content="noindex, nofollow">';

ob_start();
?>
<div class="error-page">
    <div class="error-container">
        <!-- Lien de retour vers l'accueil -->
        <a href="/site/home" class="error-top-link">
            <img src="/_assets/images/fleche-gauche.svg" alt="" aria-hidden="true">
            Échapper à cette dimension
        </a>

        <!-- Carte d'erreur -->
        <div class="error-card">
            <!-- Statut de l'erreur -->
            <div class="error-hero">
                <span class="error-status">ERROR 404</span>
                <span class="error-separator">•</span>
                <span class="error-status">Vous êtes dans une zone interdite</span>
            </div>

            <!-- Image d'illustration de l'erreur -->
            <div class="error-image">
                <picture>
                    <source srcset="/_assets/images/error-404.avif" type="image/avif">
                    <source srcset="/_assets/images/error-404.webp" type="image/webp">
                    <img src="/_assets/images/error-404.jpg" alt="Erreur 404">
                </picture>
            </div>

            <!-- Message d'avertissement -->
            <div class="error-alert">
                <div class="alert-title">⚠️ AVERTISSEMENT SYSTÈME ⚠️</div>
                <p>Cette page a été corrompue par une entité inconnue.</p>
                <div class="error-meta">
                    <span>STATUS : 404</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
