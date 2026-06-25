<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coiffe Chez Toi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="hero-slider">
        <?php
        $images = glob("imgid/*.{jpg,jpeg,png,webp}", GLOB_BRACE);
        foreach ($images as $index => $image) {
            echo '<div class="slide ' . ($index === 0 ? 'active' : '') . '" style="background-image: url(\'' . $image . '\');"></div>';
        }
        ?>
        <div class="overlay"></div>

        <div class="hero-content">
            <div class="lang-selector" onclick="changeLanguage()">
                🌐 <span id="current-lang">FR</span>
            </div>

            <h1 class="typing-effect">COIFFE CHEZ TOI</h1>
            <p class="hero-subtitle">
                Coiffure à domicile premium. Prise de rendez-vous simple et paiement sécurisé.
            </p>
            
            <div class="role-selection">
                <h3>Qui êtes-vous ?</h3>
                <div class="cta-buttons">
                    <button class="btn-role" data-role="client">Je suis Client</button>
                    <button class="btn-role" data-role="coiffeur">Je suis Coiffeur</button>
                </div>
                
                <a id="btn-register" href="#" class="btn-gold" style="display: none; margin-top: 20px;">
                    S'inscrire comme <span id="role-text">...</span>
                </a>
            </div>

            <div class="login-section">
                <p>Déjà membre ?</p>
                <a href="?page=login" class="btn-link">Se connecter</a>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>