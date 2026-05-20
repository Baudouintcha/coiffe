<?php
// 1. TOUT EN HAUT : Gestion des sessions et de la base de données (AVANT le HTML pour éviter l'erreur de Headers)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/security/config.php';


$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données communes
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $sexe = $_POST['sexe'];
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $ville = htmlspecialchars($_POST['ville']);
    $quartier = htmlspecialchars($_POST['quartier']);

    // Traitement du diplôme si le rôle est 'coiffeur'
    $diplome_path = NULL;
    if ($role == 'coiffeur' && isset($_FILES['diplome']) && $_FILES['diplome']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $fileName = $_FILES['diplome']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            $diplome_path = 'uploads/diplomes/' . uniqid('', true) . '.' . $fileExt;
            if (!file_exists('uploads/diplomes')) {
                mkdir('uploads/diplomes', 0777, true);
            }
            move_uploaded_file($_FILES['diplome']['tmp_name'], $diplome_path);
        }
    }

    // Traitement de la photo de profil
    $photo_profil_path = 'uploads/profil/default.png'; // Image par défaut
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $fileName = $_FILES['photo_profil']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            $photo_profil_path = 'uploads/profil/' . uniqid('', true) . '.' . $fileExt;
            if (!file_exists('uploads/profil')) {
                mkdir('uploads/profil', 0777, true);
            }
            move_uploaded_file($_FILES['photo_profil']['tmp_name'], $photo_profil_path);
        }
    }

    // Vérification si l'email existe déjà (Note: correction de la colonne 'id' en 'id_user' pour correspondre à ton projet)
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $message = "<div class='alert alert-danger text-center'>Cet email est déjà utilisé.</div>";
    } else {
        // Insertion incluant le champ diplôme et photo_profil
        $ins = $pdo->prepare("INSERT INTO users (nom, prenom, sexe, email, telephone, password, role, ville, quartier, diplome, photo_profil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($ins->execute([$nom, $prenom, $sexe, $email, $telephone, $password, $role, $ville, $quartier, $diplome_path, $photo_profil_path])) {
            
            // A. ON RÉCUPÈRE L'ID QUE LA BDD VIENT DE CRÉER POUR CET UTILISATEUR
            $id_nouvel_user = $pdo->lastInsertId();

            // B. ON LE CONNECTE AUTOMATIQUEMENT EN REMPLISSANT LA SESSION IMMÉDIATEMENT
            $_SESSION['id_user'] = $id_nouvel_user;
            $_SESSION['nom']     = $nom;
            $_SESSION['prenom']  = $prenom;
            $_SESSION['role']    = $role;

            // C. LA TOUR DE CONTRÔLE D'AIGUILLAGE SELON LE RÔLE
            if ($_SESSION['role'] === 'coiffeur') {
                // Si c'est un coiffeur, direction la gestion de son catalogue/agenda
                header("Location: gestion_catalogue.php");
                exit();
            } else {
                // Si c'est un client, retour automatique à l'accueil avec session active !
                header("Location: index.php");
                exit();
            }
        } else {
            $message = "<div class='alert alert-danger text-center'>Une erreur est survenue lors de l'enregistrement.</div>";
        }
    }
}

// 2. INCLUSION DU HEADER (Maintenant qu'aucune redirection PHP par header() ne peut être bloquée)
include 'header.php';
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid var(--gold); border-radius: 20px;">
                    <h2 class="text-center text-warning mb-4" style="letter-spacing: 2px;">REJOINDRE L'AVENTURE</h2>

                    <?php echo $message; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-4 text-center">
                            <label class="text-white mb-3 fw-bold">Vous êtes ?</label>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="roleClient" value="client" onclick="toggleDiplomeField()" checked>
                                <label class="btn btn-outline-warning w-100 py-3" for="roleClient">
                                    <span class="d-block h4 mb-0">🙋‍♂️</span>
                                    <span>Client</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="roleCoiffeur" value="coiffeur" onclick="toggleDiplomeField()">
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
                                <select name="ville" class="form-select" required>
                                    <option value="">Sélectionnez votre ville</option>
                                    <?php
                                    $villes_stmt = $pdo->query("SELECT nom_ville FROM villes ORDER BY nom_ville ASC");
                                    while ($v = $villes_stmt->fetch()) {
                                        echo "<option value='" . htmlspecialchars($v['nom_ville']) . "'>" . htmlspecialchars($v['nom_ville']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-white small mb-1">Quartier</label>
                                <input type="text" name="quartier" class="form-control" placeholder="Ex: Gbégamey, Godomey..." required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="text-white small mb-1">Photo de profil</label>
                                <input type="file" name="photo_profil" class="form-control" accept="image/*" required>
                            </div>
                        </div>

                        <div class="row" id="diplomeGroup" style="display: none;">
                            <div class="col-md-12 mb-3">
                                <label class="text-white small mb-1">Photo ou scan de votre diplôme / certification</label>
                                <input type="file" name="diplome" class="form-control" accept="image/*,application/pdf">
                            </div>
                        </div>

                        <div class="row">
    <div class="col-md-12 mb-4">
        <label class="text-white small mb-1">Mot de passe</label>
        <div class="input-group">
            <input type="password" name="password" id="passwordField" class="form-control" required>
            <button class="btn btn-outline-warning" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()">
                👁️
            </button>
        </div>
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

<script>
    function toggleDiplomeField() {
        var roleCoiffeur = document.getElementById('roleCoiffeur');
        var diplomeGroup = document.getElementById('diplomeGroup');

        if (roleCoiffeur.checked) {
            diplomeGroup.style.display = 'block';
        } else {
            diplomeGroup.style.display = 'none';
        }
    }
    function togglePasswordVisibility() {
    var passwordField = document.getElementById('passwordField');
    var toggleBtn = document.getElementById('togglePasswordBtn');

    // Si c'est en mode caché, on l'affiche
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleBtn.textContent = '🙈'; // Change l'icône quand le MDP est visible
    } else {
        // Sinon, on le remet en mode caché
        passwordField.type = 'password';
        toggleBtn.textContent = '👁️'; // Remet l'œil normal
    }
}
</script>

<style>
    .form-control,
    .form-select {
        background-color: #fff !important;
        color: #000 !important;
        border: 2px solid transparent;
        border-radius: 8px;
    }

    .btn-check:checked+.btn-outline-warning {
        background-color: var(--gold) !important;
        color: #000 !important;
    }

    .btn-gold {
        background-color: var(--gold);
        color: black;
        border: none;
    }

    .btn-gold:hover {
        background-color: #f1c40f;
    }
</style>

<?php include 'footer.php'; ?>