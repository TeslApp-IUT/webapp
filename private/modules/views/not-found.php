<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Encodage et viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO et indexation -->
    <meta name="description" content="Page non trouvée - La page que vous recherchez n'existe pas ou a été déplacée">
    <meta name="robots" content="noindex, nofollow">
    
    <title>Erreur 404 - MedBoard</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/_assets/css/styles.css">
</head>
<body class="light-theme error-page-body">
    
    <?php 
    /**
     * Inclusion du header approprié selon le statut de connexion
     */
    if (Auth::check()) {
        require __DIR__ ."/partials/header_user.php";
    } else {
        require __DIR__ ."/partials/header_guest.php";
    }
    ?>

    <main>
        <!-- Page d'erreur 404 -->
        <div class="error-page">
            <div class="error-container">
                <!-- Lien de retour vers l'accueil -->
                <a href="/site/home" class="error-top-link">
                    <img src="/_assets/images/fleche-gauche.svg" alt="Retour">
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
    </main>

    <?php
    /**
     * Inclusion du footer
     */
    require __DIR__ ."/partials/footer.php";
    ?>

    <!-- Scripts JavaScript -->
    <script src="/_assets/js/common.js" defer></script>
</body>
</html>
