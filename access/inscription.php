<?php
/**
 * access/inscription.php — Page d'inscription
 * Migration Design System v2.0 — Parcours Visiteur — Page 3/6
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — Seuls HTML/CSS/composants ont été modifiés.
 *     Aucune requête SQL, variable PHP, session, sécurité ou traitement fichier touché.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/csrf.php';

// Rôle pré-défini depuis l'URL — logique PHP inchangée
$role_predefini = isset($_GET['role']) ? trim($_GET['role']) : 'client';
if (!in_array($role_predefini, ['client', 'coiffeur'])) {
    $role_predefini = 'client';
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    csrf_verify();

    if (
        empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['sexe']) ||
        empty($_POST['email']) || empty($_POST['telephone']) || empty($_POST['password']) ||
        empty($_POST['role']) || empty($_POST['ville']) || empty($_POST['id_quartier'])
    ) {
        $message = "msg-danger::Champs obligatoires manquants.";
    } else {
        $nom         = strip_tags(trim($_POST['nom']));
        $prenom      = strip_tags(trim($_POST['prenom']));
        $sexe        = $_POST['sexe'];
        $email       = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $telephone   = strip_tags(trim($_POST['telephone']));
        $password    = $_POST['password'];
        $role        = $_POST['role'];
        $id_ville    = intval($_POST['ville']);
        $id_quartier = intval($_POST['id_quartier']);

        $errors = [];
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $nom))      $errors[] = "Nom invalide (lettres uniquement).";
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $prenom))   $errors[] = "Prénom invalide (lettres uniquement).";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = "Adresse email invalide.";
        if (!in_array($sexe, ['homme', 'femme']))              $errors[] = "Sexe invalide.";
        if (!in_array($role, ['client', 'coiffeur']))          $errors[] = "Rôle invalide.";
        if (strlen($password) < 6)                             $errors[] = "Le mot de passe doit faire au moins 6 caractères.";
        if ($id_ville <= 0)                                    $errors[] = "Veuillez sélectionner une ville.";
        if (!preg_match('/^\+?[0-9\s\-]{8,20}$/', $telephone)) $errors[] = "Numéro de téléphone invalide.";

        if (!empty($errors)) {
            $message = "msg-danger::" . implode('<br>', array_map('htmlspecialchars', $errors));
        } elseif ($role == 'coiffeur' && (!isset($_FILES['diplome']) || $_FILES['diplome']['error'] !== 0)) {
            $message = "msg-danger::Le diplôme est obligatoire pour les coiffeurs.";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $diplome_path  = NULL;

            if ($role == 'coiffeur') {
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
                        $message = "msg-danger::Format diplôme invalide (JPG, PNG, PDF).";
                    }
                } else {
                    $message = "msg-danger::Le diplôme est obligatoire pour les coiffeurs.";
                }
            }

            if (empty($message)) {
                $photo_profil_path = null;
                if (!empty($_POST['photo_profil_b64'])) {
                    $b64 = $_POST['photo_profil_b64'];
                    if (preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/', $b64, $m)) {
                        $ext     = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                        $imgData = base64_decode($m[2]);
                        $photo_profil_path = 'uploads/profil/' . uniqid('', true) . '.' . $ext;
                        $target2 = __DIR__ . '/../uploads/profil/';
                        if (!is_dir($target2)) mkdir($target2, 0755, true);
                        file_put_contents($target2 . basename($photo_profil_path), $imgData);
                    }
                }
            }

            if (empty($message)) {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                if ($check->rowCount() > 0) {
                    $message = "msg-danger::Cet email est déjà utilisé.";
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
                        header("Cache-Control: no-store, no-cache, must-revalidate");
                        header("Pragma: no-cache");
                        if ($role === 'client') {
                            $redirect_url = $_SESSION['redirect_url'] ?? '/coiffons/index.php?page=dashboard';
                            unset($_SESSION['redirect_url']);
                            header("Location: " . $redirect_url);
                        } elseif ($role === 'coiffeur') {
                            header("Location: /coiffons/index.php?page=dashboard_coiffeur");
                        } else {
                            header("Location: /coiffons/index.php");
                        }
                        exit();
                    } else {
                        $message = "msg-danger::Erreur lors de l'enregistrement.";
                    }
                }
            }
        }
    }
}

// Villes pour le dropdown — logique PHP inchangée
$villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
$villes_list = $villes_stmt->fetchAll();

// Image de fond — logique PHP inchangée
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image  = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';

// Préparer le message pour l'affichage DS
$msg_class   = '';
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
    <title>Inscription — Coiffe Chez Toi</title>

    <!-- Design System v2.0 — Ordre de chargement officiel -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">

    <!-- Bootstrap 5.3.3 (grille uniquement) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts — Polices officielles DS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques à la page inscription ── */
    /* Tous utilisent exclusivement des var(--*) du DS */

    /* Preview photo de profil */
    .avatar-preview {
        width: 52px; height: 52px;
        object-fit: cover;
        border-radius: var(--radius-circle);
        border: 2px solid var(--gold);
        margin-top: 6px;
        display: block;
    }

    /* Séparateur de section dans le formulaire */
    .form-section-title {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--gold);
        border-bottom: 1px solid var(--glass-border);
        padding-bottom: 6px;
        margin-bottom: 14px;
        margin-top: 4px;
    }

    /* Bloc diplôme coiffeur */
    .diplome-block {
        background: var(--glass-bg);
        border: 1px dashed var(--glass-border-md);
        border-radius: var(--radius-md);
        padding: 14px 16px;
        margin-bottom: 12px;
    }
    .diplome-note {
        font-size: .70rem;
        color: var(--text-muted);
        margin-top: 4px;
        display: block;
    }

    /* Wrapper fichier stylisé */
    .fc-dark[type="file"] {
        color: var(--text-secondary) !important;
        padding: 9px 14px !important;
        cursor: pointer;
    }
    .fc-dark[type="file"]::file-selector-button {
        background: var(--gold-dim);
        border: 1px solid rgba(212,175,55,.3);
        color: var(--gold);
        border-radius: var(--radius-sm);
        padding: 4px 12px;
        font-size: .75rem;
        font-weight: 600;
        cursor: pointer;
        margin-right: 10px;
        transition: var(--transition-fast);
    }
    .fc-dark[type="file"]::file-selector-button:hover {
        background: rgba(212,175,55,.2);
    }
    </style>
</head>
<body>

<!-- Fond flou fixe — image slider (logique PHP inchangée) -->
<div class="auth-bg"
     style="background-image:url('<?= htmlspecialchars($bg_image) ?>');"
     aria-hidden="true"></div>
<div class="auth-overlay" aria-hidden="true"></div>

<!-- Wrapper + AuthCard CL §34 -->
<main class="auth-wrapper" id="main-content">
    <div class="auth-card auth-card--lg fade-in-card" role="main">

        <!-- Lien retour -->
        <a href="/coiffons/index.php"
           class="btn-ghost btn-sm d-inline-flex align-items-center gap-1 mb-4"
           aria-label="Retour à l'accueil">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Accueil
        </a>

        <!-- Titre + indicateur de rôle -->
        <h1 style="font-family:var(--font-display);color:var(--gold);letter-spacing:2px;font-size:1.4rem;text-align:center;margin-bottom:6px;">
            REJOINDRE L'AVENTURE
        </h1>
        <p style="text-align:center;color:var(--text-muted);font-size:.82rem;margin-bottom:1.5rem;">
            Compte
            <strong style="color:var(--gold);">
                <?= $role_predefini === 'coiffeur' ? 'Coiffeur / Prestataire' : 'Client' ?>
            </strong>
            &nbsp;&middot;&nbsp;
            <a href="/coiffons/index.php?page=register&role=<?= $role_predefini === 'coiffeur' ? 'client' : 'coiffeur' ?>"
               style="color:var(--text-disabled);text-decoration:none;">
                Je suis <?= $role_predefini === 'coiffeur' ? 'un client' : 'un coiffeur' ?> →
            </a>
        </p>

        <!-- Message d'erreur / succès — .msg-danger DS §11 -->
        <?php if (!empty($msg_class) && !empty($msg_content)): ?>
            <div class="<?= htmlspecialchars($msg_class) ?> mb-4"
                 role="alert"
                 aria-live="polite">
                <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                <?= $msg_content ?>
            </div>
        <?php endif; ?>

        <!-- ══ FORMULAIRE — logique PHP inchangée, HTML migré DS ══ -->
        <form method="POST"
              enctype="multipart/form-data"
              id="inscriptionForm"
              novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="role"          value="<?= htmlspecialchars($role_predefini) ?>">
            <input type="hidden" name="photo_profil_b64" id="photo_profil_b64">
            <input type="hidden" name="diplome_b64"   id="diplome_b64">

            <!-- ── SECTION IDENTITÉ ── -->
            <div class="form-section-title">Identité</div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-4">
                    <label for="nom" class="form-label-cct">Nom</label>
                    <input type="text"
                           id="nom" name="nom"
                           class="fc-dark"
                           placeholder="Dossou"
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                           autocomplete="family-name"
                           required aria-required="true">
                </div>
                <div class="col-12 col-sm-4">
                    <label for="prenom" class="form-label-cct">Prénom</label>
                    <input type="text"
                           id="prenom" name="prenom"
                           class="fc-dark"
                           placeholder="Jean"
                           value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                           autocomplete="given-name"
                           required aria-required="true">
                </div>
                <div class="col-12 col-sm-4">
                    <label for="sexe" class="form-label-cct">Sexe</label>
                    <select id="sexe" name="sexe"
                            class="fc-dark"
                            required aria-required="true">
                        <option value="">—</option>
                        <option value="homme" <?= ($_POST['sexe'] ?? '') === 'homme' ? 'selected' : '' ?>>Homme</option>
                        <option value="femme" <?= ($_POST['sexe'] ?? '') === 'femme' ? 'selected' : '' ?>>Femme</option>
                    </select>
                </div>
            </div>

            <!-- ── SECTION CONTACT ── -->
            <div class="form-section-title">Contact</div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6">
                    <label for="email" class="form-label-cct">Email</label>
                    <input type="email"
                           id="email" name="email"
                           class="fc-dark"
                           placeholder="exemple@mail.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           autocomplete="email"
                           required aria-required="true">
                </div>
                <div class="col-12 col-sm-6">
                    <label for="telephone" class="form-label-cct">Téléphone</label>
                    <input type="tel"
                           id="telephone" name="telephone"
                           class="fc-dark"
                           placeholder="+229..."
                           value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                           autocomplete="tel"
                           required aria-required="true">
                </div>
            </div>

            <!-- ── SECTION LOCALISATION ── -->
            <div class="form-section-title">Localisation</div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6">
                    <label for="villeSelect" class="form-label-cct">Ville</label>
                    <select id="villeSelect" name="ville"
                            class="fc-dark"
                            required aria-required="true">
                        <option value="">Sélectionnez</option>
                        <?php foreach ($villes_list as $v): ?>
                            <option value="<?= $v['id'] ?>"
                                <?= intval($_POST['ville'] ?? 0) === intval($v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nom_ville']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6">
                    <label for="quartierSelect" class="form-label-cct">Quartier</label>
                    <select id="quartierSelect" name="id_quartier"
                            class="fc-dark"
                            required aria-required="true"
                            <?= empty($_POST['ville']) ? 'disabled' : '' ?>
                            aria-describedby="quartier-hint">
                        <option value="">Choisissez la ville d'abord</option>
                    </select>
                    <span id="quartier-hint" style="font-size:.68rem;color:var(--text-muted);margin-top:3px;display:block;">
                        Sélectionnez d'abord votre ville.
                    </span>
                </div>
            </div>

            <!-- ── SECTION PHOTO DE PROFIL ── -->
            <div class="form-section-title">Photo de profil</div>
            <div class="mb-3">
                <label for="photo_profil_input" class="form-label-cct">
                    Photo de profil
                    <span style="color:var(--text-muted);font-weight:400;">(Optionnel — compressée automatiquement)</span>
                </label>
                <input type="file"
                       id="photo_profil_input"
                       class="fc-dark"
                       accept="image/*"
                       onchange="compresserPhoto(this, 'photo_profil_b64', 'preview_profil')"
                       aria-describedby="photo-hint">
                <span id="photo-hint" class="diplome-note">
                    Formats acceptés : JPG, PNG, WebP. Maximum 5 Mo.
                </span>
                <div id="preview_profil" aria-live="polite" aria-label="Aperçu de la photo de profil"></div>
            </div>

            <!-- ── SECTION DIPLÔME (coiffeur uniquement) ── -->
            <?php if ($role_predefini === 'coiffeur'): ?>
            <div class="form-section-title">Certification professionnelle</div>
            <div class="diplome-block mb-3">
                <label for="diplome_input" class="form-label-cct">
                    Diplôme / Certification
                    <span style="color:var(--danger-icon);">*</span>
                </label>
                <input type="file"
                       id="diplome_input"
                       class="fc-dark"
                       accept="image/*,application/pdf"
                       onchange="compresserDiplome(this)"
                       required
                       aria-required="true"
                       aria-describedby="diplome-hint">
                <span id="diplome-hint" class="diplome-note">
                    <i class="bi bi-shield-check me-1" style="color:var(--gold);" aria-hidden="true"></i>
                    Formats : JPG, PNG, PDF. Votre diplôme sera vérifié par notre équipe avant activation.
                </span>
            </div>
            <?php endif; ?>

            <!-- ── SECTION MOT DE PASSE ── -->
            <div class="form-section-title">Sécurité</div>
            <div class="mb-4">
                <label for="pwField" class="form-label-cct">Mot de passe</label>
                <div class="input-group-cct">
                    <span class="ig-prefix" aria-hidden="true">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password"
                           id="pwField"
                           name="password"
                           class="fc-dark"
                           placeholder="Min. 6 caractères"
                           autocomplete="new-password"
                           minlength="6"
                           required aria-required="true">
                    <button type="button"
                            class="ig-suffix"
                            id="pwToggle"
                            onclick="togglePw()"
                            aria-label="Afficher ou masquer le mot de passe"
                            aria-pressed="false">
                        <i class="bi bi-eye" id="pwIcon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Bouton submit — PrimaryButton CL §12 -->
            <button type="submit"
                    id="submitBtn"
                    class="btn-gold w-100"
                    style="padding:13px;font-size:.95rem;">
                CRÉER MON COMPTE
            </button>

        </form>

        <!-- Lien connexion -->
        <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;color:var(--text-muted);">
            Déjà inscrit ?
            <a href="/coiffons/access/connexion.php"
               style="color:var(--gold);text-decoration:none;font-weight:700;">
                Se connecter
            </a>
        </p>

    </div>
</main>

<!-- ══ JAVASCRIPT — strictement UI, aucune logique métier modifiée ══ -->
<script>
// ── Protection retour arrière — inchangé ──
history.pushState(null, null, location.href);
window.addEventListener('popstate', () => {
    history.pushState(null, null, location.href);
});

// ── Restauration du quartier si ville pré-remplie (après erreur PHP) — inchangé ──
const villePreRemplie   = '<?= intval($_POST['ville'] ?? 0) ?>';
const quartierPreRempli = '<?= intval($_POST['id_quartier'] ?? 0) ?>';

document.addEventListener('DOMContentLoaded', () => {
    if (villePreRemplie && villePreRemplie !== '0') {
        chargerQuartiers(villePreRemplie, quartierPreRempli);
    }
    // Mettre à jour l'hint quartier quand la ville change
    document.getElementById('villeSelect').addEventListener('change', function() {
        chargerQuartiers(this.value, null);
        const hint = document.getElementById('quartier-hint');
        if (hint) hint.style.display = this.value ? 'none' : 'block';
    });
});

// ── Chargement AJAX des quartiers — inchangé ──
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
                if (quartierASelectionner && q.id == quartierASelectionner) o.selected = true;
                qs.appendChild(o);
            });
        })
        .catch(() => {
            qs.innerHTML = '<option value="0">Centre-ville</option>';
            qs.disabled = false;
        });
}

// ── Toggle mot de passe — UI uniquement ──
function togglePw() {
    const field  = document.getElementById('pwField');
    const icon   = document.getElementById('pwIcon');
    const button = document.getElementById('pwToggle');
    const isHidden = field.type === 'password';
    field.type = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    button.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
}

// ── Compression Canvas — inchangé ──
function compresserImageCanvas(file, qualite, maxLargeur, callback) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let w = img.width, h = img.height;
            if (w > maxLargeur) { h = Math.round(h * maxLargeur / w); w = maxLargeur; }
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            callback(canvas.toDataURL('image/jpeg', qualite));
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function compresserPhoto(input, champCible, previewId) {
    const file = input.files[0];
    if (!file) return;
    const traiter = (b64) => {
        document.getElementById(champCible).value = b64;
        afficherPreview(previewId, b64);
    };
    if (file.size <= 819200) {
        const r = new FileReader();
        r.onload = e => traiter(e.target.result);
        r.readAsDataURL(file);
    } else {
        compresserImageCanvas(file, 0.75, 800, traiter);
    }
}

function compresserDiplome(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.type === 'application/pdf') {
        const r = new FileReader();
        r.onload = e => { document.getElementById('diplome_b64').value = e.target.result; };
        r.readAsDataURL(file);
        return;
    }
    const traiter = (b64) => { document.getElementById('diplome_b64').value = b64; };
    if (file.size <= 2097152) {
        const r = new FileReader();
        r.onload = e => traiter(e.target.result);
        r.readAsDataURL(file);
    } else {
        compresserImageCanvas(file, 0.80, 1200, traiter);
    }
}

// ── Aperçu photo de profil — DS Avatar ──
function afficherPreview(previewId, src) {
    const el = document.getElementById(previewId);
    if (!el) return;
    el.innerHTML = `<img src="${src}" class="avatar-preview" alt="Aperçu de votre photo de profil">`;
}
</script>

</body>
</html>
