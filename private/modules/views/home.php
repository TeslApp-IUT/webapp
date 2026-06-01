<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Encodage et viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO et indexation -->
    <meta name="description" content="MedBoard - Plateforme médicale nouvelle génération pour la gestion hospitalière intelligente">

    <!-- Métadonnées Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://medboard.alwaysdata.net/">
    <meta property="og:title" content="MedBoard - Plateforme médicale nouvelle génération">
    <meta property="og:description" content="MedBoard révolutionne la gestion hospitalière avec des outils intelligents, un monitoring en temps réel et une interface intuitive pensée pour les professionnels de santé.">
    <meta property="og:image" content="https://medboard.alwaysdata.net/_assets/images/LogoMedBoard.svg">

    <!-- Métadonnées Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://medboard.alwaysdata.net/">
    <meta name="twitter:title" content="MedBoard - Plateforme médicale nouvelle génération">
    <meta name="twitter:description" content="MedBoard révolutionne la gestion hospitalière avec des outils intelligents, un monitoring en temps réel et une interface intuitive pensée pour les professionnels de santé.">
    <meta name="twitter:image" content="https://medboard.alwaysdata.net/_assets/images/LogoMedBoard.svg">

    <!-- URL canonique -->
    <link rel="canonical" href="https://medboard.alwaysdata.net/">

    <title>MedBoard - Plateforme médicale nouvelle génération</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/_assets/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/_assets/css/styles.css">

    <!-- Données structurées Schema.org pour le référencement -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "MedicalOrganization",
      "name": "MedBoard",
      "url": "https://medboard.alwaysdata.net",
      "logo": "https://medboard.alwaysdata.net/_assets/images/LogoMedBoard.svg",
      "description": "Plateforme médicale nouvelle génération pour la gestion hospitalière intelligente",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "413, Avenue Gaston Berger",
        "addressLocality": "Aix-en-Provence",
        "postalCode": "13100",
        "addressCountry": "FR"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "contact@medboard.fr",
        "contactType": "customer service"
      }
    }
    </script>
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
        <!-- Section Hero (bandeau principal) -->
        <section class="hero" aria-labelledby="hero-title">
            <!-- Images de fond responsive avec différents formats -->
            <picture>
                <source srcset="/_assets/images/banniere_light.avif" type="image/avif">
                <source srcset="/_assets/images/banniere_light.webp" type="image/webp">
                <img src="/_assets/images/banniere_light.jpg" alt="" class="hero-bg hero-bg-light" aria-hidden="true" fetchpriority="high">
            </picture>
            <picture>
                <source srcset="/_assets/images/banniere_dark.avif" type="image/avif">
                <source srcset="/_assets/images/banniere_dark.webp" type="image/webp">
                <img src="/_assets/images/banniere_dark.jpg" alt="" class="hero-bg hero-bg-dark" aria-hidden="true" fetchpriority="high">
            </picture>
            <div class="hero-overlay" aria-hidden="true"></div>

            <!-- Contenu principal du hero -->
            <div class="container">
                <h1 id="hero-title" class="hero-title">
                    Plateforme médicale <span class="text-highlight">nouvelle génération</span>
                </h1>
                <p class="hero-description">
                    MedBoard révolutionne la gestion hospitalière avec des outils intelligents, un monitoring en temps réel et une interface intuitive pensée pour les professionnels de santé.
                </p>
                <!-- Boutons d'action -->
                <div class="hero-actions">
                    <a href="/auth/register" class="btn-primary">Créer un compte</a>
                    <a href="/auth/login" class="btn-secondary">Se connecter</a>
                </div>
            </div>
        </section>

        <!-- Section des fonctionnalités -->
        <section class="features" aria-labelledby="features-title">
            <div class="container">
                <!-- En-tête de section -->
                <div class="section-header">
                    <h2 id="features-title" class="section-title">Une plateforme médicale complète</h2>
                    <p class="section-description">
                        Découvrez les fonctionnalités avancées de notre solution développée spécialement pour optimiser les flux de travail médicaux
                    </p>
                </div>

                <!-- Grille des fonctionnalités -->
                <div class="features-grid">
                    <!-- Fonctionnalité : Gestion des patients -->
                    <article class="feature-card">
                        <div class="feature-icon feature-icon-green" aria-hidden="true">
                            <img src="/_assets/images/features-1.svg" alt="">
                        </div>
                        <h3 class="feature-title">Gestion des patients</h3>
                        <p class="feature-description">
                            Centralisation complète des dossiers médicaux avec historique, allergies et traitements en cours
                        </p>
                    </article>

                    <!-- Fonctionnalité : Monitoring temps réel -->
                    <article class="feature-card">
                        <div class="feature-icon feature-icon-orange" aria-hidden="true">
                            <img src="/_assets/images/features-2.svg" alt="">
                        </div>
                        <h3 class="feature-title">Monitoring temps réel</h3>
                        <p class="feature-description">
                            Surveillance continue des signes vitaux avec alertes automatiques et tableaux de bord personnalisables
                        </p>
                    </article>

                    <!-- Fonctionnalité : Sécurité renforcée -->
                    <article class="feature-card">
                        <div class="feature-icon feature-icon-red" aria-hidden="true">
                            <img src="/_assets/images/features-3.svg" alt="">
                        </div>
                        <h3 class="feature-title">Sécurité renforcée</h3>
                        <p class="feature-description">
                            Protection optimale des données sensibles avec chiffrement et conformité aux normes médicales
                        </p>
                    </article>

                    <!-- Fonctionnalité : Planning intelligent -->
                    <article class="feature-card">
                        <div class="feature-icon feature-icon-blue" aria-hidden="true">
                            <img src="/_assets/images/features-4.svg" alt="">
                        </div>
                        <h3 class="feature-title">Planning intelligent</h3>
                        <p class="feature-description">
                            Gestion optimisée des rendez-vous, rotations d'équipes et disponibilités des ressources médicales
                        </p>
                    </article>

                    <!-- Fonctionnalité : Collaboration médicale -->
                    <article class="feature-card">
                        <div class="feature-icon feature-icon-cyan" aria-hidden="true">
                            <img src="/_assets/images/features-5.svg" alt="">
                        </div>
                        <h3 class="feature-title">Collaboration médicale</h3>
                        <p class="feature-description">
                            Communication fluide entre services avec messagerie sécurisée et partage d'informations cliniques
                        </p>
                    </article>

                    <!-- Fonctionnalité : Analyses avancées -->
                    <article class="feature-card">
                        <div class="feature-icon feature-icon-orange" aria-hidden="true">
                            <img src="/_assets/images/features-6.svg" alt="">
                        </div>
                        <h3 class="feature-title">Analyses avancées</h3>
                        <p class="feature-description">
                            Rapports détaillés et statistiques médicales pour optimiser les soins et la gestion hospitalière
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Section FAQ -->
        <section class="faq" aria-labelledby="faq-title">
            <div class="container-narrow">
                <!-- En-tête de section -->
                <div class="section-header">
                    <h2 id="faq-title" class="section-title">Questions fréquentes</h2>
                    <p class="section-description">Trouvez rapidement les réponses à vos questions</p>
                </div>

                <!-- Liste des questions/réponses -->
                <div class="faq-list">
                    <!-- Question 1 -->
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>Comment s'organise le développement de notre projet R3.01 ?</span>
                            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
                        </button>
                        <div class="faq-answer">
                            <p>Nous avons structuré notre travail en plusieurs étapes : analyse du besoin, conception de la base de données, développement front-end et back-end, puis tests et déploiement.</p>
                        </div>
                    </div>

                    <!-- Question 2 -->
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>Où trouver les consignes officielles du rendu ?</span>
                            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
                        </button>
                        <div class="faq-answer">
                            <p>Toutes les consignes détaillées sont accessibles dans le document fourni par l'enseignant :</p>
                            <a href="https://www.mickael-martin-nevot.com/univ-amu/iut/but-informatique/developpement-web/?:s24-projet.pdf" class="faq-link" target="_blank" rel="noopener noreferrer">Consignes du projet R3.01</a>
                        </div>
                    </div>

                    <!-- Question 3 -->
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>Quelles technologies utilisons-nous pour ce projet ?</span>
                            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
                        </button>
                        <div class="faq-answer">
                            <p>Le projet repose sur un stack HTML, CSS, JavaScript et PHP. La base de données est en MySQL, hébergée chez Alwaysdata.</p>
                        </div>
                    </div>

                    <!-- Question 4 -->
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>Comment gérez-vous la sécurité des données ?</span>
                            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
                        </button>
                        <div class="faq-answer">
                            <p>Nous mettons en place une gestion stricte des comptes utilisateurs : mots de passe hachés avec Argon2id, validation des entrées côté serveur, et bonnes pratiques de protection contre les injections SQL ou XSS.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section CTA (Call to Action) -->
        <section class="cta" aria-labelledby="cta-title">
            <div class="container">
                <h2 id="cta-title" class="section-title">Prêt à commencer ?</h2>
                <p class="section-description">
                    La plateforme médicale nouvelle génération qui révolutionne la gestion des soins de santé.
                </p>
                <!-- Boutons d'action -->
                <div class="cta-actions">
                    <a href="/auth/register" class="btn-primary">Créer un compte</a>
                    <a href="/auth/login" class="btn-secondary">Se connecter</a>
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
    <script src="/_assets/js/script.js" defer></script>
</body>
</html>
