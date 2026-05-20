<?php
// 1. TOUT EN HAUT : Sécurité et Session (AVANT d'inclure quoi que ce soit)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Seul un coiffeur connecté peut accéder à cette page
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    echo "<script>window.location.href='connexion.php';</script>";
    exit();
}

// 2. Maintenant que la sécurité est OK, on inclut le visuel et la BDD
require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';

$coiffeur_id = $_SESSION['id_user']; 
$message = "";

// Ajout d'une prestation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_prestation'])) {
    $nom_style = htmlspecialchars($_POST['nom_style']);
    $prix = floatval($_POST['prix']);
    $description = htmlspecialchars($_POST['description']);
    $duree = intval($_POST['duree']); // AJOUT : Récupération de la durée choisie

    $photo_style_path = NULL;
    if (isset($_FILES['photo_style']) && $_FILES['photo_style']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $fileName = $_FILES['photo_style']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            $photo_style_path = 'uploads/prestations/' . uniqid('', true) . '.' . $fileExt;
            if (!file_exists('uploads/prestations')) {
                mkdir('uploads/prestations', 0777, true);
            }
            move_uploaded_file($_FILES['photo_style']['tmp_name'], $photo_style_path);
        }
    }

    // AJOUT : Insertion du champ 'duree' dans la requête SQL
    $stmt = $pdo->prepare("INSERT INTO prestations (id_coiffeur, nom_style, prix, photo_style, description, duree) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$coiffeur_id, $nom_style, $prix, $photo_style_path, $description, $duree])) {
        $message = "<div class='alert alert-success'>Prestation ajoutée avec succès !</div>";
    }
}

// Suppression d'une prestation
if (isset($_GET['supprimer'])) {
    $id_prestation = intval($_GET['supprimer']);
    // Vérification de sécurité pour s'assurer que l'utilisateur est le propriétaire du style
    $check = $pdo->prepare("SELECT * FROM prestations WHERE id_prestation = ? AND id_coiffeur = ?");
    $check->execute([$id_prestation, $coiffeur_id]);

    if ($check->rowCount() > 0) {
        $del = $pdo->prepare("DELETE FROM prestations WHERE id_prestation = ?");
        $del->execute([$id_prestation]);
        $message = "<div class='alert alert-warning'>Prestation supprimée.</div>";
    }
}

// Récupération des prestations du coiffeur
$stmt = $pdo->prepare("SELECT * FROM prestations WHERE id_coiffeur = ? ORDER BY id_prestation DESC");
$stmt->execute([$coiffeur_id]);
$prestations = $stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">GÉRER MON CATALOGUE</h2>

        <?php echo $message; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 shadow-lg h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <h4 class="text-warning mb-3">Ajouter un style</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="text-white small">Nom de la coiffure</label>
                            <input type="text" name="nom_style" class="form-control" placeholder="Ex: Tresses vanilles" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Prix (FCFA)</label>
                            <input type="number" name="prix" class="form-control" placeholder="Ex: 5000" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Durée estimée</label>
                            <select name="duree" class="form-select" required>
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 heure</option>
                                <option value="90">1h 30min</option>
                                <option value="120">2 heures</option>
                                <option value="150">2h 30min</option>
                                <option value="180">3 heures</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Image de la coiffure</label>
                            <input type="file" name="photo_style" class="form-control" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Ex: Mèches fournies, lavage inclus..."></textarea>
                        </div>
                        <button type="submit" name="ajouter_prestation" class="btn btn-gold w-100 py-2 fw-bold mt-2">
                            AJOUTER
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <h4 class="text-warning mb-3">Mes prestations actuelles</h4>
                <div class="row g-3">
                    <?php if (empty($prestations)): ?>
                        <div class="col-12 text-center text-secondary py-5">
                            Aucune prestation enregistrée pour le moment.
                        </div>
                    <?php else: ?>
                        <?php foreach ($prestations as $p): ?>
                            <div class="col-md-6">
                                <div class="card h-100 shadow-sm" style="background-color: #111; border: 1px solid #333; border-radius: 15px; overflow: hidden;">
                                    <?php if (!empty($p['photo_style']) && file_exists($p['photo_style'])): ?>
                                        <img src="<?php echo $p['photo_style']; ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Style">
                                    <?php else: ?>
                                        <div class="bg-secondary text-center py-5"><i class="bi bi-image" style="font-size: 2rem;"></i></div>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <h5 class="text-warning mb-2"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-success fw-bold"><?php echo number_format($p['prix'], 0, ',', ' '); ?> FCFA</span>
                                            <span class="badge bg-dark border border-warning text-warning">
                                                <i class="bi bi-clock"></i> <?php echo isset($p['duree']) ? $p['duree'] : '60'; ?> min
                                            </span>
                                        </div>
                                        
                                        <p class="text-light small mb-0"><?php echo htmlspecialchars($p['description']); ?></p>
                                    </div>

                                    <div class="card-footer bg-dark border-top border-secondary d-flex justify-content-between">
                                        <a href="gestion_catalogue.php?supprimer=<?php echo $p['id_prestation']; ?>" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce style ?');">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .form-control, .form-select {
        background-color: #fff;
        color: #000;
        border-radius: 8px;
    }

    .btn-gold {
        background-color: var(--gold);
        color: black;
        border: none;
        border-radius: 8px;
    }

    .btn-gold:hover {
        background-color: #c99b2c;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>