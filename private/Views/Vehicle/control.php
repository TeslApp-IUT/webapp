<?php
/**
 * Vehicle command page — the "Véhicule" dashboard tab (issue #26).
 * Hosts the remote command buttons; each button POSTs to /vehicle/<action>
 * (handled by VehicleCommandController), VIN resolved server-side from session.
 */
$title = 'Commandes du véhicule — TeslApp';
$description = 'Commandes à distance de votre véhicule Tesla : verrouillage, klaxon, coffres et trappe de charge.';
$header = 'user';
$extraCss = ['dashboard', 'vehicle-actions'];
$extraJs = ['vehicle-actions'];

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <?php $activeNav = 'vehicle'; require __DIR__ . '/../partials/dashboard_nav.php'; ?>

      <main class="dashboard-content">
        <h1 class="dashboard-title">Commandes du véhicule</h1>

        <!-- Actions (vehicle commands, issue #26) -->
        <div class="dashboard-actions">
          <h2 class="dashboard-actions-title">Actions disponibles</h2>
          <div class="actions-grid">
            <button class="action-btn" type="button" data-action="lock">Verrouiller</button>
            <button class="action-btn" type="button" data-action="unlock">Déverrouiller</button>
            <button class="action-btn" type="button" data-action="honk">Klaxon</button>
            <button class="action-btn" type="button" data-action="flash">Appel de phares</button>
            <button class="action-btn" type="button" data-action="trunk-front">Coffre avant</button>
            <button class="action-btn" type="button" data-action="trunk-rear">Coffre arrière</button>
            <button class="action-btn" type="button" data-action="charge-port-open">Ouvrir la trappe de charge</button>
            <button class="action-btn" type="button" data-action="charge-port-close">Fermer la trappe de charge</button>
            <button class="action-btn" type="button" data-action="wake">Réveiller</button>
            <!-- Owned by other issues (battery #25, climate #28, location #32) — left disabled -->
            <button class="action-btn" type="button" disabled>Batterie</button>
            <button class="action-btn" type="button" disabled>Climatisation</button>
            <button class="action-btn" type="button" disabled>Localisation</button>
          </div>
          <p class="actions-feedback" role="status" aria-live="polite" data-action-feedback></p>
        </div>

        <p class="dashboard-change-vehicle">
          <a href="/vehicle/select">Changer de véhicule</a>
        </p>
      </main>
    </div>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
