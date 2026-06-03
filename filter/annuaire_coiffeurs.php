<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Gestion des filtres de recherche
$ville_filtre = isset($_GET['ville']) ? intval($_GET['ville']) : 0;
$quartier_filtre = isset($_GET['id_quartier']) ? intval($_GET['id_quartier']) : 0;
$prix_filtre = isset($_GET['prix_max']) ? trim($_GET['prix_max']) : '';

// 🛠️ ANALYSE ET RESTRUCTURATION DE LA REQUÊTE : Utilisation de la table pivot zones_coiffeur pour le filtrage par quartier
$sql = "SELECT DISTINCT u.*, v.nom_ville, q.nom_quartier 
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN quartier q ON u.id_quartier = q.id"; // Correction de 'quartiers' en 'quartier'

// Si un quartier est sélectionné, on applique la jointure avec la table pivot zones_coiffeur
if ($quartier_filtre > 0) {
    $sql .= " INNER JOIN zones_coiffeur z ON u.id = z.id_coiffeur";
}

$sql .= " WHERE u.role = 'coiffeur' AND u.valide = 1";
$params = [];

if ($ville_filtre > 0) {
    $sql .= " AND u.ville = ? ";
    $params[] = $ville_filtre;
}

if ($quartier_filtre > 0) {
    $sql .= " AND z.id_quartier = ? ";
    $params[] = $quartier_filtre;
}

// Application des marges de prix réelles des coiffeurs de zone
if ($prix_filtre === 'eco') {
    $sql .= " AND u.tarif_base <= 1500 ";
} elseif ($prix_filtre === 'moyen') {
    $sql .= " AND u.tarif_base > 1500 AND u.tarif_base <= 3000 ";
} elseif ($prix_filtre === 'premium') {
    $sql .= " AND u.tarif_base > 3000 ";
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$coiffeurs = $stmt->fetchAll();

// Récupération de TOUTES les villes enregistrées dans la DB
$villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
$villes = $villes_stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">NOS COIFFEURS CERTIFIÉS</h2>

        <div class="row justify-content-center mb-5">
            <div class="col-md-12">
                <form method="GET" class="p-4 shadow rounded" style="background-color: #111; border: 1px solid var(--gold, #f39c12);">
                    <div class="row g-3">
                        
                        <div class="col-md-4">
                            <label class="text-white small mb-1">Filtrer par ville</label>
                            <select name="ville" id="villeSelectAnnuaire" class="form-select">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($villes as $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php echo ($ville_filtre == $v['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['nom_ville']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="text-white small mb-1">Filtrer par quartier d'intervention</label>
                            <select name="id_quartier" id="quartierSelectAnnuaire" class="form-select" <?php echo ($ville_filtre > 0) ? '' : 'disabled'; ?>>
                                <option value="">Tous les quartiers</option>
                                <?php 
                                if ($ville_filtre > 0) {
                                    // Correction de 'quartiers' en 'quartier'
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
                            <label class="text-white small mb-1">Budget maximum</label>
                            <select name="prix_max" class="form-select">
                                <option value="">Tous les tarifs</option>
                                <option value="eco" <?php echo ($prix_filtre === 'eco') ? 'selected' : ''; ?>>Moins de 1 500 FCFA</option>
                                <option value="moyen" <?php echo ($prix_filtre === 'moyen') ? 'selected' : ''; ?>>1 500 - 3 000 FCFA</option>
                                <option value="premium" <?php echo ($prix_filtre === 'premium') ? 'selected' : ''; ?>>Plus de 3 000 FCFA</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-gold w-100 fw-bold py-2"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($coiffeurs)): ?>
                <p class="text-secondary text-center py-5">Aucun coiffeur certifié ne correspond à cette recherche.</p>
            <?php else: ?>
                <?php foreach ($coiffeurs as $c): ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-lg" style="background-color: #111; border-radius: 20px; overflow: hidden; border: 1px solid var(--gold, #f39c12);">

                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <?php if (!empty($c['photo_profil']) && file_exists('../access/' . $c['photo_profil'])): ?>
                                        <img src="<?php echo '../access/' . $c['photo_profil']; ?>"
                                             alt="Photo de profil"
                                             class="rounded-circle border border-warning"
                                             style="width: 110px; height: 110px; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle text-warning" style="font-size: 5rem;"></i>
                                    <?php endif; ?>
                                </div>

                                <h4 class="text-warning mb-1">
                                    <?php echo htmlspecialchars(strtoupper($c['nom'] . ' ' . $c['prenom'])); ?>
                                </h4>

                                <p class="text-secondary small mb-2">
                                    <i class="bi bi-geo-alt-fill text-warning"></i>
                                    <?php 
                                    $nom_ville_affiche = !empty($c['nom_ville']) ? $c['nom_ville'] : 'Bénin';
                                    $nom_quartier_affiche = (!empty($c['nom_quartier']) && $c['id_quartier'] != 0) ? $c['nom_quartier'] : 'Centre-ville / Autre';
                                    echo htmlspecialchars($nom_ville_affiche . ' - ' . $nom_quartier_affiche); 
                                    ?>
                                </p>

                                <p class="text-white small mb-3 fw-bold">
                                    <span class="text-warning">Tarif :</span> à partir de <?php echo number_format($c['tarif_base'], 0, ',', ' '); ?> FCFA
                                </p>

                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9+]/', '', $c['telephone']); ?>"
                                   target="_blank"
                                   class="btn btn-outline-success btn-sm w-100 mb-3 py-2 fw-bold">
                                    <i class="bi bi-whatsapp"></i> <?php echo htmlspecialchars($c['telephone']); ?>
                                </a>
                            </div>

                            <div class="card-footer text-center bg-dark border-top border-warning">
                                <p class="text-white small fw-bold mb-2">Diplôme / Certification :</p>
                                <?php if (!empty($c['diplome']) && file_exists('../access/' . $c['diplome'])): ?>
                                    <a href="<?php echo '../access/' . $c['diplome']; ?>" target="_blank">
                                        <img src="<?php echo '../access/' . $c['diplome']; ?>"
                                             alt="Diplôme"
                                             class="img-fluid rounded shadow-sm"
                                             style="height: 140px; width: 100%; object-fit: cover; border: 1px solid var(--gold, #f39c12);">
                                    </a>
                                    <a href="<?php echo '../access/' . $c['diplome']; ?>" target="_blank" class="btn btn-gold btn-sm w-100 mt-2 fw-bold">
                                        <i class="bi bi-eye"></i> Voir en grand
                                    </a>
                                <?php else: ?>
                                    <div class="p-3 bg-secondary text-dark rounded small">
                                        <i class="bi bi-exclamation-triangle"></i> Aucun diplôme vérifié pour le moment.
                                    </div>
                                <?php endif; ?>
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

        // Fetch AJAX vers get_quartiers.php corrigé en cas de variations d'arborescence locale
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
        --gold: #f39c12;
    }
    .btn-gold {
        background-color: var(--gold);
        color: black;
        border: none;
    }
    .btn-gold:hover {
        background-color: #c99b2c;
    }
    .form-control,
    .form-select {
        background-color: #fff;
        color: #000;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>