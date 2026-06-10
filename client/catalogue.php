<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Récupération de l'éventuel coiffeur connecté pour la comparaison de sécurité
$id_coiffeur_connecte = isset($_SESSION['id_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur' ? intval($_SESSION['id_user']) : null;

// Récupération de toutes les prestations avec le nom de la ville et du quartier en clair
$stmt = $pdo->query("SELECT p.*, u.nom, u.prenom, u.telephone, 
                            v.nom_ville AS ville, 
                            q.nom_quartier AS quartier 
                     FROM prestations p 
                     JOIN users u ON p.id_coiffeur = u.id 
                     LEFT JOIN villes v ON u.ville = v.id
                     LEFT JOIN quartiers q ON u.id_quartier = q.id
                     ORDER BY p.id_prestation DESC");
$prestations = $stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">CATALOGUE DES STYLES</h2>

        <div class="row g-4">
            <?php if (empty($prestations)): ?>
                <p class="text-secondary text-center py-5">Aucune coiffure n'a été ajoutée pour le moment.</p>
            <?php else: ?>
                <?php foreach ($prestations as $p): 
                    // Extraction de la description de base seule pour l'affichage propre
                    $explode_desc = explode("|||", $p['description']);
                    $description_affichage = $explode_desc[0] ?? '';
                    
                    // Vérification propriété
                    $est_le_proprietaire = ($id_coiffeur_connecte !== null && $id_coiffeur_connecte === intval($p['id_coiffeur']));
                    
                    // 🎯 CORRIGÉ : LIGNE 36 — Le chemin pointe désormais vers le sous-dossier coiffeurs
                    $lien_profil = "/coiffons/coiffeurs/profil_public.php?id_coiffeur=" . $p['id_coiffeur'];
                ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-lg position-relative catalogue-card-js" 
                             style="background-color: #111; border-radius: 20px; overflow: hidden; border: 1px solid var(--gold); cursor: pointer;"
                             data-href="<?php echo !$est_le_proprietaire ? $lien_profil : '#'; ?>">

                            <?php if (!empty($p['photo_style']) && file_exists(__DIR__ . '/../' . $p['photo_style'])): ?>
                                <img src="/coiffons/<?php echo $p['photo_style']; ?>" class="card-img-top" style="height: 230px; object-fit: cover;" alt="Style de coiffure">
                            <?php else: ?>
                                <div class="bg-secondary text-center py-5 text-white">
                                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="text-warning mb-1"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                    <p class="text-success fw-bold fs-5 mb-2"><?php echo number_format($p['prix'], 0, ',', ' '); ?> FCFA</p>
                                    <p class="text-light small mb-3"><?php echo htmlspecialchars($description_affichage); ?></p>

                                    <hr class="border-warning my-2">

                                    <p class="text-secondary small mb-1">
                                        <i class="bi bi-person-fill text-warning"></i> Coiffeur : <strong><?php echo htmlspecialchars(strtoupper($p['nom'] . ' ' . $p['prenom'])); ?></strong>
                                    </p>
                                    <p class="text-secondary small mb-0">
                                        <i class="bi bi-geo-alt-fill text-warning"></i> Localisation : <?php echo htmlspecialchars($p['ville'] . ' - ' . $p['quartier']); ?>
                                    </p>
                                </div>

                                <?php if ($est_le_proprietaire): ?>
                                    <div class="d-flex gap-2 mt-4 pt-2 border-top border-secondary-zone">
                                        <a href="gestion_catalogue.php?ouvrir_modifier=<?php echo $p['id_prestation']; ?>" class="btn btn-success btn-sm flex-grow-1 fw-bold text-white py-2 action-btn-js">
                                            <i class="bi bi-pencil-square"></i> Éditer
                                        </a>
                                        <a href="gestion_catalogue.php?supprimer=<?php echo $p['id_prestation']; ?>" class="btn btn-danger btn-sm px-3 py-2 fw-bold action-btn-js" onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette coiffure de votre catalogue ?');">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!$est_le_proprietaire): ?>
                                <div class="card-footer bg-dark border-top border-warning text-center">
                                    <a href="<?php echo $lien_profil; ?>&presta_id=<?php echo $p['id_prestation']; ?>" class="btn btn-warning btn-sm w-100 py-2 fw-bold text-dark action-btn-js">
                                        <i class="bi bi-eye-fill"></i> Voir le profil & Réserver
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
// JavaScript malin : rend la carte cliquable sans casser le comportement des boutons internes (Éditer / Supprimer)
document.querySelectorAll('.catalogue-card-js').forEach(card => {
    card.addEventListener('click', function(e) {
        if (e.target.closest('.action-btn-js')) {
            return;
        }
        
        const url = this.getAttribute('data-href');
        if (url && url !== '#') {
            window.location.href = url;
        }
    });
});
</script>

<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(255, 193, 7, 0.2) !important;
    }
    .btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    .btn-success:hover {
        background-color: #157347 !important;
    }
    .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    .btn-danger:hover {
        background-color: #bb2d3b !important;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Récupération de l'éventuel coiffeur connecté pour la comparaison de sécurité
$id_coiffeur_connecte = isset($_SESSION['id_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur' ? intval($_SESSION['id_user']) : null;

// Récupération de toutes les prestations avec le nom de la ville et du quartier en clair
$stmt = $pdo->query("SELECT p.*, u.nom, u.prenom, u.telephone, 
                            v.nom_ville AS ville, 
                            q.nom_quartier AS quartier 
                     FROM prestations p 
                     JOIN users u ON p.id_coiffeur = u.id 
                     LEFT JOIN villes v ON u.ville = v.id
                     LEFT JOIN quartiers q ON u.id_quartier = q.id
                     ORDER BY p.id_prestation DESC");
$prestations = $stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">CATALOGUE DES STYLES</h2>

        <div class="row g-4">
            <?php if (empty($prestations)): ?>
                <p class="text-secondary text-center py-5">Aucune coiffure n'a été ajoutée pour le moment.</p>
            <?php else: ?>
                <?php foreach ($prestations as $p): 
                    // Extraction de la description de base seule pour l'affichage propre
                    $explode_desc = explode("|||", $p['description']);
                    $description_affichage = $explode_desc[0] ?? '';
                    
                    // Vérification propriété
                    $est_le_proprietaire = ($id_coiffeur_connecte !== null && $id_coiffeur_connecte === intval($p['id_coiffeur']));
                    
                    // 🎯 LA CORRECTION EST ICI : On change le chemin pour aller dans /coiffeurs/
                    $lien_profil = "/coiffons/coiffeurs/profil_public.php?id_coiffeur=" . $p['id_coiffeur'];
                ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-lg position-relative catalogue-card-js" 
                             style="background-color: #111; border-radius: 20px; overflow: hidden; border: 1px solid var(--gold); cursor: pointer;"
                             data-href="<?php echo !$est_le_proprietaire ? $lien_profil : '#'; ?>">

                            <?php if (!empty($p['photo_style']) && file_exists(__DIR__ . '/../' . $p['photo_style'])): ?>
                                <img src="/coiffons/<?php echo $p['photo_style']; ?>" class="card-img-top" style="height: 230px; object-fit: cover;" alt="Style de coiffure">
                            <?php else: ?>
                                <div class="bg-secondary text-center py-5 text-white">
                                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="text-warning mb-1"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                    <p class="text-success fw-bold fs-5 mb-2"><?php echo number_format($p['prix'], 0, ',', ' '); ?> FCFA</p>
                                    <p class="text-light small mb-3"><?php echo htmlspecialchars($description_affichage); ?></p>

                                    <hr class="border-warning my-2">

                                    <p class="text-secondary small mb-1">
                                        <i class="bi bi-person-fill text-warning"></i> Coiffeur : <strong><?php echo htmlspecialchars(strtoupper($p['nom'] . ' ' . $p['prenom'])); ?></strong>
                                    </p>
                                    <p class="text-secondary small mb-0">
                                        <i class="bi bi-geo-alt-fill text-warning"></i> Localisation : <?php echo htmlspecialchars($p['ville'] . ' - ' . $p['quartier']); ?>
                                    </p>
                                </div>

                                <?php if ($est_le_proprietaire): ?>
                                    <div class="d-flex gap-2 mt-4 pt-2 border-top border-secondary-zone">
                                        <a href="gestion_catalogue.php?ouvrir_modifier=<?php echo $p['id_prestation']; ?>" class="btn btn-success btn-sm flex-grow-1 fw-bold text-white py-2 action-btn-js">
                                            <i class="bi bi-pencil-square"></i> Éditer
                                        </a>
                                        <a href="gestion_catalogue.php?supprimer=<?php echo $p['id_prestation']; ?>" class="btn btn-danger btn-sm px-3 py-2 fw-bold action-btn-js" onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette coiffure de votre catalogue ?');">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!$est_le_proprietaire): ?>
                                <div class="card-footer bg-dark border-top border-warning text-center">
                                    <a href="<?php echo $lien_profil; ?>&presta_id=<?php echo $p['id_prestation']; ?>" class="btn btn-warning btn-sm w-100 py-2 fw-bold text-dark action-btn-js">
                                        <i class="bi bi-eye-fill"></i> Voir le profil & Réserver
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.catalogue-card-js').forEach(card => {
    card.addEventListener('click', function(e) {
        if (e.target.closest('.action-btn-js')) {
            return;
        }
        
        const url = this.getAttribute('data-href');
        if (url && url !== '#') {
            window.location.href = url;
        }
    });
});
</script>

<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(255, 193, 7, 0.2) !important;
    }
    .btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    .btn-success:hover {
        background-color: #157347 !important;
    }
    .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    .btn-danger:hover {
        background-color: #bb2d3b !important;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Récupération de l'éventuel coiffeur connecté pour la comparaison de sécurité
$id_coiffeur_connecte = isset($_SESSION['id_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur' ? intval($_SESSION['id_user']) : null;

// Récupération de toutes les prestations avec le nom de la ville et du quartier en clair
$stmt = $pdo->query("SELECT p.*, u.nom, u.prenom, u.telephone, 
                            v.nom_ville AS ville, 
                            q.nom_quartier AS quartier 
                     FROM prestations p 
                     JOIN users u ON p.id_coiffeur = u.id 
                     LEFT JOIN villes v ON u.ville = v.id
                     LEFT JOIN quartiers q ON u.id_quartier = q.id
                     ORDER BY p.id_prestation DESC");
$prestations = $stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">CATALOGUE DES STYLES</h2>

        <div class="row g-4">
            <?php if (empty($prestations)): ?>
                <p class="text-secondary text-center py-5">Aucune coiffure n'a été ajoutée pour le moment.</p>
            <?php else: ?>
                <?php foreach ($prestations as $p): 
                    // Extraction de la description de base seule pour l'affichage propre
                    $explode_desc = explode("|||", $p['description']);
                    $description_affichage = $explode_desc[0] ?? '';
                    
                    // Vérification propriété
                    $est_le_proprietaire = ($id_coiffeur_connecte !== null && $id_coiffeur_connecte === intval($p['id_coiffeur']));
                    
                    // 🎯 FIX ICI : On utilise ?id= au lieu de ?id_coiffeur= pour correspondre au profil public
                    $lien_profil = "/coiffons/coiffeurs/profil_public.php?id=" . $p['id_coiffeur'];
                ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-lg position-relative catalogue-card-js" 
                             style="background-color: #111; border-radius: 20px; overflow: hidden; border: 1px solid var(--gold); cursor: pointer;"
                             data-href="<?php echo !$est_le_proprietaire ? $lien_profil : '#'; ?>">

                            <?php if (!empty($p['photo_style']) && file_exists(__DIR__ . '/../' . $p['photo_style'])): ?>
                                <img src="/coiffons/<?php echo $p['photo_style']; ?>" class="card-img-top" style="height: 230px; object-fit: cover;" alt="Style de coiffure">
                            <?php else: ?>
                                <div class="bg-secondary text-center py-5 text-white">
                                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="text-warning mb-1"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                    <p class="text-success fw-bold fs-5 mb-2"><?php echo number_format($p['prix'], 0, ',', ' '); ?> FCFA</p>
                                    <p class="text-light small mb-3"><?php echo htmlspecialchars($description_affichage); ?></p>

                                    <hr class="border-warning my-2">

                                    <p class="text-secondary small mb-1">
                                        <i class="bi bi-person-fill text-warning"></i> Coiffeur : <strong><?php echo htmlspecialchars(strtoupper($p['nom'] . ' ' . $p['prenom'])); ?></strong>
                                    </p>
                                    <p class="text-secondary small mb-0">
                                        <i class="bi bi-geo-alt-fill text-warning"></i> Localisation : <?php echo htmlspecialchars($p['ville'] . ' - ' . $p['quartier']); ?>
                                    </p>
                                </div>

                                <?php if ($est_le_proprietaire): ?>
                                    <div class="d-flex gap-2 mt-4 pt-2 border-top border-secondary-zone">
                                        <a href="gestion_catalogue.php?ouvrir_modifier=<?php echo $p['id_prestation']; ?>" class="btn btn-success btn-sm flex-grow-1 fw-bold text-white py-2 action-btn-js">
                                            <i class="bi bi-pencil-square"></i> Éditer
                                        </a>
                                        <a href="gestion_catalogue.php?supprimer=<?php echo $p['id_prestation']; ?>" class="btn btn-danger btn-sm px-3 py-2 fw-bold action-btn-js" onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette coiffure de votre catalogue ?');">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!$est_le_proprietaire): ?>
                                <div class="card-footer bg-dark border-top border-warning text-center">
                                    <a href="<?php echo $lien_profil; ?>&presta_id=<?php echo $p['id_prestation']; ?>" class="btn btn-warning btn-sm w-100 py-2 fw-bold text-dark action-btn-js">
                                        <i class="bi bi-eye-fill"></i> Voir le profil & Réserver
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.catalogue-card-js').forEach(card => {
    card.addEventListener('click', function(e) {
        if (e.target.closest('.action-btn-js')) {
            return;
        }
        
        const url = this.getAttribute('data-href');
        if (url && url !== '#') {
            window.location.href = url;
        }
    });
});
</script>

<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(255, 193, 7, 0.2) !important;
    }
    .btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    .btn-success:hover {
        background-color: #157347 !important;
    }
    .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    .btn-danger:hover {
        background-color: #bb2d3b !important;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>