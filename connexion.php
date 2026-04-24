<?php 
include 'header.php'; 
require 'config.php'; 

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // ON STOCKO LES INFOS CLÉS EN SESSION
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['sexe']    = $user['sexe']; // Détermine si c'est Vanessa ou Daniel

        // Redirection selon le rôle
        if ($user['role'] == 'coiffeur') {
            header("Location: catalogue.php"); // Il pourra gérer ses coupes
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 80vh;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid var(--gold); border-radius: 20px;">
                    <h2 class="text-center text-warning mb-4">CONNEXION</h2>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="text-white small mb-1">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="text-white small mb-1">Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
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

<?php include 'footer.php'; ?>