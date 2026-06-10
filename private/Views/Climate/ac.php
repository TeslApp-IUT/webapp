<?php
use Teslapp\Models\Climate\PreconditioningPlanner;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;

/** @var string $vehicleId Vehicle public id, set by the rendering controller. */

$inside_temp = $data['inside_temp'] ?? 'N/A';
$csrf = $_SESSION['csrf_token'] ?? '';
$title = 'Climatisation — TeslApp';
$description = 'Gérez la climatisation de votre Tesla à distance.';
$header = 'user';
$extraCss = ['dashboard', 'ac'];
$extraJs = ['ac'];
// Leaflet (self-hosted vendor) powers the schedule location picker in the dialog.
// `defer` keeps document order, so it executes before ac.js (also deferred).
$headExtra =
  '<link rel="stylesheet" href="/_assets/vendor/leaflet/leaflet.css">' .
  '<script src="/_assets/vendor/leaflet/leaflet.js" defer></script>';

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <!-- Left Section -->
      <?php $activeNav = 'ac';
      require __DIR__ . '/../partials/dashboard_nav.php'; ?>
      <!-- Right Section -->
      <div class="dashboard-content">
        <div class="dashboard-title">
          <h1>Climatisation</h1>
        </div>
        <div class="dashboard-grid">
          <!-- Enable / Disable -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true">
                <path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14" />
              </svg>
            </div>
            <div class="card-content">
              <p class="card-value"><?= e((string)$inside_temp) ?><span> °C</span></p>
              <p class="card-label">Température intérieure</p>
            </div>
            <h2 class="card-title">Climatisation</h2>
            <div class="card-details">
              <!-- Activate Button -->
              <form method="post" action="/climate/toggle" id="form-start">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="vehicle_id" value="<?= e($vehicleId) ?>">
                <input type="hidden" name="action" value="start">
                <input type="hidden" name="temperature" value="23" id="temp-hidden">
                <button type="button" class="btn-enabled" id="btn-activer">Activer</button>
              </form>
              <!-- Temperature Section -->
              <div id="volet-temp" style="display:none; margin-top: 16px;">
                <label class="card-label">Température</label>
                <div class="temp-controls">
                  <button type="button" class="btn-temp" id="btn-minus">−</button>
                  <span class="temp-value"><span id="temp-display">23</span> °C</span>
                  <button type="button" class="btn-temp" id="btn-plus">+</button>
                </div>
                <button type="submit" form="form-start" class="btn-success" style="margin-top: 12px;">Confirmer</button>
              </div>
              <!-- Disable Button -->
              <form method="post" action="/climate/toggle" style="margin-top: 8px;">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="vehicle_id" value="<?= e($vehicleId) ?>">
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
                <input type="hidden" name="vehicle_id" value="<?= e($vehicleId) ?>">
                <select name="climate_keeper_mode" class="card-input">
                  <option value="0">Off</option>
                  <option value="1">Keep</option>
                  <option value="2">Dog</option>
                  <option value="3">Camp</option>
                </select>
                <button type="submit" class="btn-success">Appliquer</button>
              </form>
            </div>
          </div>
        </div>
        <!-- Scheduled Preconditioning Section -->
        <section class="precond-section">
          <div class="precond-section__header">
            <h2 class="precond-section__title">Préconditionnement programmé</h2>
            <button type="button" class="precond-add" id="precond-add" aria-label="Ajouter une planification">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
              </svg>
            </button>
          </div>
          <!-- List Schedules Section -->
          <h3 class="precond-subtitle">Mes planifications</h3>
          <?php if (empty($plans)): ?>
            <p class="precond-empty">Aucune planification pour le moment.</p>
          <?php else: ?>
            <ul class="precond-list">
              <?php foreach ($plans as $plan): ?>
                <li class="precond-card">
                  <div class="precond-card__info">
                    <span class="precond-card__time"><?= e($plan->activationHour) ?></span>
                    <span class="precond-card__days"><?= e(
                        implode(', ', array_map(
                          static fn(DayOfWeek $d): string => $d->labelFr(),
                          $plan->days,
                        ))
                      ) ?></span>
                    <?php if ($plan->locationLabel !== null): ?>
                      <span class="precond-card__location"><?= e($plan->locationLabel) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="precond-card__actions">
                    <button
                      type="button"
                      class="btn-soft precond-card__edit"
                      data-plan-id="<?= e($plan->id) ?>"
                      data-hour="<?= e($plan->activationHour) ?>"
                      data-days="<?= e(implode(',', $plan->dayIds())) ?>"
                      data-memorize="<?= $plan->isMemorizedLongTerm() ? '1' : '0' ?>"
                      data-enabled="<?= $plan->enabled ? '1' : '0' ?>"
                      data-lat="<?= e($plan->location !== null ? (string)$plan->location->latitude : '') ?>"
                      data-lon="<?= e($plan->location !== null ? (string)$plan->location->longitude : '') ?>"
                      data-label="<?= e($plan->locationLabel ?? '') ?>"
                    >Modifier</button>
                    <form method="post" action="/dashboard/<?= e($vehicleId) ?>/ac/precondition/toggle">
                      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                      <input type="hidden" name="plan_id" value="<?= e($plan->id) ?>">
                      <input type="hidden" name="enabled" value="<?= $plan->enabled ? '0' : '1' ?>">
                      <button
                        type="submit"
                        class="switch-btn <?= $plan->enabled ? 'switch-btn--on' : '' ?>"
                        aria-label="<?= $plan->enabled ? 'Désactiver' : 'Activer' ?>"
                        aria-pressed="<?= $plan->enabled ? 'true' : 'false' ?>"
                      ><span class="switch-btn__knob"></span></button>
                    </form>
                    <form method="post" action="/dashboard/<?= e($vehicleId) ?>/ac/precondition/delete">
                      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                      <input type="hidden" name="plan_id" value="<?= e($plan->id) ?>">
                      <button type="submit" class="btn-soft btn-danger">Supprimer</button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <!-- Schedule dialog (create + edit). Opened by the "+" and "Modifier" buttons (ac.js). -->
          <dialog class="precond-dialog" id="precond-dialog">
            <div class="precond-dialog__header">
              <h3 class="precond-dialog__title" id="precond-dialog-title">Nouvelle planification</h3>
              <button type="button" class="precond-dialog__close" id="precond-dialog-close" aria-label="Fermer">&times;</button>
            </div>
            <!-- data-action-base lets ac.js switch the action between .../create and .../update. -->
            <form
              class="precond-form precond-form--dialog"
              id="precond-form"
              method="post"
              action="/dashboard/<?= e($vehicleId) ?>/ac/precondition/create"
              data-action-base="/dashboard/<?= e($vehicleId) ?>/ac/precondition"
            >
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <!-- Enabled by ac.js in edit mode only (a disabled input is not submitted). -->
              <input type="hidden" name="plan_id" id="plan_id" disabled>
              <div class="precond-field">
                <label for="activation_hour">Heure d'activation</label>
                <input class="precond-input" type="time" id="activation_hour" name="activation_hour" required>
              </div>
              <fieldset class="precond-days">
                <legend>Jours</legend>
                <div class="precond-days__grid">
                  <?php foreach (DayOfWeek::cases() as $day): ?>
                    <label class="precond-check">
                      <input type="checkbox" name="days[]" value="<?= e((string)$day->value) ?>">
                      <?= e($day->labelFr()) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
              </fieldset>
              <div class="precond-switches">
                <label class="switch">
                  <input type="checkbox" name="memorize" value="1">
                  <span class="switch__track"><span class="switch__knob"></span></span>
                  Mémoriser (récurrente)
                </label>
                <label class="switch">
                  <input type="checkbox" name="enabled" value="1" checked>
                  <span class="switch__track"><span class="switch__knob"></span></span>
                  Activée
                </label>
              </div>
              <div class="precond-map-block">
                <label for="location_label">Lieu d'activation</label>
                <div class="precond-map" id="precond-map" aria-label="Carte de sélection du lieu"></div>
                <div class="precond-address">
                  <input
                    class="precond-input precond-address__input"
                    type="text"
                    id="location_label"
                    name="location_label"
                    placeholder="Adresse (ou cliquez sur la carte)"
                    autocomplete="off"
                    required
                  >
                  <button type="button" class="btn-soft precond-address__search" id="precond-address-search">Rechercher</button>
                </div>
                <p class="precond-map__hint">Cliquez sur la carte ou saisissez une adresse puis « Rechercher » — les deux restent synchronisés.</p>
                <p class="precond-form__error" id="precond-form-error" role="alert" hidden></p>
              </div>
              <input type="hidden" name="latitude" id="latitude">
              <input type="hidden" name="longitude" id="longitude">
              <button class="btn-success" type="submit" id="precond-submit">Créer la planification</button>
            </form>
          </dialog>
        </section>
      </div>
    </div>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';