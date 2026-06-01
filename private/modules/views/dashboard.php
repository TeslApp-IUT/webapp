<?php
/**
 * Vue du tableau de bord utilisateur
 * 
 * Affiche l'interface principale du tableau de bord pour les utilisateurs connectés.
 * Contient des cartes de présentation des différentes fonctionnalités à venir.
 * 
 * @var string $firstname Prénom de l'utilisateur connecté
 * @var string $lastname Nom de l'utilisateur connecté
 * @var string $specialization Spécialisation médicale de l'utilisateur (optionnel)
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Encodage et viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO et indexation -->
    <meta name="description" content="Accédez à votre tableau de bord MedBoard pour gérer vos patients, consultations et données médicales">
    <meta name="robots" content="noindex, nofollow">
    
    <title>Tableau de bord - MedBoard</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/_assets/css/styles.css">
</head>
<body class="light-theme dashboard-page">
    
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
        <!-- Section principale du tableau de bord -->
        <div class="dashboard-hero">
            <div class="dashboard-container">
                <!-- En-tête avec salutation personnalisée -->
                <div class="dashboard-header">
                    <p class="dashboard-greeting">
                        Bonjour, <?= htmlspecialchars($firstname) ?> <?= htmlspecialchars($lastname) ?>
                    </p>

                    <?php 
                    /**
                     * Affichage de la spécialisation si définie
                     */
                    if (!empty($specialization)) : 
                    ?>
                        <p class="dashboard-specialization">
                            <?= htmlspecialchars($specialization) ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <p class="dashboard-subtitle">Bienvenue sur votre tableau de bord</p>

                <!-- Grille des fonctionnalités -->
                <div class="dashboard-grid">
                    <!-- Carte Projets -->
                    <div class="dashboard-card">
                        <div class="card-title">Projets</div>
                        <div class="card-placeholder"></div>
                        <span class="card-badge">En construction</span>
                    </div>

                    <!-- Carte Équipe -->
                    <div class="dashboard-card">
                        <div class="card-title">Équipe</div>
                        <div class="card-placeholder"></div>
                        <span class="card-badge">Prochainement</span>
                    </div>

                    <!-- Carte Activité -->
                    <div class="dashboard-card">
                        <div class="card-title">Activité</div>
                        <div class="card-placeholder"></div>
                        <span class="card-badge">En développement</span>
                    </div>

                    <!-- Carte Rapports -->
                    <div class="dashboard-card">
                        <div class="card-title">Rapports</div>
                        <div class="card-placeholder"></div>
                        <span class="card-badge">Planifié</span>
                    </div>

                    <!-- Carte Paramètres -->
                    <div class="dashboard-card">
                        <div class="card-title">Paramètres</div>
                        <div class="card-placeholder"></div>
                        <span class="card-badge">À venir</span>
                    </div>

                    <!-- Carte Notifications -->
                    <div class="dashboard-card">
                        <div class="card-title">Notifications</div>
                        <div class="card-placeholder"></div>
                        <span class="card-badge">Bientôt</span>
                    </div>
                </div>

                <!-- Message informatif -->
                <p class="dashboard-footer-text">Interface en cours de développement</p>
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
