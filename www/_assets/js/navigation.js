// Trips started within this window show a relative time ("il y a 2 heures");
// anything older falls back to an absolute date.
const RELATIVE_TIME_OFFSET_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

const locale = new Intl.Locale('fr-FR');
const absoluteFormat = new Intl.DateTimeFormat(locale, {
  dateStyle: 'long',
  timeStyle: 'short',
});
const relativeFormat = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

// Full date + time, used in the trip details card where precision matters.
const detailedFormat = new Intl.DateTimeFormat(locale, {
  dateStyle: 'long',
  timeStyle: 'medium',
});

// Largest unit whose threshold the elapsed time reaches, longest first.
const RELATIVE_UNITS = [
  ['day', 24 * 60 * 60 * 1000],
  ['hour', 60 * 60 * 1000],
  ['minute', 60 * 1000],
  ['second', 1000],
];

function formatRelative(date) {
  const elapsed = date.getTime() - Date.now(); // negative in the past
  for (const [unit, ms] of RELATIVE_UNITS) {
    if (Math.abs(elapsed) >= ms || unit === 'second') {
      return relativeFormat.format(Math.round(elapsed / ms), unit);
    }
  }
}

// Unix seconds -> human label (relative when recent, absolute otherwise).
function formatTimestamp(seconds) {
  const date = new Date(seconds * 1000);
  const elapsed = Date.now() - date.getTime();
  return elapsed <= RELATIVE_TIME_OFFSET_MS ? formatRelative(date) : absoluteFormat.format(date);
}

// Unix seconds -> full date + time (e.g. "11 juin 2026 à 14:30:05").
function formatDateTime(seconds) {
  return detailedFormat.format(new Date(seconds * 1000));
}

// Render the trip start times in the list.
document.querySelectorAll('.start-time > span[data-timestamp]').forEach((e) => {
  e.textContent = formatTimestamp(Number(e.dataset.timestamp));
});

// --- Trip details panel: click a trip, fetch its details, show them next to the list ---
const tripsList = document.querySelector('.trips-list');
const detailsPanel = document.getElementById('trip-details');

if (tripsList && detailsPanel) {
  const endpoint = tripsList.dataset.tripEndpoint;
  const tripItems = tripsList.querySelectorAll('.trip-item');
  const mapElement = document.getElementById('trip-map');

  // Leaflet map + the layer holding the start/end markers. Both are created
  // lazily on the first trip selection and reused afterwards.
  let map = null;
  let markers = null;

  // Trip details, keyed by trip id, so re-clicking a trip skips the round trip.
  // Stores the fetch promise (not just the resolved data) so rapid repeat clicks
  // while a request is still in flight reuse it instead of firing a second one.
  const detailsCache = new Map();

  tripItems.forEach((item) => {
    item.addEventListener('click', () => selectTrip(item));
  });

  async function selectTrip(item) {
    tripItems.forEach((other) => {
      other.classList.toggle('ring-2', other === item);
    });

    detailsPanel.textContent = 'Chargement…';

    const tripId = item.dataset.tripId;
    try {
      const trip = await fetchTrip(tripId);
      renderDetails(trip);
      renderMap(trip);
    } catch {
      // Drop the rejected promise so a later click can retry the fetch.
      detailsCache.delete(tripId);
      detailsPanel.textContent = 'Impossible de charger les détails du trajet.';
      mapElement.classList.add('hidden');
    }
  }

  function fetchTrip(tripId) {
    let request = detailsCache.get(tripId);
    if (request === undefined) {
      request = fetch(`${endpoint}?id=${encodeURIComponent(tripId)}`, {
        headers: { Accept: 'application/json' },
      }).then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
      });
      detailsCache.set(tripId, request);
    }
    return request;
  }

  function renderDetails(trip) {
    const rows = [
      ['Départ', trip.startAddress ?? 'Adresse inconnue'],
      ['Arrivée', trip.endAddress ?? 'Adresse inconnue'],
      ['Début', formatDateTime(trip.startTimestamp)],
      ['Fin', trip.running ? 'En cours' : formatDateTime(trip.endTimestamp)],
      ['Distance', `${trip.distanceKm} km`],
      ['Durée', `${trip.durationMinutes} min`],
    ];

    detailsPanel.replaceChildren(
      ...rows.map(([label, value]) => {
        const row = document.createElement('div');
        row.className = 'flex flex-row justify-between gap-4 py-1';

        const labelEl = document.createElement('span');
        labelEl.className = 'text-gray-400';
        labelEl.textContent = label;

        const valueEl = document.createElement('span');
        valueEl.className = 'font-medium text-right';
        valueEl.textContent = value;

        row.append(labelEl, valueEl);
        return row;
      }),
    );
  }

  function renderMap(trip) {
    const leaflet = globalThis.L;
    if (!mapElement || !leaflet) {
      return;
    }

    // The container must be visible before Leaflet measures it, otherwise the
    // tiles lay out against a zero-size box.
    mapElement.classList.remove('hidden');

    if (map === null) {
      map = leaflet.map(mapElement, { scrollWheelZoom: false });
      leaflet
        .tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© OpenStreetMap',
        })
        .addTo(map);
      markers = leaflet.layerGroup().addTo(map);
    } else {
      // The card was hidden when the map was first built; recompute its size.
      map.invalidateSize();
    }

    const start = [trip.startLat, trip.startLon];
    const end = [trip.endLat, trip.endLon];
    const route =
      Array.isArray(trip.route) && trip.route.length > 0
        ? trip.route
        : Array.isArray(trip.points)
          ? trip.points
          : [];

    markers.clearLayers();

    // Trace the route taken, then draw the endpoint markers on top of it.
    if (route.length > 1) {
      leaflet.polyline(route, { color: '#3b82f6', weight: 4, opacity: 0.8 }).addTo(markers);
    }
    addMarker(leaflet, start, '#22c55e', trip.startAddress); // green = départ
    addMarker(leaflet, end, '#ef4444', trip.endAddress); // red = arrivée

    // Frame the whole route when we have one, otherwise just the two endpoints.
    const bounds = leaflet.latLngBounds(route.length > 1 ? route : [start, end]);
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });
  }

  function addMarker(leaflet, latLng, color, address) {
    const marker = leaflet.circleMarker(latLng, {
      radius: 8,
      color: '#ffffff',
      weight: 2,
      fillColor: color,
      fillOpacity: 1,
    });

    if (typeof address === 'string' && address !== '') {
      // Text node, so an address can never inject markup into the popup.
      const label = document.createElement('span');
      label.textContent = address;
      marker.bindPopup(label);
    }

    marker.addTo(markers);
  }
}
