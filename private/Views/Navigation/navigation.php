<?php

/**
 * Navigation page view.
 * Displays navigation-related information for the vehicle.
 *
 * @var string $vehicleId Vehicle public id, set by the rendering controller.
 * @var Trip[] $trips
 * @var array<string, array{start: ?string, end: ?string}> $addresses reverse-geocoded trip endpoints, keyed by trip id
 */

use Teslapp\Models\Navigation\Trip;

$title = 'Navigation — TeslApp';
$description = 'Navigation de votre Tesla : routes et informations de trajet.';
$header = 'user';
$extraCss = ['dashboard', 'navigation'];
$extraJs = ['navigation'];

$runningDot = [
  true => '<div class="bg-green-500/80 rounded-full h-3 aspect-square animate-pulse"></div>',
  false => '<div class="bg-amber-500/80 ring-amber-500 ring-2 rounded-full h-3 aspect-square drop-shadow-lg drop-shadow-amber-500/30"></div>'
];

ob_start();
?>
<section>
  <div class="dashboard-layout">
    <!-- Left Section -->
    <?php $activeNav = 'navigation';
    require_once __DIR__ . '/../partials/dashboard_nav.php'; ?>
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
          <h2 class="card-title">Historique des trajets</h2>
          <div class="card-content">
            <div class="flex flex-col gap-2">
              <?php
              foreach ($trips as $trip) {
                $startTimestamp = date_timestamp_get($trip->startTime);
                $unknown = 'Adresse inconnue';
                $startAddress = htmlspecialchars($addresses[$trip->id]['start'] ?? $unknown, ENT_QUOTES);
                $endAddress = htmlspecialchars($addresses[$trip->id]['end'] ?? $unknown, ENT_QUOTES);
                echo <<<EOD
                <div class="flex flex-row justify-between items-center w-full bg-gray-500/10 rounded-xl p-3.5">
                  <div class="flex flex-row gap-4 items-center">
                    {$runningDot[$trip->running]}
                    <div class="flex flex-col gap-1">
                      <div class="start-end-addresses flex flex-row items-center gap-2 font-medium"><span>$startAddress</span><img src="/_assets/images/fleche-gauche.svg" class="-scale-x-100" alt="flèche vers la droite"><span>$endAddress</span></div>
                      <div class="start-time text-sm text-gray-400"><span data-timestamp="$startTimestamp"></span></div>
                    </div>
                  </div>
                </div>
                EOD;
              }
              ?>
            </div>
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
