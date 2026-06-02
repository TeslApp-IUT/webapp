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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Tesla</title>
</head>
<body>

<h1>Dashboard — Mon véhicule</h1>

<section>
  <h2>Batterie</h2>
  <p>Batterie actuelle : <?= $battery_level ?> %</p>
  <p>Charge activée : <?= $charge_enable_request ? 'Oui' : 'Non' ?></p>
  <p>Charge programmée : <?= $scheduled_charging_start_time ?? 'Non programmée' ?></p>
</section>

<section>
  <h2>Climatisation</h2>
  <p>Température intérieure : <?= $inside_temp ?> °C</p>
  <p>Mode keeper : <?= $keeper_modes[$climate_keeper_mode] ?? 'Inconnu' ?></p>
  <p>AC activée : <?= $hvac_ac_enabled ? 'Oui' : 'Non' ?></p>
</section>

<section>
  <h2>Actions disponibles</h2>
  <!--  Autre dans le futur-->
  <button disabled>Verrouiller / Déverrouiller</button>
  <button disabled>Klaxon</button>
  <button disabled>Batterie</button>
  <button disabled>Climatisation</button>
  <button disabled>Localisation</button>
</section>

</body>
</html>