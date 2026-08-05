<?php
/**
 * coiffeurs/bienvenue.php — Écran de bienvenue post-inscription coiffeur
 * Affiché une seule fois, juste après l'inscription.
 * Explique le flow : payer → diplôme validé → visible.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : coiffeur connecté uniquement
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'coiffeur') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

// Nettoyer le flag de session
unset($_SESSION['nouveau_coiffeur']);

$prenom = htmlspecialchars($_SESSION['prenom'] ?? 'Coiffeur');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur Coiffe Chez Toi ✂️</title>
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    body { background: var(--dark); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }

    .welcome-card {
        max-width: 560px;
        width: 100%;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(212,175,55,0.20);
        border-radius: var(--radius-2xl);
        padding: 2.5rem 2rem;
        text-align: center;
        box-shadow: var(--shadow-xl);
        animation: fadeSlideIn .45s ease;
    }
    @keyframes fadeSlideIn {
        from { opacity:0; transform:translateY(20px); }
        to   { opacity:1; transform:translateY(0); }
    }

    .welcome-icon {
        width: 80px; height: 80px; border-radius: 50%;
        background: rgba(212,175,55,0.12);
        border: 2px solid rgba(212,175,55,0.35);
        display: flex; align-items: center; justify-content: center;
        font-size: 2.2rem; margin: 0 auto 1.5rem;
    }

    .step-list { list-style: none; padding: 0; margin: 0; text-align: left; }
    .step-item {
        display: flex; align-items: flex-start; gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .step-item:last-child { border-bottom: none; }
    .step-num {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
        background: rgba(212,175,55,0.12);
        border: 1.5px solid rgba(212,175,55,0.30);
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 800; color: var(--gold);
        margin-top: 2px;
    }
    .step-title { font-weight: 700; font-size: .88rem; color: var(--text-primary); margin-bottom: 3px; }
    .step-desc  { font-size: .78rem; color: var(--text-muted); line-height: 1.55; margin: 0; }
    .step-badge {
        display: inline-block; font-size: .65rem; font-weight: 700;
        padding: 2px 8px; border-radius: 20px; margin-top: 5px;
    }
    .step-badge--pending { background: rgba(255,193,7,.12); color: #ffd60a; border: 1px solid rgba(255,193,7,.25); }
    .step-badge--ok      { background: rgba(25,135,84,.12); color: var(--success-text); border: 1px solid rgba(25,135,84,.25); }

    .cta-block {
        background: linear-gradient(135deg, rgba(212,175,55,.09), rgba(212,175,55,.04));
        border: 1px solid rgba(212,175,55,.25);
        border-radius: var(--radius-lg);
        padding: 16px 20px;
        margin-top: 1.75rem;
        margin-bottom: 1.25rem;
    }
    </style>
</head>
<body>

<div class="welcome-card">

    <!-- Icône -->
    <div class="welcome-icon" aria-hidden="true">✂️</div>

    <!-- Titre -->
    <h1 style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:var(--gold);margin-bottom:.5rem;">
        Bienvenue, <?= $prenom ?> !
    </h1>
    <p style="color:var(--text-muted);font-size:.88rem;line-height:1.65;margin-bottom:2rem;">
        Votre compte coiffeur est créé. Voici les <strong style="color:var(--text-primary);">3 étapes</strong>
        pour apparaître dans l'annuaire et recevoir vos premiers clients.
    </p>

    <!-- Étapes -->
    <ul class="step-list">
        <li class="step-item">
            <div class="step-num">1</div>
            <div>
                <div class="step-title">Payez votre abonnement mensuel</div>
                <p class="step-desc">
                    L'abonnement coûte <strong style="color:var(--gold);">1 500 FCFA / mois</strong>.
                    C'est ce paiement qui active votre dossier et déclenche la vérification de votre diplôme par notre équipe.
                </p>
                <span class="step-badge step-badge--pending">
                    <i class="bi bi-circle me-1"></i>En attente
                </span>
            </div>
        </li>
        <li class="step-item">
            <div class="step-num">2</div>
            <div>
                <div class="step-title">Notre équipe valide votre diplôme</div>
                <p class="step-desc">
                    Après votre paiement, un admin vérifie votre certification.
                    <strong style="color:var(--text-primary);">Délai : quelques heures.</strong>
                    Vous recevrez une notification dès validation.
                </p>
                <span class="step-badge step-badge--pending">
                    <i class="bi bi-hourglass-split me-1"></i>Après paiement
                </span>
            </div>
        </li>
        <li class="step-item">
            <div class="step-num">3</div>
            <div>
                <div class="step-title">Votre profil devient visible</div>
                <p class="step-desc">
                    Une fois approuvé, vous apparaissez dans l'annuaire et pouvez recevoir des demandes de réservation.
                </p>
                <span class="step-badge step-badge--ok">
                    <i class="bi bi-check2 me-1"></i>Objectif final
                </span>
            </div>
        </li>
    </ul>

    <!-- CTA paiement -->
    <div class="cta-block">
        <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.75rem;">
            <i class="bi bi-lightning-fill" style="color:var(--gold);" aria-hidden="true"></i>
            En attendant, vous pouvez déjà préparer votre profil :
            ajoutez vos prestations, configurez votre agenda et vos zones d'intervention.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="/coiffons/paiement/simuler_abonnement.php"
               class="btn-gold"
               style="padding:12px 24px;font-size:.88rem;font-weight:700;">
                <i class="bi bi-credit-card-fill me-2" aria-hidden="true"></i>
                Payer mon abonnement — 1 500 FCFA
            </a>
        </div>
    </div>

    <!-- Lien dashboard -->
    <a href="/coiffons/index.php?page=dashboard_coiffeur"
       style="font-size:.78rem;color:var(--text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        Continuer vers mon dashboard
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </a>

</div>

</body>
</html>
