// ── Unsaved-changes pill ──────────────────────────────────
const inputs     = document.querySelectorAll('.profile-input');
const pill       = document.getElementById('unsaved-pill');
const origValues = {};

inputs.forEach(input => { origValues[input.id] = input.value; });

inputs.forEach(input => {
  input.addEventListener('input', () => {
    const dirty = [...inputs].some(i => i.value !== origValues[i.id]);
    pill.classList.toggle('hidden', !dirty);
  });
});

// ── Form validation & submit spinner ─────────────────────
document.getElementById('profile-form').addEventListener('submit', (e) => {
  let hasError = false;

  function toggleFieldValidation(input, errorSpan, isValid) {
    if (isValid) {
      errorSpan.classList.add('hidden');
      input.classList.remove('border-red-500');
      input.classList.add('border-[#2a2a2a]');
    } else {
      errorSpan.classList.remove('hidden');
      input.classList.add('border-red-500');
      input.classList.remove('border-[#2a2a2a]');
      hasError = true;
    }
  }

  const first      = document.getElementById('firstname');
  const last       = document.getElementById('lastname');
  const email      = document.getElementById('email');
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  toggleFieldValidation(first, document.querySelector('#field-firstname .error-msg'), first.value.trim() !== '');
  toggleFieldValidation(last,  document.querySelector('#field-lastname .error-msg'),  last.value.trim()  !== '');
  toggleFieldValidation(email, document.querySelector('#field-email .error-msg'),
    email.value.trim() !== '' && emailRegex.test(email.value));

  if (hasError) { e.preventDefault(); return; }

  // Show spinner
  const btn     = document.getElementById('submit-btn');
  const label   = document.getElementById('btn-label');
  const arrow   = document.getElementById('btn-arrow');
  const spinner = document.getElementById('btn-spinner');
  btn.disabled  = true;
  btn.classList.add('opacity-70');
  label.textContent = 'Enregistrement…';
  arrow.classList.add('hidden');
  spinner.classList.remove('hidden');
});

// ── Success banner (shown if ?saved=1 in URL) ─────────────
if (new URLSearchParams(location.search).get('saved') === '1') {
  document.getElementById('success-banner').classList.remove('hidden');
  setTimeout(() => document.getElementById('success-banner').classList.add('hidden'), 4000);
}

// ── Delete modal ─────────────────────────────────────────
document.getElementById('delete-btn').addEventListener('click',    () => document.getElementById('delete-modal').classList.remove('hidden'));
document.getElementById('cancel-delete').addEventListener('click', () => document.getElementById('delete-modal').classList.add('hidden'));
document.getElementById('delete-modal').addEventListener('click', (e) => {
  if (e.target === document.getElementById('delete-modal'))
    document.getElementById('delete-modal').classList.add('hidden');
});