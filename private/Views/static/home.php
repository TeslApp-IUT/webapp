<?php
$title = 'TeslApp - Votre Tesla, partout avec vous';
$description = "TeslApp - Contrôlez votre Tesla à distance. Climatisation, recharge, verrouillage et historique de trajets via l'API Fleet.";
$extraJs = ['script'];

ob_start();
?>
  <!-- Hero Section (main banner) -->
  <section class="hero" aria-labelledby="hero-title">
    <!-- Responsive background image -->
    <picture>
      <source srcset="/_assets/images/banniere_light.avif" type="image/avif">
      <source srcset="/_assets/images/banniere_light.webp" type="image/webp">
      <img src="/_assets/images/banniere_light.jpg" alt="" class="hero-bg hero-bg-light" aria-hidden="true" fetchpriority="high">
    </picture>
    <div class="hero-overlay" aria-hidden="true"></div>

    <!-- Main content of the hero -->
    <div class="container">
      <h1 id="hero-title" class="hero-title">
        Votre Tesla, <span class="text-highlight">partout avec vous</span>
      </h1>
      <p class="hero-description">
        Contrôlez votre véhicule Tesla à distance : climatisation, recharge, verrouillage, localisation et historique de trajets. Une interface intuitive et sécurisée connectée à l'API Fleet.
      </p>
      <!-- Action: Tesla login or Dashboard access -->
      <div class="hero-actions">
        <?php
        if ($_SESSION['logged_in'] === true) {
          echo '<a href="/dashboard" class="btn-primary !bg-white !text-black !font-normal"><span>Tableau de bord</span></a>';
        } else {
          require_once __DIR__ . '/../partials/login.php';
        }
        ?>
      </div>
    </div>
  </section>

  <!-- Features section -->
  <section class="features" aria-labelledby="features-title">
    <div class="container">
      <div class="section-header">
        <h2 id="features-title" class="section-title">Tout le contrôle de votre Tesla en un clic</h2>
        <p class="section-description">
          Découvrez les fonctionnalités de TeslApp pour piloter votre véhicule à distance, où que vous soyez
        </p>
      </div>

      <div class="features-grid">
        <article class="feature-card">
          <div class="feature-icon feature-icon-red" aria-hidden="true">
            <img src="/_assets/images/features-1.svg" alt="">
          </div>
          <h3 class="feature-title">Climatisation intelligente</h3>
          <p class="feature-description">
            Activez la climatisation à distance, réglez la température et programmez le préchauffage ou le refroidissement de votre habitacle
          </p>
        </article>

        <article class="feature-card">
          <div class="feature-icon feature-icon-red" aria-hidden="true">
            <img src="/_assets/images/features-2.svg" alt="">
          </div>
          <h3 class="feature-title">Gestion de la recharge</h3>
          <p class="feature-description">
            Suivez le niveau de batterie en temps réel, définissez une limite de charge et planifiez vos sessions de recharge
          </p>
        </article>

        <article class="feature-card">
          <div class="feature-icon feature-icon-red" aria-hidden="true">
            <img src="/_assets/images/features-3.svg" alt="">
          </div>
          <h3 class="feature-title">Verrouillage et sécurité</h3>
          <p class="feature-description">
            Verrouillez ou déverrouillez votre véhicule, activez le mode Sentinelle et déclenchez klaxon ou phares à distance
          </p>
        </article>

        <article class="feature-card">
          <div class="feature-icon feature-icon-red" aria-hidden="true">
            <img src="/_assets/images/features-4.svg" alt="">
          </div>
          <h3 class="feature-title">Localisation GPS</h3>
          <p class="feature-description">
            Retrouvez votre Tesla sur une carte interactive et consultez sa position en temps réel depuis votre tableau de bord
          </p>
        </article>

        <article class="feature-card">
          <div class="feature-icon feature-icon-red" aria-hidden="true">
            <img src="/_assets/images/features-5.svg" alt="">
          </div>
          <h3 class="feature-title">Historique des trajets</h3>
          <p class="feature-description">
            Consultez tous vos trajets passés avec distance, durée et consommation. Exportez vos données au format CSV
          </p>
        </article>

        <article class="feature-card">
          <div class="feature-icon feature-icon-red" aria-hidden="true">
            <img src="/_assets/images/features-6.svg" alt="">
          </div>
          <h3 class="feature-title">Tableau de bord complet</h3>
          <p class="feature-description">
            Visualisez d'un coup d'œil l'état de votre véhicule : batterie, kilométrage, état de verrouillage et statut de charge
          </p>
        </article>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq" aria-labelledby="faq-title">
    <div class="container-narrow">
      <div class="section-header">
        <h2 id="faq-title" class="section-title">Questions fréquentes</h2>
        <p class="section-description">Trouvez rapidement les réponses à vos questions sur TeslApp</p>
      </div>

      <div class="faq-list">
        <div class="faq-item">
          <button class="faq-question" aria-expanded="false">
            <span>Comment TeslApp se connecte-t-il à ma Tesla ?</span>
            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
          </button>
          <div class="faq-answer">
            <p>TeslApp utilise l'API Fleet officielle de Tesla avec une authentification OAuth2 sécurisée. Vous vous connectez avec votre compte Tesla et autorisez TeslApp à accéder à votre véhicule. Vos identifiants Tesla ne sont jamais stockés sur nos serveurs.</p>
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" aria-expanded="false">
            <span>Pourquoi ma Tesla met-elle du temps à répondre aux commandes ?</span>
            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
          </button>
          <div class="faq-answer">
            <p>Votre Tesla peut être en mode veille pour économiser la batterie. TeslApp envoie automatiquement une commande "wake-up" avant chaque action. Ce réveil peut prendre quelques secondes selon l'état du véhicule et la qualité du réseau.</p>
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" aria-expanded="false">
            <span>Mes données de localisation sont-elles protégées ?</span>
            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
          </button>
          <div class="faq-answer">
            <p>Absolument. TeslApp respecte le RGPD : vos données de localisation sont minimisées, chiffrées en base de données et vous pouvez les supprimer à tout moment. Les tokens OAuth sont stockés de manière sécurisée et jamais exposés côté client.</p>
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" aria-expanded="false">
            <span>Quelles actions puis-je effectuer avec TeslApp ?</span>
            <img src="/_assets/images/fleche-bas.svg" alt="" class="faq-icon" aria-hidden="true">
          </button>
          <div class="faq-answer">
            <p>TeslApp vous permet de : verrouiller/déverrouiller votre véhicule, activer la climatisation et régler la température, gérer la recharge (limite, planification), ouvrir les coffres et la trappe de recharge, déclencher le klaxon et les phares, consulter l'historique de vos trajets et localiser votre véhicule sur une carte.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA (Call-to-Action) Section -->
  <section class="cta" aria-labelledby="cta-title">
    <div class="container">
      <h2 id="cta-title" class="section-title">Prenez le contrôle de votre Tesla</h2>
      <p class="section-description">
        Rejoignez TeslApp et pilotez votre véhicule à distance en toute simplicité et sécurité.
      </p>
      <div class="cta-actions">
        <?php require __DIR__ . '/../partials/login.php'; ?>
      </div>
    </div>
  </section>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
