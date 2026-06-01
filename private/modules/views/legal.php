<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Encodage et viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO et indexation -->
    <meta name="description" content="Mentions légales de MedBoard - Informations sur l'éditeur, l'hébergeur et les conditions d'utilisation">

    <!-- Métadonnées Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://medboard.alwaysdata.net/legal">
    <meta property="og:title" content="Mentions légales - MedBoard">
    <meta property="og:description" content="Mentions légales de MedBoard - Informations sur l'éditeur, l'hébergeur et les conditions d'utilisation">
    <meta property="og:image" content="https://medboard.alwaysdata.net/_assets/images/LogoMedBoard.svg">

    <!-- Métadonnées Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="https://medboard.alwaysdata.net/legal">
    <meta name="twitter:title" content="Mentions légales - MedBoard">
    <meta name="twitter:description" content="Mentions légales de MedBoard - Informations sur l'éditeur, l'hébergeur et les conditions d'utilisation">
    <meta name="twitter:image" content="https://medboard.alwaysdata.net/_assets/images/LogoMedBoard.svg">

    <!-- URL canonique -->
    <link rel="canonical" href="https://medboard.alwaysdata.net/legal">

    <title>Mentions légales - MedBoard</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/_assets/css/styles.css">
</head>
<body class="light-theme">
    
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
        <!-- Section des mentions légales -->
        <section class="legal-section">
            <div class="legal-container">
                <!-- Lien de retour -->
                <a href="/site/home" class="back-link">
                    <img src="/_assets/images/fleche-gauche.svg" alt="Retour">
                    Retour à l'accueil
                </a>

                <!-- En-tête de la page -->
                <div class="legal-header">
                    <h1 class="legal-title">Mentions légales</h1>
                    <p class="legal-subtitle">Dernière mise à jour : <span>29/09/2025</span></p>
                </div>

                <!-- Contenu des mentions légales -->
                <div class="legal-content">
                    <!-- Introduction -->
                    <p class="legal-paragraph">
                        Conformément aux dispositions des articles 6-III et 19 de la loi n° 2004-575 du 21 juin 2004 pour la confiance dans l'économie numérique (LCEN), il est porté à la connaissance des utilisateurs et visiteurs du site MedBoard les présentes mentions légales.
                    </p>
                    <p class="legal-paragraph">
                        Le site MedBoard est accessible à l'adresse suivante : <a href="https://medboard.alwaysdata.net/" target="_blank" rel="noopener">https://medboard.alwaysdata.net/</a> (ci-après « le Site »). L'accès et l'utilisation du Site sont soumis aux présentes mentions légales détaillées ci-après, ainsi qu'aux lois et/ou règlements applicables. La connexion, l'utilisation et l'accès au Site impliquent l'acceptation intégrale et sans réserve de toutes les dispositions des présentes mentions légales.
                    </p>

                    <!-- Article 1 : Informations légales -->
                    <h2 class="legal-section-heading">Article 1 – Informations légales</h2>

                    <h3 class="legal-subheading">A. Éditeurs du site</h3>
                    <p class="legal-paragraph">
                        Le site MedBoard est édité par :<br>
                        Alexis BARBERIS<br>
                        Vassili REGNIER<br>
                        Jérémy WATRIPONT<br>
                        Étudiants de deuxième année de BUT Informatique à l'IUT Aix-Marseille, site d'Aix-en-Provence.<br>
                        (ci-après « les Éditeurs »)
                    </p>

                    <h3 class="legal-subheading">B. Directeur de publication</h3>
                    <p class="legal-paragraph">
                        Les directeurs de publication sont :<br>
                        Alexis BARBERIS<br>
                        Vassili REGNIER<br>
                        Jérémy WATRIPONT<br>
                        Étudiants de deuxième année de BUT Informatique à l'IUT Aix-Marseille, site d'Aix-en-Provence.<br>
                        (ci-après « les Directeurs de publication »)
                    </p>

                    <h3 class="legal-subheading">C. Hébergeur</h3>
                    <div class="legal-highlight">
                        <p class="legal-paragraph">
                            Le site MedBoard est hébergé par :<br>
                            <strong>alwaysdata (SARL)</strong><br>
                            Adresse : 91, rue du Faubourg Saint-Honoré, 75008 Paris, France<br>
                            Téléphone : +33 1 84 16 23 49<br>
                            Site web : <a href="https://www.alwaysdata.com/" target="_blank" rel="noopener">https://www.alwaysdata.com/</a><br>
                            Support : <a href="mailto:support@alwaysdata.com">support@alwaysdata.com</a><br>
                            Espace client : <a href="https://admin.alwaysdata.com/" target="_blank" rel="noopener">https://admin.alwaysdata.com/</a><br>
                            (ci-après « l'Hébergeur »)
                        </p>
                    </div>

                    <h3 class="legal-subheading">D. Utilisateurs</h3>
                    <p class="legal-paragraph">
                        Sont considérés comme utilisateurs tous les internautes qui naviguent, lisent, visionnent et utilisent le site MedBoard (ci-après « les Utilisateurs »).
                    </p>

                    <!-- Article 2 : Accès au site -->
                    <h2 class="legal-section-heading">Article 2 – Accès au site</h2>
                    <p class="legal-paragraph">
                        Le site MedBoard est accessible gratuitement en tout lieu à tout Utilisateur disposant d'un accès à Internet. Tous les frais supportés par l'Utilisateur pour accéder au service (matériel informatique, logiciels, connexion Internet, etc.) sont à sa charge. L'Éditeur met en œuvre tous les moyens raisonnables à sa disposition pour assurer un accès de qualité, mais n'est tenu à aucune obligation de résultat.
                    </p>

                    <!-- Article 3 : Propriété intellectuelle -->
                    <h2 class="legal-section-heading">Article 3 – Contenu et propriété intellectuelle</h2>
                    <p class="legal-paragraph">
                        L'ensemble des contenus du site (textes, images, illustrations, logos, vidéos, etc.) est protégé par le droit d'auteur et demeure la propriété exclusive des Éditeurs ou de leurs partenaires. Toute reproduction, représentation, modification ou adaptation, totale ou partielle, de ces contenus, est strictement interdite sans l'autorisation préalable écrite des Éditeurs.
                    </p>

                    <!-- Article 4 : Responsabilité -->
                    <h2 class="legal-section-heading">Article 4 – Responsabilité</h2>
                    <p class="legal-paragraph">
                        Les Éditeurs s'efforcent de fournir des informations fiables et à jour, mais ne peuvent garantir l'exactitude, la complétude et l'actualité des informations diffusées sur le site. En conséquence, l'Utilisateur reconnaît utiliser ces informations sous sa responsabilité exclusive.
                    </p>

                    <!-- Article 5 : Données personnelles -->
                    <h2 class="legal-section-heading">Article 5 – Données personnelles</h2>
                    <p class="legal-paragraph">
                        Le traitement des données personnelles collectées dans le cadre du site MedBoard est réalisé conformément à la réglementation en vigueur. Les Utilisateurs disposent d'un droit d'accès, de rectification, d'opposition et de suppression des données les concernant. Ces droits peuvent être exercés à l'adresse suivante : <a href="mailto:contact@medboard.fr">contact@medboard.fr</a>.
                    </p>

                    <!-- Article 6 : Loi applicable -->
                    <h2 class="legal-section-heading">Article 6 – Loi applicable</h2>
                    <p class="legal-paragraph">
                        Les présentes mentions légales sont régies par la loi française. En cas de litige et à défaut d'accord amiable, le différend sera porté devant les tribunaux compétents français conformément aux règles de compétence en vigueur.
                    </p>
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
