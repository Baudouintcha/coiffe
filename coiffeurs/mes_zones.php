<?php
// 1. SÉCURITÉ ET SESSION : Uniquement pour le coiffeur
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur n'est pas connecté ou n'est pas coiffeur, expulsion immédiate
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header("Location: connexion.php");
    exit();
}

require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header_coiffeur.php';

$id_coiffeur = $_SESSION['id_user'];
$message = "";

// 🎯 CONFIGURATION DES IDS DE TES VILLES PROCHES
$id_cotonou = 1; 
$id_calavi  = 2;

// 2. TRAITEMENT DU FORMULAIRE : Quand le coiffeur valide ses choix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_zones'])) {
    
    try {
        $pdo->beginTransaction();

        // Étape A : On purge les anciennes zones de ce coiffeur
        $delete_stmt = $pdo->prepare("DELETE FROM zones_coiffeur WHERE id_coiffeur = ?");
        $delete_stmt->execute([$id_coiffeur]);

        // Étape B : Si le coiffeur a coché au moins un quartier, on insère
        if (isset($_POST['quartiers_choisis']) && is_array($_POST['quartiers_choisis'])) {
            $insert_stmt = $pdo->prepare("INSERT INTO zones_coiffeur (id_coiffeur, id_quartier) VALUES (?, ?)");
            
            foreach ($_POST['quartiers_choisis'] as $id_quartier) {
                $insert_stmt->execute([$id_coiffeur, intval($id_quartier)]);
            }
        }

        $pdo->commit();
        $message = "<div class='alert alert-success text-center fw-bold'>✅ Vos zones d'intervention ont été mises à jour avec succès !</div>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger text-center fw-bold'>❌ Une erreur est survenue : " . $e->getMessage() . "</div>";
    }
}

// 3. RÉCUPÉRATION DES DONNÉES DE LA DB AVEC JOINTURE POUR LA VILLE

// Récupérer la ville d'origine du coiffeur
$profile_stmt = $pdo->prepare("SELECT ville FROM users WHERE id = ?");
$profile_stmt->execute([$id_coiffeur]);
$id_ville_coiffeur = intval($profile_stmt->fetchColumn());

// Requête SQL optimisée avec un JOIN pour récupérer le nom de la ville lié au quartier
if ($id_ville_coiffeur === $id_cotonou || $id_ville_coiffeur === $id_calavi) {
    $all_quartiers_stmt = $pdo->prepare("
        SELECT q.*, v.nom_ville 
        FROM quartiers q 
        JOIN villes v ON q.id_ville = v.id 
        WHERE q.id_ville IN (?, ?) 
        ORDER BY v.nom_ville ASC, q.nom_quartier ASC
    ");
    $all_quartiers_stmt->execute([$id_cotonou, $id_calavi]);
} else {
    $all_quartiers_stmt = $pdo->prepare("
        SELECT q.*, v.nom_ville 
        FROM quartiers q 
        JOIN villes v ON q.id_ville = v.id 
        WHERE q.id_ville = ? 
        ORDER BY q.nom_quartier ASC
    ");
    $all_quartiers_stmt->execute([$id_ville_coiffeur]);
}

$tous_les_quartiers = $all_quartiers_stmt->fetchAll();

// B. Liste des quartiers déjà cochés (CORRECTIF : conversion forcée en INT pour bloquer le bug)
$mes_zones_stmt = $pdo->prepare("SELECT id_quartier FROM zones_coiffeur WHERE id_coiffeur = ?");
$mes_zones_stmt->execute([$id_coiffeur]);
$raw_zones = $mes_zones_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
$mes_zones_actives = array_map('intval', $raw_zones); // <--- Forçage de type strict
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--gold);margin-bottom:2px;">
            <i class="bi bi-geo-alt me-2"></i>Mes Zones d'Intervention
        </h2>
        <p style="color:rgba(255,255,255,0.35);font-size:0.78rem;margin:0;">Cochez les quartiers où vous acceptez de vous déplacer</p>
    </div>
    <a href="/coiffons/index.php?page=dashboard_coiffeur"
       style="color:rgba(255,255,255,0.35);text-decoration:none;font-size:0.8rem;display:flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="<?= str_contains($message,'success') ? 'msg-success' : 'msg-danger' ?> mb-4">
        <?= strip_tags($message) ?>
    </div>
<?php endif; ?>

<div class="glass-cct p-4 mb-4" style="background:rgba(212,175,55,0.04);border:1px dashed rgba(212,175,55,0.2);border-radius:12px;">
    <i class="bi bi-lightbulb" style="color:var(--gold);margin-right:8px;"></i>
    <span style="color:rgba(255,255,255,0.65);font-size:0.82rem;">
        Plus vous couvrez de zones, plus vous apparaissez dans les résultats de recherche des clients.
    </span>
</div>

<form method="POST">
    <div class="row g-3 mb-4">
        <?php if (!empty($tous_les_quartiers)): ?>
            <?php foreach ($tous_les_quartiers as $q): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:12px;cursor:pointer;transition:all 0.2s;"
                           class="zone-label" for="quartier-<?= $q['id'] ?>">
                        <input type="checkbox" name="quartiers_choisis[]"
                               value="<?= $q['id'] ?>"
                               id="quartier-<?= $q['id'] ?>"
                               style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;"
                               <?= in_array(intval($q['id']), $mes_zones_actives, true) ? 'checked' : '' ?>>
                        <div>
                            <div style="font-size:0.82rem;font-weight:600;color:#fff;"><?= htmlspecialchars($q['nom_quartier']) ?></div>
                            <div style="font-size:0.68rem;color:rgba(255,255,255,0.35);"><?= htmlspecialchars($q['nom_ville']) ?></div>
                        </div>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12" style="text-align:center;color:rgba(255,255,255,0.3);font-size:0.85rem;padding:2rem;">
                Aucun quartier disponible pour votre zone géographique.
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center">
        <button type="submit" name="enregistrer_zones" class="btn-gold-cct" style="padding:12px 40px;border-radius:50px;font-size:0.88rem;">
            <i class="bi bi-shield-check me-2"></i>Enregistrer mes zones
        </button>
    </div>
</form>

<style>
    .zone-label:hover { border-color:rgba(212,175,55,0.3)!important;background:rgba(212,175,55,0.05)!important; }
    .glass-cct { background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px; }
    .btn-gold-cct { background:var(--gold);color:#000;font-weight:700;border:none;border-radius:10px;cursor:pointer;transition:all 0.2s; }
    .btn-gold-cct:hover { background:#c9a227;transform:translateY(-1px); }
    .msg-success { background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#6ee7b7;border-radius:10px;padding:10px 16px;font-size:0.85rem; }
    .msg-danger  { background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ffb3b3;border-radius:10px;padding:10px 16px;font-size:0.85rem; }
</style>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>