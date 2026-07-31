<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SÉCURITÉ : Vérification stricte de l'authentification et du rôle Administrateur
require_once __DIR__ . '/../security/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirection sécurisée si l'utilisateur n'est pas admin
    header("Location: ../index.php");
    exit();
}

// Récupération de l'action demandée
$action = $_GET['action'] ?? '';

switch ($action) {

    // =================================================================
    // ACTION 1 : Enregistrer un flux sortant (Dépense) dans transactions_site
    // =================================================================
    case 'ajouter_depense':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $montant = floatval($_POST['montant']);
            $motif = trim($_POST['motif']);

            if ($montant > 0 && !empty($motif)) {
                // Utilisation de transactions_site de manière fidèle à votre BDD
                $stmt = $pdo->prepare("INSERT INTO transactions_site (montant, motif, type_mouvement) VALUES (?, ?, 'sortie')");
                $stmt->execute([$montant, $motif]);
                
                header("Location: admin_dashboard.php?success=depense_ajoutee");
                exit();
            }
        }
        break;

    // =================================================================
    // ACTION 2 : Exclure définitivement un utilisateur de la plateforme
    // =================================================================
    case 'supprimer_user':
        $id_user = intval($_GET['id'] ?? 0);

        if ($id_user > 0) {
            // ✅ BUG CORRIGÉ : id_user → id (colonne réelle de la table users)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_user]);

            header("Location: admin_dashboard.php?success=user_supprime");
            exit();
        }
        break;

    // =================================================================
    // ACTION 3 : Supprimer définitivement un commentaire inapproprié
    // =================================================================
    case 'supprimer_commentaire':
        $id_commentaire = intval($_GET['id'] ?? 0);

        if ($id_commentaire > 0) {
            $stmt = $pdo->prepare("DELETE FROM commentaires WHERE id_commentaire = ?");
            $stmt->execute([$id_commentaire]);

            header("Location: admin_dashboard.php?success=com_supprime");
            exit();
        }
        break;

    // =================================================================
    // ACTION 4 : Nettoyer/Supprimer un log de l'historique des suppressions
    // =================================================================
    case 'supprimer_log_suppression':
        $id_suppression = intval($_GET['id'] ?? 0);

        if ($id_suppression > 0) {
            $stmt = $pdo->prepare("DELETE FROM suppressions_comptes WHERE id_suppression = ?");
            $stmt->execute([$id_suppression]);

            header("Location: admin_dashboard.php?success=log_nettoye");
            exit();
        }
        break;

    // =================================================================
    // ACTION 5 : Mettre à jour le statut WA relance et rediriger l'Admin
    // =================================================================
    case 'marquer_wa_envoye':
        $id_rdv = intval($_GET['id'] ?? 0);
        $redirect_url = $_GET['redirect'] ?? '';

        if ($id_rdv > 0 && !empty($redirect_url)) {
            // Mise à jour de la colonne whatsapp_envoye
            $stmt = $pdo->prepare("UPDATE rendez_vous SET whatsapp_envoye = 1 WHERE id = ?");
            $stmt->execute([$id_rdv]);

            // Redirection immédiate vers le lien API de WhatsApp
            header("Location: " . $redirect_url);
            exit();
        }
        break;

    // =================================================================
    // ACTION 6 : Valider ou suspendre l'approbation publique d'un coiffeur
    // =================================================================
    case 'toggle_approbation':
        $id_user = intval($_GET['id'] ?? 0);
        if ($id_user > 0) {
            // On récupère le statut actuel — ✅ BUG CORRIGÉ : id_user → id
            $stmt = $pdo->prepare("SELECT is_approved FROM users WHERE id = ?");
            $stmt->execute([$id_user]);
            $user = $stmt->fetch();

            if ($user) {
                $nouveau_statut = $user['is_approved'] ? 0 : 1;
                $update = $pdo->prepare("UPDATE users SET is_approved = ? WHERE id = ?");
                $update->execute([$nouveau_statut, $id_user]);
            }
            header("Location: admin_dashboard.php?success=statut_visibilite_maj");
            exit();
        }
        break;

    // =================================================================
    // ACTION 7 : Bannir ou réactiver un utilisateur (avec motif)
    // =================================================================
    case 'toggle_bannissement':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_user = intval($_POST['id_user'] ?? 0);
            $raison = trim($_POST['raison_ban'] ?? '');

            if ($id_user > 0) {
                // On récupère le statut actuel — ✅ BUG CORRIGÉ : id_user → id
                $stmt = $pdo->prepare("SELECT statut FROM users WHERE id = ?");
                $stmt->execute([$id_user]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($user['statut'] === 'actif') {
                        $update = $pdo->prepare("UPDATE users SET statut = 'banni', raison_ban = ? WHERE id = ?");
                        $update->execute([$raison, $id_user]);
                    } else {
                        $update = $pdo->prepare("UPDATE users SET statut = 'actif', raison_ban = NULL WHERE id = ?");
                        $update->execute([$id_user]);
                    }
                }
                header("Location: admin_dashboard.php?success=bannissement_maj");
                exit();
            }
        }
        break;

    // =================================================================
    // ACTION 8 : Gérer la fraude de contournement (Sanction & Récompense client)
    // =================================================================
    case 'valider_fraude_contournement':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_coiffeur = intval($_POST['coiffeur_id'] ?? 0);
            $id_client = intval($_POST['client_id'] ?? 0);
            $id_plainte = intval($_POST['plainte_id'] ?? 0);

            if ($id_coiffeur > 0 && $id_client > 0 && $id_plainte > 0) {
                $pdo->beginTransaction();
                try {
                    // 1. Bannir le coiffeur tricheur
                    $stmt1 = $pdo->prepare("UPDATE users SET statut = 'banni', raison_ban = 'Contournement de plateforme avéré' WHERE id_user = ?");
                    $stmt1->execute([$id_coiffeur]);

                    // 2. Créditer le portefeuille du client dénonciateur (+2000 FCFA) — ✅ BUG CORRIGÉ : id_user → id
                    $stmt2 = $pdo->prepare("UPDATE users SET solde = solde + 2000 WHERE id = ?");
                    $stmt2->execute([$id_client]);

                    // 3. Envoyer une notification in-app au client
                    $msg_notif = "Félicitations ! Votre signalement a été validé par notre équipe. Un bonus de 2 000 FCFA a été ajouté à votre portefeuille.";
                    $stmt3 = $pdo->prepare("INSERT INTO notifications (id_user, message, statut_lecture) VALUES (?, ?, 0)");
                    $stmt3->execute([$id_client, $msg_notif]);

                    // 4. Mettre à jour le statut de la plainte/dénonciation
                    $stmt4 = $pdo->prepare("UPDATE plaintes SET statut = 'resolu' WHERE id = ?");
                    $stmt4->execute([$id_plainte]);

                    $pdo->commit();
                    header("Location: admin_dashboard.php?success=fraude_traitee");
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    header("Location: admin_dashboard.php?error=echec_traitement");
                    exit();
                }
            }
        }
        break;

    // =================================================================
    // ACTION 9 : Envoyer/Pousser une notification In-App globale ou ciblée
    // =================================================================
    case 'pousser_notification':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cible = $_POST['cible_role'] ?? 'tous';
            $message = trim($_POST['message'] ?? '');

            if (!empty($message)) {
                if ($cible === 'tous') {
                    $stmt = $pdo->prepare("INSERT INTO notifications (id_user, message, statut_lecture) SELECT id_user, ?, 0 FROM users WHERE role != 'admin'");
                    $stmt->execute([$message]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notifications (id_user, message, statut_lecture) SELECT id_user, ?, 0 FROM users WHERE role = ?");
                    $stmt->execute([$message, $cible]);
                }
                header("Location: admin_dashboard.php?success=notifications_envoyees");
                exit();
            }
        }
        break;

    // Action inconnue : retour au dashboard par défaut
    default:
        header("Location: admin_dashboard.php");
        exit();
}

// Redirection de sécurité en fin de fichier au cas où aucune condition n'est validée
header("Location: admin_dashboard.php");
exit();