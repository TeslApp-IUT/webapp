<?php

/**
 * Navigation page view.
 * Displays navigation-related information for the vehicle.
 *
 * @var string $vehicleId Vehicle public id, set by the rendering controller.
 * @var array $data Navigation telemetry data.
 */

$title = 'Navigation — TeslApp';
$description = 'Navigation de votre Tesla : routes et informations de trajet.';
$header = 'user';
$extraCss = ['dashboard', 'navigation'];
$extraJs = ['navigation'];

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <!-- Left Section -->
      <?php $activeNav = 'navigation';
      require __DIR__ . '/../partials/dashboard_nav.php'; ?>
      <!-- Right Section -->
      <div class="dashboard-content">
        <h1 class="dashboard-title">Navigation</h1>
        <div class="dashboard-grid">
          <!-- Navigation state card -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" />
              </svg>
            </div>
            <h2 class="card-title">État navigation</h2>
            <div class="card-content">
              <p class="card-label">Navigation disponible</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
?>
