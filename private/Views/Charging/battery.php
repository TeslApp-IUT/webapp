<?php
use Teslapp\Models\Charging\ChargingPlanner;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;

/** @var string $vehicleId Vehicle public id, set by the rendering controller. */
/** @var list<ChargingPlanner> $plans */
/** @var array<string, mixed> $data Latest telemetry row (app.overview), may be empty. */

$batteryLevelRaw = $data['battery_level'] ?? null;
$batteryLevel = is_numeric($batteryLevelRaw)
  ? (int) max(0, min(100, round((float) $batteryLevelRaw)))
  : null;
$chargeEnabled = (bool) ($data['charge_enable'] ?? false);

// Live charge settings, clamped to the ranges the controls (and Tesla) accept.
// Fall back to the page defaults when telemetry hasn't reported them yet.
$chargeLimitRaw = $data['charge_limit'] ?? null;
$chargeLimit = is_numeric($chargeLimitRaw)
  ? (int) max(50, min(100, round((float) $chargeLimitRaw)))
  : 80;
$chargeCurrentRaw = $data['charge_current'] ?? null;
$chargeCurrent = is_numeric($chargeCurrentRaw)
  ? (int) max(5, min(48, round((float) $chargeCurrentRaw)))
  : 16;

// TIMESTAMP from the DB -> short readable label; keep the raw value if unparsable.
$scheduledRaw = $data['scheduled_charging_start_time'] ?? null;
$scheduledLabel = null;
if (is_string($scheduledRaw) && $scheduledRaw !== '') {
  try {
    $scheduledLabel = (new DateTimeImmutable($scheduledRaw))->format('d/m · H:i');
  } catch (Exception) {
    $scheduledLabel = $scheduledRaw;
  }
}
$lastSeenRaw = $data['last_seen_at'] ?? null;
$lastSeenLabel = null;
if (is_string($lastSeenRaw) && $lastSeenRaw !== '') {
  try {
    $lastSeenLabel = (new DateTimeImmutable($lastSeenRaw))->format('d/m · H:i');
  } catch (Exception) {
    $lastSeenLabel = $lastSeenRaw;
  }
}

$gaugeModifier = '';
if ($batteryLevel !== null && $batteryLevel <= 10) {
  $gaugeModifier = ' battery-gauge--critical';
} elseif ($batteryLevel !== null && $batteryLevel <= 20) {
  $gaugeModifier = ' battery-gauge--low';
}

$csrf = $_SESSION['csrf_token'] ?? '';
$title = 'Batterie — TeslApp';
$description = 'Gérez la recharge de votre Tesla : charge, limite et fenêtres heures creuses.';
$header = 'user';
$extraCss = ['dashboard', 'battery'];
$extraJs = ['battery'];

// Leaflet (self-hosted vendor) powers the schedule location picker in the dialog.
// `defer` keeps document order, so it executes before battery.js (also deferred).
$headExtra =
  '<link rel="stylesheet" href="/_assets/vendor/leaflet/leaflet.css">' .
  '<script src="/_assets/vendor/leaflet/leaflet.js" defer></script>';

ob_start();
?>
  <section>
    <div class="dashboard-layout">
      <!-- Left Section -->
      <?php $activeNav = 'battery';
      require __DIR__ . '/../partials/dashboard_nav.php'; ?>
      <!-- Right Section -->
      <div class="dashboard-content">
        <h1 class="dashboard-title">Batterie</h1>
        <div class="battery-grid">
          <!-- Battery state + immediate charge commands -->
          <div class="dashboard-card battery-card">
            <div class="battery-card__header">
              <span class="battery-card__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0 0 21 15.75v-6a2.25 2.25 0 0 0-2.25-2.25h-15A2.25 2.25 0 0 0 1.5 9.75v6A2.25 2.25 0 0 0 3.75 18Z" />
                </svg>
              </span>
              <h2 class="battery-card__title">État de la batterie</h2>
              <span class="battery-chip<?= $chargeEnabled ? ' battery-chip--on' : '' ?>">
                <span class="battery-chip__dot" aria-hidden="true"></span>
                Charge <?= $chargeEnabled ? 'activée' : 'désactivée' ?>
              </span>
            </div>
            <div class="battery-gauge<?= $gaugeModifier ?>">
              <p class="battery-gauge__value">
                <?= $batteryLevel !== null ? e((string) $batteryLevel) : 'N/A' ?><span> %</span>
              </p>
              <div class="battery-gauge__track" role="img"
                   aria-label="Niveau de batterie : <?= $batteryLevel !== null ? e((string) $batteryLevel) . ' %' : 'inconnu' ?>">
                <div class="battery-gauge__fill" style="width: <?= $batteryLevel ?? 0 ?>%"></div>
                <span class="battery-gauge__limit" id="gauge-limit" style="left: <?= $chargeLimit ?>%"
                      title="Limite de charge choisie"></span>
              </div>
            </div>
            <dl class="battery-meta">
              <div class="battery-meta__item">
                <dt>Charge programmée</dt>
                <dd><?= $scheduledLabel !== null ? e($scheduledLabel) : 'Aucune' ?></dd>
              </div>
              <div class="battery-meta__item">
                <dt>Dernière remontée</dt>
                <dd><?= $lastSeenLabel !== null ? e($lastSeenLabel) : '—' ?></dd>
              </div>
            </dl>
            <div class="battery-actions">
              <form method="post" action="/charging/toggle">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="vehicle_id" value="<?= e($vehicleId) ?>">
                <input type="hidden" name="action" value="start">
                <button type="submit" class="btn-success battery-actions__btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z" />
                  </svg>
                  Démarrer la charge
                </button>
              </form>
              <form method="post" action="/charging/toggle">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="vehicle_id" value="<?= e($vehicleId) ?>">
                <input type="hidden" name="action" value="stop">
                <button type="submit" class="btn-primary battery-actions__btn">Arrêter</button>
              </form>
            </div>
          </div>
          <!-- Charge settings: limit + amps -->
          <div class="dashboard-card battery-card">
            <div class="battery-card__header">
              <span class="battery-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6" />
                </svg>
              </span>
              <h2 class="battery-card__title">Réglages de charge</h2>
            </div>
            <form method="post" action="/charging/limit" class="battery-setting">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="vehicle_id" value="<?= e($vehicleId) ?>">
              <input type="hidden" name="percent" value="<?= $chargeLimit ?>" id="limit-value">
              <div class="battery-setting__head">
                <span class="battery-setting__label">Limite de charge</span>
                <span class="battery-setting__range">50 – 100 %</span>
              </div>
              <div class="battery-setting__controls">
                <div class="battery-presets" role="group" aria-label="Limites rapides">
                  <?php foreach ([80, 90, 100] as $preset): ?>
                    <button type="button" class="battery-preset<?= $preset === $chargeLimit ? ' is-active' : '' ?>" data-preset="limit" data-value="<?= $preset ?>"><?= $preset ?> %</button>
                  <?php endforeach; ?>
                </div>
                <div class="battery-stepper">
                  <button type="button" class="battery-stepper__btn" id="limit-minus" aria-label="Diminuer la limite">−</button>
                  <span class="battery-stepper__value"><span id="limit-display"><?= $chargeLimit ?></span> %</span>
                  <button type="button" class="battery-stepper__btn" id="limit-plus" aria-label="Augmenter la limite">+</button>
                </div>
                <button type="submit" class="btn-success battery-setting__apply">Appliquer</button>
              </div>
            </form>
            <hr class="battery-divider">
            <form method="post" action="/charging/amps" class="battery-setting">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="vehicle_id" value="<?= e($vehicleId) ?>">
              <input type="hidden" name="amps" value="<?= $chargeCurrent ?>" id="amps-value">
              <div class="battery-setting__head">
                <span class="battery-setting__label">Courant de charge</span>
                <span class="battery-setting__range">5 – 48 A</span>
              </div>
              <div class="battery-setting__controls">
                <div class="battery-presets" role="group" aria-label="Courants rapides">
                  <?php foreach ([8, 16, 32] as $preset): ?>
                    <button type="button" class="battery-preset<?= $preset === $chargeCurrent ? ' is-active' : '' ?>" data-preset="amps" data-value="<?= $preset ?>"><?= $preset ?> A</button>
                  <?php endforeach; ?>
                </div>
                <div class="battery-stepper">
                  <button type="button" class="battery-stepper__btn" id="amps-minus" aria-label="Diminuer le courant">−</button>
                  <span class="battery-stepper__value"><span id="amps-display"><?= $chargeCurrent ?></span> A</span>
                  <button type="button" class="battery-stepper__btn" id="amps-plus" aria-label="Augmenter le courant">+</button>
                </div>
                <button type="submit" class="btn-success battery-setting__apply">Appliquer</button>
              </div>
            </form>
          </div>
        </div>
        <!-- Scheduled Charging Section (off-peak windows) -->
        <section class="precond-section">
          <div class="precond-section__header">
            <div>
              <h2 class="precond-section__title">Recharge programmée</h2>
              <p class="precond-section__lead">
                La voiture restreint sa charge à ces fenêtres lorsqu'elle est stationnée au lieu
                indiqué — idéal pour les heures creuses.
              </p>
            </div>
            <button type="button" class="precond-add" id="plan-add" aria-label="Ajouter une planification">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
              </svg>
            </button>
          </div>
          <?php if (empty($plans)): ?>
            <p class="precond-empty">Aucune planification pour le moment. Créez-en une avec le bouton « + ».</p>
          <?php else: ?>
            <ul class="precond-list">
              <?php foreach ($plans as $plan): ?>
                <li class="precond-card<?= $plan->enabled ? '' : ' precond-card--off' ?>">
                  <div class="precond-card__info">
                    <span class="precond-card__time"><?= e($plan->activationHour) ?><?= $plan->deactivationHour !== null ? ' → ' . e($plan->deactivationHour) : '' ?></span>
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
                      data-end="<?= e($plan->deactivationHour ?? '') ?>"
                      data-days="<?= e(implode(',', $plan->dayIds())) ?>"
                      data-memorize="<?= $plan->isMemorizedLongTerm() ? '1' : '0' ?>"
                      data-enabled="<?= $plan->enabled ? '1' : '0' ?>"
                      data-lat="<?= e($plan->location !== null ? (string) $plan->location->latitude : '') ?>"
                      data-lon="<?= e($plan->location !== null ? (string) $plan->location->longitude : '') ?>"
                      data-label="<?= e($plan->locationLabel ?? '') ?>"
                    >Modifier</button>
                    <form method="post" action="/dashboard/<?= e($vehicleId) ?>/battery/plan/toggle">
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
                    <form method="post" action="/dashboard/<?= e($vehicleId) ?>/battery/plan/delete">
                      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                      <input type="hidden" name="plan_id" value="<?= e($plan->id) ?>">
                      <button type="submit" class="btn-soft btn-danger">Supprimer</button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <!-- Schedule dialog (create + edit). Opened by the "+" and "Modifier" buttons (battery.js). -->
          <dialog class="precond-dialog" id="plan-dialog">
            <div class="precond-dialog__header">
              <h3 class="precond-dialog__title" id="plan-dialog-title">Nouvelle planification</h3>
              <button type="button" class="precond-dialog__close" id="plan-dialog-close" aria-label="Fermer">&times;</button>
            </div>
            <!-- data-action-base lets battery.js switch the action between .../create and .../update. -->
            <form
              class="precond-form precond-form--dialog"
              id="plan-form"
              method="post"
              action="/dashboard/<?= e($vehicleId) ?>/battery/plan/create"
              data-action-base="/dashboard/<?= e($vehicleId) ?>/battery/plan"
            >
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <!-- Enabled by battery.js in edit mode only (a disabled input is not submitted). -->
              <input type="hidden" name="plan_id" id="plan_id" disabled>
              <div class="precond-field">
                <label for="activation_hour">Début de la fenêtre</label>
                <input class="precond-input" type="time" id="activation_hour" name="activation_hour" required>
              </div>
              <div class="precond-field">
                <label for="deactivation_hour">Fin de la fenêtre <span class="precond-optional">(optionnel)</span></label>
                <input class="precond-input" type="time" id="deactivation_hour" name="deactivation_hour">
              </div>
              <p class="precond-window-hint">
                La fenêtre peut passer minuit.
                <button type="button" class="btn-soft" id="offpeak-preset">Heures creuses (23h30 → 7h30)</button>
              </p>
              <fieldset class="precond-days">
                <legend>Jours</legend>
                <div class="precond-days__grid">
                  <?php foreach (DayOfWeek::cases() as $day): ?>
                    <label class="precond-check">
                      <input type="checkbox" name="days[]" value="<?= e((string) $day->value) ?>">
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
                <label for="location_label">
                  Lieu d'activation
                  <span class="precond-optional">(optionnel — requis pour synchroniser avec le véhicule)</span>
                </label>
                <div class="precond-map" id="plan-map" role="application" aria-label="Carte de sélection du lieu"></div>
                <div class="precond-address">
                  <input
                    class="precond-input precond-address__input"
                    type="text"
                    id="location_label"
                    name="location_label"
                    placeholder="Adresse (ou cliquez sur la carte)"
                    autocomplete="off"
                  >
                  <button type="button" class="btn-soft precond-address__search" id="plan-address-search">Rechercher</button>
                </div>
                <p class="precond-map__hint">
                  Cliquez sur la carte ou saisissez une adresse puis « Rechercher » — les deux restent
                  synchronisés. Sans lieu, la planification reste locale (non envoyée au véhicule).
                </p>
                <p class="precond-form__error" id="plan-form-error" role="alert" hidden></p>
              </div>
              <input type="hidden" name="latitude" id="latitude">
              <input type="hidden" name="longitude" id="longitude">
              <button class="btn-success" type="submit" id="plan-submit">Créer la planification</button>
            </form>
          </dialog>
        </section>
      </div>
    </div>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
