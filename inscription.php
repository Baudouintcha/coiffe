<?php 
include 'header.php'; 
require 'config.php'; 

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $sexe = $_POST['sexe']; // NOUVEAU : récupère 'homme' ou 'femme'
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; 
    $ville = htmlspecialchars($_POST['ville']);

    // Vérification si l'email existe déjà
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() > 0) {
        $message = "<div class='alert alert-danger text-center'>Cet email est déjà utilisé.</div>";
    } else {
        // Insertion incluant la colonne 'sexe'
        $ins = $pdo->prepare("INSERT INTO users (nom, prenom, sexe, email, telephone, password, role, ville) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($ins->execute([$nom, $prenom, $sexe, $email, $telephone, $password, $role, $ville])) {
            $message = "<div class='alert alert-success text-center'>Inscription réussie ! <a href='connexion.php' class='fw-bold text-dark'>Connectez-vous ici</a></div>";
        }
    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid var(--gold); border-radius: 20px;">
                    <h2 class="text-center text-warning mb-4" style="letter-spacing: 2px;">REJOINDRE L'AVENTURE</h2>
                    
                    <?php echo $message; ?>

                    <form method="POST">
                        <div class="row mb-4 text-center">
                            <label class="text-white mb-3 fw-bold">Vous êtes ?</label>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="roleClient" value="client" checked>
                                <label class="btn btn-outline-warning w-100 py-3" for="roleClient">
                                    <span class="d-block h4 mb-0">🙋‍♂️</span>
                                    <span>Client</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="roleCoiffeur" value="coiffeur">
                                <label class="btn btn-outline-warning w-100 py-3" for="roleCoiffeur">
                                    <span class="d-block h4 mb-0">✂️</span>
                                    <span>Coiffeur</span>
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="text-white small mb-1">Nom</label>
                                <input type="text" name="nom" class="form-control" placeholder="Dossou" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="text-white small mb-1">Prénom</label>
                                <input type="text" name="prenom" class="form-control" placeholder="Jean" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="text-white small mb-1">Sexe</label>
                                <select name="sexe" class="form-select" required>
                                    <option value="homme">Homme</option>
                                    <option value="femme">Femme</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-white small mb-1">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-white small mb-1">Téléphone (WhatsApp)</label>
                                <input type="text" name="telephone" class="form-control" placeholder="+229..." required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-white small mb-1">Ville</label>
                                <select name="ville" class="form-select">
                                    <option value="Cotonou">Cotonou</option>
                                    <option value="Abomey-Calavi">Abomey-Calavi</option>
                                    <option value="Porto-Novo">Porto-Novo</option>
                                    <option value="Parakou">Parakou</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="text-white small mb-1">Mot de passe</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-3 fs-5 fw-bold">
                            CRÉER MON COMPTE
                        </button>
                    </form>
                    
                    <p class="text-center mt-4 text-secondary small">
                        Déjà inscrit ? <a href="connexion.php" class="text-warning">Connectez-vous</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .form-control, .form-select {
        background-color: #fff !important;
        color: #000 !important;
        border: 2px solid transparent;
        border-radius: 8px;
    }
    .btn-check:checked + .btn-outline-warning {
        background-color: var(--gold) !important;
        color: #000 !important;
    }
    .btn-gold { background-color: var(--gold); color: black; border: none; }
    .btn-gold:hover { background-color: #f1c40f; }
</style>

<?php include 'footer.php'; ?>