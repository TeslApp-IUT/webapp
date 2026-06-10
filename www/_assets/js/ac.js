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