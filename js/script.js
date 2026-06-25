document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.slide');
    let currentIndex = 0;

    // Fonction pour changer de slide
    function changeSlide() {
        // Enlève la classe active de l'image actuelle
        slides[currentIndex].classList.remove('active');
        
        // Calcule l'index suivant
        currentIndex = (currentIndex + 1) % slides.length;
        
        // Ajoute la classe active à la nouvelle image
        slides[currentIndex].classList.add('active');
    }

    // Défilement automatique toutes les 4 secondes
    setInterval(changeSlide, 4000);
});
document.querySelectorAll('.btn-role').forEach(button => {
    button.addEventListener('click', function() {
        const role = this.getAttribute('data-role');
        const btnRegister = document.getElementById('btn-register');
        const roleText = document.getElementById('role-text');

        // Mise à jour du lien et du texte
        btnRegister.href = '?page=register&role=' + role;
        roleText.innerText = role.toUpperCase();
        
        // Affichage fluide
        btnRegister.style.display = 'inline-block';
        btnRegister.style.opacity = '1';
    });
});

function changeLanguage() {
    const current = document.getElementById('current-lang').innerText;
    const newLang = (current === 'FR') ? 'EN' : 'FR';
    
    // On appelle une petite route PHP pour changer la session
    window.location.href = '?lang=' + newLang;
}