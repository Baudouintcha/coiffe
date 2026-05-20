<?php 
// Détermination de l'avatar et du nom selon le sexe stocké en session
$nomIA = "Vanessa"; 
$avatarIcon = "👩‍💼"; // Avatar féminin par défaut
if (isset($_SESSION['sexe']) && $_SESSION['sexe'] == 'femme') { 
    $nomIA = "Daniel"; 
    $avatarIcon = "👨‍💼"; // Avatar masculin si l'utilisateur est une femme
}
?>

<footer class="py-5 mt-5" style="background-color: #000; border-top: 2px solid #D4AF37;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 mb-4">
                <h5 class="text-warning mb-3">Coiffe_Chez_Toi</h5>
                <p class="text-secondary small">
                    L'élégance à domicile au Bénin. Nous connectons les meilleurs talents avec ceux qui exigent l'excellence.
                </p>
            </div>

            <div class="col-md-4 mb-4 text-center">
                <a href="javascript:window.scrollTo({top: 0, behavior: 'smooth'});" class="text-decoration-none hover-gold" style="color: #D4AF37;">
                    <div class="fs-2">▲</div>
                    <span class="small fw-bold text-uppercase" style="letter-spacing: 2px;">Back to Top</span>
                </a>
            </div>

            <div class="col-md-4 mb-4 text-md-end">
                <h5 class="text-warning mb-3">Contactez-nous</h5>
                <div class="d-flex justify-content-md-end gap-3 fs-4 mb-2">
                    <a href="#" class="text-secondary hover-gold"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-secondary hover-gold"><i class="bi bi-whatsapp"></i></a>
                </div>
                <p class="text-secondary small">contact@coiffecheztoi.bj</p>
            </div>
        </div>
        
        <hr class="border-secondary">
        
        <div class="text-center">
            <p class="text-secondary mb-0 small">
                &copy; <?php echo date('Y'); ?> **Coiffe_Chez_Toi** | Premium Barbiers & Coiffeurs
            </p>
        </div>
    </div>
</footer>

<div id="ai-bubble" onclick="toggleChat()" style="position:fixed; bottom:30px; right:30px; width:75px; height:75px; background: #1a1a1a; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow: 0 0 20px rgba(212,175,55,0.6); z-index:9998; border: 2px solid #D4AF37; transition: 0.4s;">
    <span style="font-size: 40px; filter: drop-shadow(0 0 5px rgba(212,175,55,0.5));">
        <?php echo $avatarIcon; ?>
    </span>
</div>

<div id="chat-window" style="display:none; position:fixed; bottom:120px; right:30px; width:350px; background:#111; border:2px solid #D4AF37; border-radius:20px; z-index:9999; box-shadow: 0 10px 40px rgba(0,0,0,0.9);">
    <div class="p-3 border-bottom border-warning d-flex justify-content-between align-items-center bg-dark" style="border-radius: 20px 20px 0 0;">
        <div class="d-flex align-items-center">
            <span class="me-2" style="font-size: 24px;"><?php echo $avatarIcon; ?></span>
            <div>
                <strong class="text-warning d-block" style="line-height: 1;"><?php echo $nomIA; ?></strong>
                <small class="text-success" style="font-size: 0.7rem;">● En ligne</small>
            </div>
        </div>
        <button onclick="toggleChat()" class="btn-close btn-close-white btn-sm"></button>
    </div>

    <div id="chat-content" style="height:350px; overflow-y:auto; padding:20px; background:#0a0a0a; scroll-behavior: smooth;">
        <div class="mb-3 text-start">
            <div class="p-3 rounded-4 bg-dark text-white shadow-sm border border-secondary" style="border-top-left-radius: 0; font-size: 0.9rem;">
                Bonjour ! Je suis **<?php echo $nomIA; ?>**, votre assistant(e) personnel(le). 
                Comment puis-je vous aider aujourd'hui ?
            </div>
        </div>
    </div>

    <div class="p-3 border-top border-secondary bg-dark" style="border-radius: 0 0 20px 20px;">
        <div class="input-group mb-2 shadow-sm">
            <input type="text" id="user-input" class="form-control bg-black text-white border-secondary shadow-none" placeholder="Posez une question...">
            <button class="btn btn-warning fw-bold" id="btn-vocal" title="Parler">🎤</button>
        </div>
        <div class="d-flex gap-2">
            <label for="ai-photo" class="btn btn-sm btn-outline-secondary flex-grow-1" style="font-size: 0.8rem;">📷 Envoyer une photo</label>
            <input type="file" id="ai-photo" hidden>
            <button class="btn btn-sm btn-gold px-4 text-uppercase" onclick="sendMessage()" style="font-size: 0.8rem;">Envoyer</button>
        </div>
    </div>
</div>

<style>
    .btn-gold { background-color: #D4AF37; color: #000; font-weight: bold; border: none; }
    .hover-gold:hover { color: #D4AF37 !important; transition: 0.3s; transform: translateY(-3px); }
    #ai-bubble:hover { transform: scale(1.1) rotate(5deg); box-shadow: 0 0 30px rgba(212,175,55,0.8); }
    #chat-content::-webkit-scrollbar { width: 4px; }
    #chat-content::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleChat() {
    const win = document.getElementById('chat-window');
    win.style.display = (win.style.display === 'none') ? 'block' : 'none';
}

// LOGIQUE VOCALE
const btnVocal = document.getElementById('btn-vocal');
const userInput = document.getElementById('user-input');

if ('webkitSpeechRecognition' in window) {
    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'fr-FR';
    
    btnVocal.onclick = () => {
        recognition.start();
        btnVocal.style.background = "#ff4444";
    };

    recognition.onresult = (event) => {
        userInput.value = event.results[0][0].transcript;
        btnVocal.style.background = "#D4AF37";
        sendMessage();
    };
}

function parler(texte) {
    const msg = new SpeechSynthesisUtterance();
    msg.text = texte;
    msg.lang = 'fr-FR';
    window.speechSynthesis.speak(msg);
}

function sendMessage() {
    const text = userInput.value;
    if(!text.trim()) return;
    const content = document.getElementById('chat-content');
    content.innerHTML += `<div class="text-end mb-3"><div class="d-inline-block p-2 px-3 rounded-4 bg-warning text-dark small fw-bold" style="border-top-right-radius:0;">${text}</div></div>`;
    userInput.value = "";
    
    setTimeout(() => {
        const rep = "C'est noté ! Je recherche les meilleures options pour vous.";
        content.innerHTML += `<div class="mb-3 text-start"><div class="p-2 px-3 rounded-4 bg-dark border border-warning text-white small" style="border-top-left-radius:0;">${rep}</div></div>`;
        parler(rep);
        content.scrollTop = content.scrollHeight;
    }, 800);
}
</script>