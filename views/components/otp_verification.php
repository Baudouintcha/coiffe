<?php
/**
 * views/components/otp_verification.php — Composant de Vérification OTP
 * Coiffe Chez Toi — Design System v2.0
 *
 * Composant réutilisable pour tous les flux nécessitant une vérification OTP.
 * Respecte le Design System et supporte mobile/desktop.
 *
 * PARAMÈTRES (via $GLOBALS ou variables passées):
 * - $otp_purpose       : Type d'OTP (registration, password_reset, etc.)
 * - $user_email        : Email du destinataire (masqué)
 * - $submit_action     : Action du formulaire (ex: verify_otp)
 * - $csrf_token        : Token CSRF
 * - $error_message     : Message d'erreur (optionnel)
 * - $show_resend       : Afficher bouton renvoyer (défaut: true)
 *
 * USAGE dans une page PHP:
 *   <?php
 *       $otp_purpose = 'registration';
 *       $user_email = 'u***@gmail.com';
 *       $submit_action = 'verify_otp';
 *       $csrf_token = $_SESSION['_csrf_token'];
 *       include 'views/components/otp_verification.php';
 *   ?>
 */

// Paramètres par défaut
$otp_purpose    = $otp_purpose    ?? 'registration';
$user_email     = $user_email     ?? 'user@example.com';
$submit_action  = $submit_action  ?? 'verify_otp';
$csrf_token     = $csrf_token     ?? ($_SESSION['_csrf_token'] ?? '');
$error_message  = $error_message  ?? '';
$show_resend    = $show_resend    ?? true;
?>

<div class="otp-verification-container">
    <div class="otp-card">
        <!-- TITRE -->
        <div class="otp-title">
            <i class="bi bi-shield-check"></i>
            <h2>Vérification requise</h2>
        </div>

        <!-- INSTRUCTION -->
        <div class="otp-instruction">
            <p>Un code de vérification a été envoyé à</p>
            <p class="otp-email"><strong><?= htmlspecialchars($user_email) ?></strong></p>
        </div>

        <!-- FORMULAIRE -->
        <form method="POST" action="" class="otp-form" id="otp-form">
            <input type="hidden" name="action" value="<?= htmlspecialchars($submit_action) ?>">
            <input type="hidden" name="otp_purpose" value="<?= htmlspecialchars($otp_purpose) ?>">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- 6 CHAMPS OTP -->
            <div class="otp-inputs">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input 
                        type="text"
                        class="otp-input"
                        maxlength="1"
                        inputmode="numeric"
                        pattern="[0-9]"
                        placeholder="•"
                        name="otp_digit_<?= $i ?>"
                        id="otp_digit_<?= $i ?>"
                        aria-label="Chiffre <?= $i ?> du code OTP"
                        tabindex="<?= $i - 1 ?>"
                    >
                <?php endfor; ?>
            </div>

            <!-- CHAMP CACHÉ pour stocker le code complet -->
            <input type="hidden" name="otp_code" id="otp_code" value="">

            <!-- MESSAGE D'ERREUR -->
            <?php if (!empty($error_message)): ?>
                <div class="otp-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <!-- COMPTE À REBOURS + VALIDATION -->
            <div class="otp-controls">
                <div class="otp-timer">
                    <span class="timer-text">Code valable pendant</span>
                    <span class="timer-countdown" id="countdown">05:00</span>
                </div>

                <!-- BOUTON VÉRIFIER -->
                <button type="submit" class="otp-btn-verify" id="otp-btn-verify">
                    <span class="btn-content">
                        <i class="bi bi-check-circle"></i>
                        Vérifier
                    </span>
                    <span class="btn-loading" style="display:none;">
                        <span class="spinner"></span>
                        Vérification...
                    </span>
                </button>
            </div>

            <!-- BOUTON RENVOYER -->
            <?php if ($show_resend): ?>
                <div class="otp-resend-section">
                    <p class="otp-resend-text">Vous n'avez pas reçu le code ?</p>
                    <button type="button" class="otp-btn-resend" id="otp-btn-resend">
                        <i class="bi bi-arrow-repeat"></i>
                        Renvoyer le code
                    </button>
                </div>
            <?php endif; ?>
        </form>

        <!-- AIDE -->
        <div class="otp-help">
            <p>
                <strong>Besoin d'aide ?</strong> 
                <a href="#" onclick="alert('Contactez le support: support@coiffes-chez-toi.local')">Contactez-nous</a>
            </p>
        </div>
    </div>
</div>

<!-- STYLES -->
<style>
:root {
    --gold: #D4AF37;
    --gold-dim: rgba(212,175,55,0.12);
    --gold-border: rgba(212,175,55,0.25);
    --dark: #0a0a0a;
    --dark-2: #111;
    --dark-3: #161616;
    --white: #fff;
    --gray-light: #f5f5f5;
    --gray: #999;
    --danger: #dc3545;
}

.otp-verification-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
    padding: 20px;
    background: var(--dark);
}

.otp-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 32px 28px;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}

/* TITRE */
.otp-title {
    text-align: center;
    margin-bottom: 28px;
}

.otp-title i {
    font-size: 2.5rem;
    color: var(--gold);
    display: block;
    margin-bottom: 12px;
}

.otp-title h2 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--white);
    margin: 0;
}

/* INSTRUCTION */
.otp-instruction {
    text-align: center;
    margin-bottom: 24px;
}

.otp-instruction p {
    margin: 0 0 6px 0;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.6);
}

.otp-email {
    font-size: 0.9rem;
    color: var(--gold);
}

/* INPUTS OTP */
.otp-inputs {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin: 32px 0;
    flex-wrap: wrap;
}

.otp-input {
    width: 48px;
    height: 56px;
    border-radius: 12px;
    border: 2px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.03);
    color: var(--white);
    font-size: 1.4rem;
    font-weight: 700;
    text-align: center;
    font-family: 'Courier New', monospace;
    transition: all 0.2s;
    outline: none;
}

.otp-input::placeholder {
    color: rgba(255,255,255,0.2);
}

.otp-input:hover {
    border-color: var(--gold-border);
    background: rgba(255,255,255,0.05);
}

.otp-input:focus {
    border-color: var(--gold);
    background: rgba(212,175,55,0.08);
    box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
}

.otp-input.has-value {
    border-color: var(--gold);
    background: rgba(212,175,55,0.1);
    color: var(--gold);
}

/* MESSAGE D'ERREUR */
.otp-error {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(220,53,69,0.15);
    border: 1px solid rgba(220,53,69,0.3);
    color: #ffb3b3;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.85rem;
    animation: slideInDown 0.3s ease;
}

.otp-error i {
    font-size: 1rem;
    flex-shrink: 0;
}

/* CONTRÔLES */
.otp-controls {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 20px;
}

.otp-timer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.5);
}

.timer-text {
    font-weight: 500;
}

.timer-countdown {
    font-weight: 700;
    color: var(--gold);
    min-width: 40px;
    text-align: right;
    font-family: 'Courier New', monospace;
}

.timer-countdown.warning {
    color: #ffc107;
}

.timer-countdown.danger {
    color: var(--danger);
    animation: pulse 1s infinite;
}

/* BOUTON VÉRIFIER */
.otp-btn-verify {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--gold) 0%, #c9a227 100%);
    color: #000;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    outline: none;
}

.otp-btn-verify:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(212,175,55,0.3);
}

.otp-btn-verify:active:not(:disabled) {
    transform: translateY(0);
}

.otp-btn-verify:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-loading {
    display: flex;
    align-items: center;
    gap: 6px;
}

.spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(0,0,0,0.3);
    border-top-color: #000;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* RENVOYER */
.otp-resend-section {
    text-align: center;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.otp-resend-text {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.4);
    margin-bottom: 10px;
}

.otp-btn-resend {
    background: transparent;
    color: var(--gold);
    border: 1px solid var(--gold-border);
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    outline: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.otp-btn-resend:hover:not(:disabled) {
    background: var(--gold-dim);
    border-color: var(--gold);
}

.otp-btn-resend:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* AIDE */
.otp-help {
    text-align: center;
    margin-top: 20px;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.3);
}

.otp-help a {
    color: var(--gold);
    text-decoration: none;
    border-bottom: 1px dotted var(--gold);
    transition: color 0.2s;
}

.otp-help a:hover {
    color: #c9a227;
}

/* ANIMATIONS */
@keyframes slideInDown {
    from { transform: translateY(-10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* RESPONSIVE */
@media (max-width: 480px) {
    .otp-card {
        padding: 24px 20px;
    }

    .otp-title h2 {
        font-size: 1rem;
    }

    .otp-inputs {
        gap: 6px;
    }

    .otp-input {
        width: 42px;
        height: 50px;
        font-size: 1.2rem;
    }

    .otp-btn-verify {
        padding: 10px 20px;
        font-size: 0.85rem;
    }
}
</style>

<!-- JAVASCRIPT -->
<script>
(function() {
    'use strict';

    const COUNTDOWN_INITIAL = 300; // 5 minutes en secondes
    let countdownInterval = null;
    let remainingSeconds = COUNTDOWN_INITIAL;

    // ═══════════════════════════════════════════════════════
    // INITIALISATION
    // ═══════════════════════════════════════════════════════

    function init() {
        const inputs = document.querySelectorAll('.otp-input');
        const form = document.getElementById('otp-form');
        const btn_verify = document.getElementById('otp-btn-verify');
        const btn_resend = document.getElementById('otp-btn-resend');
        const countdown = document.getElementById('countdown');

        // Autofocus au premier champ
        if (inputs[0]) inputs[0].focus();

        // Démarrer le compte à rebours
        startCountdown();

        // Événements sur les inputs
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => handleInput(e, inputs));
            input.addEventListener('keydown', (e) => handleKeydown(e, inputs, index));
            input.addEventListener('paste', (e) => handlePaste(e, inputs));
        });

        // Soumission du formulaire
        form.addEventListener('submit', (e) => handleSubmit(e, btn_verify));

        // Renvoyer le code
        if (btn_resend) {
            btn_resend.addEventListener('click', () => handleResend(btn_resend));
        }
    }

    // ═══════════════════════════════════════════════════════
    // GESTION DES INPUTS
    // ═══════════════════════════════════════════════════════

    function handleInput(e, inputs) {
        const input = e.target;
        const value = input.value;

        // Ne garder que les chiffres
        if (!/^\d*$/.test(value)) {
            input.value = '';
            return;
        }

        // Garder seulement 1 chiffre
        if (value.length > 1) {
            input.value = value.slice(-1);
        }

        if (value.match(/\d/)) {
            input.classList.add('has-value');
        } else {
            input.classList.remove('has-value');
        }

        // Auto-focus au champ suivant
        if (value && input.nextElementSibling) {
            input.nextElementSibling.focus();
        }

        // Mettre à jour le champ caché
        updateOtpCode(inputs);
    }

    function handleKeydown(e, inputs, index) {
        if (e.key === 'Backspace' && e.target.value === '') {
            // Backspace sur un champ vide → aller au précédent
            if (index > 0) {
                inputs[index - 1].focus();
            }
            e.preventDefault();
        } else if (e.key === 'ArrowLeft' && index > 0) {
            inputs[index - 1].focus();
            e.preventDefault();
        } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
            inputs[index + 1].focus();
            e.preventDefault();
        }
    }

    function handlePaste(e, inputs) {
        e.preventDefault();
        const pastedData = (e.clipboardData || window.clipboardData).getData('text');
        const digits = pastedData.replace(/\D/g, '').slice(0, 6);

        inputs.forEach((input, index) => {
            input.value = digits[index] || '';
            if (input.value) input.classList.add('has-value');
        });

        if (digits.length === 6) {
            inputs[5].focus();
        } else if (digits.length > 0) {
            inputs[Math.min(digits.length, 5)].focus();
        }

        updateOtpCode(inputs);
    }

    // ═══════════════════════════════════════════════════════
    // COMPTE À REBOURS
    // ═══════════════════════════════════════════════════════

    function startCountdown() {
        const countdown = document.getElementById('countdown');
        remainingSeconds = COUNTDOWN_INITIAL;

        countdownInterval = setInterval(() => {
            remainingSeconds--;

            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            const display = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            countdown.textContent = display;

            // Classe de couleur d'alerte
            if (remainingSeconds < 60) {
                countdown.classList.add('warning');
            }
            if (remainingSeconds < 30) {
                countdown.classList.remove('warning');
                countdown.classList.add('danger');
            }

            if (remainingSeconds <= 0) {
                clearInterval(countdownInterval);
                countdown.textContent = '00:00';
                disableForm();
                showErrorMessage('Votre code a expiré. Veuillez en demander un nouveau.');
            }
        }, 1000);
    }

    // ═══════════════════════════════════════════════════════
    // SOUMISSION
    // ═══════════════════════════════════════════════════════

    function handleSubmit(e, btn_verify) {
        e.preventDefault();

        const inputs = document.querySelectorAll('.otp-input');
        const otp_code_field = document.getElementById('otp_code');

        // Vérifier que les 6 champs sont remplis
        if (!otp_code_field.value || otp_code_field.value.length !== 6) {
            showErrorMessage('Veuillez entrer les 6 chiffres.');
            return;
        }

        // Désactiver le bouton
        btn_verify.disabled = true;
        btn_verify.querySelector('.btn-content').style.display = 'none';
        btn_verify.querySelector('.btn-loading').style.display = 'flex';

        // Laisser le formulaire se soumettre naturellement
        setTimeout(() => {
            e.target.submit();
        }, 500);
    }

    // ═══════════════════════════════════════════════════════
    // RENVOYER LE CODE
    // ═══════════════════════════════════════════════════════

    function handleResend(btn_resend) {
        btn_resend.disabled = true;
        btn_resend.innerHTML = '<span class="spinner" style="width:12px;height:12px;"></span> Envoi...';

        // Simuler l'envoi (À intégrer avec endpoint réel)
        setTimeout(() => {
            btn_resend.disabled = false;
            btn_resend.innerHTML = '<i class="bi bi-arrow-repeat"></i> Code renvoyé !';

            // Redémarrer le compte à rebours
            clearInterval(countdownInterval);
            startCountdown();

            setTimeout(() => {
                btn_resend.innerHTML = '<i class="bi bi-arrow-repeat"></i> Renvoyer le code';
            }, 2000);
        }, 1500);
    }

    // ═══════════════════════════════════════════════════════
    // UTILITAIRES
    // ═══════════════════════════════════════════════════════

    function updateOtpCode(inputs) {
        const code = Array.from(inputs).map(i => i.value).join('');
        document.getElementById('otp_code').value = code;
    }

    function showErrorMessage(msg) {
        let errorDiv = document.querySelector('.otp-error');
        if (!errorDiv) {
            const form = document.querySelector('.otp-form');
            errorDiv = document.createElement('div');
            errorDiv.className = 'otp-error';
            form.insertBefore(errorDiv, form.querySelector('.otp-controls'));
        }
        errorDiv.innerHTML = `<i class="bi bi-exclamation-circle"></i><span>${msg}</span>`;
        errorDiv.style.display = 'flex';
    }

    function disableForm() {
        const inputs = document.querySelectorAll('.otp-input');
        const btn = document.getElementById('otp-btn-verify');
        inputs.forEach(i => i.disabled = true);
        if (btn) btn.disabled = true;
    }

    // Démarrer au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
