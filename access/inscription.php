<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/csrf.php';

// Rôle pré-défini depuis l'URL — plus de sélection dans le formulaire
$role_predefini = isset($_GET['role']) ? trim($_GET['role']) : 'client';
if (!in_array($role_predefini, ['client', 'coiffeur'])) {
    $role_predefini = 'client';
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ── VÉRIFICATION CSRF ──
    csrf_verify();

    if (
        empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['sexe']) ||
        empty($_POST['email']) || empty($_POST['telephone']) || empty($_POST['password']) ||
        empty($_POST['role']) || empty($_POST['ville']) || empty($_POST['id_quartier'])
    ) {
        $message = "<div class='alert-msg alert-msg-danger'>Champs obligatoires manquants.</div>";
    } else {
        // ── NETTOYAGE ET VALIDATION STRICTE ──
        $nom         = strip_tags(trim($_POST['nom']));
        $prenom      = strip_tags(trim($_POST['prenom']));
        $sexe        = $_POST['sexe'];
        $email       = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $telephone   = strip_tags(trim($_POST['telephone']));
        $password    = $_POST['password'];
        $role        = $_POST['role'];
        $id_ville    = intval($_POST['ville']);
        $id_quartier = intval($_POST['id_quartier']);

        // ── VALIDATIONS ──
        $errors = [];

        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $nom)) {
            $errors[] = "Nom invalide (lettres uniquement).";
        }
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $prenom)) {
            $errors[] = "Prénom invalide (lettres uniquement).";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Adresse email invalide.";
        }
        if (!in_array($sexe, ['homme', 'femme'])) {
            $errors[] = "Sexe invalide.";
        }
        if (!in_array($role, ['client', 'coiffeur'])) {
            $errors[] = "Rôle invalide.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit faire au moins 6 caractères.";
        }
        if ($id_ville <= 0) {
            $errors[] = "Veuillez sélectionner une ville.";
        }
        if (!preg_match('/^\+?[0-9\s\-]{8,20}$/', $telephone)) {
            $errors[] = "Numéro de téléphone invalide.";
        }

        if (!empty($errors)) {
            $message = "<div class='alert-msg alert-msg-danger'>" . implode('<br>', $errors) . "</div>";
        } elseif ($role == 'coiffeur' && (!isset($_FILES['diplome']) || $_FILES['diplome']['error'] !== 0)) {
            $message = "<div class='alert-msg alert-msg-danger'>Le diplôme est obligatoire pour les coiffeurs.</div>";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $diplome_path = NULL;
            if ($role == 'coiffeur') {
                // Traitement diplôme — compressé via Canvas JS (base64) ou PDF brut
                if (!empty($_POST['diplome_b64'])) {
                    $b64d = $_POST['diplome_b64'];
                    if (preg_match('/^data:(image\/(jpeg|png|webp)|application\/pdf);base64,(.+)$/', $b64d, $md)) {
                        $is_pdf    = $md[1] === 'application/pdf';
                        $ext_d     = $is_pdf ? 'pdf' : ($md[2] === 'jpeg' ? 'jpg' : $md[2]);
                        $imgData_d = base64_decode($md[3]);
                        $diplome_path = 'uploads/diplomes/' . uniqid('', true) . '.' . $ext_d;
                        $target = __DIR__ . '/../uploads/diplomes/';
                        if (!is_dir($target)) mkdir($target, 0755, true);
                        file_put_contents($target . basename($diplome_path), $imgData_d);
                    } else {
                        $message = "<div class='alert-msg alert-msg-danger'>Format diplôme invalide (JPG, PNG, PDF).</div>";
                    }
                } else {
                    $message = "<div class='alert-msg alert-msg-danger'>Le diplôme est obligatoire pour les coiffeurs.</div>";
                }
            }

            if (empty($message)) {
                $photo_profil_path = null;
                // Traitement photo profil — compressée via Canvas JS (base64)
                if (!empty($_POST['photo_profil_b64'])) {
                    $b64 = $_POST['photo_profil_b64'];
                    // Extrait les données base64
                    if (preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/', $b64, $m)) {
                        $ext       = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                        $imgData   = base64_decode($m[2]);
                        $photo_profil_path = 'uploads/profil/' . uniqid('', true) . '.' . $ext;
                        $target2   = __DIR__ . '/../uploads/profil/';
                        if (!is_dir($target2)) mkdir($target2, 0755, true);
                        file_put_contents($target2 . basename($photo_profil_path), $imgData);
                    }
                }
            }

            if (empty($message)) {
                // Vérifie email unique
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                if ($check->rowCount() > 0) {
                    $message = "<div class='alert-msg alert-msg-danger'>Cet email est déjà utilisé.</div>";
                } else {
                    $ins = $pdo->prepare("INSERT INTO users (nom, prenom, sexe, email, telephone, password, role, ville, id_quartier, diplome, photo_profil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($ins->execute([$nom, $prenom, $sexe, $email, $telephone, $password_hash, $role, $id_ville, $id_quartier, $diplome_path, $photo_profil_path])) {
                        $_SESSION['id_user']  = $pdo->lastInsertId();
                        $_SESSION['nom']      = $nom;
                        $_SESSION['prenom']   = $prenom;
                        $_SESSION['role']     = $role;
                        $_SESSION['ville']    = $id_ville;
                        $vn = $pdo->prepare("SELECT nom_ville FROM villes WHERE id = ?");
                        $vn->execute([$id_ville]);
                        $vd = $vn->fetch();
                        $_SESSION['nom_ville'] = $vd ? $vd['nom_ville'] : '';

                        // Redirection selon le rôle avec headers anti-cache
                        header("Cache-Control: no-store, no-cache, must-revalidate");
                        header("Pragma: no-cache");
                        if ($role === 'client') {
                            header("Location: /coiffons/index.php?page=dashboard");
                        } elseif ($role === 'coiffeur') {
                            header("Location: /coiffons/index.php?page=dashboard_coiffeur");
                        } else {
                            header("Location: /coiffons/index.php");
                        }
                        exit();
                    } else {
                        $message = "<div class='alert-msg alert-msg-danger'>Erreur lors de l'enregistrement.</div>";
                    }
                }
            }
        }
    }
}

// Villes pour le dropdown
$villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
$villes_list = $villes_stmt->fetchAll();

// Première image du slider comme fond
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image  = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Coiffe Chez Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gold: #D4AF37; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { min-height: 100vh; font-family: 'Segoe UI', sans-serif; }

        /* Fond figé flou — même image que le slider home.php */
        .auth-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image: url('<?= $bg_image ?>');
            background-size: cover;
            background-position: center;
            filter: blur(20px) brightness(0.5);
            transform: scale(1.1);
        }
        .auth-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: rgba(0,0,0,0.30);
        }

        /* Wrapper centré avec scroll */
        .auth-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow-y: auto;
        }

        /* Carte glassmorphism */
        .glass-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.5);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 620px;
            color: #fff;
        }
        .glass-card h2 {
            color: var(--gold);
            font-size: 1.4rem;
            letter-spacing: 2px;
        }

        /* Labels */
        .glass-card label {
            display: block;
            color: rgba(255,255,255,0.65);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        /* Champs semi-transparents */
        .glass-card .form-control,
        .glass-card .form-select {
            background: rgba(255,255,255,0.10) !important;
            border: 1px solid rgba(255,255,255,0.20) !important;
            color: #fff !important;
            border-radius: 10px !important;
        }
        .glass-card .form-control:focus,
        .glass-card .form-select:focus {
            background: rgba(255,255,255,0.18) !important;
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.15) !important;
            color: #fff !important;
            outline: none;
        }
        .glass-card .form-control::placeholder { color: rgba(255,255,255,0.30) !important; }
        .glass-card .form-select option { background: #1a1a1a; color: #fff; }
        .glass-card .form-control[type="file"] { color: rgba(255,255,255,0.6) !important; }

        /* Toggle password */
        .glass-card .btn-pw {
            background: rgba(255,255,255,0.10) !important;
            border: 1px solid rgba(255,255,255,0.20) !important;
            border-left: none !important;
            color: rgba(255,255,255,0.5) !important;
            border-radius: 0 10px 10px 0 !important;
        }
        .glass-card .btn-pw:hover { background: rgba(212,175,55,0.15) !important; color: var(--gold) !important; }
        .glass-card .input-group .form-control { border-right: none !important; border-radius: 10px 0 0 10px !important; }

        /* Bouton submit */
        .btn-gold {
            background: var(--gold);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.25s;
            width: 100%;
        }
        .btn-gold:hover { background: #c9a227; transform: translateY(-1px); }

        /* Messages */
        .alert-msg {
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            text-align: center;
        }
        .alert-msg-danger {
            background: rgba(220,53,69,0.20);
            border: 1px solid rgba(220,53,69,0.35);
            color: #ffb3b3;
        }

        small { color: rgba(255,255,255,0.30); font-size: 0.70rem; }
    </style>
</head>
<body>

<div class="auth-bg"></div>
<div class="auth-overlay"></div>

<div class="auth-wrapper">
    <div class="glass-card">

        <h2 class="text-center mb-1">REJOINDRE L'AVENTURE</h2>
        <p class="text-center mb-4" style="color:rgba(255,255,255,0.40);font-size:0.8rem;">
            Compte
            <strong style="color:var(--gold)"><?= $role_predefini === 'coiffeur' ? 'Coiffeur / Prestataire' : 'Client' ?></strong>
            &nbsp;&middot;&nbsp;
            <a href="/coiffons/index.php?page=register&role=<?= $role_predefini === 'coiffeur' ? 'client' : 'coiffeur' ?>"
               style="color:rgba(255,255,255,0.40);text-decoration:none;">
                Je suis <?= $role_predefini === 'coiffeur' ? 'un client' : 'un coiffeur' ?> &rarr;
            </a>
        </p>

        <?= $message ?>

        <form method="POST" enctype="multipart/form-data" id="inscriptionForm">
            <?= csrf_field() ?>
            <input type="hidden" name="role" value="<?= htmlspecialchars($role_predefini) ?>">
            <!-- Champ caché qui recevra la photo compressée en base64 -->
            <input type="hidden" name="photo_profil_b64" id="photo_profil_b64">
            <input type="hidden" name="diplome_b64" id="diplome_b64">

            <div class="row g-3 mb-2">
                <div class="col-4">
                    <label>Nom</label>
                    <!-- value pré-rempli si erreur PHP -->
                    <input type="text" name="nom" class="form-control"
                           placeholder="Dossou"
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                           required>
                </div>
                <div class="col-4">
                    <label>Prénom</label>
                    <input type="text" name="prenom" class="form-control"
                           placeholder="Jean"
                           value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                           required>
                </div>
                <div class="col-4">
                    <label>Sexe</label>
                    <select name="sexe" class="form-select" required>
                        <option value="">—</option>
                        <option value="homme" <?= ($_POST['sexe'] ?? '') === 'homme' ? 'selected' : '' ?>>Homme</option>
                        <option value="femme" <?= ($_POST['sexe'] ?? '') === 'femme' ? 'selected' : '' ?>>Femme</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="exemple@mail.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
                <div class="col-6">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" class="form-control"
                           placeholder="+229..."
                           value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                           required>
                </div>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-6">
                    <label>Ville</label>
                    <select name="ville" id="villeSelect" class="form-select" required>
                        <option value="">Sélectionnez</option>
                        <?php foreach ($villes_list as $v): ?>
                            <option value="<?= $v['id'] ?>"
                                <?= intval($_POST['ville'] ?? 0) === intval($v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nom_ville']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label>Quartier</label>
                    <select name="id_quartier" id="quartierSelect" class="form-select" required
                            <?= empty($_POST['ville']) ? 'disabled' : '' ?>>
                        <option value="">Choisissez la ville d'abord</option>
                    </select>
                </div>
            </div>

            <div class="mb-2">
                <label>
                    Photo de profil
                    <small>(Optionnel — compressée automatiquement)</small>
                </label>
                <!-- L'input file déclenche la compression JS, le fichier réel n'est pas envoyé -->
                <input type="file" id="photo_profil_input" class="form-control"
                       accept="image/*" onchange="compresserPhoto(this, 'photo_profil_b64', 'preview_profil')">
                <div id="preview_profil" style="margin-top:6px;"></div>
            </div>

            <?php if ($role_predefini === 'coiffeur'): ?>
            <div class="mb-2">
                <label>
                    Diplôme / Certification <span style="color:#ff6b6b;">*</span>
                    <small>(JPG, PNG, PDF — compressé auto si image)</small>
                </label>
                <input type="file" id="diplome_input" class="form-control"
                       accept="image/*,application/pdf"
                       onchange="compresserDiplome(this)"
                       required>
                <small>Vérifié par notre équipe avant activation</small>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label>Mot de passe</label>
                <div class="input-group">
                    <input type="password" name="password" id="pwField" class="form-control"
                           placeholder="••••••••" required>
                    <button type="button" class="btn-pw btn" onclick="togglePw()">
                        <i class="bi bi-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-gold" id="submitBtn">CRÉER MON COMPTE</button>
        </form>

        <p class="text-center mt-3" style="font-size:0.80rem;color:rgba(255,255,255,0.35);">
            Déjà inscrit ?
            <a href="/coiffons/access/connexion.php"
               style="color:var(--gold);text-decoration:none;font-weight:700;">Se connecter</a>
        </p>
    </div>
</div>

<script>
// ── Protection retour arrière ──
// Empêche de revenir sur le formulaire après inscription réussie
history.pushState(null, null, location.href);
window.addEventListener('popstate', () => {
    history.pushState(null, null, location.href);
});

// ── Restauration du quartier si ville pré-remplie (après erreur PHP) ──
const villePreRemplie = '<?= intval($_POST['ville'] ?? 0) ?>';
const quartierPreRempli = '<?= intval($_POST['id_quartier'] ?? 0) ?>';

document.addEventListener('DOMContentLoaded', () => {
    if (villePreRemplie && villePreRemplie !== '0') {
        chargerQuartiers(villePreRemplie, quartierPreRempli);
    }
});

document.getElementById('villeSelect').addEventListener('change', function() {
    chargerQuartiers(this.value, null);
});

function chargerQuartiers(idVille, quartierASelectionner) {
    const qs = document.getElementById('quartierSelect');
    if (!idVille || idVille === '0') {
        qs.innerHTML = '<option value="">Choisissez la ville d\'abord</option>';
        qs.disabled = true;
        return;
    }
    fetch('/coiffons/access/get_quartiers.php?id_ville=' + idVille)
        .then(r => r.json())
        .then(data => {
            qs.innerHTML = '<option value="0">Centre-ville / Autre</option>';
            qs.disabled = false;
            data.forEach(q => {
                const o = document.createElement('option');
                o.value = q.id;
                o.textContent = q.nom_quartier;
                // Re-sélectionne le quartier si erreur PHP
                if (quartierASelectionner && q.id == quartierASelectionner) {
                    o.selected = true;
                }
                qs.appendChild(o);
            });
        })
        .catch(() => {
            qs.innerHTML = '<option value="0">Centre-ville</option>';
            qs.disabled = false;
        });
}

// ── Toggle mot de passe ──
function togglePw() {
    const f = document.getElementById('pwField');
    const i = document.getElementById('pwIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// ── Compression automatique des photos côté client ──
// Utilise le Canvas HTML5 pour réduire le poids avant envoi
// Transparent pour l'utilisateur, fonctionne sur mobile
function compresserImageCanvas(file, qualite, maxLargeur, callback) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let w = img.width, h = img.height;
            // Redimensionne si trop grand
            if (w > maxLargeur) {
                h = Math.round(h * maxLargeur / w);
                w = maxLargeur;
            }
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);
            // Convertit en JPEG compressé
            const b64 = canvas.toDataURL('image/jpeg', qualite);
            callback(b64);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function compresserPhoto(input, champCible, previewId) {
    const file = input.files[0];
    if (!file) return;

    // Si c'est déjà petit (<= 800Ko), pas besoin de compresser
    if (file.size <= 819200) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(champCible).value = e.target.result;
            afficherPreview(previewId, e.target.result);
        };
        reader.readAsDataURL(file);
        return;
    }

    // Compresse : max 800px de large, qualité 0.75
    compresserImageCanvas(file, 0.75, 800, (b64) => {
        document.getElementById(champCible).value = b64;
        afficherPreview(previewId, b64);
    });
}

function compresserDiplome(input) {
    const file = input.files[0];
    if (!file) return;

    // Si PDF, on ne compresse pas (pas d'image)
    if (file.type === 'application/pdf') {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('diplome_b64').value = e.target.result;
        };
        reader.readAsDataURL(file);
        return;
    }

    // Compresse l'image du diplôme : max 1200px, qualité 0.80
    if (file.size <= 2097152) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('diplome_b64').value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        compresserImageCanvas(file, 0.80, 1200, (b64) => {
            document.getElementById('diplome_b64').value = b64;
        });
    }
}

function afficherPreview(previewId, src) {
    const el = document.getElementById(previewId);
    if (el) {
        el.innerHTML = `<img src="${src}" style="height:50px;width:50px;object-fit:cover;border-radius:50%;border:2px solid var(--gold);margin-top:4px;">`;
    }
}
</script>

</body>
</html>
