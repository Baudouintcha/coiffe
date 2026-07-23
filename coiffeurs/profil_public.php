<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

// Redirection si non connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'invite') {
    header("Location: /coiffons/index.php?page=login");
    exit();
}

// Fix URL : accepte ?id= ET ?id_coiffeur= pour compatibilité
$id_coiffeur = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
            ?? filter_input(INPUT_GET, 'id_coiffeur', FILTER_VALIDATE_INT);

if (!$id_coiffeur) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erreur</title></head><body style='background:#0a0a0a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;'><div style='text-align:center'><p style='color:rgba(255,255,255,0.4);'>Coiffeur introuvable.</p><a href='/coiffons/index.php' style='color:#D4AF37;'>Retour à l'accueil</a></div></body></html>";
    exit();
}

try {
    // Requête coiffeur — compatible cft (abonnement_status=1) et domizi (actif)
    $stmt = $pdo->prepare("
        SELECT u.*, v.nom_ville, q.nom_quartier
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        WHERE u.id = ? AND LOWER(u.role) = 'coiffeur'
        AND (u.abonnement_status = 1 OR LOWER(u.abonnement_status) = 'actif')
    ");
    $stmt->execute([$id_coiffeur]);
    $coiffeur = $stmt->fetch();

    if (!$coiffeur) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='background:#0a0a0a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;'><div style='text-align:center'><p style='color:rgba(255,255,255,0.4);'>Ce coiffeur n'est pas disponible.</p><a href='/coiffons/index.php' style='color:#D4AF37;'>Retour</a></div></body></html>";
        exit();
    }

    // REQUÊTE AUTORÉPARATRICE POUR LE CATALOGUE DES PRESTATIONS
    $stmt_presta = $pdo->prepare("SELECT * FROM prestations");
    $stmt_presta->execute();
    $toutes_les_prestas = $stmt_presta->fetchAll();

    $prestations = [];
    foreach ($toutes_les_prestas as $p) {
        $cles_nettoyees = array_combine(array_map('trim', array_keys($p)), array_values($p));
        if (isset($cles_nettoyees['id_coiffeur']) && $cles_nettoyees['id_coiffeur'] == $id_coiffeur) {
            $prestations[] = $p;
        }
    }

    // Note moyenne — requête directe, propre et compatible domizi
    try {
        $stmt_note = $pdo->prepare("SELECT AVG(note) as moy, COUNT(*) as nb FROM commentaires WHERE id_coiffeur = ?");
        $stmt_note->execute([$id_coiffeur]);
        $stats_note   = $stmt_note->fetch();
        $note_moyenne = $stats_note['nb'] > 0 ? round($stats_note['moy'], 1) : null;
        $stats_avis   = ['total' => intval($stats_note['nb'])];
    } catch (Exception $e) {
        $note_moyenne = null;
        $stats_avis   = ['total' => 0];
    }

    // Disponibilités — requête directe filtrée
    $stmt_dispo = $pdo->prepare("SELECT * FROM disponibilites WHERE coiffeur_id = ?");
    $stmt_dispo->execute([$id_coiffeur]);
    $toutes_les_dispos = $stmt_dispo->fetchAll();

    $planning_coiffeur = [];
    foreach ($toutes_les_dispos as $d) {
        $planning_coiffeur[strtolower(trim($d['jour_semaine']))] = [
            'debut' => $d['heure_debut'],
            'fin'   => $d['heure_fin']
        ];
    }

    // Créneaux occupés — filtrés par coiffeur
    $stmt_rdv = $pdo->prepare("
        SELECT date_rdv, heure_debut FROM rendez_vous
        WHERE coiffeur_id = ? AND statut_rdv IN ('en_attente','accepte','confirme')
    ");
    $stmt_rdv->execute([$id_coiffeur]);
    $busy_slots = [];
    foreach ($stmt_rdv->fetchAll() as $r) {
        $key = $r['date_rdv'] . ' ' . substr($r['heure_debut'], 0, 5);
        $busy_slots[$key] = true;
    }

} catch (Exception $e) {
    echo "<div style='background:#0a0a0a;color:#fff;padding:40px;font-family:sans-serif;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

require_once __DIR__ . '/../security/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($coiffeur['nom']) ?> — Coiffe Chez Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── DESIGN SYSTEM VARIABLES ── */
        :root {
            --gold:            #D4AF37;
            --gold-dim:        rgba(212,175,55,0.12);
            --gold-hover:      #C9A227;
            --dark:            #0A0A0A;
            --dark-2:          #111111;
            --dark-3:          #161616;
            --dark-4:          #1A1A1A;
            --black:           #000000;
            --text-primary:    #FFFFFF;
            --text-secondary:  rgba(255,255,255,0.65);
            --text-muted:      rgba(255,255,255,0.35);
            --glass-bg:        rgba(255,255,255,0.06);
            --glass-border:    rgba(255,255,255,0.08);
            --glass-border-md: rgba(255,255,255,0.14);
            --font-display:    'Playfair Display', Georgia, serif;
            --font-body:       'Inter', sans-serif;
            --radius-sm:       8px;
            --radius-md:       12px;
            --radius-lg:       16px;
            --radius-xl:       20px;
            --radius-pill:     50px;
            --transition-fast: all 0.2s ease;
            --transition-base: all 0.3s ease;
            --transition-spring: all 0.3s cubic-bezier(0.25,0.8,0.25,1);
            --navbar-height:   64px;
            --bottomnav-height: 56px;
        }

        /* ── RESET & BASE ── */
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--dark);
            color: var(--text-primary);
            font-family: var(--font-body);
            min-height: 100vh;
        }

        /* ── PAGE TRANSITION (DS §8 — fadeSlideIn) ── */
        .page-transition {
            animation: fadeSlideIn 0.3s ease forwards;
        }
        @keyframes fadeSlideIn {
            from { opacity:0; transform:translateY(12px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── TOP NAVIGATION — TopNavigation (DS §10, CL §21) ── */
        .profil-topbar {
            position: sticky;
            top: 0;
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: var(--navbar-height);
            background: rgba(10,10,10,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .profil-brand {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--gold) !important;
            text-decoration: none;
            letter-spacing: 1px;
        }
        .profil-back {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition-fast);
        }
        .profil-back:hover { color: var(--gold); }

        /* ── BOTTOM NAVIGATION MOBILE (DS §10, CL §20) ── */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--black);
            border-top: 1px solid rgba(255,255,255,0.06);
            z-index: 200;
            padding: 6px 0 2px;
        }
        .bn-items { display: flex; justify-content: space-around; }
        .bn-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            color: rgba(255,255,255,0.30);
            text-decoration: none;
            font-size: 0.58rem;
            padding: 4px 12px;
            border-radius: var(--radius-sm);
            transition: color 0.2s;
        }
        .bn-item i { font-size: 1.15rem; }
        .bn-item:hover, .bn-item.active { color: var(--gold); }

        /* ── PROFILE SUMMARY SECTION (CL §28) ── */
        .profile-summary-section {
            padding: 4rem 0;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        /* ── AVATAR (CL §25 — taille LG 80px) ── */
        .avatar-lg {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            background: var(--gold-dim);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--gold);
            box-shadow: 0 0 20px rgba(212,175,55,0.30);
        }
        .avatar-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        /* ── PROFILE SUMMARY TYPOGRAPHY (DS §3) ── */
        .profile-name {
            font-family: var(--font-display);
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 700;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.4rem;
        }
        .profile-location {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
        }
        .profile-bio {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ── BADGE GOLD (DS §14) ── */
        .badge-gold {
            background: var(--gold-dim);
            border: 1px solid rgba(212,175,55,0.20);
            color: var(--gold);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-muted {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.10);
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .stars { color: var(--gold); font-size: 0.85rem; letter-spacing: 1px; }

        /* ── FLOATING SECTION (CL §3 — glass-cct) ── */
        .section-booking {
            padding: 4rem 0;
            background: var(--dark-3);
        }
        .section-portfolio {
            padding: 4rem 0;
            background: var(--dark);
        }
        .section-heading {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        .section-sub {
            color: var(--text-muted);
            font-size: 0.82rem;
            margin-bottom: 2rem;
        }
