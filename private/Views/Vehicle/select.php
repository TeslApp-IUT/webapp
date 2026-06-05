<?php
/**
 * @var array<int, array{vehicle: Vehicle, model: string, image: string, status: VehicleConnectivityStatus, selected: bool}> $cards
 * @var string $csrfToken
 */

use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Vehicle\Vehicle;

$title = 'Sélection du véhicule — TeslApp';
$description = 'Choisissez le véhicule Tesla à piloter.';

ob_start();
?>
<section class="vehicle-select" aria-labelledby="vehicle-select-title">
    <div class="container">
        <div class="section-header">
            <h1 id="vehicle-select-title" class="section-title">Choisissez votre véhicule</h1>
            <p class="section-description">Sélectionnez la Tesla que vous souhaitez piloter.</p>
        </div>

        <?php if ($cards === []): ?>
            <div class="vehicle-empty">
                <p>Aucun véhicule n'est associé à votre compte pour le moment.</p>
            </div>
        <?php else: ?>
            <ul class="vehicle-grid">
                <?php foreach ($cards as $card): ?>
                    <?php $vehicle = $card['vehicle']; $status = $card['status']; ?>
                    <li class="vehicle-card<?= $card['selected'] ? ' vehicle-card--selected' : '' ?>">
                        <div class="vehicle-card__media">
                            <span class="vehicle-card__status vehicle-card__status--<?= e($status->value) ?>">
                                <span class="sr-only"><?= e($status->label()) ?></span>
                            </span>
                            <?php if ($card['image'] !== ''): ?>
                                <img class="vehicle-card__image" src="<?= e($card['image']) ?>"
                                     alt="Tesla <?= e($card['model']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="vehicle-card__image vehicle-card__image--placeholder">
                                    <img src="/_assets/images/tesla_logo_gray.png" alt="" aria-hidden="true">
                                    <span><?= e($card['model']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-card__body">
                            <p class="vehicle-card__model"><?= e($card['model']) ?></p>
                            <h2 class="vehicle-card__name"><?= e($vehicle->name !== '' ? $vehicle->name : 'Véhicule') ?></h2>
                        </div>

                        <?php if ($card['selected']): ?>
                            <p class="vehicle-card__badge">Sélectionné</p>
                        <?php else: ?>
                            <form class="vehicle-card__form" method="post" action="/vehicle/choose">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="vin" value="<?= e($vehicle->vin->value) ?>">
                                <button type="submit" class="btn-primary vehicle-card__select">Sélectionner</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
