<!DOCTYPE html>
<html lang="<?= $lang ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domizi — Le service à domicile, chez toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold:       #D4AF37;
            --gold-dim:   rgba(212,175,55,0.15);
            --dark:       #0a0a0a;
            --dark-2:     #111111;
            --text-muted: rgba(255,255,255,0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--dark);
            color: #fff;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* ── HERO BACKGROUND ────────────────────────────── */
        .hero-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            /* Image composite : à remplacer par la vraie photo */
            background:
                linear-gradient(
                    135deg,
                    rgba(10,10,10,0.92) 0%,
                    rgba(10,10,10,0.75) 50%,
                    rgba(10,10,10,0.88) 100%
                ),
                url('/coiffons/images/hero-domizi.jpg') center/cover no-repeat;
        }

        /* Overlay subtil en bas pour la lisibilité */
        .hero-bg::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(to top, var(--dark), transparent);
        }

        /* ── LAYOUT PRINCIPAL ───────────────────────────── */
        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto 1fr auto;
        }

        /* ── HEADER ─────────────────────────────────────── */
        .site-header {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 1.5rem 2.5rem;
        }

        .lang-switcher {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px;
            padding: 4px 8px;
        }
        .lang-btn {
            color: rgba(255,255,255,0.45);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 20px;
            transition: all 0.2s;
            text-transform: uppercase;
        }
        .lang-btn:hover,
        .lang-btn.active {
            background: var(--gold-dim);
            color: var(--gold);
        }

        /* ── COLONNE GAUCHE ─────────────────────────────── */
        .col-left {
            grid-column: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 3rem 4rem 3.5rem;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: clamp(3.5rem, 8vw, 7rem);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -2px;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .brand-name span { color: var(--gold); }

        .brand-tagline {
            font-size: 1rem;
            color: rgba(255,255,255,0.55);
            font-weight: 300;
            line-height: 1.6;
            max-width: 380px;
            margin-bottom: 2rem;
        }

        .brand-desc {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.3);
            max-width: 340px;
            line-height: 1.7;
        }

        /* Ligne décorative dorée */
        .gold-line {
            width: 48px;
            height: 2px;
            background: var(--gold);
            margin-bottom: 2rem;
            border-radius: 2px;
        }

        /* ── COLONNE DROITE ─────────────────────────────── */
        .col-right {
            grid-column: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 3.5rem 4rem 2rem;
        }

        .question-label {
            font-size: 0.75rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .question-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 700;
            margin-bottom: 2.5rem;
            line-height: 1.2;
        }

        /* ── GRILLE DOMAINES ────────────────────────────── */
        .domaines-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .domaine-card {
            position: relative;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 1.4rem 1.2rem;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            background: rgba(255,255,255,0.02);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            overflow: hidden;
        }

        .domaine-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--gold-dim), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .domaine-card:hover {
            border-color: rgba(212,175,55,0.4);
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.5);
            color: #fff;
        }
        .domaine-card:hover::before { opacity: 1; }

        .domaine-card.inactive {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }

        .domaine-icon {
            font-size: 1.5rem;
            color: var(--gold);
        }

        .domaine-name {
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .domaine-meta {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .soon-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.6rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 2px 8px;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ── SOUS-MÉTIERS (apparaît au clic sur un domaine) ── */
        .metiers-panel {
            display: none;
            grid-column: 2;
            flex-direction: column;
            justify-content: center;
            padding: 0 3.5rem 4rem 2rem;
            animation: fadeSlideIn 0.3s ease;
        }
        .metiers-panel.visible {
            display: flex;
        }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .metiers-list {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .metier-btn {
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            background: rgba(255,255,255,0.02);
            display: flex;
            align-items: center;
            gap: 0.9rem;
            transition: all 0.25s;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .metier-btn:hover {
            border-color: var(--gold);
            background: var(--gold-dim);
            color: #fff;
            transform: translateX(4px);
        }
        .metier-btn i {
            font-size: 1.1rem;
            color: var(--gold);
            min-width: 22px;
        }
        .metier-btn .metier-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            display: block;
            margin-top: 1px;
        }

        .back-domaines {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.78rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 1.5rem;
            padding: 0;
            transition: color 0.2s;
        }
        .back-domaines:hover { color: var(--gold); }

        /* ── FOOTER ─────────────────────────────────────── */
        .site-footer {
            grid-column: 1 / -1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .site-footer p {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.5px;
        }

        /* ── RESPONSIVE MOBILE ──────────────────────────── */
        @media (max-width: 768px) {
            .page-wrapper {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto auto;
            }
            .col-left {
                grid-column: 1;
                padding: 2rem 1.5rem 1.5rem;
                text-align: center;
                align-items: center;
            }
            .brand-tagline, .brand-desc { max-width: 100%; }
            .gold-line { margin: 0 auto 2rem; }

            .col-right {
                grid-column: 1;
                padding: 0 1.5rem 2rem;
            }
            .metiers-panel {
                grid-column: 1;
                padding: 0 1.5rem 2rem;
            }
            .domaines-grid {
                grid-template-columns: 1fr 1fr;
            }
            .site-header { padding: 1.2rem 1.5rem; }
        }

        @media (max-width: 380px) {
            .domaines-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="hero-bg"></div>

<div class="page-wrapper">

    <!-- ── HEADER ── -->
    <header class="site-header">
        <nav class="lang-switcher" aria-label="Changer la langue">
            <?php
            $langs = ['fr' => 'FR', 'en' => 'EN', 'es' => 'ES', 'ar' => 'AR'];
            foreach ($langs as $code => $label):
            ?>
                <a href="/lang/<?= $code ?>"
                   class="lang-btn <?= $lang === $code ? 'active' : '' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </header>

    <!-- ── COLONNE GAUCHE : IDENTITÉ DOMIZI ── -->
    <section class="col-left">
        <div class="gold-line"></div>

        <h1 class="brand-name">
            DOM<span>IZI</span>
        </h1>

        <p class="brand-tagline">
            <?= match($lang) {
                'en' => 'Home services, at your doorstep.',
                'es' => 'El servicio a domicilio, en tu casa.',
                'ar' => 'خدمة منزلية، في منزلك.',
                default => 'Le service à domicile, chez toi.'
            } ?>
        </p>

        <p class="brand-desc">
            <?= match($lang) {
                'en' => 'Find a qualified professional near you, or offer your services at home.',
                'es' => 'Encuentra un profesional calificado cerca de ti, u ofrece tus servicios.',
                'ar' => 'ابحث عن محترف مؤهل بالقرب منك، أو قدم خدماتك في المنازل.',
                default => 'Trouvez un professionnel qualifié près de chez vous, ou proposez vos services à domicile.'
            } ?>
        </p>
    </section>

    <!-- ── COLONNE DROITE : SÉLECTION DOMAINE ── -->
    <section class="col-right" id="domaines-section">

        <p class="question-label">
            <?= match($lang) {
                'en' => 'You are here for',
                'es' => 'Estás aquí para',
                'ar' => 'أنت هنا من أجل',
                default => 'Vous êtes ici pour'
            } ?>
        </p>

        <h2 class="question-title">
            <?= match($lang) {
                'en' => 'What do you need?',
                'es' => '¿Qué necesitas?',
                'ar' => 'ما الذي تحتاج إليه؟',
                default => 'De quoi avez-vous besoin ?'
            } ?>
        </h2>

        <div class="domaines-grid" role="list">
            <?php foreach ($domaines as $domaine): ?>
                <?php if ($domaine['actif']): ?>
                    <button
                        class="domaine-card"
                        onclick="ouvrirDomaine(<?= $domaine['id'] ?>, '<?= htmlspecialchars($domaine['nom']) ?>')"
                        role="listitem"
                        aria-label="<?= htmlspecialchars($domaine['nom']) ?>"
                        style="border-left: 3px solid <?= htmlspecialchars($domaine['couleur'] ?? '#D4AF37') ?>;"
                    >
                        <i class="bi <?= htmlspecialchars($domaine['icone'] ?? 'bi-grid') ?> domaine-icon"></i>
                        <div>
                            <div class="domaine-name"><?= htmlspecialchars($domaine['nom']) ?></div>
                            <div class="domaine-meta">
                                <?= $domaine['nb_metiers'] ?> service<?= $domaine['nb_metiers'] > 1 ? 's' : '' ?>
                            </div>
                        </div>
                    </button>
                <?php else: ?>
                    <div class="domaine-card inactive" role="listitem">
                        <i class="bi <?= htmlspecialchars($domaine['icone'] ?? 'bi-grid') ?> domaine-icon"></i>
                        <div>
                            <div class="domaine-name"><?= htmlspecialchars($domaine['nom']) ?></div>
                        </div>
                        <span class="soon-badge">
                            <?= match($lang) {
                                'en' => 'Soon',
                                'es' => 'Pronto',
                                'ar' => 'قريباً',
                                default => 'Bientôt'
                            } ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── PANEL SOUS-MÉTIERS (affiché après clic sur un domaine) ── -->
    <section class="metiers-panel" id="metiers-section" aria-live="polite">
        <button class="back-domaines" onclick="retourDomaines()">
            <i class="bi bi-arrow-left"></i>
            <span id="back-label">
                <?= match($lang) {
                    'en' => 'Back',
                    'es' => 'Volver',
                    'ar' => 'رجوع',
                    default => 'Retour'
                } ?>
            </span>
        </button>

        <p class="question-label" id="metiers-domaine-label"></p>

        <h2 class="question-title">
            <?= match($lang) {
                'en' => 'Which service?',
                'es' => '¿Qué servicio?',
                'ar' => 'أي خدمة؟',
                default => 'Quel service ?'
            } ?>
        </h2>

        <div class="metiers-list" id="metiers-list" role="list">
            <!-- Rempli dynamiquement via fetch() -->
        </div>
    </section>

    <!-- ── FOOTER ── -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> Domizi &mdash; Bénin & Afrique de l'Ouest</p>
    </footer>

</div>

<script>
/**
 * Quand l'utilisateur clique sur un domaine :
 * 1. Appelle l'API pour récupérer les métiers
 * 2. Cache le panel domaines
 * 3. Affiche le panel sous-métiers avec les résultats
 */
async function ouvrirDomaine(idDomaine, nomDomaine) {
    try {
        const response = await fetch(`/api/metiers/${idDomaine}`);
        const data = await response.json();

        if (!data.success || data.metiers.length === 0) return;

        // Met à jour le label du panel
        document.getElementById('metiers-domaine-label').textContent = nomDomaine;

        // Construit la liste des métiers
        const list = document.getElementById('metiers-list');
        list.innerHTML = '';

        data.metiers.forEach(metier => {
            const btn = document.createElement('a');
            btn.href = `/${metier.slug}`;
            btn.className = 'metier-btn';
            btn.setAttribute('role', 'listitem');
            btn.innerHTML = `
                <i class="bi ${metier.icone || 'bi-grid'}"></i>
                <div>
                    <span>${metier.nom}</span>
                    <span class="metier-desc">${metier.description || ''}</span>
                </div>
            `;
            list.appendChild(btn);
        });

        // Animation : cache domaines, montre métiers
        const domainesSection = document.getElementById('domaines-section');
        const metiersSection  = document.getElementById('metiers-section');

        domainesSection.style.display = 'none';
        metiersSection.classList.add('visible');

    } catch (err) {
        console.error('Erreur chargement métiers:', err);
    }
}

function retourDomaines() {
    document.getElementById('domaines-section').style.display = '';
    document.getElementById('metiers-section').classList.remove('visible');
}
</script>

</body>
</html>
