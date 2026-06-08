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
$extraCss = ['dashboard'];

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <!-- Left Section -->
      <nav class="dashboard-navigation">
        <a href="/dashboard/overview" class="nav-item nav-item--active" aria-label="Vue générale">
            <span class="nav-item__icon nav-item__icon--overview">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </span>
          <span class="nav-item__label">Vue d’ensemble</span>
        </a>
        <a href="/vehicle/select" class="nav-item" aria-label="Véhicule">
            <span class="nav-item__icon nav-item__icon--vehicle">
              <svg width="100" height="100" viewBox="4 4 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M28.5 62.5C28.5 66.6421 25.1421 70 21 70C16.8579 70 13.5 66.6421 13.5 62.5C13.5 58.3579 16.8579 55 21 55C25.1421 55 28.5 58.3579 28.5 62.5ZM21 67.1875C23.5888 67.1875 25.6875 65.0888 25.6875 62.5C25.6875 59.9112 23.5888 57.8125 21 57.8125C18.4112 57.8125 16.3125 59.9112 16.3125 62.5C16.3125 65.0888 18.4112 67.1875 21 67.1875Z"
                      fill="currentColor" />
                <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M83.5 62.5C83.5 66.6421 80.1421 70 76 70C71.8579 70 68.5 66.6421 68.5 62.5C68.5 58.3579 71.8579 55 76 55C80.1421 55 83.5 58.3579 83.5 62.5ZM76 67.1875C78.5888 67.1875 80.6875 65.0888 80.6875 62.5C80.6875 59.9112 78.5888 57.8125 76 57.8125C73.4112 57.8125 71.3125 59.9112 71.3125 62.5C71.3125 65.0888 73.4112 67.1875 76 67.1875Z"
                      fill="currentColor" />
                <path
                  d="M12 62C12 62 8.99989 61.5 9 59C9.00011 56.5 9 45.5 9 45.5H22.5C24 42 30 35 30 35H54.5C59 35 70 45.5 70 45.5C81 45.5 88 49.5 91.5 52V62H85M30 62H67"
                  stroke="currentColor" stroke-width="3" stroke-linejoin="round" />
              </svg>
            </span>
          <span class="nav-item__label">Véhicule</span>
        </a>
        <a href="/dashboard/ac" class="nav-item" aria-label="Climatisation">
            <span class="nav-item__icon nav-item__icon--ac">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14" />
                </svg>
            </span>
          <span class="nav-item__label">Climatisation</span>
        </a>
        <a href="/dashboard/battery" class="nav-item" aria-label="Batterie">
            <span class="nav-item__icon nav-item__icon--battery">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0 0 21 15.75v-6a2.25 2.25 0 0 0-2.25-2.25h-15A2.25 2.25 0 0 0 1.5 9.75v6A2.25 2.25 0 0 0 3.75 18Z" />
                </svg>
            </span>
          <span class="nav-item__label">Batterie</span>
        </a>
        <a href="/dashboard/navigation" class="nav-item" aria-label="Navigation">
            <span class="nav-item__icon nav-item__icon--navigation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" />
                    <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" />
                </svg>
            </span>
          <span class="nav-item__label">Navigation</span>
        </a>
      </nav>

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