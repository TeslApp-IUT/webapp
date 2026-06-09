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
