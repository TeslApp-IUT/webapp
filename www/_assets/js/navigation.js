// Trips started within this window show a relative time ("il y a 2 heures");
// anything older falls back to an absolute date.
const RELATIVE_TIME_OFFSET_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

const locale = new Intl.Locale('fr-FR');
const absoluteFormat = new Intl.DateTimeFormat(locale, {
  dateStyle: 'long',
  timeStyle: 'short'
});
const relativeFormat = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

// Largest unit whose threshold the elapsed time reaches, longest first.
const RELATIVE_UNITS = [
  ['day', 24 * 60 * 60 * 1000],
  ['hour', 60 * 60 * 1000],
  ['minute', 60 * 1000],
  ['second', 1000]
];

function formatRelative(date) {
  const elapsed = date.getTime() - Date.now(); // negative in the past
  for (const [unit, ms] of RELATIVE_UNITS) {
    if (Math.abs(elapsed) >= ms || unit === 'second') {
      return relativeFormat.format(Math.round(elapsed / ms), unit);
    }
  }
}

document.querySelectorAll('.start-time > span[data-timestamp]').forEach((e) => {
  const date = new Date(e.getAttribute('data-timestamp') * 1000);
  const elapsed = Date.now() - date.getTime();
  e.textContent =
    elapsed <= RELATIVE_TIME_OFFSET_MS
      ? formatRelative(date)
      : absoluteFormat.format(date);
});
