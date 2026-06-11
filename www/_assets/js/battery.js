/* Battery page: steppers + quick presets for the charge settings, the gauge limit
   marker, and the charging-schedule dialog with its Leaflet location picker. */

/* ---- Steppers + quick presets (charge limit, charging amps) ---- */

function initStepper({ name, min, max, step, onChange }) {
  const hidden = document.getElementById(`${name}-value`);
  const display = document.getElementById(`${name}-display`);
  const presets = [...document.querySelectorAll(`.battery-preset[data-preset="${name}"]`)];

  function set(value) {
    const clamped = Math.min(max, Math.max(min, value));
    hidden.value = clamped;
    display.textContent = clamped;
    presets.forEach((button) => {
      button.classList.toggle('is-active', Number(button.dataset.value) === clamped);
    });
    if (onChange) onChange(clamped);
  }

  document.getElementById(`${name}-minus`).addEventListener('click', () => {
    set(parseInt(hidden.value, 10) - step);
  });
  document.getElementById(`${name}-plus`).addEventListener('click', () => {
    set(parseInt(hidden.value, 10) + step);
  });
  presets.forEach((button) => {
    button.addEventListener('click', () => set(Number(button.dataset.value)));
  });

  // The server renders the vehicle's actual setting (or a default) in the hidden
  // input; sync the active preset and the gauge marker with it on load.
  set(Number.parseInt(hidden.value, 10));
}

/* ---- Battery gauge fill ----
   The width is set through the CSSOM instead of an inline style attribute: the
   deployed environments ship a strict CSP (style-src without 'unsafe-inline')
   that blocks inline styles, which left the gauge empty there. */
const gaugeTrack = document.querySelector('.battery-gauge__track');
if (gaugeTrack) {
  const level = Number.parseFloat(gaugeTrack.dataset.level);
  const fill = gaugeTrack.querySelector('.battery-gauge__fill');
  if (fill && !Number.isNaN(level)) {
    fill.style.width = `${level}%`;
  }
}

const gaugeLimit = document.getElementById('gauge-limit');

/* Charge limit: 5% steps, clamped to Tesla's [50, 100] range; mirrors onto the gauge marker. */
initStepper({
  name: 'limit',
  min: 50,
  max: 100,
  step: 5,
  onChange: (value) => {
    if (gaugeLimit) gaugeLimit.style.left = `${value}%`;
  },
});

/* Charging amps: 1A steps, clamped to Tesla's [5, 48] range. */
initStepper({ name: 'amps', min: 5, max: 48, step: 1 });

/* ---- Charging schedules: create/edit dialog + Leaflet location picker ---- */

const dialog = document.getElementById('plan-dialog');

if (dialog) {
  const form = document.getElementById('plan-form');
  const dialogTitle = document.getElementById('plan-dialog-title');
  const submitBtn = document.getElementById('plan-submit');
  const planIdInput = document.getElementById('plan_id');
  const hourInput = document.getElementById('activation_hour');
  const endInput = document.getElementById('deactivation_hour');
  const memorizeInput = form.querySelector('input[name="memorize"]');
  const enabledInput = form.querySelector('input[name="enabled"]');
  const dayInputs = [...form.querySelectorAll('input[name="days[]"]')];
  const latInput = document.getElementById('latitude');
  const lonInput = document.getElementById('longitude');
  const addressInput = document.getElementById('location_label');
  const errorBox = document.getElementById('plan-form-error');

  const FRANCE_CENTER = [46.6, 2.45];

  let map = null;
  let marker = null;
  // Bumped on each geocode request so stale responses are ignored.
  let geocodeSeq = 0;
  let reverseTimer = null;
  // Set when the user types in the address field.
  let addressDirty = false;
  let submitting = false;

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
    map = L.map('plan-map').setView(FRANCE_CENTER, 5);
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
      // Keep the field readable until the address resolves.
      addressInput.value = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
      const seq = ++geocodeSeq;
      clearTimeout(reverseTimer);
      // Wait for the marker to settle before hitting Nominatim.
      reverseTimer = setTimeout(() => {
        fetch(`/geocode/reverse?lat=${lat}&lon=${lon}`, {
          headers: { Accept: 'application/json' },
        })
          .then((response) => (response.ok ? response.json() : null))
          .then((data) => {
            if (seq === geocodeSeq && data?.label) addressInput.value = data.label;
          })
          .catch(() => {});
      }, 400);
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
    const seq = ++geocodeSeq;
    try {
      const response = await fetch(`/geocode?q=${encodeURIComponent(query)}`, {
        headers: { Accept: 'application/json' },
      });
      if (seq !== geocodeSeq) return false;
      if (!response.ok) {
        showError('Adresse introuvable : précisez (rue, ville…) ou cliquez sur la carte.');
        return false;
      }
      const data = await response.json();
      if (seq !== geocodeSeq) return false;
      setLocation(data.lat, data.lon, { reverseGeocode: false });
      addressInput.value = data.label;
      map.setView([data.lat, data.lon], 15);
      return true;
    } catch {
      if (seq === geocodeSeq) showError('Recherche impossible pour le moment, réessayez.');
      return false;
    }
  }

  /* Opens the dialog in create mode (planData = null) or edit mode (planData = button dataset). */
  function openDialog(planData) {
    // Drop any geocode response still in flight.
    geocodeSeq++;
    clearTimeout(reverseTimer);
    addressDirty = false;
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
      endInput.value = planData.end;
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

  document.getElementById('plan-add').addEventListener('click', () => openDialog(null));
  document.querySelectorAll('.precond-card__edit').forEach((button) => {
    button.addEventListener('click', () => openDialog(button.dataset));
  });
  document.getElementById('plan-dialog-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', (e) => {
    // A click on the native backdrop targets the <dialog> element itself.
    if (e.target === dialog) dialog.close();
  });

  /* Pre-fills the charging window with a typical off-peak tariff window. */
  document.getElementById('offpeak-preset').addEventListener('click', () => {
    hourInput.value = '23:30';
    endInput.value = '07:30';
  });

  document.getElementById('plan-address-search').addEventListener('click', searchAddress);
  addressInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchAddress();
    }
  });
  addressInput.addEventListener('input', () => {
    // Typing invalidates the stored coordinates.
    addressDirty = true;
    geocodeSeq++;
    clearLocation();
  });

  form.addEventListener('submit', async (e) => {
    // Location already resolved, nothing to do here.
    if (latInput.value !== '' && lonInput.value !== '') return;

    // No location at all: fine, the schedule simply stays local (not pushed to the car).
    if (addressInput.value.trim() === '') return;

    // A typed address is not geocoded yet: resolve it before letting the form go.
    e.preventDefault();
    if (submitting) return;

    if (!addressDirty) {
      // Never geocode a stored label behind the user's back.
      showError('Vérifiez le lieu : lancez la recherche, cliquez sur la carte, ou videz le champ.');
      return;
    }

    submitting = true;
    submitBtn.disabled = true;
    try {
      if (await searchAddress()) form.requestSubmit();
    } finally {
      submitting = false;
      submitBtn.disabled = false;
    }
  });
}
