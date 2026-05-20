<?php
include 'header.php';
require 'config.php';

// Gestion des filtres de recherche
$ville_filtre = isset($_GET['ville']) ? trim($_GET['ville']) : '';
$quartier_filtre = isset($_GET['quartier']) ? trim($_GET['quartier']) : '';

// Construction de la requête SQL
$sql = "SELECT * FROM users WHERE role = 'coiffeur' ";

$params = [];
if (!empty($ville_filtre)) {
    $sql .= " AND ville = ? ";
    $params[] = $ville_filtre;
}
if (!empty($quartier_filtre)) {
    $sql .= " AND quartier LIKE ? ";
    $params[] = '%' . $quartier_filtre . '%';
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$coiffeurs = $stmt->fetchAll();

// Récupération de la liste des villes pour la liste déroulante du filtre
$villes_stmt = $pdo->query("SELECT DISTINCT ville FROM users WHERE role = 'coiffeur' ORDER BY ville ASC");
$villes = $villes_stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">NOS COIFFEURS CERTIFIÉS</h2>

        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <form method="GET" class="p-4 shadow rounded" style="background-color: #111; border: 1px solid var(--gold);">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="text-white small mb-1">Filtrer par ville</label>
                            <select name="ville" class="form-select">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($villes as $v): ?>
                                    <?php if (!empty($v['ville'])): ?>
                                        <option value="<?php echo htmlspecialchars($v['ville']); ?>" <?php echo ($ville_filtre == $v['ville']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($v['ville']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="text-white small mb-1">Rechercher un quartier</label>
                            <input type="text" name="quartier" class="form-control" placeholder="Ex: Gbégamey" value="<?php echo htmlspecialchars($quartier_filtre); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-gold w-100 fw-bold">Filtrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($coiffeurs)): ?>
                <p class="text-secondary text-center py-5">Aucun coiffeur ne correspond à cette recherche.</p>
            <?php else: ?>
                <?php foreach ($coiffeurs as $c): ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-lg" style="background-color: #111; border-radius: 20px; overflow: hidden; border: 1px solid var(--gold);">

                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <?php if (!empty($c['photo_profil']) && file_exists($c['photo_profil'])): ?>
                                        <img src="<?php echo $c['photo_profil']; ?>"
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

                                <p class="text-secondary small mb-3">
                                    <i class="bi bi-geo-alt-fill text-warning"></i>
                                    <?php echo htmlspecialchars($c['ville'] . ' - ' . $c['quartier']); ?>
                                </p>

                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9+]/', '', $c['telephone']); ?>"
                                    target="_blank"
                                    class="btn btn-outline-success btn-sm w-100 mb-3 py-2 fw-bold">
                                    <i class="bi bi-whatsapp"></i> <?php echo htmlspecialchars($c['telephone']); ?>
                                </a>
                            </div>

                            <div class="card-footer text-center bg-dark border-top border-warning">
                                <p class="text-white small fw-bold mb-2">Diplôme / Certification :</p>
                                <?php if (!empty($c['diplome']) && file_exists($c['diplome'])): ?>
                                    <a href="<?php echo $c['diplome']; ?>" target="_blank">
                                        <img src="<?php echo $c['diplome']; ?>"
                                            alt="Diplôme"
                                            class="img-fluid rounded shadow-sm"
                                            style="height: 140px; width: 100%; object-fit: cover; border: 1px solid var(--gold);">
                                    </a>
                                    <a href="<?php echo $c['diplome']; ?>" target="_blank" class="btn btn-gold btn-sm w-100 mt-2 fw-bold">
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

<style>
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

<?php include 'footer.php'; ?>