const btnActiver = document.getElementById('btn-activer');
const voletTemp = document.getElementById('volet-temp');
const tempSlider = document.getElementById('temp-slider');
const tempDisplay = document.getElementById('temp-display');
const tempHidden = document.getElementById('temp-hidden');

/* Displays the pane when you click “Enable” */
btnActiver.addEventListener('click', () => {
  voletTemp.style.display = voletTemp.style.display === 'none' ? 'block' : 'none';
});

/* Updates the display and the hidden value when the slider is moved */
tempSlider.addEventListener('input', () => {
  tempDisplay.textContent = tempSlider.value;
  tempHidden.value = tempSlider.value;
});
