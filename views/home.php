<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coiffe Chez Toi </title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="hero-slider">
        <?php
        // Chargement dynamique des images
        $images = glob("imgid/*.{jpg,jpeg,png,webp}", GLOB_BRACE);
        foreach ($images as $index => $image) {
            echo '<div class="slide ' . ($index === 0 ? 'active' : '') . '" style="background-image: url(\'' . $image . '\');"></div>';
        }
        ?>
        <div class="overlay"></div>

        <div class="hero-content">
    <div class="lang-selector" style="position: absolute; top: 20px; right: 20px; cursor: pointer;">
        🌐 FR
    </div>

    <h1 class="typing-effect">COIFFE CHEZ TOI</h1>
    <p>Des coupes modernes, un style unique et une expérience premium.</p>
    
    <div class="role-selection">
        <h3>Qui êtes-vous ?</h3>
        <div class="cta-buttons">
            <a href="?page=register&role=client" class="btn-gold">Je suis Client</a>
            <a href="?page=register&role=coiffeur" class="btn-gold">Je suis Coiffeur</a>
        </div>
    </div>

    <div class="login-section" style="margin-top: 30px;">
        <p style="font-size: 0.9rem; margin-bottom: 10px; opacity: 0.8;">Vous avez déjà un compte ?</p>
        <a href="?page=login" class="btn-transparent">Se connecter</a>
    </div>
</div>

    <script src="js/script.js"></script>
</body>
</html>