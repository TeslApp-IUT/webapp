<?php
/** @var array<string, mixed> $user Profile fields, set by ProfileController. */
/** @var string $profilePicture Avatar URL (may be empty), set by ProfileController. */
/** @var string $csrfToken CSRF token for the update/delete actions, set by ProfileController. */
/** @var array<string, string> $errors Validation errors, set by ProfileController. */

$title = 'Profile - Teslapp';
$description = 'Modifiez vos informations concernant votre profil.';
$header = 'user';
$extraJs = ['profile'];


ob_start();
?>

    <div class="flex flex-col items-center justify-start py-10 px-4">
        <div class="w-full max-w-[520px] flex flex-col gap-6">

            <!-- Save success banner (hidden by default, shown via JS) -->
            <div id="success-banner" class="hidden border-l-3 border-green-500 bg-green-600/20 p-3 rounded-lg text-green-300 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Vos modifications ont été enregistrées.
            </div>

            <!-- General error banner -->
            <?php if (!empty($errors['general'])): ?>
                <div class="border-l-3 border-red-600/50 bg-red-600/30 p-2 rounded-lg text-red-200 text-sm w-full">
                    <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- ── Avatar card ─────────────────────────────── -->
            <div class="bg-[#1A1A1A] border border-[#4A4A4A] rounded-xl p-6 flex items-center gap-5 shadow-2xl">
                <div class="relative group shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#4A4A4A] shadow-lg bg-[#0d0d0d] flex items-center justify-center transition-all duration-300 group-hover:border-[#3b82f6]/60">
                        <img
                            src="<?= strlen($profilePicture) > 1 ? htmlspecialchars($profilePicture, ENT_QUOTES, 'UTF-8') : '/_assets/images/placeholder_pfp.png' ?>"
                            alt="Photo de profil"
                            class="w-full h-full object-cover"
                        >
                    </div>
                    <div class="absolute -inset-1 rounded-full border border-[#3b82f6]/0 scale-95 pointer-events-none group-hover:scale-100 group-hover:border-[#3b82f6]/30 transition-all duration-300"></div>
                </div>
                <div class="flex flex-col gap-0.5">
                    <p class="text-white font-medium text-base leading-snug">
                        <?= htmlspecialchars(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')) ?>
                    </p>
                    <p class="text-neutral-500 text-xs"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <p class="text-neutral-600 text-[11px] mt-2 leading-relaxed">
                        Photo issue de votre profil Tesla.<br>
                        Pour la modifier, utilisez l'application Tesla.
                    </p>
                </div>
            </div>

            <!-- ── Personal info card ──────────────────────── -->
            <form id="profile-form" method="post" action="/profile/update">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="bg-[#1A1A1A] border border-[#4A4A4A] rounded-xl shadow-2xl overflow-hidden transition-all duration-300 hover:border-neutral-500">

                    <!-- Card header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-[#2a2a2a]">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-[#3b82f6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">Informations personnelles</span>
                        </div>
                        <!-- Unsaved indicator -->
                        <span id="unsaved-pill" class="hidden text-[10px] font-medium bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-full px-2.5 py-0.5">
            Non enregistré
          </span>
                    </div>

                    <!-- Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 p-6">

                        <!-- Prénom -->
                        <div class="field flex flex-col" id="field-firstname">
                            <label for="firstname" class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 text-left">Prénom</label>
                            <div class="relative flex items-center text-neutral-500 focus-within:text-[#3b82f6] transition-colors duration-200">
                                <div class="absolute left-3.5 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text" id="firstname" name="firstName"
                                    placeholder="Prénom..."
                                    value="<?= htmlspecialchars($user['firstName'] ?? '') ?>"
                                    autocomplete="given-name"
                                    class="profile-input w-full bg-[#0d0d0d] border border-[#2a2a2a] hover:border-[#444] focus:border-[#3b82f6] focus:ring-4 focus:ring-[#3b82f6]/10 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-neutral-600 transition-all duration-200 outline-none text-sm"
                                >
                            </div>
                            <span class="error-msg text-xs text-red-500 mt-1.5 text-left <?= isset($errors['firstName']) ? '' : 'hidden' ?>">Champ requis</span>
                        </div>

                        <!-- Nom -->
                        <div class="field flex flex-col" id="field-lastname">
                            <label for="lastname" class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 text-left">Nom</label>
                            <div class="relative flex items-center text-neutral-500 focus-within:text-[#3b82f6] transition-colors duration-200">
                                <div class="absolute left-3.5 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text" id="lastname" name="lastName"
                                    placeholder="Nom..."
                                    value="<?= htmlspecialchars($user['lastName'] ?? '') ?>"
                                    autocomplete="family-name"
                                    class="profile-input w-full bg-[#0d0d0d] border border-[#2a2a2a] hover:border-[#444] focus:border-[#3b82f6] focus:ring-4 focus:ring-[#3b82f6]/10 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-neutral-600 transition-all duration-200 outline-none text-sm"
                                >
                            </div>
                            <span class="error-msg text-xs text-red-500 mt-1.5 text-left <?= isset($errors['lastName']) ? '' : 'hidden' ?>">Champ requis</span>
                        </div>

                        <!-- Email -->
                        <div class="field flex flex-col sm:col-span-2" id="field-email">
                            <label for="email" class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 text-left">Adresse e-mail</label>
                            <div class="relative flex items-center text-neutral-500 focus-within:text-[#3b82f6] transition-colors duration-200">
                                <div class="absolute left-3.5 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <input
                                    type="email" id="email" name="email"
                                    placeholder="Email..."
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                    autocomplete="email"
                                    class="profile-input w-full bg-[#0d0d0d] border border-[#2a2a2a] hover:border-[#444] focus:border-[#3b82f6] focus:ring-4 focus:ring-[#3b82f6]/10 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-neutral-600 transition-all duration-200 outline-none text-sm"
                                >
                            </div>
                            <span class="error-msg text-xs text-red-500 mt-1.5 text-left <?= isset($errors['email']) ? '' : 'hidden' ?>">Adresse e-mail invalide</span>
                        </div>

                        <div class="divider border-t border-[#2a2a2a] w-full my-2 sm:col-span-2"></div>

                        <!-- Save button -->
                        <div class="submit-wrap w-full sm:col-span-2 mt-1">
                            <button type="submit" id="submit-btn" class="group w-full btn-primary hover:bg-gray-300! active:scale-95 transition-all! bg-white! text-black! font-normal! py-2.5 rounded-lg justify-center text-center flex items-center gap-2 cursor-pointer">
                                <span id="btn-label">Enregistrer les modifications</span>
                                <svg id="btn-arrow" class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                                <svg id="btn-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </button>
                        </div>

                    </div>
                </div>
            </form>

            <!-- ── Danger zone ─────────────────────────────── -->
            <div class="bg-[#1A1A1A] border border-red-900/40 rounded-xl shadow-2xl overflow-hidden">
                <div class="flex items-center gap-2.5 px-6 py-4 border-b border-red-900/30">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span class="text-[10px] font-bold text-red-500/80 uppercase tracking-widest">Zone de danger</span>
                </div>
                <div class="px-6 py-5 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-white font-medium">Supprimer mon compte</p>
                        <!--<p class="text-xs text-neutral-500 mt-0.5">Cette action est irréversible. Toutes vos données seront perdues.</p>!-->
                    </div>
                    <button
                        type="button"
                        id="delete-btn"
                        class="shrink-0 text-xs font-medium text-red-400 hover:text-red-300 border border-red-700/50 hover:border-red-500/70 rounded-lg px-4 py-2 transition-all duration-200 cursor-pointer"
                    >
                        Supprimer
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ── Delete confirmation modal ──────────────── -->
    <div id="delete-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-[#1A1A1A] border border-[#4A4A4A] rounded-xl p-7 w-full max-w-sm shadow-2xl flex flex-col gap-5">
            <div class="flex flex-col gap-1.5">
                <h2 class="text-white font-semibold text-base">Supprimer le compte</h2>
                <p class="text-neutral-400 text-sm leading-relaxed">Êtes-vous sûr ? Cette action est permanente et supprimera toutes vos données TeslApp.</p>
            </div>
            <div class="flex gap-3">
                <button id="cancel-delete" class="flex-1 text-sm font-medium text-neutral-300 bg-[#2a2a2a] hover:bg-[#333] rounded-lg py-2.5 transition-colors duration-200 cursor-pointer">
                    Annuler
                </button>
                <a href="/profile/delete?csrf_token=<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" class="flex-1 text-sm font-medium text-center text-white bg-red-700 hover:bg-red-600 rounded-lg py-2.5 transition-colors duration-200">
                    Confirmer
                </a>
            </div>
        </div>
    </div>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';