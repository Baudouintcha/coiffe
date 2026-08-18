<?php
/**
 * client/formulaires/finaliser_reservation.php
 * 
 * Formulaire MINIMAL pour visiteur invité
 * Avant de passer à la sélection de date/heure
 * 
 * Crée automatiquement un compte client après validation
 * 
 * GET params:
 *   - id_service : ID prestation
 *   - prestataire_id : ID coiffeur
 *   - return_url : URL de retour après création compte (optionnel)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/csrf.php';

// Si déjà connecté → rediriger vers réservation directe
if (isset($_SESSION['id_user'])) {
    $id_service = isset($_GET['id_service']) ? intval($_GET['id_service']) : 0;
    if ($id_service > 0) {
        header("Location: /coiffons/client/creer_rendezvous.php?id_service=" . $id_service);
        exit();
    }
}

$id_service = isset($_GET['id_service']) ? intval($_GET['id_service']) : 0;
$prestataire_id = isset($_GET['prestataire_id']) ? intval($_GET['prestataire_id']) : 0;
$return_url = isset($_GET['return_url']) ? trim($_GET['return_url']) : '';

if ($id_service <= 0) {
    header("Location: /coiffons/index.php");
    exit();
}

// Charger prestation
$presta = $pdo->prepare("SELECT * FROM services WHERE id_service = ?");
$presta->execute([$id_service]);
$prestation = $presta->fetch();

if (!$prestation) {
    header("Location: /coiffons/index.php");
    exit();
}

$message = "";
$success = false;

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finaliser_reservation'])) {
    csrf_verify();

    $nom = strip_tags(trim($_POST['nom'] ?? ''));
    $prenom = strip_tags(trim($_POST['prenom'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $telephone = strip_tags(trim($_POST['telephone'] ?? ''));
    $id_ville = intval($_POST['id_ville'] ?? 0);
    $id_quartier = intval($_POST['id_quartier'] ?? 0);

    $errors = [];

    // Validations
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $nom)) $errors[] = "Nom invalide.";

    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $prenom)) $errors[] = "Prénom invalide.";

    if (empty($email)) $errors[] = "L'email est obligatoire.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire.";
    elseif (!preg_match('/^\+?[0-9\s\-]{8,20}$/', $telephone)) $errors[] = "Téléphone invalide.";

    if ($id_ville <= 0) $errors[] = "Veuillez sélectionner une ville.";
    if ($id_quartier <= 0) $errors[] = "Veuillez sélectionner un quartier.";

    if (!empty($errors)) {
        $message = "msg-danger::" . implode('<br>', array_map('htmlspecialchars', $errors));
    } else {
        // Vérifier si email existe déjà
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $message = "msg-danger::Cet email est déjà utilisé. Veuillez vous connecter.";
        } else {
            try {
                // Créer un compte client automatiquement
                // Générer mot de passe aléatoire (utilisateur peut le changer plus tard)
                $temp_password = bin2hex(random_bytes(8));
                $password_hash = password_hash($temp_password, PASSWORD_BCRYPT);

                $ins = $pdo->prepare("
                    INSERT INTO users 
                    (nom, prenom, sexe, email, telephone, password, role, id_ville, id_quartier, statut, is_approved, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $ins->execute([
                    $nom,
                    $prenom,
                    'homme', // Valeur par défaut (peut être complétée plus tard)
                    $email,
                    $telephone,
                    $password_hash,
                    'client',
                    $id_ville,
                    $id_quartier,
                    'actif',      // Client actif immédiatement
                    1             // Client approuvé (pas besoin de validation admin)
                ]);

                if ($ins->rowCount() > 0) {
                    $new_user_id = $pdo->lastInsertId();

                    // Créer session automatiquement
                    $_SESSION['id_user'] = $new_user_id;
                    $_SESSION['nom'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $_SESSION['role'] = 'client';
                    $_SESSION['email'] = $email;
                    $_SESSION['id_ville'] = $id_ville;
                    $_SESSION['id_quartier'] = $id_quartier;

                    $success = true;
                    $message = "msg-success::Bienvenue ! Votre compte a été créé.";

                    // Rediriger vers sélection date/heure après 2 secondes
                    header("Refresh: 2; url=/coiffons/client/creer_rendezvous.php?id_service=" . $id_service);
                } else {
                    $message = "msg-danger::Erreur lors de la création du compte.";
                }
            } catch (PDOException $e) {
                error_log("Erreur création compte client: " . $e->getMessage());
                $message = "msg-danger::Erreur technique. Veuillez réessayer.";
            }
        }
    }
}

// Charger villes pour dropdown
$villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
$villes_list = $villes_stmt->fetchAll();

// Image de fond
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';

// Parser message
$msg_class = '';
$msg_content = '';
if (!empty($message)) {
    [$msg_class, $msg_content] = explode('::', $message, 2);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalisez votre réservation — Coiffe Chez Toi</title>

    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    .form-page { padding-top: 2rem; padding-bottom: 2rem; min-height: 100vh; background: var(--dark); }
    .form-card { background: var(--dark-2); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 2rem; max-width: 600px; margin: 0 auto; }
    .form-title { font-family: var(--font-display); font-size: 1.5rem; color: var(--gold); text-align: center; margin-bottom: 0.5rem; letter-spacing: 1px; }
    .form-subtitle { text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem; }
    .step-indicator { display: flex; justify-content: center; gap: 12px; margin-bottom: 2rem; }
    .step { width: 32px; height: 32px; border-radius: 50%; background: var(--dark-3); border: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); }
    .step.active { background: var(--gold); color: var(--black); border-color: var(--gold); }
    .service-recap { background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 2rem; }
    .service-name { font-weight: 700; color: var(--gold); font-size: 1rem; margin-bottom: 0.25rem; }
    .service-price { font-size: 1.3rem; font-weight: 800; color: var(--gold); }
    </style>
</head>
<body>
<main class="form-page page-transition" id="main-content">
    <div class="container">
        <div class="form-card fade-in-card">

            <!-- Lien retour -->
            <div class="mb-3">
                <a href="javascript:history.back()" class="btn-ghost btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour
                </a>
            </div>

            <!-- Titre -->
            <h1 class="form-title">
                <i class="bi bi-check-circle me-2" style="color: var(--gold);" aria-hidden="true"></i>
                Finalisez votre réservation
            </h1>
            <p class="form-subtitle">
                Renseignez vos coordonnées pour que le coiffeur puisse vous contacter
            </p>

            <!-- Indicateur étape -->
            <div class="step-indicator">
                <div class="step active">1</div>
                <div class="step">2</div>
                <div class="step">3</div>
            </div>

            <!-- Récap prestation -->
            <div class="service-recap">
                <div class="service-name">
                    <i class="bi bi-scissors me-2" style="color: var(--gold);" aria-hidden="true"></i>
                    <?= htmlspecialchars($prestation['nom_service']) ?>
                </div>
                <div class="service-price">
                    <?= number_format($prestation['prix'], 0, ',', ' ') ?> FCFA
                </div>
            </div>

            <!-- Message d'erreur/succès -->
            <?php if (!empty($msg_class) && !empty($msg_content)): ?>
                <div class="<?= htmlspecialchars($msg_class) ?> mb-4" role="alert" aria-live="polite">
                    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                    <?= $msg_content ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <!-- Formulaire -->
            <form method="POST" id="finaliserForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id_service" value="<?= $id_service ?>">
                <input type="hidden" name="prestataire_id" value="<?= $prestataire_id ?>">

                <!-- Nom -->
                <div class="mb-3">
                    <label for="nom" class="form-label-cct">Nom</label>
                    <input type="text" id="nom" name="nom" class="fc-dark" 
                           placeholder="Votre nom" autocomplete="family-name"
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                           required aria-required="true">
                </div>

                <!-- Prénom -->
                <div class="mb-3">
                    <label for="prenom" class="form-label-cct">Prénom</label>
                    <input type="text" id="prenom" name="prenom" class="fc-dark" 
                           placeholder="Votre prénom" autocomplete="given-name"
                           value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                           required aria-required="true">
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label-cct">Adresse email</label>
                    <input type="email" id="email" name="email" class="fc-dark" 
                           placeholder="votre@email.com" autocomplete="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required aria-required="true">
                </div>

                <!-- Téléphone -->
                <div class="mb-3">
                    <label for="telephone" class="form-label-cct">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" class="fc-dark" 
                           placeholder="+229 XX XX XX XX" autocomplete="tel"
                           value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                           required aria-required="true">
                </div>

                <!-- Ville -->
                <div class="mb-3">
                    <label for="villeSelect" class="form-label-cct">Ville</label>
                    <select id="villeSelect" name="id_ville" class="fc-dark" 
                            required aria-required="true">
                        <option value="">— Sélectionnez votre ville —</option>
                        <?php foreach ($villes_list as $v): ?>
                            <option value="<?= $v['id'] ?>"
                                <?= intval($_POST['id_ville'] ?? 0) === intval($v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nom_ville']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quartier -->
                <div class="mb-4">
                    <label for="quartierSelect" class="form-label-cct">Quartier</label>
                    <select id="quartierSelect" name="id_quartier" class="fc-dark" 
                            required aria-required="true" 
                            <?= empty($_POST['id_ville']) ? 'disabled' : '' ?>>
                        <option value="">— Choisissez la ville d'abord —</option>
                    </select>
                    <small style="color: var(--text-muted); margin-top: 4px; display: block;">
                        Sélectionnez votre ville pour voir les quartiers disponibles
                    </small>
                </div>

                <!-- Info message -->
                <div style="background: rgba(212, 175, 55, 0.08); border-left: 3px solid var(--gold); padding: 12px 16px; border-radius: 4px; margin-bottom: 1.5rem;">
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">
                        <i class="bi bi-shield-check me-1" style="color: var(--gold);" aria-hidden="true"></i>
                        Vos données sont sécurisées. Un compte sera créé automatiquement.
                    </p>
                </div>

                <!-- Bouton submit -->
                <button type="submit" name="finaliser_reservation" class="btn-gold w-100" 
                        style="padding: 14px; font-size: 0.95rem; font-weight: 700;">
                    <i class="bi bi-arrow-right me-2" aria-hidden="true"></i>
                    Continuer vers la sélection de date
                </button>
            </form>
            <?php else: ?>
            <!-- Écran succès -->
            <div style="text-align: center; padding: 2rem 0;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(25, 135, 84, 0.15); border: 2px solid rgba(25, 135, 84, 0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="bi bi-check-circle-fill" style="font-size: 2rem; color: var(--success-text);" aria-hidden="true"></i>
                </div>
                <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Compte créé !</h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">
                    Vous allez maintenant choisir la date et l'heure de votre rendez-vous...
                </p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Chargement AJAX des quartiers
document.getElementById('villeSelect').addEventListener('change', function() {
    const qs = document.getElementById('quartierSelect');
    const idVille = this.value;
    
    if (!idVille || idVille === '0') {
        qs.innerHTML = '<option value="">— Choisissez la ville d\'abord —</option>';
        qs.disabled = true;
        return;
    }
    
    fetch('/coiffons/access/get_quartiers.php?id_ville=' + idVille)
        .then(r => r.json())
        .then(data => {
            qs.innerHTML = '<option value="">— Sélectionnez un quartier —</option>';
            qs.disabled = false;
            data.forEach(q => {
                const o = document.createElement('option');
                o.value = q.id;
                o.textContent = q.nom_quartier;
                qs.appendChild(o);
            });
        })
        .catch(() => {
            qs.innerHTML = '<option value="0">Centre-ville</option>';
            qs.disabled = false;
        });
});
</script>

</body>
</html>
