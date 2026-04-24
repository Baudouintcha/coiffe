<?php 
include 'header.php'; 

// Gestion dynamique du nom de l'IA selon le sexe
$nomIA = "Vanessa"; // Par défaut
if (isset($_SESSION['sexe']) && $_SESSION['sexe'] == 'femme') {
    $nomIA = "Daniel";
}
$role = $_SESSION['role'] ?? 'invite'; 
?>

<main style="background-color: #121212; min-height: 100vh; padding-bottom: 50px;">

    <div class="container py-5 text-center">
        <h2 class="text-warning display-5 fw-bold">CATALOGUE</h2>
        <p class="text-secondary">
            <?php 
                if($role == 'coiffeur') echo "Gérez vos styles et vos tarifs pour attirer plus de clients.";
                else echo "Choisissez votre style, nous trouvons l'expert idéal pour vous.";
            ?>
        </p>
    </div>

    <?php if($role == 'coiffeur'): ?>
    <div class="container mb-5">
        <div class="card p-4 border-warning bg-dark shadow-lg mb-5">
            <h4 class="text-warning mb-3">➕ Ajouter une nouvelle coiffure</h4>
            <form action="traitement_catalogue.php" method="POST" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-4">
                    <input type="file" name="photo" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="nom_style" class="form-control" placeholder="Nom du style" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="prix" class="form-control" placeholder="Prix (FCFA)" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-gold w-100">Publier</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if($role != 'coiffeur'): ?>
    <div class="container mb-5">
        <div class="p-4 rounded-4 border border-warning text-center" style="background: linear-gradient(45deg, #000, #1a1a1a);">
            <h3 class="text-warning">✨ Analyse par IA avec <?php echo $nomIA; ?></h3>
            <p class="text-light">Besoin d'aide ? Discutez par écrit ou par **voix** avec <?php echo $nomIA; ?> pour trouver votre style.</p>
            <button class="btn btn-outline-warning px-4" onclick="toggleChat()">Discuter avec <?php echo $nomIA; ?></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
            <div class="col">
                <div class="card h-100 shadow-sm" style="background-color: #1e1e1e; border: 1px solid #333; border-radius: 15px; overflow: hidden;">
                    <img src="images/ara.jpg" class="card-img-top" style="height: 280px; object-fit: cover;">
                    <div class="card-body text-center">
                        <h4 class="text-white">SISCO</h4>
                        <span class="text-warning fw-bold d-block mb-3">À partir de 2.500 FCFA</span>
                        
                        <?php if($role == 'coiffeur'): ?>
                            <button class="btn btn-outline-danger btn-sm w-100">Supprimer de mon profil</button>
                        <?php else: ?>
                            <a href="choisir_coiffeur.php?style=sisco" class="btn btn-gold w-100">RÉSERVER</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
    </div>
</main>

<?php include 'footer.php'; ?>