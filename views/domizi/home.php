<!DOCTYPE html>
<html lang="<?= $lang ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domizi — Le service à domicile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #D4AF37;
            --gold-dim: rgba(212, 175, 55, 0.15);
            --dark: #0a0a0a;
            --dark-2: #111111;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--dark);
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── FOND PHOTO ── */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: url('/coiffons/images/domizi-scene.jpg') center/cover no-repeat;
            filter: brightness(0.18) saturate(0.6);
        }
        /* Dégradé gauche → droite pour lisibilité du texte gauche */
        .bg-scene::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                105deg,
                rgba(10,10,10,0.97) 0%,
                rgba(10,10,10,0.75) 45%,
                rgba(10,10,10,0.35) 100%
            );
        }

        /* ── SWITCHER LANGUE — ICÔNE DROPDOWN ── */
        .lang-switcher {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 100;
        }
        .lang-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.7);
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        .lang-toggle:hover {
            background: var(--gold-dim);
            border-color: var(--gold);
            color: var(--gold);
        }
        .lang-toggle .bi-globe2 { font-size: 0.9rem; }
        .lang-chevron {
            font-size: 0.65rem;
            transition: transform 0.2s;
        }
        .lang-chevron.open { transform: rotate(180deg); }

        .lang-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: #111;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
            min-width: 150px;
            display: none;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
        }
        .lang-dropdown.open { display: block; }

        .lang-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            text-decoration: none;
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            transition: all 0.15s;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .lang-option:last-child { border-bottom: none; }
        .lang-option:hover { background: rgba(255,255,255,0.04); color: #fff; }
        .lang-option.active { color: var(--gold); }
        .lang-code {
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 1px;
            min-width: 24px;
        }
        .lang-label { font-weight: 400; }

        /* ── LAYOUT PRINCIPAL ── */
        .main-layout {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        /* ── COLONNE GAUCHE : Nom + Description ── */
        .col-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 6rem 5rem 6rem 6rem;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: clamp(4rem, 8vw, 7rem);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -2px;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .brand-name span {
            color: var(--gold);
        }

        .brand-tagline {
            font-size: clamp(0.95rem, 1.5vw, 1.15rem);
            font-weight: 300;
            color: rgba(255,255,255,0.55);
            line-height: 1.7;
            max-width: 380px;
            margin-bottom: 3rem;
        }

        .brand-line {
            width: 60px;
            height: 2px;
            background: var(--gold);
            margin-bottom: 2rem;
            opacity: 0.6;
        }

        /* ── COLONNE DROITE : Question + Domaines ── */
        .col-right {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 6rem 6rem 6rem 4rem;
            border-left: 1px solid rgba(255,255,255,0.05);
        }

        .section-label {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .question {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.4rem, 2.5vw, 2rem);
            font-weight: 400;
            line-height: 1.3;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2.5rem;
        }

        /* ── GRILLE DES DOMAINES ── */
        .domaines-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .domaine-card {
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 1.4rem 1.2rem;
            cursor: pointer;
            background: rgba(255,255,255,0.02);
            transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            position: relative;
            overflow: hidden;
        }
        .domaine-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--gold-dim), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }
        .domaine-card:hover::before { opacity: 1; }
        .domaine-card:hover {
            border-color: rgba(212,175,55,0.4);
            transform: translateY(-2px);
        }
        .domaine-card.inactive {
            opacity: 0.35;
            cursor: not-allowed;
        }
        .domaine-card.inactive:hover {
            transform: none;
            border-color: rgba(255,255,255,0.08);
        }
        .domaine-card.inactive:hover::before { opacity: 0; }

        .domaine-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--gold-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--gold);
            flex-shrink: 0;
        }
        .domaine-name {
            font-size: 0.88rem;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
            letter-spacing: 0.2px;
        }
        .domaine-sub {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.3);
        }
        .domaine-soon {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.6rem;
            background: rgba(255,255,255,0.07);
            color: rgba(255,255,255,0.3);
            border-radius: 4px;
            padding: 2px 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ── SOUS-MÉTIERS (apparaît après clic domaine) ── */
        .metiers-panel {
            display: none;
            flex-direction: column;
            gap: 0.6rem;
        }
        .metiers-panel.visible { display: flex; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgba(255,255,255,0.35);
            font-size: 0.78rem;
            cursor: pointer;
            margin-bottom: 1.2rem;
            background: none;
            border: none;
            padding: 0;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--gold); }

        .metier-item {
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 1rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            color: #fff;
            background: rgba(255,255,255,0.02);
            transition: all 0.2s;
        }
        .metier-item:hover {
            border-color: var(--gold);
            background: var(--gold-dim);
            color: #fff;
            transform: translateX(4px);
        }
        .metier-item .mi-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--gold-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: var(--gold);
            flex-shrink: 0;
        }
        .metier-item .mi-name {
            font-size: 0.88rem;
            font-weight: 500;
        }
        .metier-item .mi-desc {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.35);
        }
        .metier-item .mi-arrow {
            margin-left: auto;
            color: rgba(255,255,255,0.2);
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .metier-item:hover .mi-arrow {
            color: var(--gold);
            transform: translateX(3px);
        }

        /* ── RESPONSIVE MOBILE ── */
        @media (max-width: 900px) {
            .main-layout {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            .col-left {
                padding: 6rem 2rem 2rem 2rem;
                text-align: center;
                align-items: center;
            }
            .brand-tagline { text-align: center; max-width: 100%; }
            .brand-line { margin: 0 auto 2rem auto; }
            .col-right {
                padding: 2rem 2rem 4rem 2rem;
                border-left: none;
                border-top: 1px solid rgba(255,255,255,0.05);
            }
        }
        @media (max-width: 480px) {
            .domaines-grid { grid-template-columns: 1fr; }
            .lang-switcher { top: 16px; right: 16px; }
        }
    </style>
</head>
<body>

<div class="bg-scene"></div>

        <!-- Sélecteur de langue — icône dropdown -->
        <div class="lang-switcher" id="langSwitcher">
            <button class="lang-toggle" onclick="toggleLang(event)" title="Changer la langue">
                <i class="bi bi-globe2"></i>
                <span id="lang-current"><?= strtoupper($lang) ?></span>
                <i class="bi bi-chevron-down lang-chevron" id="lang-chevron"></i>
            </button>
            <div class="lang-dropdown" id="langDropdown">
                <?php
                $langs = ['fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'ar' => 'العربية'];
                foreach ($langs as $code => $label):
                ?>
                    <a href="/coiffons/app.php?lang=<?= $code ?>"
                       class="lang-option <?= $lang === $code ? 'active' : '' ?>">
                        <span class="lang-code"><?= strtoupper($code) ?></span>
                        <span class="lang-label"><?= $label ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

<div class="main-layout">

    <!-- ── COLONNE GAUCHE ── -->
    <div class="col-left">
        <div class="brand-name">
            Dom<span>i</span>zi
        </div>
        <div class="brand-line"></div>
        <p class="brand-tagline">
            Le service à domicile, chez vous.<br>
            Des professionnels qualifiés, partout dans le Bénin.           
        </p>
    </div>

    <!-- ── COLONNE DROITE ── -->
    <div class="col-right">

        <!-- VUE 1 : Choix du domaine -->
        <div id="view-domaines">
            <p class="section-label">Bienvenue</p>
            <h2 class="question">
                Qu'est-ce qui vous amène ?
            </h2>

            <div class="domaines-grid">
                <?php foreach ($domaines as $domaine): ?>
                    <?php if ($domaine['actif']): ?>
                        <div class="domaine-card"
                             onclick="chargerMetiers(<?= $domaine['id'] ?>, '<?= htmlspecialchars($domaine['nom']) ?>')">
                            <div class="domaine-icon">
                                <i class="bi <?= htmlspecialchars($domaine['icone'] ?? 'bi-grid') ?>"></i>
                            </div>
                            <div>
                                <div class="domaine-name"><?= htmlspecialchars($domaine['nom']) ?></div>
                                <div class="domaine-sub"><?= $domaine['nb_metiers'] ?> service<?= $domaine['nb_metiers'] > 1 ? 's' : '' ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="domaine-card inactive">
                            <span class="domaine-soon">Bientôt</span>
                            <div class="domaine-icon">
                                <i class="bi <?= htmlspecialchars($domaine['icone'] ?? 'bi-grid') ?>"></i>
                            </div>
                            <div>
                                <div class="domaine-name"><?= htmlspecialchars($domaine['nom']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- VUE 2 : Sous-métiers (chargés dynamiquement) -->
        <div id="view-metiers" style="display:none;">
            <button class="back-link" onclick="retourDomaines()">
                <i class="bi bi-arrow-left"></i> Retour
            </button>
            <p class="section-label" id="label-domaine-actif"></p>
            <h2 class="question">Quel service recherchez-vous ?</h2>

            <div class="metiers-panel visible" id="metiers-list">
                <!-- Chargé en JS -->
            </div>
        </div>

    </div>
</div>

<script>
    // ── DROPDOWN LANGUE ──
    function toggleLang(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('langDropdown');
        const chevron  = document.getElementById('lang-chevron');
        dropdown.classList.toggle('open');
        chevron.classList.toggle('open');
    }
    // Ferme si clic ailleurs
    document.addEventListener('click', () => {
        document.getElementById('langDropdown').classList.remove('open');
        document.getElementById('lang-chevron').classList.remove('open');
    });

    // ── CHARGEMENT DES MÉTIERS ──
    async function chargerMetiers(idDomaine, nomDomaine) {
        try {
            // Fonctionne avec index.php : on passe par ?action=api_metiers&domaine=X
            const resp = await fetch(`/coiffons/index.php?action=api_metiers&domaine=${idDomaine}`);
            const data = await resp.json();

            if (!data.success || !data.metiers.length) {
                alert('Aucun service disponible pour ce domaine pour le moment.');
                return;
            }

            document.getElementById('label-domaine-actif').textContent = nomDomaine;

            const list = document.getElementById('metiers-list');
            list.innerHTML = '';

            data.metiers.forEach(m => {
                list.innerHTML += `
                    <a href="/coiffons/app.php?metier=${m.slug}" class="metier-item">
                        <div class="mi-icon">
                            <i class="bi ${m.icone || 'bi-grid'}"></i>
                        </div>
                        <div>
                            <div class="mi-name">${m.nom}</div>
                            <div class="mi-desc">${m.description || ''}</div>
                        </div>
                        <i class="bi bi-arrow-right mi-arrow"></i>
                    </a>
                `;
            });

            document.getElementById('view-domaines').style.display = 'none';
            document.getElementById('view-metiers').style.display  = 'block';

        } catch (e) {
            console.error('Erreur chargement métiers:', e);
        }
    }

    function retourDomaines() {
        document.getElementById('view-metiers').style.display  = 'none';
        document.getElementById('view-domaines').style.display = 'block';
    }
</script>

</body>
</html>
