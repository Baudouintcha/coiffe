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
include __DIR__ . '/../layout/header.php';

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

<section class="py-5" style="background-color: #000; min-height: 90vh; color: #fff;">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <?php echo $message; ?>

                <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid var(--gold, #f39c12); border-radius: 20px;">
                    
                    <div class="text-center mb-4">
                        <h2 class="text-warning fw-bold" style="letter-spacing: 1px;">PÉRIMÈTRE D'INTERVENTION</h2>
                        <p class="text-secondary small">Cochez les quartiers dans lesquels vous acceptez de vous déplacer pour travailler.</p>
                    </div>

                    <form method="POST">
                        
                        <div class="p-3 mb-4 rounded text-center" style="background-color: #1a1610; border: 1px solid rgba(243, 156, 18, 0.25);">
                            <span style="color: #f1f1f1; font-size: 0.9rem; font-weight: 500;">
                                <span style="color: var(--gold);">💡 Conseil :</span> Plus vous couvrez de zones, plus vous apparaîtrez dans les résultats de recherche des clients de votre région élargie !
                            </span>
                        </div>

                        <div class="row g-3 px-2 mb-4">
                            <?php if (!empty($tous_les_quartiers)): ?>
                                <?php foreach ($tous_les_quartiers as $q): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="p-3 rounded zone-card" style="background-color: #1a1a1a; border: 1px solid #222; transition: 0.2s;">
                                            <div class="form-check d-flex align-items-center gap-2 m-0">
                                                <input class="form-check-input checkbox-gold" type="checkbox" 
                                                       name="quartiers_choisis[]" 
                                                       value="<?php echo $q['id']; ?>" 
                                                       id="quartier-<?php echo $q['id']; ?>"
                                                       <?php echo in_array(intval($q['id']), $mes_zones_actives, true) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-white small fw-semibold cursor-pointer w-100" for="quartier-<?php echo $q['id']; ?>">
                                                    <?php echo htmlspecialchars($q['nom_quartier']); ?> 
                                                    <span class="d-block text-muted style-subcity" style="font-size: 0.75rem;">(<?php echo htmlspecialchars($q['nom_ville']); ?>)</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center text-muted py-4">
                                    Aucun quartier disponible pour votre zone géographique actuelle dans la base de données.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 text-center mt-4">
                            <button type="submit" name="enregistrer_zones" class="btn btn-gold px-5 py-2.5 fw-bold" style="border-radius: 8px; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-check me-2"></i> ENREGISTRER MON PÉRIMÈTRE
                            </button>
                        </div>

                    </form>

                </div>

            </div>
        </div>
    </div>
</section>

<style>
    :root { --gold: #f39c12; }
    .btn-gold { background-color: var(--gold) !important; color: #000 !important; border: none !important; transition: 0.3s ease-in-out; }
    .btn-gold:hover { background-color: #d35400 !important; transform: translateY(-2px); }
    .zone-card:hover { border-color: var(--gold) !important; background-color: #222 !important; }
    .cursor-pointer { cursor: pointer; }
    .checkbox-gold { cursor: pointer; width: 1.2em; height: 1.2em; }
    .checkbox-gold:checked { background-color: var(--gold) !important; border-color: var(--gold) !important; }
    .checkbox-gold:focus { box-shadow: 0 0 0 0.25rem rgba(243, 156, 18, 0.25) !important; border-color: var(--gold) !important; }
    .style-subcity { opacity: 0.6; font-style: italic; }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>