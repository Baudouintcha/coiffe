<?php
// 1. TOUT EN HAUT : Gestion des sessions et de la base de données
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Vérification stricte que TOUS les champs communs sont remplis (Tolérance Zéro)
    if (
        empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['sexe']) || 
        empty($_POST['email']) || empty($_POST['telephone']) || empty($_POST['password']) || 
        empty($_POST['role']) || empty($_POST['ville']) || empty($_POST['id_quartier']) ||
        !isset($_FILES['photo_profil']) || $_FILES['photo_profil']['error'] !== 0
    ) {
        $message = "<div class='alert alert-danger text-center'>Champs obligatoires manquants. Veuillez remplir tout le formulaire.</div>";
    } else {
        
        // Récupération et sécurisation des données
        $nom = trim(htmlspecialchars($_POST['nom']));
        $prenom = trim(htmlspecialchars($_POST['prenom']));
        $sexe = $_POST['sexe'];
        $email = trim(htmlspecialchars($_POST['email']));
        $telephone = trim(htmlspecialchars($_POST['telephone']));
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $id_ville = intval($_POST['ville']);
        $id_quartier = intval($_POST['id_quartier']);

        // Anti-Pseudo : On interdit les chiffres et caractères spéciaux dans le Nom et Prénom
        // Permet d'éviter les "Coiffeur99", "Boss_du_229", etc.
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $nom) || !preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $prenom)) {
            $message = "<div class='alert alert-danger text-center'>Veuillez entrer un vrai nom et prénom (lettres uniquement).</div>";
        } 
        // Si le rôle est coiffeur, le diplôme est STRICTEMENT obligatoire
        elseif ($role == 'coiffeur' && (!isset($_FILES['diplome']) || $_FILES['diplome']['error'] !== 0)) {
            $message = "<div class='alert alert-danger text-center'>Le téléchargement du diplôme est obligatoire pour les coiffeurs.</div>";
        } else {
            
            // Traitement du diplôme si coiffeur
            $diplome_path = NULL;
            if ($role == 'coiffeur') {
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                $fileName = $_FILES['diplome']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (in_array($fileExt, $allowed)) {
                    $diplome_path = 'uploads/diplomes/' . uniqid('', true) . '.' . $fileExt;
                    if (!file_exists('uploads/diplomes')) {
                        mkdir('uploads/diplomes', 0777, true);
                    }
                    move_uploaded_file($_FILES['diplome']['tmp_name'], $diplome_path);
                } else {
                    $message = "<div class='alert alert-danger text-center'>Format de diplôme invalide (Uniquement JPG, PNG, PDF).</div>";
                }
            }

            // Si aucune erreur sur le diplôme, on gère la photo de profil
            if (empty($message)) {
                $allowedPhoto = ['jpg', 'jpeg', 'png'];
                $photoName = $_FILES['photo_profil']['name'];
                $photoExt = strtolower(pathinfo($photoName, PATHINFO_EXTENSION));

                if (in_array($photoExt, $allowedPhoto)) {
                    $photo_profil_path = 'uploads/profil/' . uniqid('', true) . '.' . $photoExt;
                    if (!file_exists('uploads/profil')) {
                        mkdir('uploads/profil', 0777, true);
                    }
                    move_uploaded_file($_FILES['photo_profil']['tmp_name'], $photo_profil_path);
                } else {
                    $message = "<div class='alert alert-danger text-center'>Format de photo de profil invalide (JPG, PNG uniquement).</div>";
                }
            }

            // Si tout est au top, on vérifie l'email unique et on insère
            if (empty($message)) {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);

                if ($check->rowCount() > 0) {
                    $message = "<div class='alert alert-danger text-center'>Cet email est déjà utilisé.</div>";
                } else {
                    // Par défaut, un coiffeur est enregistré avec valide = 0 (En attente de modération admin)
                    // Pense à ajouter une colonne `valide` INT DEFAULT 1 dans ta table users si tu veux filtrer plus tard.
                    $ins = $pdo->prepare("INSERT INTO users (nom, prenom, sexe, email, telephone, password, role, ville, id_quartier, diplome, photo_profil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    if ($ins->execute([$nom, $prenom, $sexe, $email, $telephone, $password, $role, $id_ville, $id_quartier, $diplome_path, $photo_profil_path])) {
                        
                        $id_nouvel_user = $pdo->lastInsertId();

                        $_SESSION['id_user'] = $id_nouvel_user;
                        $_SESSION['nom']     = $nom;
                        $_SESSION['prenom']  = $prenom;
                        $_SESSION['role']    = $role;
                        $_SESSION['ville']   = $id_ville;

                        header("Location: " . BASE_URL . "index.php");
                        exit();
                        
                    } else {
                        $message = "<div class='alert alert-danger text-center'>Une erreur est survenue lors de l'enregistrement.</div>";
                    }
                }
            }
        }
    }
}

// 2. INCLUSION DU HEADER
include __DIR__ . '/../layout/header.php';
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
                                <input type="radio" class="btn-check" name="role" id="roleClient" value="client" onclick="toggleRoleFields()" checked>
                                <label class="btn btn-outline-warning w-100 py-3" for="roleClient">
                                    <span class="d-block h4 mb-0">🙋‍♂️</span>
                                    <span>Client</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="roleCoiffeur" value="coiffeur" onclick="toggleRoleFields()">
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
                                <select name="ville" id="villeSelect" class="form-select" required>
                                    <option value="">Sélectionnez votre ville</option>
                                    <?php
                                    $villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
                                    while ($v = $villes_stmt->fetch()) {
                                        echo "<option value='" . $v['id'] . "'>" . htmlspecialchars($v['nom_ville']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-white small mb-1">Quartier</label>
                                <select name="id_quartier" id="quartierSelect" class="form-select" required disabled>
                                    <option value="">Sélectionnez d'abord une ville</option>
                                </select>
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
                                <label class="text-white small mb-1">Photo ou scan de votre diplôme / certification <span class="text-danger">*</span></label>
                                <input type="file" name="diplome" id="diplomeInput" class="form-control" accept="image/*,application/pdf">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="text-white small mb-1">Mot de passe</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="passwordField" class="form-control" required>
                                    <button class="btn btn-outline-warning d-flex align-items-center justify-content-center" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()" style="width: 45px;">
                                        <i class="bi bi-eye" id="passwordIcon" style="color: #000; font-size: 1.1rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-3 fs-5 fw-bold">
                            CRÉER MON COMPTE
                        </button>
                    </form>

                    <p class="text-center mt-4 text-secondary small">
                        Déjà inscrit ? <a href="/coiffons/access/connexion.php" class="text-warning">Connectez-vous</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Chargement dynamique des quartiers (AJAX)
    document.getElementById('villeSelect').addEventListener('change', function() {
        var idVille = this.value;
        var quartierSelect = document.getElementById('quartierSelect');
        
        if (!idVille) {
            quartierSelect.innerHTML = '<option value="">Sélectionnez d\'abord une ville</option>';
            quartierSelect.disabled = true;
            return;
        }

        fetch('get_quartiers.php?id_ville=' + idVille)
            .then(response => response.json())
            .then(data => {
                quartierSelect.innerHTML = '';
                quartierSelect.disabled = false;

                // Option générique obligatoire pour ne jamais bloquer l'utilisateur
                var defaultOpt = document.createElement('option');
                defaultOpt.value = "0"; // Sera une valeur par défaut neutre
                defaultOpt.textContent = "Autre / Centre-ville";
                quartierSelect.appendChild(defaultOpt);

                data.forEach(quartier => {
                    var option = document.createElement('option');
                    option.value = quartier.id;
                    option.textContent = quartier.nom_quartier;
                    quartierSelect.appendChild(option);
                });
                
                // Forcer la sélection du premier élément par défaut pour le required
                quartierSelect.selectedIndex = 0;
            })
            .catch(error => console.error('Erreur:', error));
    });

    // Gestion dynamique de l'obligation du diplôme selon le rôle choisi
    function toggleRoleFields() {
        var roleCoiffeur = document.getElementById('roleCoiffeur');
        var diplomeGroup = document.getElementById('diplomeGroup');
        var diplomeInput = document.getElementById('diplomeInput');

        if (roleCoiffeur.checked) {
            diplomeGroup.style.display = 'block';
            diplomeInput.required = true; // Déclenche le required HTML5
        } else {
            diplomeGroup.style.display = 'none';
            diplomeInput.required = false;
            diplomeInput.value = ""; // Vide le fichier si sélectionné par erreur
        }
    }

    // Toggle visuel de l'icône mot de passe (Comme sur connexion.php)
    function togglePasswordVisibility() {
        var passwordField = document.getElementById('passwordField');
        var passwordIcon = document.getElementById('passwordIcon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordIcon.className = 'bi bi-eye-slash';
        } else {
            passwordField.type = 'password';
            passwordIcon.className = 'bi bi-eye';
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

<?php include __DIR__ . '/../layout/footer.php'; ?>