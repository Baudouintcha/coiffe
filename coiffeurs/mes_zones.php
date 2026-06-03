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

require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';

$id_coiffeur = $_SESSION['id_user'];
$message = "";

// 2. TRAITEMENT DU FORMULAIRE : Quand le coiffeur valide ses choix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_zones'])) {
    
    try {
        // Début d'une transaction pour s'assurer que tout se passe bien ou rien
        $pdo->beginTransaction();

        // Étape A : On purge les anciennes zones de ce coiffeur pour repartir sur du propre
        $delete_stmt = $pdo->prepare("DELETE FROM zones_coiffeur WHERE id_coiffeur = ?");
        $delete_stmt->execute([$id_coiffeur]);

        // Étape B : Si le coiffeur a coché au moins un quartier, on insère ses choix
        if (isset($_POST['quartiers_choisis']) && is_array($_POST['quartiers_choisis'])) {
            $insert_stmt = $pdo->prepare("INSERT INTO zones_coiffeur (id_coiffeur, id_quartier) VALUES (?, ?)");
            
            foreach ($_POST['quartiers_choisis'] as $id_quartier) {
                $insert_stmt->execute([$id_coiffeur, intval($id_quartier)]);
            }
        }

        // On valide définitivement les changements dans la base de données
        $pdo->commit();
        
        $message = "<div class='alert alert-success text-center fw-bold'>✅ Vos zones d'intervention ont été mises à jour avec succès !</div>";
        
    } catch (Exception $e) {
        // En cas de bug, on annule tout pour ne pas corrompre la base
        $pdo->rollBack();
        $message = "<div class='alert alert-danger text-center fw-bold'>❌ Une erreur est survenue : " . $e->getMessage() . "</div>";
    }
}

// 3. RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE GRAPHIQUE

// A. Liste de TOUS les quartiers existants dans l'application
$all_quartiers_stmt = $pdo->query("SELECT * FROM quartier ORDER BY nom_quartier ASC");
$tous_les_quartiers = $all_quartiers_stmt->fetchAll();

// B. Liste des quartiers que ce coiffeur a DEJA cochés par le passé (pour les pré-cocher)
$mes_zones_stmt = $pdo->prepare("SELECT id_quartier FROM zones_coiffeur WHERE id_coiffeur = ?");
$mes_zones_stmt->execute([$id_coiffeur]);
// fetchAll(PDO::FETCH_COLUMN) permet d'obtenir un tableau simple d'identifiants : [1, 3, 12]
$mes_zones_actives = $mes_zones_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
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
                        
                        <div class="p-3 mb-4 rounded text-center" style="background-color: #161616; border: 1px dashed #333;">
                            <span class="text-muted small">💡 Plus vous couvrez de zones, plus vous apparaissez dans les résultats de recherche des clients de votre région !</span>
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
                                                       <?php echo in_array($q['id'], $mes_zones_actives) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-white small fw-semibold cursor-pointer w-100" for="quartier-<?php echo $q['id']; ?>">
                                                    <?php echo htmlspecialchars($q['nom_quartier']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center text-muted py-4">
                                    Aucun quartier configuré dans la base de données. Contactez l'administrateur.
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
    :root {
        --gold: #f39c12;
    }
    .btn-gold {
        background-color: var(--gold) !important;
        color: #000 !important;
        border: none !important;
        transition: 0.3s ease-in-out;
    }
    .btn-gold:hover {
        background-color: #d35400 !important;
        transform: translateY(-2px);
    }
    /* Style visuel des blocs de quartiers */
    .zone-card:hover {
        border-color: var(--gold) !important;
        background-color: #222 !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    /* Personnalisation graphique des checkboxes Bootstrap en version dorée */
    .checkbox-gold {
        cursor: pointer;
        width: 1.2em;
        height: 1.2em;
    }
    .checkbox-gold:checked {
        background-color: var(--gold) !important;
        border-color: var(--gold) !important;
    }
    .checkbox-gold:focus {
        box-shadow: 0 0 0 0.25rem rgba(243, 156, 18, 0.25) !important;
        border-color: var(--gold) !important;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>