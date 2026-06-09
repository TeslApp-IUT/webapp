<?php
$title = 'Mentions légales - TeslApp';
$description = "Mentions légales de TeslApp - Informations sur l'éditeur, l'hébergeur et les conditions d'utilisation";

ob_start();
?>
<section class="legal-section">
    <div class="legal-container">
        <!-- Back link -->
        <a href="/" class="back-link">
            <img src="/_assets/images/fleche-gauche.svg" alt="" aria-hidden="true">
            Retour à l'accueil
        </a>

        <!-- Page header -->
        <div class="legal-header">
            <h1 class="legal-title">Mentions légales</h1>
            <p class="legal-subtitle">Dernière mise à jour : <span>01/06/2026</span></p>
        </div>

        <!-- Legal notice content -->
        <div class="legal-content">
            <p class="legal-paragraph">
                Conformément aux dispositions des articles 6-III et 19 de la loi n° 2004-575 du 21 juin 2004 pour la confiance dans l'économie numérique (LCEN), il est porté à la connaissance des utilisateurs et visiteurs du site TeslApp les présentes mentions légales.
            </p>
            <p class="legal-paragraph">
                Le site TeslApp est accessible à l'adresse suivante : <a href="https://teslapp.feyli.dev/" target="_blank" rel="noopener">https://teslapp.feyli.dev/</a> (ci-après « le Site »). L'accès et l'utilisation du Site sont soumis aux présentes mentions légales détaillées ci-après, ainsi qu'aux lois et/ou règlements applicables. La connexion, l'utilisation et l'accès au Site impliquent l'acceptation intégrale et sans réserve de toutes les dispositions des présentes mentions légales.
            </p>

            <h2 class="legal-section-heading">Article 1 – Informations légales</h2>

            <h3 class="legal-subheading">A. Éditeurs du site</h3>
            <p class="legal-paragraph">
                Le site TeslApp est édité par :<br>
                Alexis BARBERIS<br>
                Mathis FAUTSCH<br>
                Mathis LAURIOL-TORCQ<br>
                Oriane MEJEAN<br>
                Jérémy WATRIPONT<br>
                Étudiants de deuxième année de BUT Informatique à l'IUT Aix-Marseille, site d'Aix-en-Provence.<br>
                (ci-après « les Éditeurs »)
            </p>

            <h3 class="legal-subheading">B. Directeur de publication</h3>
            <p class="legal-paragraph">
                Les directeurs de publication sont les Éditeurs susmentionnés, étudiants de deuxième année de BUT Informatique à l'IUT Aix-Marseille, site d'Aix-en-Provence.
            </p>

            <h3 class="legal-subheading">C. Hébergeur</h3>
            <div class="legal-highlight">
                <p class="legal-paragraph">
                    Le site TeslApp est hébergé par <strong>Feyli</strong>, l'infrastructure du projet, et accessible
                    à l'adresse <a href="https://teslapp.feyli.dev" target="_blank" rel="noopener noreferrer">teslapp.feyli.dev</a>
                    (ci-après « l'Hébergeur »).
                </p>
                <p class="legal-paragraph">
                    Le nom de domaine <strong>feyli.dev</strong> est enregistré auprès du bureau d'enregistrement
                    <strong>Namecheap, Inc.</strong> — 4600 East Washington Street, Suite 300, Phoenix, AZ 85034,
                    États-Unis (signalement d'abus : <a href="mailto:abuse@namecheap.com">abuse@namecheap.com</a>,
                    +1&nbsp;661&nbsp;310&nbsp;2107).
                </p>
                <p class="legal-paragraph">
                    La résolution DNS et la distribution du site sont assurées par <strong>Cloudflare,&nbsp;Inc.</strong>
                    — 101 Townsend Street, San Francisco, CA 94107, États-Unis.
                </p>
            </div>

            <h3 class="legal-subheading">D. Utilisateurs</h3>
            <p class="legal-paragraph">
                Sont considérés comme utilisateurs tous les internautes qui naviguent, lisent, visionnent et utilisent le site TeslApp (ci-après « les Utilisateurs »).
            </p>

            <h2 class="legal-section-heading">Article 2 – Accès au site</h2>
            <p class="legal-paragraph">
                Le site TeslApp est accessible gratuitement en tout lieu à tout Utilisateur disposant d'un accès à Internet. Tous les frais supportés par l'Utilisateur pour accéder au service (matériel informatique, logiciels, connexion Internet, etc.) sont à sa charge. L'Éditeur met en œuvre tous les moyens raisonnables à sa disposition pour assurer un accès de qualité, mais n'est tenu à aucune obligation de résultat.
            </p>

            <h2 class="legal-section-heading">Article 3 – Contenu et propriété intellectuelle</h2>
            <p class="legal-paragraph">
                L'ensemble des contenus du site (textes, images, illustrations, logos, vidéos, etc.) est protégé par le droit d'auteur et demeure la propriété exclusive des Éditeurs ou de leurs partenaires. Toute reproduction, représentation, modification ou adaptation, totale ou partielle, de ces contenus, est strictement interdite sans l'autorisation préalable écrite des Éditeurs. TeslApp est un projet étudiant indépendant, non affilié à Tesla, Inc.
            </p>

            <h2 class="legal-section-heading">Article 4 – Responsabilité</h2>
            <p class="legal-paragraph">
                Les Éditeurs s'efforcent de fournir des informations fiables et à jour, mais ne peuvent garantir l'exactitude, la complétude et l'actualité des informations diffusées sur le site. En conséquence, l'Utilisateur reconnaît utiliser ces informations sous sa responsabilité exclusive.
            </p>

            <h2 class="legal-section-heading">Article 5 – Données personnelles</h2>
            <p class="legal-paragraph">
                Le traitement des données personnelles collectées dans le cadre du site TeslApp est réalisé conformément à la réglementation en vigueur. Les Utilisateurs disposent d'un droit d'accès, de rectification, d'opposition et de suppression des données les concernant. Ces droits peuvent être exercés à l'adresse suivante : <a href="mailto:contact@teslapp.feyli.dev">contact@teslapp.feyli.dev</a>.
            </p>

            <h2 class="legal-section-heading">Article 6 – Loi applicable</h2>
            <p class="legal-paragraph">
                Les présentes mentions légales sont régies par la loi française. En cas de litige et à défaut d'accord amiable, le différend sera porté devant les tribunaux compétents français conformément aux règles de compétence en vigueur.
            </p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
