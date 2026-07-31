<?php
/**
 * client/laisser_avis.php — Formulaire d'avis après RDV
 * Migration Design System v2.0 — Parcours Client — Page 4
 *
 * CORRECTIONS CRITIQUES APPLIQUÉES :
 * 1. $SESSION['id_client'] → $_SESSION['id_user'] (bug identifié dans l'audit)
 * 2. Connexion PDO locale supprimée → utilisation de security/config.php
 * 3. HTML/CSS entièrement migré vers le DS v2.0
 * 4. Logique métier (requêtes SQL, vérifications sécurité) : INCHANGÉE
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

// ── SÉCURITÉ — corrigé : id_client → id_user ──
if (!isset($_SESSION['id_user'])) {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_client = $_SESSION['id_user'];

// Vérification ID RDV — inchangée
if (!isset($_GET['rdv']) || empty($_GET['rdv'])) {
    header('Location: /coiffons/client/mes_rendezvous.php');
    exit();
}

$id_rdv = intval($_GET['rdv']);

// Requête de sécurité — SQL inchangé
$query = "
    SELECT r.id, r.id_coiffeur, u.nom AS nom_coiffeur
    FROM rendez_vous r
    JOIN users u ON r.id_coiffeur = u.id
    LEFT JOIN commentaires c ON r.id = c.id_rendez_vous
    WHERE r.id = ? AND r.client_id = ? AND CONCAT(r.date_rdv, ' ', r.heure_debut) < NOW()
";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_rdv, $id_client]);
$rdv = $stmt->fetch();

if (!$rdv) {
    // Avis impossible → rediriger proprement
    header('Location: /coiffons/client/mes_rendezvous.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis — Coiffe Chez Toi</title>

    <!-- Design System v2.0 -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques à laisser_avis.php ── */
    .avis-page          { padding-top:calc(var(--navbar-height) + 2rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }

    /* Star rating — radio boutons stylisés */
    .rating-row         { display:flex; flex-direction:row-reverse; justify-content:flex-start; gap:4px; margin:12px 0; }
    .rating-row input   { display:none; }
    .rating-row label   { font-size:2.2rem; color:var(--glass-border-md); cursor:pointer; transition:color .15s; line-height:1; }
    .rating-row label:hover,
    .rating-row label:hover ~ label,
    .rating-row input:checked ~ label { color:var(--gold); }

    /* Bloc signalement */
    .plainte-block      { background:rgba(255,193,7,0.06); border:1px solid rgba(255,193,7,0.20); border-radius:var(--radius-md); padding:14px 16px; margin-top:1rem; }
    .plainte-input-wrap { display:none; margin-top:10px; }
    #signaler:checked ~ .plainte-input-wrap { display:block; }
    </style>
</head>
<body>

<!-- Navbar -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<main class="avis-page page-transition" id="main-content">
    <div class="container" style="max-width:640px;">

        <!-- Lien retour -->
        <div class="mb-4">
            <a href="/coiffons/client/mes_rendezvous.php"
               class="btn-ghost btn-sm d-inline-flex align-items-center gap-1"
               aria-label="Retour à mes réservations">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Mes réservations
            </a>
        </div>

        <!-- Titre -->
        <h1 style="font-family:var(--font-display);font-size:clamp(1.4rem,3vw,1.8rem);font-weight:700;color:var(--gold);letter-spacing:1px;margin-bottom:.5rem;">
            Votre avis
        </h1>
        <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:2rem;">
            Comment s'est passée votre prestation avec
            <strong style="color:var(--text-primary);">
                <?= htmlspecialchars($rdv['nom_coiffeur']) ?>
            </strong> ?
        </p>

        <!-- Formulaire d'avis — logique PHP inchangée -->
        <div class="glass-cct p-4 fade-in-card">
            <form action="/coiffons/traitement_avis.php" method="POST" novalidate>
                <input type="hidden" name="id_rendez_vous" value="<?= $rdv['id'] ?>">
                <input type="hidden" name="id_coiffeur"    value="<?= $rdv['id_coiffeur'] ?>">

                <!-- Star rating -->
                <div class="form-section-title" style="font-size:.72rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--gold);border-bottom:1px solid var(--glass-border);padding-bottom:6px;margin-bottom:14px;">
                    Note globale
                </div>

                <div class="rating-row" role="radiogroup" aria-label="Note de 1 à 5 étoiles">
                    <input type="radio" id="star5" name="note" value="5" required>
                    <label for="star5" aria-label="5 étoiles">★</label>
                    <input type="radio" id="star4" name="note" value="4">
                    <label for="star4" aria-label="4 étoiles">★</label>
                    <input type="radio" id="star3" name="note" value="3">
                    <label for="star3" aria-label="3 étoiles">★</label>
                    <input type="radio" id="star2" name="note" value="2">
                    <label for="star2" aria-label="2 étoiles">★</label>
                    <input type="radio" id="star1" name="note" value="1">
                    <label for="star1" aria-label="1 étoile">★</label>
                </div>

                <!-- Commentaire -->
                <div class="mt-4">
                    <label for="commentaire" class="form-label-cct">
                        Votre commentaire public
                        <span style="color:var(--danger-icon);">*</span>
                    </label>
                    <textarea id="commentaire"
                              name="commentaire"
                              class="fc-dark"
                              rows="4"
                              placeholder="Partagez votre expérience avec les autres clients..."
                              required
                              aria-required="true"
                              style="resize:vertical;"></textarea>
                </div>

                <!-- Bloc signalement -->
                <div class="plainte-block mt-3">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <input type="checkbox"
                               id="signaler"
                               name="signaler_probleme"
                               value="1"
                               style="width:16px;height:16px;accent-color:var(--warning-color);margin-top:2px;flex-shrink:0;cursor:pointer;">
                        <label for="signaler"
                               style="color:var(--warning-color);font-weight:700;font-size:.85rem;cursor:pointer;">
                            ⚠️ Signaler un problème grave à l'administration
                        </label>
                    </div>
                    <div class="plainte-input-wrap">
                        <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:8px;">
                            Le coiffeur ne verra pas ce texte. Seul l'admin mènera l'enquête.
                        </p>
                        <textarea name="motif_plainte"
                                  class="fc-dark"
                                  rows="3"
                                  placeholder="Expliquez ce qui s'est mal passé (absence, comportement inapproprié...)..."
                                  style="resize:vertical;"></textarea>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="btn-gold w-100 mt-4"
                        style="padding:13px;font-size:.95rem;">
                    <i class="bi bi-send me-2" aria-hidden="true"></i>
                    Envoyer mon avis
                </button>

            </form>
        </div>

    </div>
</main>

<!-- Footer + Bottom Nav -->
<?php
include __DIR__ . '/../views/components/footer_global.php';
$current_page = 'laisser_avis.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
