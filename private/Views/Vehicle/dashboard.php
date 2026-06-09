<?php
$vehicleName = $vehicleName ?? 'Mon véhicule';

/* Telemetry value */
$battery_level = $data['battery_level'] ?? 'N/A';
$charge_enable_request = $data['charge_enable_request'] ?? false;
$scheduled_charging_start_time = $data['scheduled_charging_start_time'] ?? null;

$inside_temp = $data['inside_temp'] ?? 'N/A';
$climate_keeper_mode = $data['climate_keeper_mode'] ?? 0;
$hvac_ac_enabled = $data['hvac_ac_enabled'] ?? false;

$keeper_modes = [
  0 => 'Inconnu',
  1 => 'Off',
  2 => 'On',
  3 => 'Dog',
  4 => 'Party',
];

$title = 'Dashboard TeslApp';
$description = 'Tableau de bord de votre véhicule Tesla : batterie, climatisation et actions à distance.';
$header = 'user';
$extraCss = ['dashboard', 'vehicle-actions'];
$extraJs = ['vehicle-actions'];

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <!-- Left Section -->
      <?php $activeNav = 'overview';
      require __DIR__ . '/../partials/dashboard_nav.php'; ?>
      <!-- Right Section -->
      <main class="dashboard-content">
        <h1 class="dashboard-title">Tableau de bord — <?= e($vehicleName) ?></h1>
        <div class="dashboard-grid">
          <!-- Battery -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                   stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0 0 21 15.75v-6a2.25 2.25 0 0 0-2.25-2.25h-15A2.25 2.25 0 0 0 1.5 9.75v6A2.25 2.25 0 0 0 3.75 18Z" />
              </svg>
            </div>
            <h2 class="card-title">Batterie</h2>
            <div class="card-content">
              <p class="card-value"><?= e((string)$battery_level) ?><span> %</span></p>
              <p class="card-label">Batterie actuelle</p>
            </div>
            <div class="card-details">
              <p>Charge activée <span
                  class="<?= $charge_enable_request ? 'status-on' : 'status-off' ?>"><?= $charge_enable_request ? 'Oui' : 'Non' ?></span>
              </p>
              <p>Charge programmée <span><?= e($scheduled_charging_start_time ?? 'Non programmée') ?></span></p>
            </div>
          </div>
          <!-- Clim -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true">
                <path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14" />
              </svg>
            </div>
            <h2 class="card-title">Climatisation</h2>
            <div class="card-content">
              <p class="card-value"><?= e((string)$inside_temp) ?><span> °C</span></p>
              <p class="card-label">Température intérieure</p>
            </div>
            <div class="card-details">
              <p>AC activée <span
                  class="<?= $hvac_ac_enabled ? 'status-on' : 'status-off' ?>"><?= $hvac_ac_enabled ? 'Oui' : 'Non' ?></span>
              </p>
              <p>Mode keeper <span><?= e($keeper_modes[$climate_keeper_mode] ?? 'Inconnu') ?></span></p>
            </div>
          </div>
        </div>
      </main>
    </div>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';