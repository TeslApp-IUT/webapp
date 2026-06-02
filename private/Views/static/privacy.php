<?php
$title = 'Politique de confidentialité - TeslApp';
$description = 'Politique de confidentialité TeslApp - Protection de vos données personnelles et conformité RGPD';

ob_start();
?>
<section class="legal-section">
    <div class="legal-container">
        <!-- Lien de retour -->
        <a href="/site/home" class="back-link">
            <img src="/_assets/images/fleche-gauche.svg" alt="" aria-hidden="true">
            Retour à l'accueil
        </a>

        <!-- En-tête de la page -->
        <div class="legal-header">
            <h1 class="legal-title">Politique de Confidentialité</h1>
            <p class="legal-subtitle">Dernière mise à jour : <span>01/06/2026</span></p>
        </div>

        <!-- Contenu de la politique -->
        <div class="legal-content">
            <p class="legal-paragraph">
                Le but de cette politique de confidentialité est d'informer les utilisateurs de notre site des données personnelles que nous recueillons ainsi que des informations suivantes, le cas échéant :
            </p>
            <ol class="legal-list--alpha">
                <li>Les données personnelles que nous recueillerons</li>
                <li>L'utilisation des données recueillies</li>
                <li>Qui a accès aux données recueillies</li>
                <li>Les droits des utilisateurs du site</li>
                <li>La politique de cookies du site</li>
            </ol>
            <p class="legal-paragraph">
                Cette politique de confidentialité s'applique en complément des conditions générales d'utilisation de notre site.
            </p>

            <h2 class="legal-section-heading">Lois applicables</h2>
            <p class="legal-paragraph">
                Conformément au Règlement général sur la protection des données (RGPD), cette politique de confidentialité est conforme aux normes suivantes.
            </p>

            <h3 class="legal-subheading">Principes du RGPD</h3>
            <p class="legal-paragraph">
                Les données à caractère personnel doivent être :
            </p>
            <ol class="legal-list--alpha">
                <li>traitées de manière licite, loyale et transparente au regard de la personne concernée (licéité, loyauté, transparence) ;</li>
                <li>collectées pour des finalités déterminées, explicites et légitimes, et ne pas être traitées ultérieurement d'une manière incompatible avec ces finalités (limitation des finalités) ;</li>
                <li>adéquates, pertinentes et limitées à ce qui est nécessaire au regard des finalités pour lesquelles elles sont traitées (minimisation des données) ;</li>
                <li>exactes et, si nécessaire, tenues à jour (exactitude) ;</li>
                <li>conservées sous une forme permettant l'identification des personnes concernées pendant une durée n'excédant pas celle nécessaire (limitation de la conservation) ;</li>
                <li>traitées de façon à garantir une sécurité appropriée des données à caractère personnel (intégrité et confidentialité).</li>
            </ol>

            <h3 class="legal-subheading">Bases légales du traitement</h3>
            <p class="legal-paragraph">
                Le traitement n'est licite que si, et dans la mesure où, au moins une des conditions suivantes est remplie :
            </p>
            <ol class="legal-list--alpha">
                <li>la personne concernée a consenti au traitement de ses données à caractère personnel ;</li>
                <li>le traitement est nécessaire à l'exécution d'un contrat ;</li>
                <li>le traitement est nécessaire au respect d'une obligation légale ;</li>
                <li>le traitement est nécessaire à la sauvegarde des intérêts vitaux ;</li>
                <li>le traitement est nécessaire à l'exécution d'une mission d'intérêt public ;</li>
                <li>le traitement est nécessaire aux fins des intérêts légitimes poursuivis.</li>
            </ol>
            <p class="legal-paragraph">
                Pour les résidents de l'État de Californie, cette politique de confidentialité vise à se conformer à la California Consumer Privacy Act (CCPA). En cas d'incohérences entre ce document et la CCPA, la législation de l'État s'appliquera.
            </p>

            <h2 class="legal-section-heading">Consentement</h2>
            <p class="legal-paragraph">
                Les utilisateurs conviennent qu'en utilisant notre site, ils acceptent :
            </p>
            <ol class="legal-list--alpha">
                <li>les conditions énoncées dans la présente politique de confidentialité ; et</li>
                <li>la collecte, l'utilisation et la conservation des données énumérées dans la présente politique.</li>
            </ol>

            <h2 class="legal-section-heading">Données personnelles que nous collectons</h2>
            <p class="legal-paragraph">
                Dans le cadre de la connexion à votre compte Tesla via l'API Fleet, TeslApp traite les données strictement nécessaires au service : identifiants OAuth (chiffrés), informations de véhicule et, le cas échéant, données de localisation. Ces données sont minimisées et ne sont jamais revendues à des tiers.
            </p>

            <h2 class="legal-section-heading">Modifications</h2>
            <p class="legal-paragraph">
                Cette politique de confidentialité peut être modifiée de temps à autre afin de maintenir la conformité avec la loi et de tenir compte de tout changement de notre processus de collecte de données. Nous recommandons à nos utilisateurs de la vérifier régulièrement pour s'assurer qu'ils sont informés de toute mise à jour.
            </p>

            <h2 class="legal-section-heading">Contact</h2>
            <div class="legal-highlight">
                <p class="legal-paragraph">
                    Si vous avez des questions à nous poser, n'hésitez pas à communiquer avec nous :<br>
                    Email : <a href="mailto:contact@teslapp.feyli.dev">contact@teslapp.feyli.dev</a>
                </p>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
