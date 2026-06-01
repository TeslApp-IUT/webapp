<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Encodage et viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO et indexation -->
    <meta name="description" content="Plan du site MedBoard - Accédez rapidement à toutes les pages de notre plateforme médicale">

    <!-- Métadonnées Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://medboard.alwaysdata.net/sitemap">
    <meta property="og:title" content="Plan du site - MedBoard">
    <meta property="og:description" content="Accédez rapidement à toutes les pages de notre plateforme médicale MedBoard">
    <meta property="og:image" content="https://medboard.alwaysdata.net/_assets/images/LogoMedBoard.svg">

    <!-- Métadonnées Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="https://medboard.alwaysdata.net/sitemap">
    <meta name="twitter:title" content="Plan du site - MedBoard">
    <meta name="twitter:description" content="Accédez rapidement à toutes les pages de notre plateforme médicale MedBoard">
    <meta name="twitter:image" content="https://medboard.alwaysdata.net/_assets/images/LogoMedBoard.svg">

    <!-- URL canonique -->
    <link rel="canonical" href="https://medboard.alwaysdata.net/sitemap">

    <title>Plan du site - MedBoard</title>

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <!-- Styles -->
    <link rel="stylesheet" href="/_assets/css/styles.css">
</head>
<body class="light-theme">

    <?php
    /**
     * Inclusion de l'en-tête du site
     */
    require __DIR__ . "/partials/header_guest.php";
    ?>

    <main>
        <!-- Section du plan du site -->
        <section class="sitemap-section">
            <div class="sitemap-container">
                <!-- Lien de retour -->
                <a href="/site/home" class="back-link">
                    <img src="/_assets/images/fleche-gauche.svg" alt="Retour">
                    Retour à l'accueil
                </a>

                <!-- En-tête du plan du site -->
                <div class="sitemap-header">
                    <h1 class="sitemap-title">Plan du site</h1>
                    <p class="sitemap-subtitle">Toutes les pages de MedBoard</p>
                </div>

                <!-- Carte listant les pages -->
                <div class="sitemap-card">
                    <h2 class="sitemap-card-title">Pages disponibles</h2>

                    <!-- Liste des pages du site -->
                    <div class="sitemap-list">
                        <!-- Page Accueil -->
                        <a class="sitemap-item" href="/site/home">
                            <span class="sitemap-page-name">Accueil</span>
                            <span class="sitemap-page-url">/site/home</span>
                        </a>

                        <!-- Page Mentions légales -->
                        <a class="sitemap-item" href="/site/legal">
                            <span class="sitemap-page-name">Mentions légales</span>
                            <span class="sitemap-page-url">/site/legal</span>
                        </a>

                        <!-- Page Politique de confidentialité -->
                        <a class="sitemap-item" href="/site/privacy">
                            <span class="sitemap-page-name">Politique de confidentialité</span>
                            <span class="sitemap-page-url">/site/privacy</span>
                        </a>

                        <!-- Page Plan du site -->
                        <a class="sitemap-item" href="/site/sitemap">
                            <span class="sitemap-page-name">Plan du site</span>
                            <span class="sitemap-page-url">/site/sitemap</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    /**
     * Inclusion du footer
     */
    require __DIR__ . "/partials/footer.php";
    ?>

    <!-- Scripts JavaScript -->
    <script src="/_assets/js/common.js" defer></script>
</body>
</html>
