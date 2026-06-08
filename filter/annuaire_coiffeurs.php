<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Activation des erreurs pour voir immédiatement si un nom de table cloche
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gestion des filtres de recherche
$ville_filtre = isset($_GET['ville']) ? intval($_GET['ville']) : 0;
$quartier_filtre = isset($_GET['id_quartier']) ? intval($_GET['id_quartier']) : 0;
$prix_filtre = isset($_GET['prix_max']) ? trim($_GET['prix_max']) : '';
$prix_saisie = isset($_GET['prix_saisie']) ? intval($_GET['prix_saisie']) : 0;

// La recherche est considérée comme active si au moins un critère principal est soumis
$a_recherche = isset($_GET['ville']) || isset($_GET['prix_max']) || isset($_GET['prix_saisie']) || isset($_GET['id_quartier']);

// 🛠️ REQUÊTE INCASSABLE ET ULTRA-SOUPLE (LEFT JOIN partout)
$sql = "SELECT DISTINCT u.*, v.nom_ville, q.nom_quartier 
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        LEFT JOIN zones_coiffeur z ON u.id = z.id_coiffeur
        WHERE u.role = 'coiffeur'"; 

$params = [];

// 1. Filtre Ville (Optionnel)
if ($ville_filtre > 0) {
    $sql .= " AND u.ville = ? ";
    $params[] = $ville_filtre;
}

// 2. Filtre Quartier (Optionnel - ne bloque pas si la table quartier est incomplète)
if ($quartier_filtre > 0) {
    $sql .= " AND (u.id_quartier = ? OR z.id_quartier = ?)";
    $params[] = $quartier_filtre;
    $params[] = $quartier_filtre;
}

// 3. GESTION ÉLASTIQUE DU PRIX (OPTIONNEL)
// Si l'utilisateur a saisi un budget, on lui montre les coiffeurs "autour" de ce prix (+/- 1500 FCFA) pour élargir ses propositions
if ($prix_saisie > 0) {
    $marge_basse = max(0, $prix_saisie - 1500); // Pour ne pas descendre en dessous de 0
    $marge_haute = $prix_saisie + 2000;         // On élargit vers le haut pour proposer les coiffeurs qui commencent un peu au-dessus
    
    $sql .= " AND u.tarif_base BETWEEN ? AND ? ";
    $params[] = $marge_basse;
    $params[] = $marge_haute;

} elseif (!empty($prix_filtre)) {
    // Si la saisie manuelle est vide, on regarde si une catégorie rapide a été cochée
    if ($prix_filtre === 'eco') {
        $sql .= " AND u.tarif_base <= 2000 "; // Ajusté pour donner plus de choix
    } elseif ($prix_filtre === 'moyen') {
        $sql .= " AND u.tarif_base BETWEEN 1500 AND 3500 ";
    } elseif ($prix_filtre === 'premium') {
        $sql .= " AND u.tarif_base > 3000 ";
    }
}

$sql .= " ORDER BY u.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coiffeurs = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-3 fw-bold'>⚠️ Problème de base de données : " . $e->getMessage() . "</div>";
    $coiffeurs = [];
}

// Récupération de TOUTES les villes pour alimenter le sélecteur
try {
    $villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
    $villes = $villes_stmt->fetchAll();
} catch (PDOException $e) {
    $villes = [];
}
?>

<section class="py-5 positions-relative" style="background: linear-gradient(135deg, #0a0a0a 0%, #161616 100%); min-height: 95vh;">
    <div class="container mt-2">
        
        <div class="text-center mb-5">
            <h2 class="text-warning fw-bold display-5" style="letter-spacing: 3px; font-family: 'Playfair Display', serif;">
                TROUVEZ VOTRE ARTISTE CAPILLAIRE
            </h2>
            <p class="text-secondary tracking-widest">Le raffinement de la coiffure privée, directement chez vous.</p>
        </div>

        <div class="row justify-content-center mb-5">
            <div class="col-lg-11">
                <form method="GET" class="p-4 shadow-lg rounded-4" style="background-color: #121212; border: 1px solid rgba(212, 175, 55, 0.25);">
                    <div class="row g-3 align-items-end">
                        
                        <div class="col-md-3">
                            <label class="text-secondary small fw-bold mb-2 text-uppercase tracking-wider">Ville</label>
                            <select name="ville" id="villeSelectAnnuaire" class="form-select custom-input py-2">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($villes as $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php echo ($ville_filtre == $v['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['nom_ville']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="text-secondary small fw-bold mb-2 text-uppercase tracking-wider">Quartier (Optionnel)</label>
                            <select name="id_quartier" id="quartierSelectAnnuaire" class="form-select custom-input py-2" <?php echo ($ville_filtre > 0) ? '' : 'disabled'; ?>>
                                <option value="">Tous les quartiers</option>
                                <?php 
                                if ($ville_filtre > 0) {
                                    $q_stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartier WHERE id_ville = ? ORDER BY nom_quartier ASC");
                                    $q_stmt->execute([$ville_filtre]);
                                    while ($q = $q_stmt->fetch()) {
                                        $selected = ($quartier_filtre == $q['id']) ? 'selected' : '';
                                        echo "<option value='".$q['id']."' $selected>".htmlspecialchars($q['nom_quartier'])."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-secondary small fw-bold mb-2 text-uppercase tracking-wider">Par Catégorie</label>
                            <select name="prix_max" class="form-select custom-input py-2">
                                <option value="">Tous les tarifs</option>
                                <option value="eco" <?php echo ($prix_filtre === 'eco') ? 'selected' : ''; ?>>Économique (≤ 1 500 F)</option>
                                <option value="moyen" <?php echo ($prix_filtre === 'moyen') ? 'selected' : ''; ?>>Standard (1 500 - 3 000 F)</option>
                                <option value="premium" <?php echo ($prix_filtre === 'premium') ? 'selected' : ''; ?>>Premium (> 3 000 F)</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-secondary small fw-bold mb-2 text-uppercase tracking-wider">Budget Max (FCFA)</label>
                            <input type="number" name="prix_saisie" class="form-control custom-input py-2" placeholder="Ex: 2500" value="<?php echo $prix_saisie > 0 ? $prix_saisie : ''; ?>">
                        </div>
                        
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-luxury-gold w-100 py-2 rounded-3">
                                <i class="bi bi-search fs-5"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            
            <?php if (!$a_recherche): ?>
                <div class="col-md-10 text-center py-5 fade-in">
                    <div class="mb-4">
                        <i class="bi bi-stars text-warning display-1 opacity-50"></i>
                    </div>
                    <h3 class="text-white fw-light mb-3">Votre recherche sur-mesure commence ici</h3>
                    <p class="text-secondary mx-auto style-paragraph" style="max-width: 600px;">
                        Sélectionnez votre zone et votre budget ci-dessus pour afficher la liste de nos coiffeurs certifiés. Notre algorithme de matching trouvera les meilleurs experts disponibles pour vous.
                    </p>
                    <div class="d-flex justify-content-center gap-4 mt-4 text-secondary small text-uppercase tracking-widest">
                        <span><i class="bi bi-shield-check text-warning"></i> Diplômes Vérifiés</span>
                        <span><i class="bi bi-clock text-warning"></i> À l'heure chez vous</span>
                        <span><i class="bi bi-wallet2 text-warning"></i> Prix Transparents</span>
                    </div>
                </div>

            <?php elseif (empty($coiffeurs)): ?>
                <div class="col-md-8 text-center py-5">
                    <div class="p-5 rounded-4" style="background-color: #121212; border: 1px dashed rgba(212,175,55,0.2);">
                        <i class="bi bi-emoji-frown text-secondary display-3 mb-3"></i>
                        <h4 class="text-white fw-light">Aucun coiffeur trouvé</h4>
                        <p class="text-secondary small mb-0">Essayez d'élargir vos filtres de prix ou de choisir la ville entière sans spécifier de quartier.</p>
                    </div>
                </div>

            <?php else: ?>
                <?php foreach ($coiffeurs as $c): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 card-luxury shadow-lg">
                            
                            <div class="card-body p-4 text-center">
                                <div class="position-relative d-inline-block mb-3">
                                    <?php if (!empty($c['photo_profil']) && file_exists('../access/' . $c['photo_profil'])): ?>
                                        <img src="<?php echo '../access/' . $c['photo_profil']; ?>" alt="Profil" class="profile-img">
                                    <?php else: ?>
                                        <div class="profile-placeholder">
                                            <i class="bi bi-person text-warning fs-1"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="badge-status"><i class="bi bi-check-circle-fill"></i> Certifié</span>
                                </div>

                                <h4 class="text-white mb-1 fw-bold tracking-wide">
                                    <?php echo htmlspecialchars(strtoupper($c['nom'] . ' ' . $c['prenom'])); ?>
                                </h4>

                                <p class="text-secondary small mb-3">
                                    <i class="bi bi-geo-alt text-warning me-1"></i>
                                    <?php 
                                    $nom_ville_affiche = !empty($c['nom_ville']) ? $c['nom_ville'] : 'Bénin';
                                    $nom_quartier_affiche = (!empty($c['nom_quartier']) && $c['id_quartier'] != 0) ? $c['nom_quartier'] : 'Toute la ville';
                                    echo htmlspecialchars($nom_ville_affiche . ' • ' . $nom_quartier_affiche); 
                                    ?>
                                </p>

                                <div class="price-box mb-4">
                                    <span class="text-secondary text-uppercase tracking-wider small-title">Tarif de Base</span>
                                    <span class="price-amount text-warning"><?php echo number_format($c['tarif_base'], 0, ',', ' '); ?> <small style="font-size: 0.9rem;">FCFA</small></span>
                                </div>

                                <a href="/coiffons/client/profil_public.php?id=<?php echo $c['id']; ?>" class="btn btn-luxury-gold w-100 py-2.5 fw-bold text-uppercase tracking-wider btn-action mb-3">
                                    <i class="bi bi-calendar3 me-2"></i> Réserver un créneau
                                </a>
                            </div>

                            <div class="card-footer px-4 py-3 border-0" style="background-color: #171717;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="small text-secondary fw-bold">Vérification :</span>
                                    <?php if (!empty($c['diplome']) && file_exists('../access/' . $c['diplome'])): ?>
                                        <a href="<?php echo '../access/' . $c['diplome']; ?>" target="_blank" class="text-warning text-decoration-none small d-flex align-items-center gap-1 link-hover">
                                            <i class="bi bi-file-earmark-image"></i> Voir le Diplôme Certifié
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Aucun document fourni</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<script>
    document.getElementById('villeSelectAnnuaire').addEventListener('change', function() {
        var idVille = this.value;
        var quartierSelect = document.getElementById('quartierSelectAnnuaire');
        
        if (!idVille) {
            quartierSelect.innerHTML = '<option value="">Tous les quartiers</option>';
            quartierSelect.disabled = true;
            return;
        }

        fetch('../access/get_quartiers.php?id_ville=' + idVille)
            .then(response => {
                if (!response.ok) throw new Error("Fichier introuvable");
                return response.json();
            })
            .then(data => {
                quartierSelect.innerHTML = '<option value="">Tous les quartiers</option>';
                quartierSelect.disabled = false;

                data.forEach(quartier => {
                    var option = document.createElement('option');
                    option.value = quartier.id;
                    option.textContent = quartier.nom_quartier;
                    quartierSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erreur AJAX Annuaire:', error);
                fetch('get_quartiers.php?id_ville=' + idVille)
                    .then(res => res.json())
                    .then(data => {
                        quartierSelect.innerHTML = '<option value="">Tous les quartiers</option>';
                        quartierSelect.disabled = false;
                        data.forEach(quartier => {
                            var option = document.createElement('option');
                            option.value = quartier.id;
                            option.textContent = quartier.nom_quartier;
                            quartierSelect.appendChild(option);
                        });
                    }).catch(err => console.log("Échec total du chargement"));
            });
    });
</script>

<style>
    :root {
        --gold-premium: #D4AF37;
        --gold-hover: #AA7C11;
        --dark-card: #141414;
    }

    /* Inputs Personnalisés Chic */
    .custom-input {
        background-color: #1e1e1e !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        color: white !important;
        border-radius: 8px !important;
        transition: 0.3s ease;
    }
    .custom-input:focus {
        border-color: var(--gold-premium) !important;
        box-shadow: 0 0 8px rgba(212,175,55,0.2) !important;
    }

    /* Bouton Or de Luxe */
    .btn-luxury-gold {
        background: linear-gradient(135deg, var(--gold-premium) 0%, #B38F1D 100%);
        color: black !important;
        font-weight: 700;
        border: none;
        transition: 0.3s all ease;
    }
    .btn-luxury-gold:hover {
        background: linear-gradient(135deg, #FFF 0%, var(--gold-premium) 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(212,175,55,0.3);
    }

    /* Cartes Coiffeurs Style Écrin */
    .card-luxury {
        background-color: var(--dark-card);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 16px;
        overflow: hidden;
        transition: 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .card-luxury:hover {
        transform: translateY(-8px);
        border-color: rgba(212, 175, 55, 0.4);
        box-shadow: 0 15px 35px rgba(0,0,0,0.6);
    }

    /* Profil Images rondes épurées */
    .profile-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--gold-premium);
        padding: 3px;
        background-color: #000;
    }
    .profile-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: #222;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed rgba(212,175,55,0.3);
        margin: 0 auto;
    }

    /* Badge Statut Certifié */
    .badge-status {
        position: absolute;
        bottom: 0;
        right: -10px;
        background-color: #198754;
        color: white;
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }

    /* Boîtier de prix */
    .price-box {
        background-color: rgba(255,255,255,0.02);
        padding: 12px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.03);
    }
    .small-title {
        display: block;
        font-size: 0.7rem;
        color: #777;
    }
    .price-amount {
        font-size: 1.4rem;
        font-weight: 800;
        display: block;
    }

    .link-hover:hover {
        color: white !important;
    }
    .style-paragraph {
        line-height: 1.7;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>