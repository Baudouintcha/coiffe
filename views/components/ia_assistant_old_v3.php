<?php
/**
 * views/components/ia_assistant.php — IAAssistant Humain & Cosy v4.0
 * Design Hologrammatique Bienveillant
 * 
 * PHILOSOPHIE DE DESIGN :
 * ═══════════════════════════════════════════════════════════════
 * 
 * 1. GÉOMÉTRIE : Cercle pur (hologramme)
 *    - Symbolise : Complétude, équilibre, nature
 *    - Pas de points, pas de détails inutiles
 *    - Beauté par la simplicité
 * 
 * 2. TYPO — CHOIX JUSTIFIÉS :
 *    
 *    a) Inter (Sans-serif, interface)
 *       - Pourquoi : Rond, amical, lisible
 *       - Utilisé pour : Étiquettes, instructions
 *       - Psychologie : Approche douce, accessible
 *       - Poids : 400 (light), 500 (normal), 600 (emphase)
 *    
 *    b) Lora (Serif, messages IA)
 *       - Pourquoi : Humain, rassurant, écrit comme une personne
 *       - Utilisé pour : Réponses de l'IA, messages longs
 *       - Psychologie : Crée intimité, confiance, comme une lettre
 *       - Poids : 400 (normal), 600 (emphase)
 *    
 *    c) Playfair Display (Serif, titres)
 *       - Pourquoi : Élégant, humanisé, luxe accessible
 *       - Utilisé pour : Nom de l'IA, titres importants
 *       - Psychologie : Premium mais humain (pas robotique)
 * 
 * 3. COULEURS : Palette naturelle et apaisante
 *    - Bleu glacier (#4FA8D5) : Calme, confiance, humain
 *    - Rose poudré (#E8C5D8) : Douceur, accueil, bienveillance
 *    - Gris chaud (#3D3D3D) : Profondeur sans froideur
 *    - Blanc ivoire (#F9F7F4) : Repos pour les yeux
 * 
 * 4. ANIMATIONS :
 *    - Douces et fluides (pas de soubresauts)
 *    - Respectent le rythme humain (0.6s à 1.2s)
 *    - Indiquent un état, pas de distraction
 * 
 * 5. HOLOGRAMME :
 *    - Halo irisé (faux hologramme)
 *    - Pas d'étoile, pas d'emoji
 *    - Cercle pur avec lumière intérieure
 *    - Suggère : technologie douce, assistée, naturelle
 */

$role_actuel = $_SESSION['role'] ?? 'invite';
$nomIA = "Assistant";
$couleur_principal = "#4FA8D5"; // Bleu glacier (humain, calme)

// Noms personnalisés selon le rôle
$noms_ia = [
    'invite'     => 'Célia',      // Féminin, doux
    'client'     => 'Célia',
    'prestataire' => 'Lucas',     // Masculin, accessible
    'admin'      => 'Lucas',
];

$nomIA = $noms_ia[$role_actuel] ?? 'Assistant';
?>

<!-- ÉTAT MACHINE : Orbe lumineux avec indicateurs d'état -->
<style>
/* Déclaration des états IA pour le CSS */
:root {
    --ia-state: 'inactive'; /* inactive | listening | thinking | acting | responding */
    --ia-color: #D4AF37;
    --ia-state-text: 'Offline';
}

.ia-state-inactive { --ia-state: 'inactive'; --ia-state-text: 'Ready'; }
.ia-state-listening { --ia-state: 'listening'; --ia-state-text: 'Listening'; }
.ia-state-thinking { --ia-state: 'thinking'; --ia-state-text: 'Thinking'; }
.ia-state-acting { --ia-state: 'acting'; --ia-state-text: 'Processing'; }
.ia-state-responding { --ia-state: 'responding'; --ia-state-text: 'Ready'; }
</style>


<!-- ────────────────────────────────────────────────────────────────────
     ORBE IA — Indicateur d'état lumineux contextuel
     Éléments :
     - Orbe principal (animé selon l'état)
     - Halo de lueur
     - Indicateur d'état (point lumineux)
     - Badge de statut texte (optionnel)
     ──────────────────────────────────────────────────────────────────── -->

<div id="ia-orb" class="ia-orb ia-state-inactive" role="button" aria-label="<?= htmlspecialchars($nomIA) ?> Assistant"
     tabindex="0" onclick="iaToggleChat()" onkeypress="if(event.key==='Enter')iaToggleChat()">

    <!-- Halo de fond (partie la plus lointaine) -->
    <div class="ia-orb-halo ia-halo-far" aria-hidden="true"></div>
    
    <!-- Halo proche (pulsation avec l'état) -->
    <div class="ia-orb-halo ia-halo-near" aria-hidden="true"></div>
    
    <!-- Orbe principal -->
    <div class="ia-orb-core" aria-hidden="true">
        <!-- Lumière interne -->
        <div class="ia-orb-glow"></div>
        
        <!-- Symbole abstrait (pas de robot) -->
        <div class="ia-orb-symbol" aria-hidden="true">✨</div>
    </div>
    
    <!-- Indicateur d'état (petit point lumineux animé) -->
    <div class="ia-status-indicator" aria-hidden="true">
        <span class="ia-status-dot"></span>
    </div>
</div>

<!-- ────────────────────────────────────────────────────────────────────
     FENÊTRE CHAT — Dialogue moderne et élégant
     ──────────────────────────────────────────────────────────────────── -->

<div id="ia-chat-window" class="ia-chat-window" role="dialog" aria-label="<?= htmlspecialchars($nomIA) ?> Chat" aria-hidden="true">
    
    <!-- En-tête avec info de l'assistant -->
    <div class="ia-chat-header">
        <div class="ia-chat-header-profile">
            <div class="ia-avatar">
                <div class="ia-avatar-glow"></div>
                <span class="ia-avatar-icon">✨</span>
            </div>
            <div class="ia-header-info">
                <strong class="ia-name"><?= htmlspecialchars($nomIA) ?></strong>
                <span class="ia-status-badge" id="ia-status-text">Ready</span>
            </div>
        </div>
        <button class="ia-close-btn" onclick="iaToggleChat()" aria-label="Fermer le chat">
            <span aria-hidden="true">✕</span>
        </button>
    </div>

    <!-- Zone de contenu chat -->
    <div id="ia-chat-content" class="ia-chat-content" role="log" aria-live="polite" aria-label="Historique du chat">
        
        <!-- Message de bienvenue -->
        <div class="ia-message ia-message-welcome">
            <div class="ia-message-header">
                <span class="ia-welcome-emoji">💬</span>
                <div class="ia-message-title">Bienvenue!</div>
            </div>
            <div class="ia-message-body">
                Je suis <?= htmlspecialchars($nomIA) ?>, votre assistant personnel.
                <br><span class="ia-message-subtitle">Ici pour faciliter votre parcours</span>
            </div>
        </div>

        <!-- Suggestions rapides contextualisées -->
        <div class="ia-suggestions" id="ia-suggestions">
            <?php if ($role_actuel === 'invite'): ?>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Je cherche un coiffeur à Cotonou')">
                    <span class="ia-suggestion-icon">🔍</span>
                    <span>Coiffeur à Cotonou</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Comment créer un compte ?')">
                    <span class="ia-suggestion-icon">📝</span>
                    <span>Créer un compte</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Comment fonctionne la plateforme ?')">
                    <span class="ia-suggestion-icon">ℹ️</span>
                    <span>Comment ça marche</span>
                </button>
            <?php elseif ($role_actuel === 'client'): ?>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Montre-moi mes rendez-vous')">
                    <span class="ia-suggestion-icon">📅</span>
                    <span>Mes RDV</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Quel est mon solde ?')">
                    <span class="ia-suggestion-icon">💰</span>
                    <span>Mon solde</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Comment réserver un coiffeur ?')">
                    <span class="ia-suggestion-icon">✂️</span>
                    <span>Réserver</span>
                </button>
            <?php elseif ($role_actuel === 'prestataire'): ?>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Combien ai-je de demandes en attente ?')">
                    <span class="ia-suggestion-icon">⏳</span>
                    <span>Demandes</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Quels sont mes RDV du jour ?')">
                    <span class="ia-suggestion-icon">📅</span>
                    <span>RDV du jour</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Quel est mon solde ?')">
                    <span class="ia-suggestion-icon">💰</span>
                    <span>Mon solde</span>
                </button>
            <?php elseif ($role_actuel === 'admin'): ?>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Combien y a-t-il de diplômes en attente ?')">
                    <span class="ia-suggestion-icon">🎓</span>
                    <span>Diplômes en attente</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Donne-moi les statistiques de la plateforme')">
                    <span class="ia-suggestion-icon">📊</span>
                    <span>Statistiques</span>
                </button>
                <button class="ia-suggestion-btn" onclick="iaSendQuick('Combien de RDV sont en attente ?')">
                    <span class="ia-suggestion-icon">📋</span>
                    <span>RDV en attente</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pied de chat (saisie + boutons) -->
    <div class="ia-chat-footer">
        <!-- Ligne de saisie -->
        <div class="ia-input-row">
            <input type="text"
                   id="ia-user-input"
                   class="ia-input"
                   placeholder="Posez une question..."
                   onkeypress="if(event.key==='Enter')iaSendMessage()"
                   aria-label="Message pour l'assistant IA">
            <button class="ia-voice-btn" id="ia-voice-btn" title="Dictée vocale" aria-label="Dicter un message">
                🎤
            </button>
        </div>
        
        <!-- Actions supplémentaires -->
        <div class="ia-actions">
            <label for="ia-photo-input" class="ia-action-btn ia-action-photo">
                📷
            </label>
            <input type="file" id="ia-photo-input" hidden accept="image/*" aria-label="Envoyer une photo">
            <button class="ia-action-btn ia-action-send" onclick="iaSendMessage()" aria-label="Envoyer le message">
                ➤
            </button>
        </div>
    </div>
</div>

<!-- ────────────────────────────────────────────────────────────────────
     STYLES CSS — Design moderne, premium et minimaliste
     ──────────────────────────────────────────────────────────────────── -->
<style>
/* VARIABLES DE DESIGN */
:root {
    --ia-primary: #D4AF37;        /* Or premium */
    --ia-dark-bg: #0a0a0a;        /* Noir profond */
    --ia-card-bg: rgba(20, 17, 8, 0.92);
    --ia-border: rgba(212, 175, 55, 0.25);
    --ia-border-light: rgba(212, 175, 55, 0.15);
    --ia-text-primary: #ffffff;
    --ia-text-secondary: rgba(255, 255, 255, 0.75);
    --ia-text-muted: rgba(255, 255, 255, 0.45);
    --ia-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.4);
    --ia-shadow-md: 0 8px 32px rgba(0, 0, 0, 0.6);
    --ia-shadow-gold: 0 0 20px rgba(212, 175, 55, 0.3);
}

/* ORBE IA — Indicateur lumineux */
.ia-orb {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    user-select: none;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* HALOS D'ANIMATION */
.ia-orb-halo {
    position: absolute;
    border-radius: 50%;
    border: 2px solid var(--ia-primary);
    opacity: 0.3;
}

.ia-halo-far {
    width: 100%;
    height: 100%;
    animation: iaHaloFar 3s ease-in-out infinite;
}

.ia-halo-near {
    width: 85%;
    height: 85%;
    animation: iaHaloNear 2.5s ease-in-out infinite;
    opacity: 0.5;
}

@keyframes iaHaloFar {
    0%, 100% { transform: scale(1); opacity: 0.2; }
    50% { transform: scale(1.1); opacity: 0.4; }
}

@keyframes iaHaloNear {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.05); opacity: 0.6; }
}

/* ORBE PRINCIPAL */
.ia-orb-core {
    position: relative;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, rgba(212, 175, 55, 0.4), rgba(20, 17, 8, 0.9));
    border: 2px solid var(--ia-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--ia-shadow-gold), inset 0 0 20px rgba(212, 175, 55, 0.2);
    z-index: 10;
}

.ia-orb-glow {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: radial-gradient(circle at center, rgba(212, 175, 55, 0.3), transparent 70%);
    animation: iaGlowPulse 2s ease-in-out infinite;
}

@keyframes iaGlowPulse {
    0%, 100% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
}

.ia-orb-symbol {
    position: relative;
    font-size: 32px;
    z-index: 2;
    animation: iaSymbolFloat 3s ease-in-out infinite;
}

@keyframes iaSymbolFloat {
    0%, 100% { transform: translateY(0); opacity: 1; }
    50% { transform: translateY(-3px); opacity: 0.9; }
}

/* INDICATEUR D'ÉTAT */
.ia-status-indicator {
    position: absolute;
    bottom: 5px;
    right: 5px;
    z-index: 15;
}

.ia-status-dot {
    display: block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);
    animation: iaStatusPulse 2s ease-in-out infinite;
}

@keyframes iaStatusPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

/* ÉTATS DE L'ORBE */
.ia-state-listening .ia-orb-core {
    background: radial-gradient(circle at 30% 30%, rgba(212, 175, 55, 0.5), rgba(20, 17, 8, 0.9));
    animation: iaOrbListening 1s ease-in-out infinite;
}

@keyframes iaOrbListening {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.ia-state-thinking .ia-orb-core {
    animation: iaOrbThinking 1.5s ease-in-out infinite;
}

@keyframes iaOrbThinking {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.ia-state-acting .ia-status-dot {
    background: #f59e0b;
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.8);
    animation: iaStatusAction 0.8s ease-in-out infinite;
}

@keyframes iaStatusAction {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.4); }
}

/* HOVER SUR ORBE */
.ia-orb:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 0 30px rgba(212, 175, 55, 0.4);
}

.ia-orb:hover .ia-orb-core {
    box-shadow: var(--ia-shadow-gold), 0 0 30px rgba(212, 175, 55, 0.5), inset 0 0 20px rgba(212, 175, 55, 0.3);
}

/* FENÊTRE CHAT */
.ia-chat-window {
    display: none;
    position: fixed;
    bottom: 110px;
    right: 30px;
    width: 380px;
    background: var(--ia-card-bg);
    border: 1px solid var(--ia-border);
    border-radius: 20px;
    z-index: 998;
    box-shadow: var(--ia-shadow-md), 0 0 0 1px var(--ia-border-light);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    display: flex;
    flex-direction: column;
    max-height: 600px;
    animation: iaChatSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow: hidden;
}

@keyframes iaChatSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.ia-chat-window[style*="display: block"] {
    display: flex !important;
}

/* EN-TÊTE CHAT */
.ia-chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid var(--ia-border-light);
    background: rgba(20, 17, 8, 0.8);
}

.ia-chat-header-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ia-avatar {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.15);
    border: 1.5px solid rgba(212, 175, 55, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.ia-avatar-glow {
    position: absolute;
    inset: -2px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.2), transparent);
}

.ia-header-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ia-name {
    color: var(--ia-primary);
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.ia-status-badge {
    color: var(--ia-text-muted);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 500;
}

.ia-close-btn {
    background: none;
    border: none;
    color: var(--ia-text-muted);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: all 0.2s;
    line-height: 1;
}

.ia-close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--ia-text-primary);
}

/* CONTENU CHAT */
.ia-chat-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    scroll-behavior: smooth;
}

.ia-chat-content::-webkit-scrollbar {
    width: 4px;
}

.ia-chat-content::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.4);
    border-radius: 2px;
}

/* MESSAGES */
.ia-message {
    margin-bottom: 12px;
    padding: 12px 14px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 12px;
    font-size: 0.9rem;
    line-height: 1.5;
    color: var(--ia-text-secondary);
    animation: iaMessageFadeIn 0.4s ease-out;
}

@keyframes iaMessageFadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ia-message-welcome {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(212, 175, 55, 0.05));
    border: 1.5px solid rgba(212, 175, 55, 0.3);
    padding: 16px;
}

.ia-message-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.ia-welcome-emoji {
    font-size: 1.5rem;
    animation: iaWelcomeFloat 2s ease-in-out infinite;
}

@keyframes iaWelcomeFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.ia-message-title {
    font-weight: 600;
    color: var(--ia-text-primary);
}

.ia-message-body {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.ia-message-subtitle {
    display: block;
    font-size: 0.85rem;
    color: rgba(212, 175, 55, 0.8);
    margin-top: 6px;
    font-weight: normal;
}

/* SUGGESTIONS */
.ia-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 12px;
}

.ia-suggestion-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(212, 175, 55, 0.08);
    border: 1px solid rgba(212, 175, 55, 0.2);
    color: var(--ia-text-secondary);
    border-radius: 16px;
    padding: 8px 12px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    font-weight: 500;
}

.ia-suggestion-btn:hover {
    background: rgba(212, 175, 55, 0.15);
    color: var(--ia-primary);
    border-color: rgba(212, 175, 55, 0.4);
}

.ia-suggestion-icon {
    font-size: 0.9rem;
}

/* PIED DU CHAT */
.ia-chat-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--ia-border-light);
    background: rgba(20, 17, 8, 0.6);
}

.ia-input-row {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}

.ia-input {
    flex: 1;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 10px;
    color: var(--ia-text-primary);
    padding: 10px 12px;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    transition: all 0.2s;
}

.ia-input:focus {
    border-color: rgba(212, 175, 55, 0.6);
    background: rgba(255, 255, 255, 0.08);
}

.ia-input::placeholder {
    color: var(--ia-text-muted);
}

.ia-voice-btn {
    background: none;
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 8px;
    color: var(--ia-primary);
    width: 40px;
    height: 40px;
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ia-voice-btn:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: rgba(212, 175, 55, 0.4);
}

.ia-actions {
    display: flex;
    gap: 8px;
}

.ia-action-btn {
    flex: 1;
    background: rgba(212, 175, 55, 0.1);
    border: 1px solid rgba(212, 175, 55, 0.25);
    border-radius: 8px;
    color: var(--ia-text-secondary);
    padding: 8px;
    cursor: pointer;
    font-size: 0.95rem;
    font-family: inherit;
    font-weight: 500;
    transition: all 0.2s;
    text-align: center;
}

.ia-action-btn:hover {
    background: rgba(212, 175, 55, 0.2);
    color: var(--ia-primary);
    border-color: var(--ia-primary);
}

/* RESPONSIVE */
@media (max-width: 576px) {
    .ia-orb {
        width: 60px;
        height: 60px;
        bottom: 20px;
        right: 20px;
    }

    .ia-orb-core {
        width: 55px;
        height: 55px;
    }

    .ia-orb-symbol {
        font-size: 28px;
    }

    .ia-chat-window {
        width: calc(100% - 32px);
        bottom: 85px;
        right: 16px;
        left: 16px;
        max-height: 500px;
        border-radius: 16px;
    }

    .ia-chat-content {
        max-height: 300px;
    }

    .ia-input-row {
        gap: 6px;
    }

    .ia-voice-btn {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
}
</style>



<!-- ────────────────────────────────────────────────────────────────────
     JAVASCRIPT — Gestion des états et interactions
     ──────────────────────────────────────────────────────────────────── -->
<script>
// ─────────────────────────────────────────────────────────────────
// VARIABLES GLOBALES
// ─────────────────────────────────────────────────────────────────

const iaOrb = document.getElementById('ia-orb');
const iaChatWindow = document.getElementById('ia-chat-window');
const iaUserInput = document.getElementById('ia-user-input');
const iaChatContent = document.getElementById('ia-chat-content');
const iaSuggestions = document.getElementById('ia-suggestions');
const iaStatusText = document.getElementById('ia-status-text');
const iaPhotoInput = document.getElementById('ia-photo-input');
const iaVoiceBtn = document.getElementById('ia-voice-btn');

// État actuel de l'IA
let iaCurrentState = 'inactive';

// ─────────────────────────────────────────────────────────────────
// GESTION DES ÉTATS
// ─────────────────────────────────────────────────────────────────

/**
 * Change l'état visuel de l'orbe IA
 * @param {string} state - État : inactive, listening, thinking, acting, responding
 */
function iaSetState(state) {
    iaCurrentState = state;
    
    // Retirer tous les états
    iaOrb.classList.remove('ia-state-inactive', 'ia-state-listening', 'ia-state-thinking', 'ia-state-acting', 'ia-state-responding');
    
    // Ajouter le nouvel état
    iaOrb.classList.add(`ia-state-${state}`);
    
    // Mettre à jour le badge
    const stateTexts = {
        'inactive': 'Ready',
        'listening': 'Listening...',
        'thinking': 'Thinking...',
        'acting': 'Processing...',
        'responding': 'Ready'
    };
    
    if (iaStatusText) {
        iaStatusText.textContent = stateTexts[state] || 'Ready';
    }
    
    console.log(`IA State: ${state}`);
}

// ─────────────────────────────────────────────────────────────────
// CONTRÔLE DU CHAT
// ─────────────────────────────────────────────────────────────────

function iaToggleChat() {
    const isOpen = iaChatWindow.style.display === 'block';
    iaChatWindow.style.display = isOpen ? 'none' : 'block';
    iaChatWindow.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
    
    if (!isOpen && iaUserInput) {
        iaUserInput.focus();
    }
}

// ─────────────────────────────────────────────────────────────────
// ENVOI DE MESSAGES
// ─────────────────────────────────────────────────────────────────

async function iaSendMessage(imageBase64 = null) {
    const text = iaUserInput?.value?.trim();
    if (!text && !imageBase64) return;
    
    // Ajouter le message de l'utilisateur
    if (text) {
        iaAddMessage('user', text);
        iaUserInput.value = '';
    } else {
        iaAddMessage('user', '📷 Photo envoyée...');
    }
    
    // État ÉCOUTE → RÉFLEXION
    iaSetState('thinking');
    
    // Masquer les suggestions
    if (iaSuggestions) {
        iaSuggestions.style.display = 'none';
    }
    
    try {
        // État ACTION (traitement)
        iaSetState('acting');
        
        // Appel API
        const res = await fetch('/coiffons/ia_controlleur.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text, image: imageBase64 })
        });
        
        const data = await res.json();
        
        // État RÉPONSE
        iaSetState('responding');
        
        if (data.status === 'success') {
            const htmlReply = iaMarkdownToHtml(data.reply);
            iaAddMessage('ia', htmlReply);
            iaSpeak(data.reply);
        } else {
            iaAddMessage('ia-error', data.reply || 'Erreur de connexion.');
        }
        
    } catch (error) {
        console.error('Erreur IA:', error);
        iaAddMessage('ia-error', 'Impossible de joindre le serveur.');
    } finally {
        // Retour à INACTIF
        setTimeout(() => iaSetState('inactive'), 1500);
    }
    
    // Scroller vers le bas
    iaChatContent.scrollTop = iaChatContent.scrollHeight;
}

/**
 * Ajoute un message au chat
 * @param {string} type - 'user', 'ia', 'ia-error'
 * @param {string} content - Contenu du message
 */
function iaAddMessage(type, content) {
    const msgEl = document.createElement('div');
    msgEl.className = `ia-message ia-message-${type}`;
    msgEl.innerHTML = content;
    iaChatContent.appendChild(msgEl);
    iaChatContent.scrollTop = iaChatContent.scrollHeight;
}

/**
 * Envoie une suggestion rapide
 */
function iaSendQuick(text) {
    if (iaUserInput) {
        iaUserInput.value = text;
        iaSendMessage();
    }
}

// ─────────────────────────────────────────────────────────────────
// MARKDOWN → HTML
// ─────────────────────────────────────────────────────────────────

function iaMarkdownToHtml(text) {
    return text
        // Liens [texte](url)
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" style="color:var(--ia-primary);text-decoration:underline;" target="_blank">$1</a>')
        // Gras **texte**
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        // Italique *texte*
        .replace(/\*([^*]+)\*/g, '<em>$1</em>')
        // Tirets de liste
        .replace(/^- (.+)/gm, '• $1')
        // Sauts de ligne
        .replace(/\n/g, '<br>');
}

// ─────────────────────────────────────────────────────────────────
// RECONNAISSANCE VOCALE
// ─────────────────────────────────────────────────────────────────

if (('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) && iaVoiceBtn) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    recognition.lang = 'fr-FR';
    recognition.continuous = false;
    recognition.interimResults = false;
    
    iaVoiceBtn.addEventListener('click', () => {
        iaSetState('listening');
        recognition.start();
        iaVoiceBtn.style.opacity = '0.5';
    });
    
    recognition.addEventListener('result', (event) => {
        const transcript = event.results[0][0].transcript;
        if (iaUserInput) {
            iaUserInput.value = transcript;
            iaSendMessage();
        }
    });
    
    recognition.addEventListener('end', () => {
        iaVoiceBtn.style.opacity = '1';
        iaSetState('inactive');
    });
    
    recognition.addEventListener('error', (event) => {
        console.error('Erreur reconnaissance vocale:', event.error);
        iaVoiceBtn.style.opacity = '1';
        iaSetState('inactive');
    });
}

// ─────────────────────────────────────────────────────────────────
// SYNTHÈSE VOCALE
// ─────────────────────────────────────────────────────────────────

function iaSpeak(texte) {
    if (!('speechSynthesis' in window)) return;
    
    // Nettoyer le texte des markdown
    const cleanText = texte.replace(/[*#_\[\]()]/g, '');
    
    const utterance = new SpeechSynthesisUtterance(cleanText);
    utterance.lang = 'fr-FR';
    utterance.rate = 0.95;
    utterance.pitch = 1;
    
    speechSynthesis.speak(utterance);
}

// ─────────────────────────────────────────────────────────────────
// UPLOAD DE PHOTO
// ─────────────────────────────────────────────────────────────────

if (iaPhotoInput) {
    iaPhotoInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (event) => {
            iaSendMessage(event.target.result);
            iaPhotoInput.value = '';
        };
        reader.readAsDataURL(file);
    });
}

// ─────────────────────────────────────────────────────────────────
// ÉVÉNEMENTS
// ─────────────────────────────────────────────────────────────────

// Clique sur l'orbe
iaOrb?.addEventListener('click', iaToggleChat);

// Touche Entrée dans l'input
iaUserInput?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        iaSendMessage();
    }
});

// Clavier : Entrée sur l'orbe
iaOrb?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        iaToggleChat();
    }
});

// ─────────────────────────────────────────────────────────────────
// INITIALISATION
// ─────────────────────────────────────────────────────────────────

// État initial = INACTIF
iaSetState('inactive');

console.log('✨ IA Assistant initialized');
</script>
