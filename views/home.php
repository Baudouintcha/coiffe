<?php
/**
 * views/home.php — Landing Page Premium — Coiffe Chez Toi
 * Refonte finale — Design System v2.0
 * Routes / SQL / variables PHP : intacts
 */

// ── Images slider — logique PHP inchangée ──
$images    = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$hp_images = [];
foreach ($images as $img) {
    $hp_images[] = '/coiffons/imgid/' . basename($img);
}

// ── Variables HeroPremium ──
$hp_title              = 'COIFFE CHEZ TOI';
$hp_subtitle           = 'Réservez un coiffeur professionnel qui se déplace directement chez vous.';
$hp_role_client_href   = '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';
$hp_login_href         = '/coiffons/index.php?page=login';
$hp_show_roles         = true;

// ── Coiffeurs populaires — SQL inchangé ──
$coiffeurs_populaires = [];
try {
    $stmt = $pdo->query("
        SELECT u.id, u.nom, u.prenom, u.photo_profil, u.tarif_base, v.nom_ville, q.nom_quartier,
               (SELECT ROUND(AVG(c.note),1) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS note_moyenne,
               (SELECT COUNT(*) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS nb_avis
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        WHERE u.role = 'coiffeur' AND u.abonnement_status = 1 AND u.is_approved = 1
        ORDER BY nb_avis DESC, note_moyenne DESC
        LIMIT 6
    ");
    $coiffeurs_populaires = $stmt->fetchAll();
} catch (Exception $e) {
    $coiffeurs_populaires = [];
}

// ── Avis récents — SQL inchangé ──
$avis_recents = [];
try {
    $stmt = $pdo->query("
        SELECT c.message, c.note, c.date_creation,
               uc.nom AS client_nom, uc.prenom AS client_prenom,
               uco.nom AS coiffeur_nom
        FROM commentaires c
        JOIN users uc ON c.id_client = uc.id
        JOIN users uco ON c.id_coiffeur = uco.id
        WHERE c.type_commentaire = 'public' AND c.statut_moderation != 'rejete'
          AND c.message != ''
        ORDER BY c.date_creation DESC
        LIMIT 6
    ");
    $avis_recents = $stmt->fetchAll();
} catch (Exception $e) {
    $avis_recents = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coiffe Chez Toi — Coiffure à domicile au Bénin</title>
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* Page globals */
    body { background: var(--dark); }

    /* Espacement uniforme entre toutes les sections */
    .home-section { padding: 5rem 0; }
    .home-section--alt { background: var(--dark-2); }
    .home-section--dark3 { background: var(--dark-3); }

    /* Conteneur max-width officiel */
    .home-wrap { max-width: 1280px; margin: 0 auto; padding: 0 1.5rem; }

    /* Heading de section */
    .section-eyebrow { font-size: .7rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--gold); display: block; margin-bottom: .5rem; }
    .section-heading { font-family: var(--font-display); font-size: clamp(1.6rem,3.5vw,2.4rem); font-weight: 700; color: var(--text-primary); margin-bottom: .6rem; }
    .section-sub { color: var(--text-muted); font-size: .9rem; line-height: 1.7; max-width: 540px; }

    /* ── Comment ça marche ── */
    .how-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 2rem 1.5rem; text-align: center; height: 100%; transition: var(--transition-spring), border-color .3s; }
    .how-card:hover { border-color: rgba(212,175,55,.30); transform: translateY(-4px); }
    .how-num { width: 52px; height: 52px; border-radius: var(--radius-circle); background: var(--gold-dim); border: 2px solid var(--gold); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-size: 1.2rem; font-weight: 900; color: var(--gold); margin: 0 auto 1.2rem; }
    .how-title { font-weight: 700; font-size: .95rem; color: var(--text-primary); margin-bottom: .5rem; }
    .how-desc { font-size: .82rem; color: var(--text-muted); line-height: 1.6; margin: 0; }

    /* ── Pourquoi CCT — 2 colonnes ── */
    .why-stat { text-align: center; padding: 1.5rem 1rem; }
    .why-stat-num { font-family: var(--font-display); font-size: clamp(2rem,5vw,3.5rem); font-weight: 900; color: var(--gold); display: block; line-height: 1; }
    .why-stat-lbl { font-size: .75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; display: block; }
    .why-point { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 1.25rem; }
    .why-point-icon { width: 36px; height: 36px; border-radius: var(--radius-md); background: var(--gold-dim); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 1rem; flex-shrink: 0; margin-top: 2px; }
    .why-point-title { font-weight: 700; font-size: .88rem; color: var(--text-primary); margin-bottom: 2px; }
    .why-point-desc { font-size: .8rem; color: var(--text-muted); line-height: 1.5; margin: 0; }

    /* ── Coiffeurs populaires ── */
    .coiffeur-home-card { background: var(--dark-2); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); overflow: hidden; transition: var(--transition-spring), border-color .3s; display: flex; flex-direction: column; height: 100%; text-decoration: none; color: inherit; }
    .coiffeur-home-card:hover { transform: translateY(-6px); border-color: rgba(212,175,55,.40); box-shadow: var(--shadow-lg); }
    .chc-cover { width: 100%; height: 160px; background: linear-gradient(135deg, var(--dark-3), var(--dark-4)); position: relative; overflow: hidden; }
    .chc-cover img { width: 100%; height: 100%; object-fit: cover; }
    .chc-cover-ph { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: rgba(255,255,255,.08); }
    .chc-badge { position: absolute; top: 10px; right: 10px; background: rgba(25,135,84,.90); color: #fff; font-size: .65rem; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
    .chc-body { padding: 1.1rem 1.1rem 1rem; flex: 1; display: flex; flex-direction: column; }
    .chc-name { font-weight: 700; font-size: .92rem; color: var(--text-primary); margin-bottom: 4px; }
    .chc-loc { font-size: .75rem; color: var(--text-muted); margin-bottom: 8px; }
    .chc-stars { font-size: .78rem; color: var(--gold); letter-spacing: 1px; margin-bottom: 10px; }
    .chc-price { font-size: .8rem; color: var(--text-muted); margin-top: auto; }
    .chc-price strong { color: var(--gold); font-size: .92rem; }

    /* ── Avis clients carousel ── */
    .reviews-scroll { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 8px; scrollbar-width: none; }
    .reviews-scroll::-webkit-scrollbar { display: none; }
    .rv-home-card { background: var(--dark-2); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 1.25rem; flex-shrink: 0; width: 280px; }
    .rv-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .rv-avatar { width: 36px; height: 36px; border-radius: 50%; background: rgba(212,175,55,.14); border: 1px solid rgba(212,175,55,.25); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--gold); font-size: .82rem; flex-shrink: 0; }
    .rv-name { font-weight: 600; font-size: .82rem; color: var(--text-primary); }
    .rv-date { font-size: .68rem; color: var(--text-muted); }
    .rv-stars { font-size: .82rem; color: var(--gold); letter-spacing: 1px; margin-bottom: 8px; }
    .rv-text { font-size: .8rem; color: var(--text-secondary); line-height: 1.6; margin: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    /* ── CTA final ── */
    .cta-final { background: var(--glass-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--glass-border-md); border-radius: var(--radius-xl); padding: 4rem 2rem; text-align: center; }
    .cta-final-title { font-family: var(--font-display); font-size: clamp(1.8rem,4vw,2.8rem); font-weight: 700; color: var(--text-primary); margin-bottom: .75rem; }
    .cta-final-sub { color: var(--text-muted); font-size: .92rem; margin-bottom: 2rem; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .home-section { padding: 3.5rem 0; }
        .rv-home-card { width: 240px; }
    }
    @media (max-width: 576px) {
        .cta-final { padding: 2.5rem 1.25rem; }
    }
    </style>
</head>
<body>

<!-- ════════════════════════════
     FLOATING HERO CAPSULE
     ════════════════════════════ -->
<?php include __DIR__ . '/components/hero_premium.php'; ?>

<!-- ════════════════════════════
     SECTION — COMMENT ÇA MARCHE
     ════════════════════════════ -->
<section class="home-section home-section--alt" aria-labelledby="how-heading">
    <div class="home-wrap">
        <div class="text-center mb-5">
            <span class="section-eyebrow">Simple &amp; rapide</span>
            <h2 id="how-heading" class="section-heading" style="text-align:center;">Comment ça marche ?</h2>
            <p class="section-sub mx-auto text-center">Réservez une coiffure à domicile en 3 étapes.</p>
        </div>
        <div class="row g-4 justify-content-center align-items-stretch">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="how-card fade-in-card">
                    <div class="how-num">1</div>
                    <div class="how-title">Parcourez l'annuaire</div>
                    <p class="how-desc">Filtrez par ville, quartier ou budget. Consultez les profils certifiés.</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="how-card fade-in-card" style="animation-delay:.1s">
                    <div class="how-num">2</div>
                    <div class="how-title">Choisissez un créneau</div>
                    <p class="how-desc">Sélectionnez un jour et une heure disponibles dans l'agenda du coiffeur.</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="how-card fade-in-card" style="animation-delay:.2s">
                    <div class="how-num">3</div>
                    <div class="how-title">Recevez le coiffeur</div>
                    <p class="how-desc">Le professionnel se déplace chez vous. Paiement sécurisé après prestation.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════
     SECTION — POURQUOI CCT
     ════════════════════════════ -->
<section class="home-section" aria-labelledby="why-heading">
    <div class="home-wrap">
        <div class="row g-5 align-items-center">
            <!-- Colonne gauche : texte -->
            <div class="col-12 col-lg-6">
                <span class="section-eyebrow">Notre promesse</span>
                <h2 id="why-heading" class="section-heading">Pourquoi choisir Coiffe Chez Toi ?</h2>
                <p class="section-sub mb-4">La première plateforme de coiffure à domicile au Bénin. Professionnels vérifiés, paiement sécurisé, expérience premium.</p>
                <div>
                    <div class="why-point">
                        <div class="why-point-icon"><i class="bi bi-patch-check-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="why-point-title">Coiffeurs certifiés</div>
                            <p class="why-point-desc">Chaque coiffeur passe par une vérification de diplôme avant d'accéder à la plateforme.</p>
                        </div>
                    </div>
                    <div class="why-point">
                        <div class="why-point-icon"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="why-point-title">Paiement sécurisé</div>
                            <p class="why-point-desc">Les fonds sont gelés jusqu'à confirmation. Remboursement intégral si le coiffeur ne se présente pas.</p>
                        </div>
                    </div>
                    <div class="why-point">
                        <div class="why-point-icon"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="why-point-title">Déplacement à domicile</div>
                            <p class="why-point-desc">Zéro déplacement pour vous. Le coiffeur vient à l'adresse de votre choix, à l'heure convenue.</p>
                        </div>
                    </div>
                    <div class="why-point">
                        <div class="why-point-icon"><i class="bi bi-star-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="why-point-title">Avis transparents</div>
                            <p class="why-point-desc">Les notes et commentaires sont réels, vérifiés après chaque prestation terminée.</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Colonne droite : statistiques -->
            <div class="col-12 col-lg-6">
                <div class="row g-3">
                    <?php
                    // Stats dynamiques ou fallback
                    $nb_coiffeurs = 0; $nb_clients = 0; $nb_rdv = 0; $note_plateforme = 0;
                    try {
                        $nb_coiffeurs     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='coiffeur' AND abonnement_status=1")->fetchColumn();
                        $nb_clients       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
                        $nb_rdv           = (int)$pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut_rdv='termine'")->fetchColumn();
                        $r = $pdo->query("SELECT ROUND(AVG(note),1) FROM commentaires WHERE type_commentaire='public'")->fetchColumn();
                        $note_plateforme  = floatval($r ?? 4.8);
                    } catch (Exception $e) {
                        $nb_coiffeurs = 12; $nb_clients = 84; $nb_rdv = 230; $note_plateforme = 4.8;
                    }
                    $stats_home = [
                        ['val' => $nb_coiffeurs . '+', 'lbl' => 'Coiffeurs certifiés'],
                        ['val' => $nb_clients . '+',   'lbl' => 'Clients satisfaits'],
                        ['val' => $nb_rdv . '+',       'lbl' => 'Prestations réalisées'],
                        ['val' => $note_plateforme ?: '4.8', 'lbl' => 'Note moyenne /5'],
                    ];
                    foreach ($stats_home as $st):
                    ?>
                    <div class="col-6">
                        <div class="glass-cct why-stat">
                            <span class="why-stat-num"><?= htmlspecialchars((string)$st['val']) ?></span>
                            <span class="why-stat-lbl"><?= htmlspecialchars($st['lbl']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════
     SECTION — COIFFEURS POPULAIRES
     ════════════════════════════ -->
<section class="home-section home-section--alt" aria-labelledby="coiffeurs-heading">
    <div class="home-wrap">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-5">
            <div>
                <span class="section-eyebrow">Talents vérifiés</span>
                <h2 id="coiffeurs-heading" class="section-heading mb-0">Coiffeurs populaires</h2>
            </div>
            <a href="/coiffons/filter/annuaire_coiffeurs.php"
               class="btn-ghost btn-sm"
               aria-label="Voir tous les coiffeurs">
                Voir tout <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>

        <?php if (!empty($coiffeurs_populaires)): ?>
        <div class="row g-4">
            <?php foreach ($coiffeurs_populaires as $cp):
                $photo_url = null;
                if (!empty($cp['photo_profil'])) {
                    $paths = [
                        __DIR__ . '/../access/' . $cp['photo_profil'],
                        __DIR__ . '/../uploads/profil/' . basename($cp['photo_profil']),
                    ];
                    foreach ($paths as $path) {
                        if (file_exists($path)) {
                            $photo_url = '/coiffons/access/' . $cp['photo_profil'];
                            break;
                        }
                    }
                }
                $nm  = floatval($cp['note_moyenne'] ?? 0);
                $nba = intval($cp['nb_avis'] ?? 0);
                $prix = intval($cp['tarif_base'] ?? 0);
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="/coiffons/coiffeurs/profil_public.php?id=<?= $cp['id'] ?>"
                   class="coiffeur-home-card"
                   aria-label="Profil de <?= htmlspecialchars($cp['nom'] . ' ' . $cp['prenom']) ?>">
                    <div class="chc-cover">
                        <?php if ($photo_url): ?>
                            <img src="<?= htmlspecialchars($photo_url) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <div class="chc-cover-ph"><i class="bi bi-person-badge"></i></div>
                        <?php endif; ?>
                        <span class="chc-badge">
                            <i class="bi bi-patch-check-fill me-1"></i>Certifié
                        </span>
                    </div>
                    <div class="chc-body">
                        <div class="chc-name"><?= htmlspecialchars(strtoupper($cp['nom']) . ' ' . htmlspecialchars($cp['prenom'] ?? '')) ?></div>
                        <div class="chc-loc">
                            <i class="bi bi-geo-alt" style="color:var(--gold)"></i>
                            <?= htmlspecialchars($cp['nom_ville'] ?? 'Bénin') ?>
                            <?php if (!empty($cp['nom_quartier'])): ?>
                                · <?= htmlspecialchars($cp['nom_quartier']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($nba > 0): ?>
                            <div class="chc-stars">
                                <?php for ($s=1;$s<=5;$s++): ?>
                                    <span style="color:<?= $s<=round($nm)?'var(--gold)':'rgba(255,255,255,.15)' ?>">★</span>
                                <?php endfor; ?>
                                <span style="color:var(--text-muted);font-size:.72rem;margin-left:4px;"><?= $nm ?> (<?= $nba ?> avis)</span>
                            </div>
                        <?php else: ?>
                            <div class="chc-stars" style="color:rgba(255,255,255,.2)">★★★★★ <span style="color:var(--text-muted);font-size:.72rem;margin-left:4px;">Nouveau</span></div>
                        <?php endif; ?>
                        <div class="chc-price">
                            <?php if ($prix > 0): ?>
                                Dès <strong><?= number_format($prix, 0, ',', ' ') ?> FCFA</strong>
                            <?php else: ?>
                                <strong>Sur devis</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Empty state si BDD vide -->
        <div class="text-center py-5">
            <i class="bi bi-scissors" style="font-size:2.5rem;color:rgba(255,255,255,.1);display:block;margin-bottom:1rem;"></i>
            <p style="color:var(--text-muted);font-size:.88rem;">
                Les premiers coiffeurs rejoignent bientôt la plateforme.
                <a href="/coiffons/index.php?page=register&role=coiffeur" style="color:var(--gold);">Inscrivez-vous →</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════════════════
     SECTION — AVIS CLIENTS
     ════════════════════════════ -->
<?php if (!empty($avis_recents)): ?>
<section class="home-section" aria-labelledby="avis-heading">
    <div class="home-wrap">
        <div class="mb-5">
            <span class="section-eyebrow">Ils nous font confiance</span>
            <h2 id="avis-heading" class="section-heading mb-0">Ce que disent nos clients</h2>
        </div>
        <div class="reviews-scroll" role="list" aria-label="Avis clients récents">
            <?php foreach ($avis_recents as $av):
                $note_av    = intval($av['note'] ?? 5);
                $initiale_av = strtoupper(substr($av['client_prenom'] ?? 'C', 0, 1));
                $prenom_masque = strtoupper(substr($av['client_prenom'] ?? 'C', 0, 1)) . '***';
                $date_av = !empty($av['date_creation']) ? date('d/m/Y', strtotime($av['date_creation'])) : '';
            ?>
            <article class="rv-home-card" role="listitem">
                <div class="rv-head">
                    <div class="rv-avatar" aria-hidden="true"><?= htmlspecialchars($initiale_av) ?></div>
                    <div>
                        <div class="rv-name"><?= htmlspecialchars($prenom_masque) ?></div>
                        <?php if ($date_av): ?>
                            <div class="rv-date"><?= $date_av ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="rv-stars" aria-label="Note : <?= $note_av ?>/5">
                    <?php for ($s=1;$s<=5;$s++): ?>
                        <span style="color:<?= $s<=$note_av?'var(--gold)':'rgba(255,255,255,.15)' ?>">★</span>
                    <?php endfor; ?>
                </div>
                <?php if (!empty($av['message'])): ?>
                    <p class="rv-text">"<?= htmlspecialchars($av['message']) ?>"</p>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════
     SECTION — CTA FINAL
     ════════════════════════════ -->
<section class="home-section home-section--dark3" aria-labelledby="cta-heading">
    <div class="home-wrap" style="max-width:800px;">
        <div class="cta-final">
            <h2 id="cta-heading" class="cta-final-title">Prêt à commencer ?</h2>
            <p class="cta-final-sub">
                Rejoignez Coiffe Chez Toi — la coiffure professionnelle, directement chez vous.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/coiffons/filter/annuaire_coiffeurs.php"
                   class="btn-gold btn-lg"
                   aria-label="Trouver un coiffeur">
                    <i class="bi bi-search me-2" aria-hidden="true"></i>Trouver un coiffeur
                </a>
                <a href="/coiffons/index.php?page=register&role=coiffeur"
                   class="btn-outline-gold btn-lg"
                   aria-label="Devenir coiffeur partenaire">
                    <i class="bi bi-scissors me-2" aria-hidden="true"></i>Devenir coiffeur
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════
     FOOTER GLOBAL
     ════════════════════════════ -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/components/footer_global.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ── IA ASSISTANT ── -->
<?php
include __DIR__ . '/components/ia_assistant.php';
?>

</body>
</html>
