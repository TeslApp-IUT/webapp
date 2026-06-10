<?php
// Variables are normally supplied by AuthSignUpController; the `??=` fallbacks keep the view
// robust (and aligned to the `signup_tmp_*` session keys) if it is reached directly.
$pendingUser ??= [
  'firstName' => (string)($_SESSION['signup_tmp_first_name'] ?? ''),
  'lastName' => (string)($_SESSION['signup_tmp_last_name'] ?? ''),
  'email' => (string)($_SESSION['signup_tmp_email'] ?? ''),
];

$profilePicture ??= (string)($_SESSION['signup_tmp_profile_picture_display'] ?? '');
$errors ??= [];
$csrfToken ??= (string)($_SESSION['csrf_token'] ?? '');

$title = 'Authentification — TeslApp';
$description = "Authentification en cours avec Tesla";
$noChrome = true;
ob_start();
?>


  <div class="w-dvw h-dvh flex flex-col items-center justify-center p-4">
    <form id="form" method="post" class="flex flex-col items-center justify-center bg-[#1A1A1A] border border-[#4A4A4A] p-8 rounded-xl gap-5 w-full max-w-[420px] shadow-2xl transition-all duration-300 hover:border-neutral-500">

      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <img src="/_assets/images/Logo.svg" width="325" alt="logo TeslApp" class="mb-2 select-none">

      <div class="border-l-3 border-l-blue-600/50 bg-blue-600/30 p-2 rounded-lg text-gray-300 text-sm">
        Veuillez compléter vos informations pour finaliser la création de votre compte.
      </div>

      <?php if (!empty($errors['general'])): ?>
        <div class="border-l-3 border-l-red-600/50 bg-red-600/30 p-2 rounded-lg text-red-200 text-sm w-full">
          <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <div class="relative group my-2">
        <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-[#4A4A4A] transition-all duration-300 shadow-xl bg-[#0d0d0d] flex items-center justify-center">
          <img src="<?= strlen($profilePicture) > 1 ? htmlspecialchars($profilePicture, ENT_QUOTES, 'UTF-8') : '/_assets/images/placeholder_pfp.png' ?>" alt="Photo de profil" class="w-full h-full object-cover">
        </div>
        <div class="absolute -inset-1 rounded-full border border-[#3b82f6]/0 scale-95 pointer-events-none group-hover:scale-100 group-hover:border-[#3b82f6]/30 transition-all duration-300"></div>
      </div>

      <div class="bg-black/30 p-2 rounded-lg text-gray-300 text-xs">
        Photo issue de votre profil Tesla. Pour la modifier, rendez-vous dans l'application Tesla.
      </div>

      <div class="form-class grid grid-cols-1 sm:grid-cols-2 gap-5 w-full mt-2">

        <div class="field flex flex-col" id="field-firstname">
          <label for="firstname" class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 text-left">Prénom</label>
          <div class="relative flex items-center text-neutral-500 focus-within:text-[#3b82f6] transition-colors duration-200">
            <div class="absolute left-3.5 pointer-events-none">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <input
              type="text"
              id="firstname"
              name="firstName"
              placeholder="Prénom..."
              value="<?= htmlspecialchars($pendingUser['firstName']) ?>"
              autocomplete="given-name"
              class="w-full bg-[#0d0d0d] border border-[#2a2a2a] hover:border-[#444] focus:border-[#3b82f6] focus:ring-4 focus:ring-[#3b82f6]/10 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-neutral-600 transition-all duration-200 outline-none text-sm"
            >
          </div>
          <span class="error-msg text-xs text-red-500 mt-1.5 text-left <?= isset($errors['firstName']) ? '' : 'hidden' ?>">Champ requis</span>
        </div>

        <div class="field flex flex-col" id="field-lastname">
          <label for="lastname" class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 text-left">Nom</label>
          <div class="relative flex items-center text-neutral-500 focus-within:text-[#3b82f6] transition-colors duration-200">
            <div class="absolute left-3.5 pointer-events-none">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <input
              type="text"
              id="lastname"
              name="lastName"
              placeholder="Nom..."
              value="<?= htmlspecialchars($pendingUser['lastName']) ?>"
              autocomplete="family-name"
              class="w-full bg-[#0d0d0d] border border-[#2a2a2a] hover:border-[#444] focus:border-[#3b82f6] focus:ring-4 focus:ring-[#3b82f6]/10 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-neutral-600 transition-all duration-200 outline-none text-sm"
            >
          </div>
          <span class="error-msg text-xs text-red-500 mt-1.5 text-left <?= isset($errors['lastName']) ? '' : 'hidden' ?>">Champ requis</span>
        </div>

        <div class="field full flex flex-col sm:col-span-2" id="field-email">
          <label for="email" class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 text-left">Adresse e-mail</label>
          <div class="relative flex items-center text-neutral-500 focus-within:text-[#3b82f6] transition-colors duration-200">
            <div class="absolute left-3.5 pointer-events-none">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Email..."
              value="<?= htmlspecialchars($pendingUser['email']) ?>"
              autocomplete="email"
              class="w-full bg-[#0d0d0d] border border-[#2a2a2a] hover:border-[#444] focus:border-[#3b82f6] focus:ring-4 focus:ring-[#3b82f6]/10 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-neutral-600 transition-all duration-200 outline-none text-sm"
            >
          </div>
          <span class="error-msg text-xs text-red-500 mt-1.5 text-left <?= isset($errors['email']) ? '' : 'hidden' ?>">Adresse e-mail invalide</span>
        </div>

        <div class="divider border-t border-[#2a2a2a] w-full my-2 sm:col-span-2"></div>

        <div class="submit-wrap w-full sm:col-span-2 mt-1">
          <button type="submit" id="submit-btn" class="group w-full btn-primary hover:bg-gray-300! active:scale-95 transition-all! bg-white! text-black! font-normal! py-2.5 rounded-lg justify-center text-center flex items-center gap-2 cursor-pointer">
            <span id="btn-label">Finaliser mon compte</span>
            <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>
          </button>
        </div>

      </div>
    </form>
  </div>

  <style>
      @keyframes slideDown {
          from {
              opacity: 0;
              transform: translateY(-6px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }

      .error-msg:not(.hidden) {
          animation: slideDown 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
          display: block !important;
      }
  </style>

  <script>
    document.getElementById('form').addEventListener('submit', (e) => {
      let hasError = false;

      // Helper function for field validation styling
      function toggleFieldValidation(input, errorSpan, isValid) {
        if (isValid) {
          errorSpan.classList.add('hidden');
          input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/10');
          input.classList.add('border-[#2a2a2a]', 'focus:border-[#e82127]', 'focus:ring-[#e82127]/10');
        } else {
          errorSpan.classList.remove('hidden');
          input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/10');
          input.classList.remove('border-[#2a2a2a]', 'focus:border-[#e82127]', 'focus:ring-[#e82127]/10');
          hasError = true;
        }
      }

      // Validate Firstname
      const firstnameInput = document.getElementById('firstname');
      const firstnameError = document.querySelector('#field-firstname .error-msg');
      toggleFieldValidation(firstnameInput, firstnameError, firstnameInput.value.trim() !== '');

      // Validate Lastname
      const lastnameInput = document.getElementById('lastname');
      const lastnameError = document.querySelector('#field-lastname .error-msg');
      toggleFieldValidation(lastnameInput, lastnameError, lastnameInput.value.trim() !== '');

      // Validate Email
      const emailInput = document.getElementById('email');
      const emailError = document.querySelector('#field-email .error-msg');
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      toggleFieldValidation(emailInput, emailError, emailInput.value.trim() !== '' && emailRegex.test(emailInput.value));

      if (hasError) {
        e.preventDefault();
      }
    });
  </script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
