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