<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {
    $email = trim(htmlspecialchars($_POST['email']));
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Recherche de l'utilisateur dans la base
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Tolérance d'écriture pour la colonne du mot de passe
            $hash_en_bdd = $user['password'] ?? $user['Password'] ?? $user['mot_de_passe'] ?? null;

            if ($hash_en_bdd && password_verify($password, $hash_en_bdd)) {
                
                // RAPPEL : Clé primaire de la table 'users' est 'id'
                $_SESSION['id_user'] = $user['id'];
                $_SESSION['nom']     = $user['nom'] ?? '';
                $_SESSION['prenom']  = $user['prenom'] ?? '';
                $_SESSION['role']    = $user['role'] ?? 'client';
                
                // Gestion de la ville de l'utilisateur (ID numérique)
                $id_ville = $user['ville'] ?? null;
                $_SESSION['ville'] = $id_ville; 

                // CONFIRMÉ : La clé primaire de la table villes est bien 'id'
                if ($id_ville) {
                    $get_ville_name = $pdo->prepare("SELECT nom_ville FROM villes WHERE id = ?");
                    $get_ville_name->execute([$id_ville]);
                    $ville_data = $get_ville_name->fetch();
                    $_SESSION['nom_ville'] = $ville_data ? $ville_data['nom_ville'] : "votre ville";
                } else {
                    $_SESSION['nom_ville'] = "votre ville";
                }

                $_SESSION['date_expiration_abo'] = $user['date_expiration_abo'] ?? 'Non abonné';

                // Redirections adaptées par rôles
                if ($_SESSION['role'] === 'admin') {
                    echo "<script>window.location.href='/coiffons/first/admin_dashboard.php';</script>";
                } else {
                    if (file_exists('index.php')) {
                        echo "<script>window.location.href='index.php';</script>";
                    } else {
                        echo "<script>window.location.href='../index.php';</script>";
                    }
                }
                exit();

            } else {
                $error = "Identifiants incorrects. Veuillez réessayer.";
            }
        } else {
            $error = "Identifiants incorrects. Veuillez réessayer.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!-- Interface HTML -->
<section class="d-flex align-items-center justify-content-center text-white" style="background-color: #000; min-height: 100vh; padding: 40px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card p-4 shadow-lg border-secondary" style="background-color: #111; border-radius: 12px;">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-warning" style="letter-spacing: 1px;">SE CONNECTER</h2>
                        <p class="text-secondary small">Accédez à votre espace CoiffeChezToi</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger text-center py-2 small" style="border-radius: 8px;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">ADRESSE EMAIL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-black border-secondary text-secondary"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control bg-black text-white border-secondary text-center" placeholder="exemple@mail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold">MOT DE PASSE</label>
                            <div class="input-group">
                                <span class="input-group-text bg-black border-secondary text-secondary"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="passwordField" class="form-control bg-black text-white border-secondary text-center" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary border-secondary bg-black" type="button" id="togglePassword">
                                    <i class="bi bi-eye text-secondary" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="connexion" class="btn btn-warning w-100 fw-bold py-2 mb-3 shadow-sm" style="border-radius: 8px; transition: 0.3s;">
                            SE CONNECTER
                        </button>

                        <div class="text-center mt-3">
                            <p class="text-secondary small mb-1">Pas encore de compte ?</p>
                            <a href="inscription.php" class="text-warning text-decoration-none small fw-bold">Créer un compte maintenant</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordField = document.getElementById('passwordField');
        const eyeIcon = document.getElementById('eyeIcon');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            passwordField.type === 'password';
            passwordField.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>