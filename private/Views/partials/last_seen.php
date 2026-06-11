<?php
/**
 * Gray "last telemetry update" line shown under a dashboard tab title.
 *
 * Set $lastSeenAt before including: the app.overview `last_seen_at` value
 * (string timestamp or DateTimeInterface), or null when never reported.
 * Rendered as a relative age ("il y a 5 minutes"); the absolute time stays
 * available on hover via the title attribute.
 */

/** @var string|DateTimeInterface|null $lastSeenAt */

$lastSeenValue = $lastSeenAt ?? null;
$lastSeenDate = null;
if ($lastSeenValue instanceof DateTimeInterface) {
  $lastSeenDate = $lastSeenValue;
} elseif (is_string($lastSeenValue) && $lastSeenValue !== '') {
  try {
    // app.overview timestamps are naive UTC (meerkat writes UTC wall-clock).
    $lastSeenDate = new DateTimeImmutable($lastSeenValue, new DateTimeZone('UTC'));
  } catch (Exception) {
    $lastSeenDate = null;
  }
}

$lastSeenText = '—';
$lastSeenTitle = null;
if ($lastSeenDate !== null) {
  $lastSeenTitle = $lastSeenDate->format('d/m/Y · H:i') . ' UTC';
  $diff = max(0, (new DateTimeImmutable('now', new DateTimeZone('UTC')))->getTimestamp() - $lastSeenDate->getTimestamp());

  if ($diff < 60) {
    $lastSeenText = "à l'instant";
  } elseif ($diff < 3600) {
    $n = intdiv($diff, 60);
    $lastSeenText = "il y a $n minute" . ($n > 1 ? 's' : '');
  } elseif ($diff < 86400) {
    $n = intdiv($diff, 3600);
    $lastSeenText = "il y a $n heure" . ($n > 1 ? 's' : '');
  } elseif ($diff < 2592000) {
    $n = intdiv($diff, 86400);
    $lastSeenText = "il y a $n jour" . ($n > 1 ? 's' : '');
  } else {
    $n = intdiv($diff, 2592000);
    $lastSeenText = "il y a $n mois";
  }
}
?>
<p class="dashboard-last-seen"<?= $lastSeenTitle !== null ? ' title="' . e($lastSeenTitle) . '"' : '' ?>>
  Dernière remontée : <?= e($lastSeenText) ?>
</p>
