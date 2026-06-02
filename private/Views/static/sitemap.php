<?php
$title = 'Plan du site - TeslApp';
$description = 'Plan du site TeslApp - Accédez rapidement à toutes les pages de notre application';

ob_start();
?>
<section class="sitemap-section">
    <div class="sitemap-container">
        <!-- Back link  -->
        <a href="/site/home" class="back-link">
            <img src="/_assets/images/fleche-gauche.svg" alt="" aria-hidden="true">
            Retour à l'accueil
        </a>

        <!-- Site Map Header -->
        <div class="sitemap-header">
            <h1 class="sitemap-title">Plan du site</h1>
            <p class="sitemap-subtitle">Toutes les pages de TeslApp</p>
        </div>

        <!-- Site Map Card -->
        <div class="sitemap-card">
            <h2 class="sitemap-card-title">Pages disponibles</h2>

            <!-- List of publicly accessible pages -->
            <div class="sitemap-list">
                <a class="sitemap-item" href="/site/home">
                    <span class="sitemap-page-name">Accueil</span>
                    <span class="sitemap-page-url">/site/home</span>
                </a>

                <a class="sitemap-item" href="/site/legal">
                    <span class="sitemap-page-name">Mentions légales</span>
                    <span class="sitemap-page-url">/site/legal</span>
                </a>

                <a class="sitemap-item" href="/site/privacy">
                    <span class="sitemap-page-name">Politique de confidentialité</span>
                    <span class="sitemap-page-url">/site/privacy</span>
                </a>

                <a class="sitemap-item" href="/site/sitemap">
                    <span class="sitemap-page-name">Plan du site</span>
                    <span class="sitemap-page-url">/site/sitemap</span>
                </a>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
