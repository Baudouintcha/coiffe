<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {
    $email = trim(htmlspecialchars($_POST['email']));
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Recherche de l'utilisateur (on récupère l'ID, le nom, le rôle ET la ville)
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Si l'utilisateur existe, on vérifie son mot de passe
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Initialisation parfaite des variables de sessions requises
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['ville'] = $user['ville']; // Crucial pour le filtre automatique

            // Redirection immédiate vers l'accueil unique
            echo "<script>window.location.href='index.php';</script>";
            exit();
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
                                <input type="password" name="password" class="form-control bg-dark text-white border-secondary" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" name="connexion" class="btn btn-gold w-100 py-2 fw-bold mb-3" style="border-radius: 8px;">
                            SE CONNECTER
                        </button>
                    </form>

                    <div class="text-center mt-2">
                        <p class="text-secondary small mb-0">Vous n'avez pas de compte ?</p>
                        <a href="inscription.php" class="text-warning small text-decoration-none fw-bold">Créer un compte maintenant</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<style>
    .form-control:focus {
        background-color: #1a1a1a !important;
        border-color: var(--gold) !important;
        color: #fff !important;
        box-shadow: none;
    }
    .input-group-text {
        border-radius: 8px 0 0 8px;
    }
    .form-control {
        border-radius: 0 8px 8px 0;
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

<?php include 'footer.php'; ?>