<?php
/**
 * Temporary standalone view for preconditioning schedules; folded into ac.php later.
 * No edit form yet (deferred to the map-based UI).
 *
 * @var list<PreconditioningPlanner> $plans
 */

use Teslapp\Models\Climate\PreconditioningPlanner;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;

$title = 'Préconditionnement — TeslApp';
$description = 'Programmez le préconditionnement de votre véhicule Tesla.';
$header = 'user';
$extraCss = ['preconditioning'];

$csrf = $_SESSION['csrf_token'] ?? '';

ob_start();
?>
  <section class="precond container-narrow">
    <h1 class="precond-title">Préconditionnement programmé</h1>

    <h2 class="precond-subtitle">Mes planifications</h2>
    <?php if ($plans === []): ?>
      <p class="precond-empty">Aucune planification pour le moment.</p>
    <?php else: ?>
      <ul class="precond-list">
        <?php foreach ($plans as $plan): ?>
          <li class="precond-card">
            <div class="precond-card__info">
              <span class="precond-card__time"><?= e($plan->activationHour) ?></span>
              <span class="precond-card__days"><?= e(
                  implode(
                      ', ',
                      array_map(static fn(DayOfWeek $day): string => $day->labelFr(), $plan->days),
                  ),
              ) ?></span>
              <?php if ($plan->locationLabel !== null): ?>
                <span class="precond-card__location"><?= e($plan->locationLabel) ?></span>
              <?php endif; ?>
            </div>

            <div class="precond-card__actions">
              <form method="post" action="/dashboard/ac/precondition/toggle">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="plan_id" value="<?= e($plan->id) ?>">
                <input type="hidden" name="enabled" value="<?= $plan->enabled ? '0' : '1' ?>">
                <button
                  type="submit"
                  class="switch-btn <?= $plan->enabled ? 'switch-btn--on' : '' ?>"
                  aria-label="<?= $plan->enabled ? 'Désactiver' : 'Activer' ?>"
                  aria-pressed="<?= $plan->enabled ? 'true' : 'false' ?>"
                >
                  <span class="switch-btn__knob"></span>
                </button>
              </form>

              <form method="post" action="/dashboard/ac/precondition/delete">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="plan_id" value="<?= e($plan->id) ?>">
                <button type="submit" class="btn-soft btn-danger">Supprimer</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <h2 class="precond-subtitle">Nouvelle planification</h2>
    <form class="precond-form" method="post" action="/dashboard/ac/precondition/create">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <div class="precond-field">
        <label for="activation_hour">Heure d'activation</label>
        <input class="precond-input" type="time" id="activation_hour" name="activation_hour" required>
      </div>

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
        <label for="latitude">Latitude (optionnel)</label>
        <input class="precond-input" type="number" step="any" id="latitude" name="latitude">
      </div>

      <div class="precond-field">
        <label for="longitude">Longitude (optionnel)</label>
        <input class="precond-input" type="number" step="any" id="longitude" name="longitude">
      </div>

      <div class="precond-field">
        <label for="location_label">Lieu (optionnel)</label>
        <input class="precond-input" type="text" id="location_label" name="location_label">
      </div>

      <button class="btn-primary" type="submit">Créer la planification</button>
    </form>

    <a class="precond-link" href="/dashboard/vehicle">Retour au tableau de bord</a>
  </section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
