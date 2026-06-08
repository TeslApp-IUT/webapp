<?php
$vin = $_SESSION['selected_vin'] ?? null;

$title = 'Climatisation — TeslApp';
$description = 'Gérez la climatisation de votre Tesla à distance.';
$header = 'user';
$extraCss = ['dashboard', 'air-conditioning'];
$extraJs = ['ac'];

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <!-- Left Section -->
      <?php $activeNav = 'ac';
      require __DIR__ . '/../partials/dashboard_nav.php'; ?>
      <!-- Right Section -->
      <main class="dashboard-content">
        <h1 class="dashboard-title">Climatisation</h1>
        <div class="dashboard-grid">
          <!-- Enable / Disable -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true">
                <path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14" />
              </svg>
            </div>
            <h2 class="card-title">Climatisation</h2>
            <div class="card-details">
              <!-- Activate Button -->
              <form method="post" action="/climate/toggle" id="form-start">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="start">
                <input type="hidden" name="temperature" value="23" id="temp-hidden">
                <button type="button" class="btn-primary" id="btn-activer">Activer</button>
              </form>
              <!-- Temperature Section -->
              <div id="volet-temp" style="display:none; margin-top: 16px;">
                <label for="temp-slider" class="card-label">Température : <span id="temp-display">23</span> °C</label>
                <input type="range" id="temp-slider" min="15" max="28" step="0.5" value="23"
                       style="width:100%; margin: 8px 0;">
                <button type="submit" form="form-start" class="btn-second">Confirmer</button>
              </div>
              <!-- Disable Button -->
              <form method="post" action="/climate/toggle" style="margin-top: 8px;">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="stop">
                <button type="submit" class="btn-primary">Désactiver</button>
              </form>
            </div>
          </div>
          <!-- Keeper Mode -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
              </svg>
            </div>
            <h2 class="card-title">Climate Keeper Mode</h2>
            <div class="card-details">
              <form method="post" action="/climate/keeper">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <select name="climate_keeper_mode" class="card-input">
                  <option value="0">Off</option>
                  <option value="1">Keep</option>
                  <option value="2">Dog</option>
                  <option value="3">Camp</option>
                </select>
                <button type="submit" class="btn-primary">Appliquer</button>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';