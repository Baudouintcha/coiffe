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
        // 1. Recherche de l'utilisateur dans la base
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. Si l'utilisateur existe, on cherche son mot de passe haché
        if ($user) {
            // Sécurité : accepte 'password' ou 'mot_de_passe' selon ta table
            $hash_en_bdd = $user['password'] ?? $user['Password'] ?? $user['mot_de_passe'] ?? null;

            // 3. Vérification du mot de passe
            if ($hash_en_bdd && password_verify($password, $hash_en_bdd)) {
                
                // Initialisation des variables de session
                $_SESSION['id_user'] = $user['id'] ?? $user['id_user'] ?? null; 
                $_SESSION['nom']      = $user['nom'] ?? '';
                $_SESSION['prenom']   = $user['prenom'] ?? '';
                $_SESSION['role']     = $user['role'] ?? '';
                $_SESSION['ville']    = $user['ville'] ?? '';
                $_SESSION['date_expiration_abo'] = $user['date_expiration_abo'] ?? 'Non abonné';

                // --- CONDITION ADOUTÉE POUR L'ADMINISTRATEUR ---
                if ($_SESSION['role'] === 'admin') {
                    // Si c'est l'admin, on l'envoie sur le dashboard
                    echo "<script>window.location.href='/coiffons/first/admin_dashboard.php';</script>";
                } else {
                    // Si c'est un client ou coiffeur, redirection dynamique classique
                    if (file_exists('index.php')) {
                        echo "<script>window.location.href='index.php';</script>";
                    } else {
                        echo "<script>window.location.href='../index.php';</script>";
                    }
                }
                exit();
                // -----------------------------------------------

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

<section class="py-5 d-flex align-items-center" style="background-color: #000; min-height: 85vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center small py-2 mb-3" style="border-radius: 8px;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="card p-4 shadow-lg border-0" style="background-color: #111; border-radius: 16px; border: 1px solid #222 !important;">
                    <div class="text-center mb-4">
                        <h3 class="text-warning fw-bold mb-1" style="letter-spacing: 1px;">CONNEXION</h3>
                        <p class="text-secondary small">Accédez à votre espace Coiffe Chez Toi</p>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="text-white small mb-1">Adresse Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control bg-dark text-white border-secondary" placeholder="exemple@mail.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="text-white small mb-1">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="passwordInput" class="form-control bg-dark text-white border-secondary border-end-0" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary bg-dark border-secondary text-secondary border-start-0" type="button" id="togglePassword" style="border-radius: 0 8px 8px 0;">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="connexion" class="btn btn-gold w-100 py-2 fw-bold mb-3" style="border-radius: 8px;">
                            SE CONNECTER
                        </button>
                    </form>

                    <div class="text-center mt-2">
                        <p class="text-secondary small mb-0">Vous n'avez pas de compte ?</p>
                        <a href="/coiffons/access/inscription.php" class="text-warning small text-decoration-none fw-bold">Créer un compte maintenant</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('passwordInput');
        const eyeIcon = document.getElementById('eyeIcon');
        
        // Permutation du type d'input
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    });
</script>

<style>
    .form-control:focus {
        background-color: #e9cbcb !important;
        border-color: var(--gold) !important;
        color: #fff !important;
        box-shadow: none;
    }
    .input-group-text {
        border-radius: 8px 0 0 8px;
    }
    /* Correction de l'arrondi suite à l'ajout du bouton d'œil */
    #passwordInput {
        border-radius: 0 !important;
    }
    #togglePassword:focus {
        box-shadow: none;
        border-color: #6c757d;
    }
    .btn-gold {
        background-color: var(--gold);
        color: black;
        transition: 0.3s;
    }
    .btn-gold:hover {
        background-color: #c99b2c;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>