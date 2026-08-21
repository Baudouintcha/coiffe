<?php
/**
 * traitement_avis.php — Traitement POST du formulaire d'avis
 *
 * CORRECTIONS APPLIQUÉES :
 * 1. $_SESSION['id_client'] → $_SESSION['user_id'] (cohérence système)
 * 2. Connexion PDO locale supprimée → security/config.php
 * 3. Noms de colonnes alignés sur la vraie BDD : id_rdv, message, date_demande
 * 4. Vérification anti-doublon avant INSERT
 * 5. Plainte gérée via type_commentaire = 'plainte' (pas de table séparée)
 * 6. Redirection vers mes_rendezvous.php (fichier existant)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';

// Sécurité — corrigé : id_client → user_id
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_client   = $_SESSION['user_id'];
$id_rdv      = intval($_POST['id_rendez_vous']);
$id_coiffeur = intval($_POST['id_coiffeur']);
$note        = intval($_POST['note']);
$commentaire = htmlspecialchars(trim($_POST['commentaire']));
$signaler    = isset($_POST['signaler_probleme']) ? intval($_POST['signaler_probleme']) : 0;
$motif       = trim($_POST['motif_plainte'] ?? '');

// 1. VALIDATION MÉTIER
if ($note < 1 || $note > 5) {
    $_SESSION['error'] = "Veuillez attribuer une note valide.";
    header("Location: /coiffons/client/laisser_avis.php?rdv=$id_rdv");
    exit();
}

if ($note <= 2 && $signaler === 1 && empty($motif)) {
    $_SESSION['error'] = "Veuillez expliquer le problème pour une note basse.";
    header("Location: /coiffons/client/laisser_avis.php?rdv=$id_rdv");
    exit();
}

// 2. VÉRIFICATION ANTI-DOUBLON — empêcher deux avis pour le même RDV
$check_doublon = $pdo->prepare("SELECT id_commentaire FROM commentaires WHERE id_rdv = ? AND id_client = ? AND type_commentaire = 'public'");
$check_doublon->execute([$id_rdv, $id_client]);
if ($check_doublon->rowCount() > 0) {
    $_SESSION['error'] = "Vous avez déjà laissé un avis pour ce rendez-vous.";
    header("Location: /coiffons/client/mes_rendezvous.php");
    exit();
}

// 3. VÉRIFICATION QUE LE RDV APPARTIENT AU CLIENT ET EST PASSÉ
$check_rdv = $pdo->prepare("
    SELECT id FROM rendez_vous
    WHERE id = ? AND client_id = ? AND CONCAT(date_rdv, ' ', heure_debut) < NOW()
");
$check_rdv->execute([$id_rdv, $id_client]);
if ($check_rdv->rowCount() === 0) {
    header("Location: /coiffons/client/mes_rendezvous.php");
    exit();
}

// 4. TRANSACTION
try {
    $pdo->beginTransaction();

    // Insertion avis public dans commentaires (colonnes BDD réelles)
    $stmt = $pdo->prepare("
        INSERT INTO commentaires (id_rdv, id_coiffeur, id_client, message, note, type_commentaire, statut_moderation)
        VALUES (?, ?, ?, ?, ?, 'public', 'en_attente')
    ");
    $stmt->execute([$id_rdv, $id_coiffeur, $id_client, $commentaire, $note]);

    // Insertion plainte si nécessaire (même table, type = 'plainte')
    if ($signaler === 1 && !empty($motif)) {
        $stmt_p = $pdo->prepare("
            INSERT INTO commentaires (id_rdv, id_coiffeur, id_client, message, note, type_commentaire, statut_admin)
            VALUES (?, ?, ?, ?, ?, 'plainte', 'en_attente')
        ");
        $stmt_p->execute([$id_rdv, $id_coiffeur, $id_client, $motif, $note]);
    }

    $pdo->commit();

    // ── Notifications post-avis (non-bloquantes) ──
    require_once __DIR__ . '/security/notifications.php';

    // Notification au coiffeur : nouvel avis reçu
    $etoiles = str_repeat('★', $note) . str_repeat('☆', 5 - $note);
    $notif_coiffeur = "Vous avez reçu un nouvel avis ($etoiles). Consultez votre profil.";
    notifier($pdo, $id_coiffeur, $notif_coiffeur, $note >= 4 ? 'success' : ($note >= 3 ? 'info' : 'warning'));

    // Alerte admin si note ≤ 2 (avis négatif)
    if ($note <= 2) {
        $prenom_client_notif = htmlspecialchars(trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')));
        $msg_admin = "⚠️ Avis négatif ($note/5) soumis par $prenom_client_notif pour le coiffeur #$id_coiffeur. Vérifier si c'est un signalement.";
        notifier_admins($pdo, $msg_admin, 'danger');
    }

    $_SESSION['success'] = "Votre avis a été enregistré avec succès. Merci !";
    header("Location: /coiffons/client/mes_rendezvous.php");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error'] = "Erreur technique lors de l'enregistrement.";
    header("Location: /coiffons/client/laisser_avis.php?rdv=$id_rdv");
    exit();
}