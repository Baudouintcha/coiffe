<?php
include 'header.php';
require 'config.php';

// Récupération de toutes les prestations de la base de données
$stmt = $pdo->query("SELECT p.*, u.nom, u.prenom, u.telephone, u.ville, u.quartier 
                     FROM prestations p 
                     JOIN users u ON p.id_coiffeur = u.id 
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
                <?php foreach ($prestations as $p): ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-lg" style="background-color: #111; border-radius: 20px; overflow: hidden; border: 1px solid var(--gold);">

                            <?php if (!empty($p['photo_style']) && file_exists($p['photo_style'])): ?>
                                <img src="<?php echo $p['photo_style']; ?>" class="card-img-top" style="height: 230px; object-fit: cover;" alt="Style de coiffure">
                            <?php else: ?>
                                <div class="bg-secondary text-center py-5 text-white">
                                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="text-warning mb-1"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                <p class="text-success fw-bold fs-5 mb-2"><?php echo number_format($p['prix'], 0, ',', ' '); ?> FCFA</p>
                                <p class="text-light small mb-3"><?php echo htmlspecialchars($p['description']); ?></p>

                                <hr class="border-warning my-2">

                                <p class="text-secondary small mb-1">
                                    <i class="bi bi-person-fill text-warning"></i> Coiffeur : <strong><?php echo htmlspecialchars(strtoupper($p['nom'] . ' ' . $p['prenom'])); ?></strong>
                                </p>
                                <p class="text-secondary small mb-0">
                                    <i class="bi bi-geo-alt-fill text-warning"></i> Localisation : <?php echo htmlspecialchars($p['ville'] . ' - ' . $p['quartier']); ?>
                                </p>
                            </div>

                            <div class="card-footer bg-dark border-top border-warning text-center">
                                <a href="reserver.php?coiffeur_id=<?php echo $p['id_coiffeur']; ?>&presta_id=<?php echo $p['id_prestation']; ?>"
                                    class="btn btn-warning btn-sm w-100 py-2 fw-bold text-dark">
                                    <i class="bi bi-calendar-check"></i> Réserver cette coiffure
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .card {
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }
</style>

<?php include 'footer.php'; ?>