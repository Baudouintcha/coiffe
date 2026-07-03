<!DOCTYPE html>
<html lang="<?= $lang ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metier['nom']) ?> — Domizi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gold: #D4AF37; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .back-btn {
            position: fixed;
            top: 24px;
            left: 24px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .back-btn:hover { color: var(--gold); }

        .metier-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(212,175,55,0.1);
            border: 1px solid rgba(212,175,55,0.3);
            border-radius: 30px;
            padding: 6px 18px;
            font-size: 0.8rem;
            color: var(--gold);
            margin-bottom: 2rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(1.8rem, 5vw, 3rem);
            font-weight: 300;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        h1 strong { font-weight: 800; color: var(--gold); }

        .subtitle {
            color: rgba(255,255,255,0.45);
            font-size: 0.95rem;
            margin-bottom: 3rem;
        }

        .role-cards {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .role-card {
            width: 220px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            background: rgba(255,255,255,0.03);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        .role-card:hover {
            border-color: var(--gold);
            background: rgba(212,175,55,0.07);
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.5);
            color: #fff;
        }
        .role-card .icon-wrap {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(212,175,55,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: var(--gold);
        }
        .role-card .card-title {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }
        .role-card .card-desc {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.4);
            line-height: 1.5;
        }

        @media (max-width: 480px) {
            .role-card { width: 100%; flex-direction: row; text-align: left; padding: 1.5rem; }
            .role-card .card-title { font-size: 0.95rem; }
        }
    </style>
</head>
<body>

    <a href="/" class="back-btn">
        <i class="bi bi-arrow-left"></i> Domizi
    </a>

    <div class="text-center px-4" style="max-width: 600px;">

        <div class="metier-badge">
            <i class="bi <?= htmlspecialchars($metier['icone'] ?? 'bi-grid') ?>"></i>
            <?= htmlspecialchars($metier['nom']) ?>
        </div>

        <h1>Vous êtes <strong>ici pour</strong> ?</h1>
        <p class="subtitle">Choisissez votre profil pour continuer</p>

        <div class="role-cards">

            <a href="/<?= htmlspecialchars($metier['slug']) ?>/client" class="role-card">
                <div class="icon-wrap">
                    <i class="bi bi-person-search"></i>
                </div>
                <div>
                    <div class="card-title">Je cherche un professionnel</div>
                    <div class="card-desc">Trouver et réserver une prestation à domicile</div>
                </div>
            </a>

            <a href="/<?= htmlspecialchars($metier['slug']) ?>/prestataire" class="role-card">
                <div class="icon-wrap">
                    <i class="bi bi-briefcase"></i>
                </div>
                <div>
                    <div class="card-title">Je propose mes services</div>
                    <div class="card-desc">Créer mon profil et recevoir des clients</div>
                </div>
            </a>

        </div>
    </div>

</body>
</html>
