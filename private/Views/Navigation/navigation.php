<?php

/**
 * Navigation page view.
 * Displays navigation-related information for the vehicle.
 *
 * @var string $vehicleId Vehicle public id, set by the rendering controller.
 * @var Trip[] $trips
 * @var array<string, array{start: ?string, end: ?string}> $addresses reverse-geocoded trip endpoints, keyed by trip id
 * @var int $page current 1-based page of the trip history
 * @var int $totalPages total number of trip history pages
 */

use Teslapp\Models\Navigation\Trip;

$title = 'Navigation — TeslApp';
$description = 'Navigation de votre Tesla : routes et informations de trajet.';
$header = 'user';
$extraCss = ['dashboard', 'navigation', 'leaflet'];
$extraJs = ['leaflet', 'navigation'];

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
            <div class="trips-list flex flex-col gap-2"
                 data-trip-endpoint="/dashboard/<?= htmlspecialchars($vehicleId, ENT_QUOTES) ?>/navigation/trip">
              <?php
              foreach ($trips as $trip) {
                $startTimestamp = date_timestamp_get($trip->startTime);
                $unknown = 'Adresse inconnue';
                $startAddress = htmlspecialchars($addresses[$trip->id]['start'] ?? $unknown, ENT_QUOTES);
                $endAddress = htmlspecialchars($addresses[$trip->id]['end'] ?? $unknown, ENT_QUOTES);
                $tripId = htmlspecialchars($trip->id, ENT_QUOTES);
                echo <<<EOD
                <button type="button" class="trip-item flex flex-row justify-between items-center w-full bg-gray-500/10 hover:bg-gray-500/20 rounded-xl p-3.5 text-left cursor-pointer transition-colors ring-blue-500/60" data-trip-id="$tripId">
                  <div class="flex flex-row gap-4 items-center">
                    {$runningDot[$trip->running]}
                    <div class="flex flex-col gap-1">
                      <div class="start-end-addresses flex flex-row items-center gap-2 font-medium"><span>$startAddress</span><img src="/_assets/images/fleche-gauche.svg" class="-scale-x-100" alt="flèche vers la droite"><span>$endAddress</span></div>
                      <div class="start-time text-sm text-gray-400"><span data-timestamp="$startTimestamp"></span></div>
                    </div>
                  </div>
                </button>
                EOD;
              }
              ?>
            </div>
            <?php if ($totalPages > 1):
              $navBase = '/dashboard/' . htmlspecialchars($vehicleId, ENT_QUOTES) . '/navigation';
              $prevDisabled = $page <= 1;
              $nextDisabled = $page >= $totalPages;
              $linkBase = 'px-4 py-2 rounded-lg text-sm bg-gray-500/10 transition-colors';
              $enabled = ' hover:bg-gray-500/20';
              $disabled = ' opacity-40 pointer-events-none';
              ?>
              <nav class="flex flex-row items-center justify-between gap-2 mt-4" aria-label="Pagination des trajets">
                <a href="<?= $prevDisabled ? '#' : "$navBase?page=" . ($page - 1) ?>"
                   class="<?= $linkBase . ($prevDisabled ? $disabled : $enabled) ?>"
                   <?= $prevDisabled ? 'aria-disabled="true" tabindex="-1"' : 'rel="prev"' ?>>Précédent</a>
                <span class="text-sm text-gray-400">Page <?= $page ?> sur <?= $totalPages ?></span>
                <a href="<?= $nextDisabled ? '#' : "$navBase?page=" . ($page + 1) ?>"
                   class="<?= $linkBase . ($nextDisabled ? $disabled : $enabled) ?>"
                   <?= $nextDisabled ? 'aria-disabled="true" tabindex="-1"' : 'rel="next"' ?>>Suivant</a>
              </nav>
            <?php endif; ?>
          </div>
        </div>

        <!-- Trip details card — filled by navigation.js when a trip is clicked -->
        <div class="dashboard-card">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="10" r="3" />
              <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 7 8 11.7z" />
            </svg>
          </div>
          <h2 class="card-title">Détails du trajet</h2>
          <div class="card-content">
            <div id="trip-details" class="text-sm text-gray-400">
              Sélectionnez un trajet pour afficher ses détails.
            </div>
            <div id="trip-map" class="hidden mt-4 h-64 rounded-xl overflow-hidden z-0"></div>
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
