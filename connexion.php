<?php 
// 1. D'ABORD LE TRAITEMENT PHP
require 'config.php'; 

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            $_SESSION['id_user']  = $user['id'];
            $_SESSION['nom']      = $user['nom'];
            $_SESSION['prenom']   = $user['prenom'];
            $_SESSION['role']     = $user['role']; 

            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_dashboard.php");
                exit();
            } elseif ($_SESSION['role'] === 'coiffeur') {
                header("Location: tableau_coiffeur.php"); 
                exit();
            } else {
                header("Location: index.php"); 
                exit();
            }

        } else {
            $error = "Adresse e-mail ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        $error = "Erreur de base de données : " . $e->getMessage();
    }
}

// 2. ENFUITE L'INCLUSION DU HEADER ET DU VISUEL HTML
include 'header.php'; 
?>

<section class="py-5" style="background-color: #000; min-height: 80vh;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid #D4AF37; border-radius: 20px;">
                    <h2 class="text-center text-warning mb-4">CONNEXION</h2>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="text-white small mb-1">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="Ex: votreemail@cft.bj" required>
                        </div>

                        <div class="mb-4">
                            <label class="text-white small mb-1">Mot de passe</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe" style="border-top-right-radius: 0; border-bottom-right-radius: 0;" required>
                                <button class="btn btn-outline-warning" type="button" id="togglePassword" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px; background-color: #fff; border: 1px solid #ccc; color: #333;">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-2 fw-bold">
                            SE CONNECTER
                        </button>
                    </form>
                    
                    <p class="text-center mt-4 text-secondary small">
                        Pas encore de compte ? <a href="inscription.php" class="text-warning">Inscrivez-vous ici</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .form-control { background-color: #fff !important; border-radius: 8px; }
    .btn-gold { background-color: #D4AF37; color: #000; border: none; transition: 0.3s; }
    .btn-gold:hover { background-color: #f1c40f; transform: scale(1.02); }
</style>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });
</script>

<?php include 'footer.php'; ?>