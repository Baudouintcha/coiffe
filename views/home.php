<?php
/**
 * views/home.php — Page d'accueil de "Coiffe Chez Toi"
 * Inclus par index.php (routeur Coiffe Chez Toi)
 * Adapté pour fonctionner depuis le dossier views/
 */

// Les variables $pdo, $role_actuel etc. sont déjà définies par index.php
// Ce fichier est une vue pure — il affiche, ne traite pas
?>

<div class="luxury-main-wrapper" style="background: linear-gradient(135deg, #0a0a0a 0%, #121212 100%); color: #fff; min-height: 100vh;">

    <?php if ($role_actuel === 'coiffeur'): ?>
        <section class="py-5">
            <div class="container">
                <div class="row mb-4 text-center">
                    <div class="col-12">
                        <h1 class="text-warning fw-bold h3 mb-1" style="letter-spacing: 0.5px;">
                            SALON DE : <?php echo strtoupper(htmlspecialchars($db_coiffeur['nom'] ?? '')); ?>
                        </h1>
                        <p class="text-success mb-0 small">
                            <i class="bi bi-check-circle-fill"></i> Votre abonnement est actif
                        </p>
                    </div>
                </div>

                <div class="row g-3">
                    <?php if (empty($mes_coiffures)): ?>
                        <div class="col-12 text-center text-muted small py-5">
                            Vous n'avez pas encore ajouté de style à votre catalogue.
                        </div>
                    <?php else: ?>
                        <?php foreach ($mes_coiffures as $c): ?>
                            <div class="col-6 col-md-4">
                                <a href="/coiffons/coiffeurs/gestion_catalogue.php?ouvrir_modifier=<?php echo $c['id_prestation']; ?>" class="text-decoration-none card-clickable-wrapper d-block h-100">
                                    <div class="card luxury-card h-100 border-0">
                                        <div class="card-body d-flex flex-column p-3">
                                            <h4 class="fw-bold text-white mb-1 text-truncate h6"><?php echo htmlspecialchars($c['nom_style'] ?? ''); ?></h4>
                                            <p class="text-secondary small mb-2 text-truncate" style="font-size: 0.7rem;">
                                                <i class="bi bi-geo-alt text-warning"></i> <?php echo htmlspecialchars($c['ville'] ?? 'Non spécifiée'); ?>
                                            </p>
                                            <h3 class="text-warning fw-bold font-monospace my-2 h5" style="font-size: 0.95rem;">
                                                <?php echo number_format($c['prix'], 0, ',', ' '); ?> <span style="font-size: 0.65rem;">FCFA</span>
                                            </h3>
                                            <div class="mt-auto pt-2 d-flex gap-1">
                                                <span class="btn btn-success btn-sm flex-grow-1 py-1" style="font-size: 0.7rem;">
                                                    <i class="bi bi-pencil-square"></i> Éditer
                                                </span>
                                                <a href="/coiffons/coiffeurs/gestion_catalogue.php?supprimer=<?php echo $c['id_prestation']; ?>" class="btn btn-danger btn-sm px-2 py-1 fw-bold" style="font-size: 0.7rem;" onclick="event.stopPropagation(); return confirm('Supprimer ?');">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="row my-4 justify-content-center">
                    <div class="col-12 col-sm-8 col-md-6 text-center">
                        <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="btn btn-warning text-black fw-bold w-100 py-2 shadow" style="border-radius: 30px;">
                            <i class="bi bi-plus-circle-fill me-1"></i> AJOUTER UN STYLE
                        </a>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="card luxury-card p-3 text-decoration-none h-100 d-flex flex-column align-items-center text-center justify-content-center">
                            <h5 class="text-warning mb-1 h6" style="font-size: 0.85rem;"><i class="bi bi-calendar3 me-1"></i> Mon Agenda</h5>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="card luxury-card p-3 text-decoration-none h-100 d-flex flex-column align-items-center text-center justify-content-center">
                            <h5 class="text-success mb-1 h6" style="font-size: 0.85rem;"><i class="bi bi-check2-circle me-1"></i> Mes Demandes</h5>
                        </a>
                    </div>
                </div>
            </div>
        </section>

    <?php elseif ($role_actuel === 'client'): ?>
        <section class="py-5 text-center">
            <div class="container">
                <h1 class="text-warning fw-bold h2 mb-3">COIFFE CHEZ TOI — VOTRE SALON DE LUXE À DOMICILE</h1>
                <p class="text-light opacity-75 small mb-4 mx-auto" style="max-width: 750px;">
                    Découvrez, réservez et planifiez vos prestations avec les meilleurs coiffeurs professionnels de votre région.
                </p>
                <p class="text-secondary small mb-4">
                    Ravi de vous revoir, <strong><?php echo htmlspecialchars($nom_affichage_client ?? 'Client'); ?></strong> !
                </p>
                <a href="/coiffons/filter/annuaire_coiffeurs.php" class="btn btn-warning text-black fw-bold px-4 shadow-lg small">EXPLORER L'ANNUAIRE DES COIFFEURS</a>
            </div>
        </section>

        <section class="py-4">
            <div class="container">
                <h3 class="text-white mb-4 h5 text-center text-md-start">
                    <i class="bi bi-stars text-warning me-2"></i> DÉCOUVREZ NOS CRÉATIONS EXCLUSIVES
                </h3>
                <div class="row g-4">
                    <?php foreach ($coiffeurs_matching as $coif): ?>
                        <div class="col-md-4">
                            <a href="/coiffons/coiffeurs/profil_public.php?id=<?php echo $coif['id_coiffeur']; ?>" class="text-decoration-none card-clickable-wrapper d-block h-100">
                                <div class="card luxury-card h-100 border-0 text-white shadow-sm">
                                    <div class="luxury-card-img-container">
                                        <?php if (!empty($coif['photo_style']) && file_exists(dirname(__DIR__) . '/uploads/' . $coif['photo_style'])): ?>
                                            <img src="/coiffons/uploads/<?php echo $coif['photo_style']; ?>" class="card-img-top" alt="Style">
                                        <?php else: ?>
                                            <div class="luxury-placeholder-img d-flex flex-column justify-content-center align-items-center">
                                                <i class="bi bi-person-badge text-warning opacity-50 display-6 mb-2"></i>
                                                <span class="text-muted text-uppercase font-monospace" style="font-size: 0.6rem; letter-spacing: 1px;">Création Privée</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column p-4 text-center align-items-center">
                                        <span class="badge bg-warning text-dark mb-3">
                                            <i class="bi bi-person-fill"></i> Par <?php echo htmlspecialchars($coif['nom_coiffeur']); ?>
                                        </span>
                                        <h4 class="fw-bold text-white mb-1 h5"><?php echo htmlspecialchars($coif['nom_style']); ?></h4>
                                        <p class="text-secondary small mb-3">
                                            <i class="bi bi-pin-map text-warning"></i>
                                            <?php echo htmlspecialchars($coif['ville']); ?> — <?php echo htmlspecialchars($coif['quartier'] ?? 'Général'); ?>
                                        </p>
                                        <h3 class="text-warning fw-bold fs-4 my-3 font-monospace">
                                            <?php echo number_format($coif['prix'], 0, ',', ' '); ?>
                                            <span class="small" style="font-size: 0.8rem;">FCFA</span>
                                        </h3>
                                        <div class="mt-auto w-100">
                                            <span class="btn btn-outline-warning btn-sm w-100 fw-bold py-2">
                                                PRENDRE RDV AVEC <?php echo strtoupper(htmlspecialchars($coif['nom_coiffeur'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    <?php else: // Invité ?>
        <section class="py-5 text-center">
            <div class="container">
                <h1 class="text-warning fw-bold h2 mb-3">COIFFE CHEZ TOI — VOTRE SALON DE LUXE À DOMICILE</h1>
                <p class="text-light opacity-75 small mb-4 mx-auto" style="max-width: 750px;">
                    Découvrez, réservez et planifiez vos prestations avec les meilleurs coiffeurs professionnels de votre région.
                </p>
                <a href="/coiffons/access/connexion.php" class="btn btn-warning text-black fw-bold px-4 shadow-lg small mb-3">
                    SE CONNECTER
                </a>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="/coiffons/access/inscription.php?role=client" class="btn btn-outline-warning fw-bold px-4 small">
                        JE SUIS CLIENT
                    </a>
                    <a href="/coiffons/access/inscription.php?role=prestataire" class="btn btn-outline-light fw-bold px-4 small">
                        JE SUIS COIFFEUR
                    </a>
                </div>
            </div>
        </section>

        <section class="py-4">
            <div class="container">
                <h3 class="text-white mb-4 h5 text-center text-md-start">
                    <i class="bi bi-stars text-warning me-2"></i> DÉCOUVREZ NOS CRÉATIONS EXCLUSIVES
                </h3>
                <div class="row g-4">
                    <?php foreach ($catalog_demo as $cd): ?>
                        <div class="col-md-4">
                            <a href="/coiffons/access/connexion.php" class="text-decoration-none card-clickable-wrapper d-block h-100">
                                <div class="card luxury-card h-100 border-0 text-white shadow-sm">
                                    <div class="luxury-card-img-container">
                                        <?php if (!empty($cd['photo_style']) && file_exists(dirname(__DIR__) . '/uploads/' . $cd['photo_style'])): ?>
                                            <img src="/coiffons/uploads/<?php echo $cd['photo_style']; ?>" class="card-img-top" alt="Style">
                                        <?php else: ?>
                                            <div class="luxury-placeholder-img d-flex flex-column justify-content-center align-items-center">
                                                <i class="bi bi-person-badge text-warning opacity-50 display-6 mb-2"></i>
                                                <span class="text-muted text-uppercase font-monospace" style="font-size: 0.6rem; letter-spacing: 1px;">Création Privée</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column p-4 text-center align-items-center">
                                        <span class="badge bg-warning text-dark mb-3">
                                            <i class="bi bi-person-fill"></i> Par <?php echo htmlspecialchars($cd['nom_coiffeur']); ?>
                                        </span>
                                        <h4 class="fw-bold text-white mb-1 h5"><?php echo htmlspecialchars($cd['nom_style']); ?></h4>
                                        <p class="text-secondary small mb-3">
                                            <i class="bi bi-pin-map text-warning"></i>
                                            <?php echo htmlspecialchars($cd['ville']); ?> — <?php echo htmlspecialchars($cd['quartier'] ?? 'Général'); ?>
                                        </p>
                                        <h3 class="text-warning fw-bold fs-4 my-3 font-monospace">
                                            <?php echo number_format($cd['prix'], 0, ',', ' '); ?>
                                            <span class="small" style="font-size: 0.8rem;">FCFA</span>
                                        </h3>
                                        <div class="mt-auto w-100">
                                            <span class="btn btn-outline-warning btn-sm w-100 fw-bold py-2">
                                                REJOINDRE POUR PRENDRE RDV
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- AVIS CLIENTS -->
    <section class="py-5 mt-4">
        <div class="container text-center">
            <h2 class="text-warning mb-4 fw-bold h4">L'AVIS DE NOS CLIENTS</h2>
            <div class="row g-4 justify-content-center">
                <?php if (empty($all_comments)): ?>
                    <?php
                    $temoignages = [
                        ['msg' => 'Service impeccable ! La coiffeuse est venue à l\'heure et le tressage est super propre.', 'auteur' => 'Mariam B. (Cotonou)'],
                        ['msg' => 'Le dégradé américain à domicile est parfait. Plus besoin de faire la queue au salon.', 'auteur' => 'Arnaud K. (Calavi)'],
                        ['msg' => 'Simple, rapide et ultra professionnel. Le paiement sécurisé donne vraiment confiance.', 'auteur' => 'Carine T. (Porto-Novo)'],
                    ];
                    foreach ($temoignages as $t):
                    ?>
                        <div class="col-md-4">
                            <div class="card luxury-card p-4 h-100 text-start">
                                <p class="text-light small fst-italic mb-3">"<?php echo $t['msg']; ?>"</p>
                                <h6 class="text-warning mb-0 small">— <?php echo $t['auteur']; ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($all_comments as $com): ?>
                        <div class="col-md-4">
                            <div class="card luxury-card p-4 h-100 text-start">
                                <p class="text-light small fst-italic mb-3">"<?php echo htmlspecialchars($com['message']); ?>"</p>
                                <h6 class="text-warning mb-0 small">— <?php echo htmlspecialchars($com['nom']); ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

</div>

<style>
    .luxury-card {
        background-color: #121212 !important;
        border: 1px solid rgba(255,255,255,0.05) !important;
        border-radius: 12px !important;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.25,0.8,0.25,1), border-color 0.3s ease;
    }
    .card-clickable-wrapper:hover .luxury-card {
        transform: translateY(-5px);
        border-color: #D4AF37 !important;
        box-shadow: 0 10px 20px rgba(0,0,0,0.5) !important;
    }
    .luxury-card-img-container {
        width: 100%; height: 200px;
        overflow: hidden; background: #1a1a1a;
    }
    .luxury-card-img-container img { width:100%; height:100%; object-fit:cover; }
    .luxury-placeholder-img { width:100%; height:100%; background: linear-gradient(135deg,#151515,#222); display:flex; flex-direction:column; align-items:center; justify-content:center; }
</style>
