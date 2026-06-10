<?php
/**
 * Vehicle command page — the "Véhicule" dashboard tab (issue #26).
 * A primary "wake" banner on top, then remote command tiles grouped by
 * category. Each wired control (the hero + every tile) is a <button data-action>
 * that POSTs to /vehicle/<action> (handled by VehicleCommandController); the VIN
 * is resolved server-side from the session. The JS (vehicle-actions.js) toggles
 * an .is-loading class and writes the shared [data-action-feedback] element — it
 * never rewrites a button's content, so the SVG pictograms are preserved.
 */
$title = 'Commandes du véhicule — TeslApp';
$description = 'Commandes à distance de votre véhicule Tesla : verrouillage, klaxon, coffres et trappe de charge.';
$header = 'user';
$extraCss = ['dashboard', 'vehicle-actions'];
$extraJs = ['vehicle-actions'];
$headExtra = '<meta name="vehicle-id" content="' . e($vehicleId) . '">';

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <?php $activeNav = 'vehicle'; require __DIR__ . '/../partials/dashboard_nav.php'; ?>

      <div class="dashboard-content">
        <header class="commands-header">
          <h1 class="dashboard-title">Commandes du véhicule</h1>
          <a href="/dashboard" class="btn-switch-vehicle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            Changer de véhicule
          </a>
        </header>

        <p class="actions-feedback" role="status" aria-live="polite" data-action-feedback></p>

        <div class="vehicle-commands">
          <!-- Primary action: wake the vehicle -->
          <button class="command-hero" type="button" data-action="wake">
            <span class="command-hero__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
              </svg>
            </span>
            <span class="command-hero__text">
              <span class="command-hero__title">Réveiller le véhicule</span>
              <span class="command-hero__hint">À lancer avant une commande si la voiture est en veille</span>
            </span>
          </button>

          <!-- Accès & sécurité -->
          <section class="command-group">
            <h2 class="command-group__title">Accès &amp; sécurité</h2>
            <div class="command-grid">
              <button class="command-card" type="button" data-action="lock">
                <span class="command-card__icon command-card__icon--access">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                  </svg>
                </span>
                <span class="command-card__label">Verrouiller</span>
              </button>
              <button class="command-card" type="button" data-action="unlock">
                <span class="command-card__icon command-card__icon--access">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                  </svg>
                </span>
                <span class="command-card__label">Déverrouiller</span>
              </button>
            </div>
          </section>

          <!-- Coffres -->
          <section class="command-group">
            <h2 class="command-group__title">Coffres</h2>
            <div class="command-grid">
              <button class="command-card" type="button" data-action="trunk-front">
                <span class="command-card__icon command-card__icon--trunk">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                  </svg>
                </span>
                <span class="command-card__label">Coffre avant</span>
              </button>
              <button class="command-card" type="button" data-action="trunk-rear">
                <span class="command-card__icon command-card__icon--trunk">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                  </svg>
                </span>
                <span class="command-card__label">Coffre arrière</span>
              </button>
            </div>
          </section>

          <!-- Recharge -->
          <section class="command-group">
            <h2 class="command-group__title">Recharge</h2>
            <div class="command-grid">
              <button class="command-card" type="button" data-action="charge-port-open">
                <span class="command-card__icon command-card__icon--charge">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                  </svg>
                </span>
                <span class="command-card__label">Ouvrir la trappe</span>
              </button>
              <button class="command-card" type="button" data-action="charge-port-close">
                <span class="command-card__icon command-card__icon--charge">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    <path d="M3 3l18 18" />
                  </svg>
                </span>
                <span class="command-card__label">Fermer la trappe</span>
              </button>
            </div>
          </section>

          <!-- Signalement -->
          <section class="command-group">
            <h2 class="command-group__title">Signalement</h2>
            <div class="command-grid">
              <button class="command-card" type="button" data-action="honk">
                <span class="command-card__icon command-card__icon--signal">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.011 9.011 0 0 1 2.25 12c0-.83.112-1.633.322-2.395C2.806 8.755 3.63 8.25 4.51 8.25H6.75Z" />
                  </svg>
                </span>
                <span class="command-card__label">Klaxon</span>
              </button>
              <button class="command-card" type="button" data-action="flash">
                <span class="command-card__icon command-card__icon--signal">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.355a14.4 14.4 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                  </svg>
                </span>
                <span class="command-card__label">Appel de phares</span>
              </button>
            </div>
          </section>
        </div>
      </div>
    </div>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
