<?php
/**
 * views/components/ia_assistant.php — IAAssistant (Bulle + Chat window)
 * Design System v2.0 | CL §32
 *
 * Ce composant est extrait de layout/footer.php.
 * Il gère l'avatar contextuel selon le sexe de l'utilisateur connecté.
 * La logique PHP est minimale et liée à l'UX uniquement.
 */

$nomIA     = "Vic";
$avatarIcon = "🤖";

if (isset($_SESSION['sexe'])) {
    if ($_SESSION['sexe'] === 'femme') {
        $nomIA      = "Daniel";
        $avatarIcon = "👨‍💼";
    } else {
        $nomIA      = "Vanessa";
        $avatarIcon = "👩‍💼";
    }
}
?>

<!-- ── AI BUBBLE — position fixed bottom-right ── -->
<div id="ai-bubble"
     onclick="toggleChat()"
     role="button"
     aria-label="Ouvrir l'assistant IA <?= htmlspecialchars($nomIA) ?>"
     tabindex="0"
     onkeypress="if(event.key==='Enter')toggleChat()">
    <span aria-hidden="true"><?= $avatarIcon ?></span>
</div>

<!-- ── CHAT WINDOW ── -->
<div id="chat-window" role="dialog" aria-label="Assistant IA" aria-hidden="true">
    <div class="chat-header">
        <div class="chat-header-info">
            <span aria-hidden="true"><?= $avatarIcon ?></span>
            <div>
                <strong><?= htmlspecialchars($nomIA) ?></strong>
                <small class="chat-online">● En ligne</small>
            </div>
        </div>
        <button onclick="toggleChat()" class="chat-close-btn" aria-label="Fermer le chat">&times;</button>
    </div>

    <div id="chat-content" class="chat-content">
        <div class="chat-bubble chat-bubble--ia">
            Bonjour ! Je suis <strong><?= htmlspecialchars($nomIA) ?></strong>,
            votre assistant(e) personnel(le). Comment puis-je vous aider aujourd'hui ?
        </div>
    </div>

    <div class="chat-footer">
        <div class="chat-input-row">
            <input type="text"
                   id="user-input"
                   class="fc-dark"
                   placeholder="Posez une question..."
                   onkeypress="if(event.key==='Enter')sendMessage()"
                   aria-label="Message pour l'assistant IA">
            <button class="btn-gold btn-sm" id="btn-vocal" title="Parler" aria-label="Dicter un message">🎤</button>
        </div>
        <div class="chat-actions">
            <label for="ai-photo" class="btn-ghost btn-sm" style="cursor:pointer;">📷 Photo</label>
            <input type="file" id="ai-photo" hidden accept="image/*" aria-label="Envoyer une photo">
            <button class="btn-gold btn-sm" onclick="sendMessage()">Envoyer</button>
        </div>
    </div>
</div>

<style>
/* AIBubble + ChatWindow — CL §32, DS v2.0 | Glassmorphism + Inter */
#ai-bubble {
    position: fixed; bottom: 30px; right: 30px;
    width: 75px; height: 75px; border-radius: var(--radius-circle);
    background: rgba(20, 17, 8, 0.85);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    border: 2px solid var(--gold);
    box-shadow: var(--shadow-gold), 0 8px 32px rgba(0,0,0,0.6);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: var(--z-ai); transition: var(--transition-base);
    font-family: 'Inter', sans-serif;
}
#ai-bubble span    { font-size: 35px; filter: drop-shadow(0 0 6px rgba(212,175,55,0.6)); }
#ai-bubble:hover   { transform: scale(1.1) rotate(5deg); box-shadow: 0 0 0 4px rgba(212,175,55,0.25), 0 8px 32px rgba(0,0,0,0.7); }

#chat-window {
    display: none; position: fixed; bottom: 120px; right: 30px;
    width: 360px;
    background: rgba(12, 10, 6, 0.92);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1.5px solid rgba(212,175,55,0.55);
    border-radius: 20px;
    z-index: var(--z-ai-window);
    box-shadow: 0 20px 60px rgba(0,0,0,0.85), 0 0 0 1px rgba(212,175,55,0.1);
    font-family: 'Inter', sans-serif;
    overflow: hidden;
    animation: chatSlideIn 0.2s ease;
}
@keyframes chatSlideIn {
    from { opacity: 0; transform: translateY(12px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1);    }
}
.chat-header {
    padding: 14px 18px;
    border-bottom: 1px solid rgba(212,175,55,0.2);
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(20, 17, 8, 0.7);
}
.chat-header-info       { display: flex; align-items: center; gap: 10px; font-size: 1.5rem; }
.chat-header-info strong{ color: var(--gold); display: block; font-size: 0.9rem; line-height: 1.2; letter-spacing: 0.02em; }
.chat-online            { color: #22c55e; font-size: 0.68rem; display: block; letter-spacing: 0.04em; }
.chat-close-btn {
    background: none; border: none;
    color: rgba(255,255,255,0.45); font-size: 1.5rem; cursor: pointer;
    line-height: 1; padding: 2px 6px; border-radius: 6px;
    transition: color 0.15s, background 0.15s;
}
.chat-close-btn:hover { color: #fff; background: rgba(255,255,255,0.08); }

.chat-content {
    height: 340px; overflow-y: auto;
    padding: 18px 16px;
    background: transparent;
    scroll-behavior: smooth;
}
.chat-content::-webkit-scrollbar       { width: 3px; }
.chat-content::-webkit-scrollbar-thumb { background: rgba(212,175,55,0.5); border-radius: 10px; }

.chat-bubble--ia {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.18);
    border-radius: 14px; border-top-left-radius: 2px;
    padding: 10px 14px; color: rgba(255,255,255,0.9);
    font-size: 0.88rem; line-height: 1.55;
    margin-bottom: 12px; max-width: 88%;
}
.chat-bubble--user {
    background: linear-gradient(135deg, var(--gold) 0%, #b8933a 100%);
    color: #000; border-radius: 14px; border-top-right-radius: 2px;
    padding: 9px 13px; font-size: 0.85rem; font-weight: 600;
    text-align: right; margin-bottom: 12px;
    margin-left: auto; max-width: 85%;
}

/* Typing dots animation */
.typing-dot {
    display: inline-block;
    animation: typingBounce 1.2s infinite ease-in-out;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingBounce {
    0%, 80%, 100% { opacity: 0.3; transform: translateY(0); }
    40%           { opacity: 1;   transform: translateY(-3px); }
}

.chat-footer {
    padding: 12px 16px;
    border-top: 1px solid rgba(212,175,55,0.15);
    background: rgba(20, 17, 8, 0.6);
}
.chat-input-row    { display: flex; gap: 6px; margin-bottom: 8px; }
.chat-input-row .fc-dark {
    flex: 1;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(212,175,55,0.25);
    border-radius: 10px;
    color: #fff; padding: 8px 12px; font-size: 0.85rem;
    font-family: 'Inter', sans-serif;
    outline: none; transition: border-color 0.2s;
}
.chat-input-row .fc-dark:focus { border-color: rgba(212,175,55,0.6); }
.chat-input-row .fc-dark::placeholder { color: rgba(255,255,255,0.3); }
.chat-actions      { display: flex; gap: 8px; }
.chat-actions label{ flex: 1; text-align: center; }

@media (max-width: 576px) {
    #ai-bubble   { width: 60px; height: 60px; bottom: 20px; right: 20px; }
    #ai-bubble span { font-size: 28px; }
    #chat-window { width: calc(100% - 32px); bottom: 92px; right: 16px; left: 16px; border-radius: 16px; }
    .chat-content { height: 300px; }
}
</style>

<script>
function toggleChat() {
    const win = document.getElementById('chat-window');
    const isOpen = win.style.display === 'block';
    win.style.display = isOpen ? 'none' : 'block';
    win.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
}

// Reconnaissance vocale
const btnVocal  = document.getElementById('btn-vocal');
const userInput = document.getElementById('user-input');

if (('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) && btnVocal) {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SR();
    recognition.lang = 'fr-FR';
    btnVocal.onclick = () => { recognition.start(); btnVocal.style.background = '#ff4444'; };
    recognition.onresult = e => { userInput.value = e.results[0][0].transcript; btnVocal.style.background = ''; sendMessage(); };
    recognition.onerror  = () => { btnVocal.style.background = ''; };
}

// Synthèse vocale
function parler(texte) {
    if (!('speechSynthesis' in window)) return;
    const msg = new SpeechSynthesisUtterance();
    msg.text = texte.replace(/[*#_]/g, '');
    msg.lang = 'fr-FR';
    const v = speechSynthesis.getVoices().find(v => v.name.includes('Google français') || v.name.includes('Julie'));
    if (v) msg.voice = v;
    speechSynthesis.speak(msg);
}

// Envoi de message
async function sendMessage(imageBase64 = null) {
    const text = userInput?.value?.trim();
    if (!text && !imageBase64) return;
    const content = document.getElementById('chat-content');
    if (text) {
        content.innerHTML += `<div class="chat-bubble chat-bubble--user">${text}</div>`;
        userInput.value = '';
    } else {
        content.innerHTML += `<div class="chat-bubble chat-bubble--user">📷 Image envoyée...</div>`;
    }
    const tempId = 'typing-' + Date.now();
    content.innerHTML += `<div id="${tempId}" class="chat-bubble chat-bubble--ia">Réflexion<span class="typing-dot">.</span><span class="typing-dot">.</span><span class="typing-dot">.</span></div>`;
    content.scrollTop = content.scrollHeight;
    try {
        const res  = await fetch('/coiffons/ia_controlleur.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({message:text, image:imageBase64}) });
        const data = await res.json();
        document.getElementById(tempId)?.remove();
        if (data.status === 'success') {
            content.innerHTML += `<div class="chat-bubble chat-bubble--ia">${data.reply}</div>`;
            parler(data.reply);
        } else {
            content.innerHTML += `<div class="chat-bubble chat-bubble--ia" style="color:var(--danger-text)">Erreur de connexion à l'assistant.</div>`;
        }
    } catch {
        document.getElementById(tempId)?.remove();
        content.innerHTML += `<div class="chat-bubble chat-bubble--ia" style="color:var(--danger-text)">Impossible de joindre le serveur.</div>`;
    }
    content.scrollTop = content.scrollHeight;
}

// Upload photo
document.getElementById('ai-photo')?.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => sendMessage(ev.target.result);
    reader.readAsDataURL(file);
});
</script>
