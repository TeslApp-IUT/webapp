<?php
/**
 * Dashboard sidebar navigation — shared partial.
 *
 * Set $activeNav before including to highlight the current tab:
 *   'overview' | 'vehicle' | 'ac' | 'battery' | 'navigation'
 * $vehicleId must also be set by the including controller/view.
 */

/** @var string $vehicleId */

$activeNav = $activeNav ?? 'overview';
?>
<nav class="dashboard-navigation">
    <a href="/dashboard/<?= e($vehicleId) ?>/overview" class="nav-item<?= $activeNav === 'overview' ? ' nav-item--active' : '' ?>" aria-label="Vue générale">
        <span class="nav-item__icon nav-item__icon--overview">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
        </span>
        <span class="nav-item__label">Vue d'ensemble</span>
    </a>
    <a href="/dashboard/<?= e($vehicleId) ?>/vehicle" class="nav-item<?= $activeNav === 'vehicle' ? ' nav-item--active' : '' ?>" aria-label="Véhicule">
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
    <a href="/dashboard/<?= e($vehicleId) ?>/ac" class="nav-item<?= $activeNav === 'ac' ? ' nav-item--active' : '' ?>" aria-label="Climatisation">
        <span class="nav-item__icon nav-item__icon--ac">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14" />
            </svg>
        </span>
        <span class="nav-item__label">Climatisation</span>
    </a>
    <a href="/dashboard/<?= e($vehicleId) ?>/battery" class="nav-item<?= $activeNav === 'battery' ? ' nav-item--active' : '' ?>" aria-label="Batterie">
        <span class="nav-item__icon nav-item__icon--battery">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0 0 21 15.75v-6a2.25 2.25 0 0 0-2.25-2.25h-15A2.25 2.25 0 0 0 1.5 9.75v6A2.25 2.25 0 0 0 3.75 18Z" />
            </svg>
        </span>
        <span class="nav-item__label">Batterie</span>
    </a>
    <a href="/dashboard/<?= e($vehicleId) ?>/navigation" class="nav-item<?= $activeNav === 'navigation' ? ' nav-item--active' : '' ?>" aria-label="Navigation">
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
