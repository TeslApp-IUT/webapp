<?php
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
<section class="dashboard">
  <div class="container">
    <h1 class="dashboard-title">Dashboard — Mon véhicule</h1>
    <div class="dashboard-grid">
      <!-- Batterie -->
      <div class="dashboard-card">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="2" y="7" width="16" height="10" rx="2"/>
            <line x1="22" y1="11" x2="22" y2="13"/>
          </svg>
        </div>
        <h2 class="card-title">Batterie</h2>
        <div class="card-content">
          <p class="card-value"><?= e((string) $battery_level) ?><span> %</span></p>
          <p class="card-label">Batterie actuelle</p>
        </div>
        <div class="card-details">
          <p>Charge activée <span class="<?= $charge_enable_request ? 'status-on' : 'status-off' ?>"><?= $charge_enable_request ? 'Oui' : 'Non' ?></span></p>
          <p>Charge programmée <span><?= e($scheduled_charging_start_time ?? 'Non programmée') ?></span></p>
        </div>
      </div>

      <!-- Climatisation -->
      <div class="dashboard-card">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14"/>
          </svg>
        </div>
        <h2 class="card-title">Climatisation</h2>
        <div class="card-content">
          <p class="card-value"><?= e((string) $inside_temp) ?><span> °C</span></p>
          <p class="card-label">Température intérieure</p>
        </div>
        <div class="card-details">
          <p>AC activée <span class="<?= $hvac_ac_enabled ? 'status-on' : 'status-off' ?>"><?= $hvac_ac_enabled ? 'Oui' : 'Non' ?></span></p>
          <p>Mode keeper <span><?= e($keeper_modes[$climate_keeper_mode] ?? 'Inconnu') ?></span></p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="dashboard-actions">
      <h2 class="dashboard-actions-title">Actions disponibles</h2>
      <div class="actions-grid">
        <button class="action-btn" type="button" disabled>Verrouiller / Déverrouiller</button>
        <button class="action-btn" type="button" disabled>Klaxon</button>
        <button class="action-btn" type="button" disabled>Batterie</button>
        <button class="action-btn" type="button" disabled>Climatisation</button>
        <button class="action-btn" type="button" disabled>Localisation</button>
      </div>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';