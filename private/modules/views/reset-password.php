<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Encodage et viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO et indexation -->
    <meta name="description" content="Créez un nouveau mot de passe sécurisé pour votre compte MedBoard">
    <meta name="robots" content="noindex, nofollow">
    
    <title>Nouveau mot de passe - MedBoard</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/_assets/css/styles.css">
</head>
<body class="light-theme change-password-page">
    
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
        <!-- Section de réinitialisation de mot de passe -->
        <section class="change-password-section">
            <div class="change-password-container">
                <!-- Lien de retour -->
                <a href="/site/home" class="back-link">
                    <img src="/_assets/images/fleche-gauche.svg" alt="" aria-hidden="true">
                    Retour à l'accueil
                </a>

                <!-- Carte de changement de mot de passe -->
                <div class="change-password-card">
                    <h1 class="change-password-title">Nouveau mot de passe</h1>
                    <p class="change-password-subtitle">Choisissez un mot de passe sécurisé pour votre compte</p>

                    <?php
                    /**
                     * Affichage des messages flash (erreurs/succès)
                     */
                    require __DIR__ ."/partials/flash_message.php";
                    ?>

                    <!-- Formulaire de réinitialisation -->
                    <form id="changePasswordForm" class="change-password-form" method="post" action="/auth/reset-password" novalidate>
                        <!-- Token CSRF pour la sécurité -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        
                        <!-- Token de réinitialisation -->
                        <input type="hidden" name="token" value="<?= htmlspecialchars((string)($token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        
                        <!-- Identifiant utilisateur -->
                        <input type="hidden" name="uid" value="<?= htmlspecialchars((string)($uid ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                        <!-- Champ nouveau mot de passe -->
                        <div class="form-group">
                            <label for="newPassword" class="form-label">Nouveau mot de passe</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    id="newPassword"
                                    name="password"
                                    class="form-input password-input"
                                    placeholder="••••••••"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                                <!-- Bouton pour afficher/masquer le mot de passe -->
                                <button type="button" class="password-toggle" aria-label="Afficher le mot de passe">
                                    <img src="/_assets/images/oeil-light.svg" alt="" class="eye-light">
                                    <img src="/_assets/images/oeil-dark.svg" alt="" class="eye-dark">
                                </button>
                            </div>
                        </div>

                        <!-- Champ confirmation du mot de passe -->
                        <div class="form-group">
                            <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    id="confirmPassword"
                                    name="password_confirm"
                                    class="form-input password-input"
                                    placeholder="••••••••"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                                <!-- Bouton pour afficher/masquer le mot de passe -->
                                <button type="button" class="password-toggle" aria-label="Afficher le mot de passe">
                                    <img src="/_assets/images/oeil-light.svg" alt="" class="eye-light">
                                    <img src="/_assets/images/oeil-dark.svg" alt="" class="eye-dark">
                                </button>
                            </div>
                        </div>

                        <!-- Bouton de soumission -->
                        <button type="submit" class="btn-submit" id="submitBtn">
                            Modifier le mot de passe
                        </button>

                        <!-- Lien vers la connexion -->
                        <p class="form-footer-text">
                            Vous vous souvenez de votre mot de passe ? <a href="/auth/login">Se connecter</a>
                        </p>
                    </form>
                </div>
            </div>
        </section>
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
