<?php
/**
 * filter/annuaire_coiffeurs.php — Annuaire des coiffeurs
 * Migration Design System v2.0 — Parcours Visiteur — Page 4/6
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — Seuls HTML/CSS/composants ont été modifiés.
 *     Correction d'un bug de syntaxe PHP (if/endif dupliqué) sans impact métier.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// Gestion des filtres — logique PHP inchangée
$ville_filtre    = isset($_GET['ville'])       ? intval($_GET['ville'])       : 0;
$quartier_filtre = isset($_GET['id_quartier']) ? intval($_GET['id_quartier']) : 0;
$prix_filtre     = isset($_GET['prix_max'])    ? trim($_GET['prix_max'])      : '';
$prix_saisie     = isset($_GET['prix_saisie']) ? intval($_GET['prix_saisie']) : 0;
$a_recherche     = isset($_GET['ville']) || isset($_GET['prix_max']) || isset($_GET['prix_saisie']) || isset($_GET['id_quartier']);

// Requête coiffeurs — SQL inchangé
$sql    = "SELECT DISTINCT u.*, v.nom_ville, q.nom_quartier 
           FROM users u
           LEFT JOIN villes v ON u.ville = v.id
           LEFT JOIN quartiers q ON u.id_quartier = q.id
           LEFT JOIN zones_coiffeur z ON u.id = z.id_coiffeur
           WHERE u.role = 'coiffeur'";
$params = [];

if ($ville_filtre > 0) {
    $sql .= " AND u.ville = ? ";
    $params[] = $ville_filtre;
}
if ($quartier_filtre > 0) {
    $sql .= " AND (u.id_quartier = ? OR z.id_quartier = ?)";
    $params[] = $quartier_filtre;
    $params[] = $quartier_filtre;
}
if ($prix_saisie > 0) {
    $marge_basse = max(0, $prix_saisie - 1500);
    $marge_haute = $prix_saisie + 2000;
    $sql .= " AND u.tarif_base BETWEEN ? AND ? ";
    $params[] = $marge_basse;
    $params[] = $marge_haute;
} elseif (!empty($prix_filtre)) {
    if ($prix_filtre === 'eco')     $sql .= " AND u.tarif_base <= 2000 ";
    elseif ($prix_filtre === 'moyen')   $sql .= " AND u.tarif_base BETWEEN 1500 AND 3500 ";
    elseif ($prix_filtre === 'premium') $sql .= " AND u.tarif_base > 3000 ";
}
$sql .= " ORDER BY u.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coiffeurs = $stmt->fetchAll();
} catch (PDOException $e) {
    $coiffeurs = [];
}

// Villes pour le sélecteur — SQL inchangé
try {
    $villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
    $villes = $villes_stmt->fetchAll();
} catch (PDOException $e) {
    $villes = [];
}

// Image pour le fond hero — logique PHP inchangée
$hero_images = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
if (empty($hero_images)) {
    $hero_images = [__DIR__ . '/../images/burst.jpg'];
}
$hero_bg_url = '/coiffons/imgid/' . basename($hero_images[0]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuaire des coiffeurs — Coiffe Chez Toi</title>

    <!-- Design System v2.0 — Ordre de chargement officiel -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">

    <!-- Bootstrap 5.3.3 (grille uniquement) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques à l'annuaire, utilisant exclusivement var(--*) DS ── */

    /* Hero Compact — CL §37 */
    .annuaire-hero {
        width: 100%;
        min-height: 35vh;
        background: linear-gradient(135deg, var(--dark) 0%, var(--dark-3) 100%);
        display: flex;
        align-items: flex-end;
        padding: 0 0 2rem;
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid rgba(212,175,55,0.10);
    }
    .annuaire-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: url('<?= htmlspecialchars($hero_bg_url) ?>') center/cover no-repeat;
        filter: blur(4px) brightness(0.18);
        transform: scale(1.05);
    }
    .annuaire-hero .container { position: relative; z-index: 1; }

    /* Carte de filtre glassmorphisme — glass niveau 2 */
    .annuaire-filter-card {
        background: var(--glass-bg-strong);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        padding: 24px;
        width: 100%;
    }

    /* Carte coiffeur résultat — variante annuaire de ServiceCard */
    .coiffeur-result-card {
        background: var(--dark-2);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        transition: var(--transition-spring), border-color 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .coiffeur-result-card:hover {
        transform: translateY(-6px);
        border-color: rgba(212,175,55,0.40);
        box-shadow: var(--shadow-lg);
    }
    .coiffeur-result-body { padding: 1.5rem; text-align: center; flex: 1; }
    .coiffeur-result-footer {
        padding: 12px 16px;
        border-top: 1px solid var(--glass-border);
        background: var(--dark-3);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: .78rem;
    }

    /* Avatar coiffeur dans l'annuaire — 80px */
    .coiffeur-avatar-wrap { display: inline-block; position: relative; margin-bottom: 12px; }
    .coiffeur-avatar-img {
        width: 80px; height: 80px;
        object-fit: cover;
        border-radius: var(--radius-circle);
        border: 2px solid var(--gold);
    }

    /* Badge certifié sur l'avatar */
    .badge-certified {
        position: absolute;
        bottom: 0; right: -6px;
        background: #198754;
        color: #fff;
        font-size: .68rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        white-space: nowrap;
    }

    /* Bloc prix */
    .price-block {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        padding: 10px 14px;
        margin-bottom: 14px;
    }
    .price-label { display: block; font-size: .68rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
    .price-value { font-size: 1.3rem; font-weight: 800; color: var(--gold); display: block; }

    /* Section résultats */
    .results-section { padding: 4rem 0; background: var(--dark); min-height: 60vh; }

    /* Empty state personnalisé pour l'annuaire */
    .annuaire-start-block {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        padding: 3rem 2rem;
        text-align: center;
    }
    .annuaire-badges { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; }
    .annuaire-badge-item {
        display: flex; align-items: center; gap: 6px;
        font-size: .75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    @media (max-width: 768px) {
        .annuaire-hero { min-height: 28vh; padding: 0 0 1.5rem; }
        .annuaire-filter-card { padding: 16px; }
        .price-value { font-size: 1.1rem; }
    }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR CLIENT — Composant CL §21
     ══════════════════════════════════════════ -->
<?php
$prenom_client = htmlspecialchars($_SESSION['prenom'] ?? '');
$photo_profil  = $_SESSION['photo_profil'] ?? null;
$nb_notifs     = 0;
$page_root     = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<!-- ══════════════════════════════════════════
     HERO COMPACT — Composant CL §37
     Fond flou + carte de filtres glassmorphisme
     ══════════════════════════════════════════ -->
<section class="annuaire-hero" aria-labelledby="annuaire-heading">
    <div class="container" style="max-width:var(--max-width);">
        <div class="annuaire-filter-card">

            <h1 id="annuaire-heading"
                style="font-family:var(--font-display);font-size:clamp(1.4rem,3vw,2rem);font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                Trouvez votre artiste capillaire
            </h1>
            <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:1.2rem;">
                Le raffinement de la coiffure privée, directement chez vous.
            </p>

            <!-- Formulaire de filtres — logique PHP inchangée, HTML migré DS -->
            <form method="GET"
                  role="search"
                  aria-label="Filtrer les coiffeurs">
                <div class="row g-3 align-items-end">

                    <!-- Ville -->
                    <div class="col-12 col-md-3">
                        <label for="villeSelectAnnuaire" class="form-label-cct">Ville</label>
                        <select name="ville"
                                id="villeSelectAnnuaire"
                                class="fc-dark">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($villes as $v): ?>
                                <option value="<?= $v['id'] ?>"
                                    <?= ($ville_filtre == $v['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['nom_ville']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quartier -->
                    <div class="col-12 col-md-3">
                        <label for="quartierSelectAnnuaire" class="form-label-cct">Quartier</label>
                        <select name="id_quartier"
                                id="quartierSelectAnnuaire"
                                class="fc-dark"
                                <?= ($ville_filtre > 0) ? '' : 'disabled' ?>
                                aria-label="Quartier (optionnel, sélectionnez d'abord une ville)">
                            <option value="">Tous les quartiers</option>
                            <?php
                            if ($ville_filtre > 0) {
                                $q_stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville = ? ORDER BY nom_quartier ASC");
                                $q_stmt->execute([$ville_filtre]);
                                while ($q = $q_stmt->fetch()) {
                                    $sel = ($quartier_filtre == $q['id']) ? 'selected' : '';
                                    echo '<option value="' . $q['id'] . '" ' . $sel . '>' . htmlspecialchars($q['nom_quartier']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Catégorie tarif -->
                    <div class="col-12 col-md-3">
                        <label for="prixMaxSelect" class="form-label-cct">Catégorie</label>
                        <select name="prix_max"
                                id="prixMaxSelect"
                                class="fc-dark">
                            <option value="">Tous les tarifs</option>
                            <option value="eco"     <?= ($prix_filtre === 'eco')     ? 'selected' : '' ?>>Économique (≤ 1 500 F)</option>
                            <option value="moyen"   <?= ($prix_filtre === 'moyen')   ? 'selected' : '' ?>>Standard (1 500–3 000 F)</option>
                            <option value="premium" <?= ($prix_filtre === 'premium') ? 'selected' : '' ?>>Premium (> 3 000 F)</option>
                        </select>
                    </div>

                    <!-- Budget personnalisé -->
                    <div class="col-8 col-md-2">
                        <label for="prixSaisie" class="form-label-cct">Budget max (FCFA)</label>
                        <input type="number"
                               id="prixSaisie"
                               name="prix_saisie"
                               class="fc-dark"
                               placeholder="Ex : 2500"
                               min="0"
                               value="<?= $prix_saisie > 0 ? $prix_saisie : '' ?>"
                               aria-label="Budget maximum en FCFA">
                    </div>

                    <!-- Bouton rechercher -->
                    <div class="col-4 col-md-1 d-flex align-items-end">
                        <button type="submit"
                                class="btn-gold w-100"
                                style="padding:11px 8px;"
                                aria-label="Lancer la recherche">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     SECTION RÉSULTATS
     ══════════════════════════════════════════ -->
<section class="results-section page-transition" aria-label="Résultats de la recherche">
    <div class="container" style="max-width:var(--max-width);">

        <?php if (!$a_recherche): ?>
            <!-- ── État initial : inviter à rechercher ── -->
            <div class="row justify-content-center">
                <div class="col-12 col-md-8">
                    <div class="annuaire-start-block">
                        <i class="bi bi-stars"
                           style="font-size:3rem;color:var(--gold);opacity:.5;display:block;margin-bottom:1rem;"
                           aria-hidden="true"></i>
                        <h2 style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;color:var(--text-primary);margin-bottom:.75rem;">
                            Votre recherche sur-mesure commence ici
                        </h2>
                        <p style="color:var(--text-muted);font-size:.88rem;line-height:1.7;max-width:520px;margin:0 auto;">
                            Sélectionnez votre zone et votre budget ci-dessus pour afficher la liste de nos coiffeurs certifiés.
                        </p>
                        <div class="annuaire-badges">
                            <span class="annuaire-badge-item">
                                <i class="bi bi-shield-check" style="color:var(--gold);" aria-hidden="true"></i>
                                Diplômes vérifiés
                            </span>
                            <span class="annuaire-badge-item">
                                <i class="bi bi-clock" style="color:var(--gold);" aria-hidden="true"></i>
                                À l'heure chez vous
                            </span>
                            <span class="annuaire-badge-item">
                                <i class="bi bi-wallet2" style="color:var(--gold);" aria-hidden="true"></i>
                                Prix transparents
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif (empty($coiffeurs)): ?>
            <!-- ── Empty State DS §17 ── -->
            <?php
            $es = [
                'icon'    => 'bi-emoji-frown',
                'message' => 'Aucun coiffeur trouvé pour ces critères.',
                'cta_label' => 'Élargir la recherche',
                'cta_href'  => '/coiffons/filter/annuaire_coiffeurs.php',
            ];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>

        <?php else: ?>
            <!-- ── Grille des résultats ── -->
            <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:1.5rem;">
                <strong style="color:var(--gold);"><?= count($coiffeurs) ?></strong>
                coiffeur<?= count($coiffeurs) > 1 ? 's' : '' ?> disponible<?= count($coiffeurs) > 1 ? 's' : '' ?>
            </p>

            <div class="row g-4">
            <?php foreach ($coiffeurs as $c):
                $nom_ville_affiche    = !empty($c['nom_ville'])    ? $c['nom_ville']    : 'Bénin';
                $nom_quartier_affiche = (!empty($c['nom_quartier']) && $c['id_quartier'] != 0) ? $c['nom_quartier'] : 'Toute la ville';
                $photo_path = (!empty($c['photo_profil']) && file_exists(__DIR__ . '/../access/' . $c['photo_profil']))
                              ? '/coiffons/access/' . $c['photo_profil']
                              : null;
            ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <article class="coiffeur-result-card" aria-label="Profil de <?= htmlspecialchars(strtoupper($c['nom'] . ' ' . $c['prenom'])) ?>">

                        <div class="coiffeur-result-body">
                            <!-- Avatar -->
                            <div class="coiffeur-avatar-wrap">
                                <?php if ($photo_path): ?>
                                    <img src="<?= htmlspecialchars($photo_path) ?>"
                                         alt="Photo de <?= htmlspecialchars($c['nom']) ?>"
                                         class="coiffeur-avatar-img">
                                <?php else: ?>
                                    <div class="avatar-placeholder"
                                         style="width:80px;height:80px;font-size:1.6rem;"
                                         aria-hidden="true">
                                        <?= strtoupper(substr($c['nom'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="badge-certified" aria-label="Coiffeur certifié">
                                    <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Certifié
                                </span>
                            </div>

                            <!-- Nom -->
                            <h3 style="font-family:var(--font-body);font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:6px;">
                                <?= htmlspecialchars(strtoupper($c['nom'] . ' ' . $c['prenom'])) ?>
                            </h3>

                            <!-- Localisation -->
                            <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:12px;">
                                <i class="bi bi-geo-alt" style="color:var(--gold);" aria-hidden="true"></i>
                                <?= htmlspecialchars($nom_ville_affiche . ' · ' . $nom_quartier_affiche) ?>
                            </p>

                            <!-- Prix -->
                            <div class="price-block">
                                <span class="price-label">Tarif de base</span>
                                <span class="price-value">
                                    <?= number_format($c['tarif_base'], 0, ',', ' ') ?>
                                    <small style="font-size:.75rem;">FCFA</small>
                                </span>
                            </div>

                            <!-- CTA -->
                            <a href="/coiffons/coiffeurs/profil_public.php?id=<?= $c['id'] ?>"
                               class="btn-gold w-100"
                               aria-label="Voir le profil et réserver un créneau avec <?= htmlspecialchars($c['nom']) ?>">
                                <i class="bi bi-calendar3 me-2" aria-hidden="true"></i>
                                Réserver un créneau
                            </a>
                        </div>

                        <!-- Footer carte -->
                        <div class="coiffeur-result-footer">
                            <span style="color:var(--text-muted);">Vérification :</span>
                            <?php if (!empty($c['diplome']) && file_exists(__DIR__ . '/../access/' . $c['diplome'])): ?>
                                <a href="/coiffons/access/<?= htmlspecialchars($c['diplome']) ?>"
                                   target="_blank"
                                   rel="noopener"
                                   style="color:var(--gold);text-decoration:none;display:flex;align-items:center;gap:4px;"
                                   aria-label="Voir le diplôme certifié de <?= htmlspecialchars($c['nom']) ?>">
                                    <i class="bi bi-file-earmark-image" aria-hidden="true"></i>
                                    Voir le diplôme
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-disabled);font-style:italic;">Aucun document</span>
                            <?php endif; ?>
                        </div>

                    </article>
                </div>
            <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</section>

<!-- ══════════════════════════════════════════
     FOOTER GLOBAL + BOTTOM NAV CLIENT
     ══════════════════════════════════════════ -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/footer_global.php';

$current_page = 'annuaire_coiffeurs.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ══ JAVASCRIPT — logique AJAX quartiers inchangée ══ -->
<script>
document.getElementById('villeSelectAnnuaire').addEventListener('change', function() {
    const idVille      = this.value;
    const quartierSelect = document.getElementById('quartierSelectAnnuaire');

    if (!idVille) {
        quartierSelect.innerHTML = '<option value="">Tous les quartiers</option>';
        quartierSelect.disabled = true;
        return;
    }

    // Tentative URL absolue puis relative (fallback) — inchangé
    fetch('/coiffons/access/get_quartiers.php?id_ville=' + idVille)
        .then(response => {
            if (!response.ok) throw new Error('Fichier introuvable');
            return response.json();
        })
        .then(data => {
            quartierSelect.innerHTML = '<option value="">Tous les quartiers</option>';
            quartierSelect.disabled = false;
            data.forEach(q => {
                const o = document.createElement('option');
                o.value = q.id;
                o.textContent = q.nom_quartier;
                quartierSelect.appendChild(o);
            });
        })
        .catch(() => {
            fetch('../access/get_quartiers.php?id_ville=' + idVille)
                .then(res => res.json())
                .then(data => {
                    quartierSelect.innerHTML = '<option value="">Tous les quartiers</option>';
                    quartierSelect.disabled = false;
                    data.forEach(q => {
                        const o = document.createElement('option');
                        o.value = q.id;
                        o.textContent = q.nom_quartier;
                        quartierSelect.appendChild(o);
                    });
                })
                .catch(err => console.error('Échec chargement quartiers:', err));
        });
});
</script>

</body>
</html>
