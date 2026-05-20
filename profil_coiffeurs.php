<?php
require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';
// Sécurité : réservé aux coiffeurs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: connexion.php');
    exit();
}

$id_coiffeur = $_SESSION['user_id'];
$message = "";

// Mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['maj_profil'])) {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $ville = htmlspecialchars($_POST['ville']);
    $quartier = htmlspecialchars($_POST['quartier']);

    $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, ville = ?, quartier = ? WHERE id = ?");
    if ($stmt->execute([$nom, $prenom, $email, $telephone, $ville, $quartier, $id_coiffeur])) {
        $_SESSION['nom'] = $nom; // Met à jour la session
        $message = "<div class='alert alert-success'>Profil mis à jour avec succès.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Une erreur est survenue.</div>";
    }
}

// Récupération des informations actuelles du coiffeur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_coiffeur]);
$coiffeur = $stmt->fetch();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4" style="max-width: 600px;">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">MON PROFIL</h2>

        <?php echo $message; ?>

        <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-white small mb-1">Nom</label>
                        <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($coiffeur['nom']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="text-white small mb-1">Prénom</label>
                        <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($coiffeur['prenom']); ?>" required>
                    </div>
                    <div class="col-12 mt-3">
                        <label class="text-white small mb-1">Adresse e-mail</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($coiffeur['email']); ?>" required>
                    </div>
                    <div class="col-12 mt-3">
                        <label class="text-white small mb-1">Téléphone / WhatsApp</label>
                        <input type="text" name="telephone" class="form-control" value="<?php echo htmlspecialchars($coiffeur['telephone']); ?>" placeholder="Ex: +22967000000" required>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="text-white small mb-1">Ville</label>
                        <input type="text" name="ville" class="form-control" value="<?php echo htmlspecialchars($coiffeur['ville']); ?>" required>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="text-white small mb-1">Quartier</label>
                        <input type="text" name="quartier" class="form-control" value="<?php echo htmlspecialchars($coiffeur['quartier']); ?>" required>
                    </div>
                </div>

                <button type="submit" name="maj_profil" class="btn btn-gold w-100 py-2 fw-bold mt-4">
                    ENREGISTRER LES MODIFICATIONS
                </button>
                
                <a href="deconnexion.php" class="btn btn-outline-danger btn-sm w-100 mt-3">
                    <i class="bi bi-power"></i> Déconnexion
                </a>
            </form>
        </div>
    </div>
</section>

<style>
    .form-control { background-color: #fff; color: #000; border-radius: 8px; }
    .btn-gold { background-color: var(--gold); color: black; border: none; border-radius: 8px; }
    .btn-gold:hover { background-color: #c99b2c; }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>