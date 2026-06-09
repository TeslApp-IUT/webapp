<?php
use Teslapp\Models\Charging\ChargingPlanner;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;

$battery_level = $data['battery_level'] ?? 'N/A';
$charge_enable = $data['charge_enable'] ?? false;
$scheduled_charging_start_time = $data['scheduled_charging_start_time'] ?? null;
$csrf = $_SESSION['csrf_token'] ?? '';
$title = 'Batterie — TeslApp';
$description = 'Gérez la recharge de votre Tesla : charge, limite et fenêtres heures creuses.';
$header = 'user';
$extraCss = ['dashboard', 'battery'];
$extraJs = ['battery'];

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
        <div class="dashboard-grid">
          <!-- Battery state -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                   stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0 0 21 15.75v-6a2.25 2.25 0 0 0-2.25-2.25h-15A2.25 2.25 0 0 0 1.5 9.75v6A2.25 2.25 0 0 0 3.75 18Z" />
              </svg>
            </div>
            <h2 class="card-title">État batterie</h2>
            <div class="card-content">
              <p class="card-value"><?= e((string)$battery_level) ?><span> %</span></p>
              <p class="card-label">Batterie actuelle</p>
            </div>
            <div class="card-details">
              <p>Charge activée <span
                  class="<?= $charge_enable ? 'status-on' : 'status-off' ?>"><?= $charge_enable ? 'Oui' : 'Non' ?></span>
              </p>
              <p>Charge programmée <span><?= e($scheduled_charging_start_time ?? 'Non programmée') ?></span></p>
            </div>
          </div>
          <!-- Start / Stop charging -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                   stroke-linejoin="round" aria-hidden="true">
                <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z" />
              </svg>
            </div>
            <h2 class="card-title">Charge</h2>
            <div class="card-details">
              <form method="post" action="/charging/toggle">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="start">
                <button type="submit" class="btn-success">Démarrer la charge</button>
              </form>
              <form method="post" action="/charging/toggle">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="stop">
                <button type="submit" class="btn-primary">Arrêter la charge</button>
              </form>
            </div>
          </div>
          <!-- Charge limit -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                   stroke-linejoin="round" aria-hidden="true">
                <path d="M12 20V10M18 20V4M6 20v-4" />
              </svg>
            </div>
            <h2 class="card-title">Limite de charge</h2>
            <div class="card-details">
              <form method="post" action="/charging/limit" id="form-limit">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="percent" value="80" id="limit-hidden">
                <div class="temp-controls">
                  <button type="button" class="btn-temp" id="limit-minus" aria-label="Diminuer la limite">−</button>
                  <span class="temp-value"><span id="limit-display">80</span> %</span>
                  <button type="button" class="btn-temp" id="limit-plus" aria-label="Augmenter la limite">+</button>
                </div>
                <button type="submit" class="btn-success">Confirmer</button>
              </form>
            </div>
          </div>
          <!-- Charging amps -->
          <div class="dashboard-card">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                   stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 6v6l4 2" />
              </svg>
            </div>
            <h2 class="card-title">Ampérage</h2>
            <div class="card-details">
              <form method="post" action="/charging/amps">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <label class="card-label" for="amps-input">Courant de charge (5 à 48 A)</label>
                <input class="precond-input" type="number" id="amps-input" name="amps" min="5" max="48" step="1"
                       value="16" required>
                <button type="submit" class="btn-success">Appliquer</button>
              </form>
            </div>
          </div>
        </div>
        <!-- Scheduled Charging Section (off-peak windows) -->
        <section class="precond-section">
          <h2 class="precond-section__title">Recharge programmée</h2>
          <!-- List Schedules Section -->
          <h3 class="precond-subtitle">Mes planifications</h3>
          <?php if (empty($plans)): ?>
            <p class="precond-empty">Aucune planification pour le moment.</p>
          <?php else: ?>
            <ul class="precond-list">
              <?php foreach ($plans as $plan): ?>
                <li class="precond-card">
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
                    <form method="post" action="/dashboard/battery/plan/toggle">
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
                    <form method="post" action="/dashboard/battery/plan/delete">
                      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                      <input type="hidden" name="plan_id" value="<?= e($plan->id) ?>">
                      <button type="submit" class="btn-soft btn-danger">Supprimer</button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <!-- New Scheduling Form -->
          <h3 class="precond-subtitle">Nouvelle planification</h3>
          <form class="precond-form" method="post" action="/dashboard/battery/plan/create">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <p class="precond-hint">
              La voiture limite sa charge à cette fenêtre lorsqu'elle est stationnée au lieu
              indiqué — idéal pour les heures creuses. La fenêtre peut passer minuit.
              <button type="button" class="btn-soft" id="offpeak-preset">Heures creuses (23h30 → 7h30)</button>
            </p>
            <div class="precond-field">
              <label for="activation_hour">Début de la fenêtre</label>
              <input class="precond-input" type="time" id="activation_hour" name="activation_hour" required>
            </div>
            <div class="precond-field">
              <label for="deactivation_hour">Fin de la fenêtre <span class="precond-optional">(optionnel)</span></label>
              <input class="precond-input" type="time" id="deactivation_hour" name="deactivation_hour">
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
            <label class="precond-check">
              <input type="checkbox" name="memorize" value="1">
              Mémoriser (planification récurrente)
            </label>
            <label class="switch">
              <input type="checkbox" name="enabled" value="1" checked>
              <span class="switch__track"><span class="switch__knob"></span></span>
              Activée
            </label>
            <div class="precond-field">
              <label for="latitude">Latitude <span class="precond-optional">(requis pour synchroniser avec le véhicule)</span></label>
              <input class="precond-input" type="number" step="any" id="latitude" name="latitude">
            </div>
            <div class="precond-field">
              <label for="longitude">Longitude <span class="precond-optional">(requis pour synchroniser avec le véhicule)</span></label>
              <input class="precond-input" type="number" step="any" id="longitude" name="longitude">
            </div>
            <div class="precond-field">
              <label for="location_label">Lieu <span class="precond-optional">(optionnel)</span></label>
              <input class="precond-input" type="text" id="location_label" name="location_label">
            </div>
            <button class="btn-success" type="submit">Créer la planification</button>
          </form>
        </section>
      </div>
    </div>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
