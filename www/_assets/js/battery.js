const limitDisplay = document.getElementById('limit-display');
const limitHidden = document.getElementById('limit-hidden');
const limitMinus = document.getElementById('limit-minus');
const limitPlus = document.getElementById('limit-plus');

/* Charge limit stepper: 5% steps, clamped to Tesla's [50, 100] range */
limitMinus.addEventListener('click', () => {
  let val = parseInt(limitHidden.value, 10);
  if (val > 50) {
    val -= 5;
    limitHidden.value = val;
    limitDisplay.textContent = val;
  }
});

limitPlus.addEventListener('click', () => {
  let val = parseInt(limitHidden.value, 10);
  if (val < 100) {
    val += 5;
    limitHidden.value = val;
    limitDisplay.textContent = val;
  }
});

/* Pre-fills the charging window with a typical off-peak tariff window */
const offpeakPreset = document.getElementById('offpeak-preset');
offpeakPreset.addEventListener('click', () => {
  document.getElementById('activation_hour').value = '23:30';
  document.getElementById('deactivation_hour').value = '07:30';
});
