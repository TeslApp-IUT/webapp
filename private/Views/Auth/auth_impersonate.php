<?php
$title = 'Délégation — TeslApp';
$description = 'Connexion déléguée à un compte utilisateur';
$noChrome = true;
ob_start();
?>

<div class="w-dvw min-h-dvh flex flex-col items-center justify-center p-4">
  <div class="flex flex-col items-center bg-[#1A1A1A] border border-[#4A4A4A] p-8 rounded-xl gap-5 w-full max-w-[520px] shadow-2xl">

    <img src="/_assets/images/Logo.svg" width="280" alt="logo TeslApp" class="mb-2 select-none">

    <div class="border-l-3 border-l-amber-500/50 bg-amber-500/10 p-2 rounded-lg text-amber-300 text-sm w-full">
      Accès développeur — sélectionnez un compte à déléguer.
    </div>

    <?php if (empty($users)): ?>
      <p class="text-neutral-400 text-sm">Aucun autre utilisateur enregistré.</p>
    <?php else: ?>
      <div class="flex flex-col gap-2 w-full">
        <?php foreach ($users as $user): ?>
          <form method="post" action="/auth/impersonate/start" class="w-full">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="group w-full flex items-center justify-between bg-[#0d0d0d] border border-[#2a2a2a] hover:border-amber-500/60 hover:bg-amber-500/5 rounded-lg px-4 py-3 text-left transition-all duration-200 cursor-pointer">
              <div class="flex flex-col gap-0.5">
                <span class="text-white text-sm font-medium">
                  <?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="text-neutral-500 text-xs"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <svg class="w-4 h-4 text-neutral-600 group-hover:text-amber-400 transition-colors duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
              </svg>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
