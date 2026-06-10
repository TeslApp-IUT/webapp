const btnActiver = document.getElementById('btn-activer');
const voletTemp = document.getElementById('volet-temp');
const tempDisplay = document.getElementById('temp-display');
const tempHidden = document.getElementById('temp-hidden');
const btnMinus = document.getElementById('btn-minus');
const btnPlus = document.getElementById('btn-plus');

/* Displays the pane when you click “Enable” */
btnActiver.addEventListener('click', () => {
  voletTemp.style.display = voletTemp.style.display === 'none' ? 'block' : 'none';
});
btnMinus.addEventListener('click', () => {
  let val = parseFloat(tempHidden.value);
  if (val > 15) {
    val = Math.round((val - 0.5) * 10) / 10;
    tempHidden.value = val;
    tempDisplay.textContent = val;
  }
});

btnPlus.addEventListener('click', () => {
  let val = parseFloat(tempHidden.value);
  if (val < 28) {
    val = Math.round((val + 0.5) * 10) / 10;
    tempHidden.value = val;
    tempDisplay.textContent = val;
  }
});

/* ---- Preconditioning schedules: create/edit dialog + Leaflet location picker ---- */

const dialog = document.getElementById('precond-dialog');

if (dialog) {
  const form = document.getElementById('precond-form');
  const dialogTitle = document.getElementById('precond-dialog-title');
  const submitBtn = document.getElementById('precond-submit');
  const planIdInput = document.getElementById('plan_id');
  const hourInput = document.getElementById('activation_hour');
  const memorizeInput = form.querySelector('input[name="memorize"]');
  const enabledInput = form.querySelector('input[name="enabled"]');
  const dayInputs = [...form.querySelectorAll('input[name="days[]"]')];
  const latInput = document.getElementById('latitude');
  const lonInput = document.getElementById('longitude');
  const addressInput = document.getElementById('location_label');
  const errorBox = document.getElementById('precond-form-error');

  const FRANCE_CENTER = [46.6, 2.45];

  let map = null;
  let marker = null;

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = false;
  }

  function clearError() {
    errorBox.hidden = true;
  }

  /* The map is created once, on first dialog opening (it needs a laid-out container). */
  function ensureMap() {
    if (map) return;
    map = L.map('precond-map').setView(FRANCE_CENTER, 5);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    map.on('click', (e) => {
      const point = e.latlng.wrap();
      setLocation(point.lat, point.lng, { reverseGeocode: true });
    });
  }

  /* Map -> form: place the marker, fill the hidden coordinates, optionally resolve the address. */
  function setLocation(lat, lon, { reverseGeocode = false } = {}) {
    latInput.value = lat.toFixed(6);
    lonInput.value = lon.toFixed(6);
    if (marker) {
      marker.setLatLng([lat, lon]);
    } else {
      marker = L.marker([lat, lon], { draggable: true }).addTo(map);
      marker.on('dragend', () => {
        const point = marker.getLatLng().wrap();
        setLocation(point.lat, point.lng, { reverseGeocode: true });
      });
    }
    clearError();
    if (reverseGeocode) {
      fetch(`/geocode/reverse?lat=${lat}&lon=${lon}`, {
        headers: { Accept: 'application/json' },
      })
        .then((response) => (response.ok ? response.json() : null))
        .then((data) => {
          addressInput.value = data?.label ?? `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
        })
        .catch(() => {
          addressInput.value = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
        });
    }
  }

  function clearLocation() {
    latInput.value = '';
    lonInput.value = '';
    if (marker) {
      marker.remove();
      marker = null;
    }
  }

  /* Form -> map: geocode the typed address, then place the marker and recenter. */
  async function searchAddress() {
    const query = addressInput.value.trim();
    if (query === '') {
      showError('Saisissez une adresse ou cliquez sur la carte.');
      return false;
    }
    try {
      const response = await fetch(`/geocode?q=${encodeURIComponent(query)}`, {
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) {
        showError('Adresse introuvable : précisez (rue, ville…) ou cliquez sur la carte.');
        return false;
      }
      const data = await response.json();
      setLocation(data.lat, data.lon, { reverseGeocode: false });
      addressInput.value = data.label;
      map.setView([data.lat, data.lon], 15);
      return true;
    } catch {
      showError('Recherche impossible pour le moment, réessayez.');
      return false;
    }
  }

  /* Opens the dialog in create mode (planData = null) or edit mode (planData = button dataset). */
  function openDialog(planData) {
    form.reset();
    clearError();
    clearLocation();
    if (planData) {
      dialogTitle.textContent = 'Modifier la planification';
      submitBtn.textContent = 'Enregistrer';
      form.action = `${form.dataset.actionBase}/update`;
      planIdInput.disabled = false;
      planIdInput.value = planData.planId;
      hourInput.value = planData.hour;
      const days = planData.days.split(',').filter(Boolean);
      dayInputs.forEach((input) => {
        input.checked = days.includes(input.value);
      });
      memorizeInput.checked = planData.memorize === '1';
      enabledInput.checked = planData.enabled === '1';
      addressInput.value = planData.label;
    } else {
      dialogTitle.textContent = 'Nouvelle planification';
      submitBtn.textContent = 'Créer la planification';
      form.action = `${form.dataset.actionBase}/create`;
      planIdInput.disabled = true;
      planIdInput.value = '';
      enabledInput.checked = true;
    }
    dialog.showModal();
    // Leaflet can only size the map once the dialog is rendered.
    requestAnimationFrame(() => {
      ensureMap();
      map.invalidateSize();
      if (planData && planData.lat !== '' && planData.lon !== '') {
        const lat = Number(planData.lat);
        const lon = Number(planData.lon);
        setLocation(lat, lon, { reverseGeocode: false });
        map.setView([lat, lon], 15);
      } else {
        map.setView(FRANCE_CENTER, 5);
      }
    });
  }

  document.getElementById('precond-add').addEventListener('click', () => openDialog(null));
  document.querySelectorAll('.precond-card__edit').forEach((button) => {
    button.addEventListener('click', () => openDialog(button.dataset));
  });
  document.getElementById('precond-dialog-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', (e) => {
    // A click on the native backdrop targets the <dialog> element itself.
    if (e.target === dialog) dialog.close();
  });

  document.getElementById('precond-address-search').addEventListener('click', searchAddress);
  addressInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchAddress();
    }
  });

  form.addEventListener('submit', async (e) => {
    // Coordinates are required: geocode the typed address as a last resort,
    // then requestSubmit() re-runs native validation before posting.
    if (latInput.value === '' || lonInput.value === '') {
      e.preventDefault();
      if (await searchAddress()) form.requestSubmit();
    }
  });
}

/* ---- Relative "last telemetry update" timestamp on the climate card ---- */

const lastUpdateP = document.querySelector('#lastUpdate');
const lastUpdateSpan = document.querySelector('#lastUpdateValue');
const timestamp = parseInt(lastUpdateP.getAttribute('data-value')) * 1000;
const lastUpdated = new Date(timestamp);

const rtf = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });
const diffSeconds = Math.round((lastUpdated - Date.now()) / 1000);
const abs = Math.abs(diffSeconds);

let relativeTime;
if (abs < 60) {
  relativeTime = rtf.format(diffSeconds, 'second');
} else if (abs < 3600) {
  relativeTime = rtf.format(Math.round(diffSeconds / 60), 'minute');
} else if (abs < 86400) {
  relativeTime = rtf.format(Math.round(diffSeconds / 3600), 'hour');
} else {
  relativeTime = rtf.format(Math.round(diffSeconds / 86400), 'day');
}

lastUpdateSpan.textContent = relativeTime;
