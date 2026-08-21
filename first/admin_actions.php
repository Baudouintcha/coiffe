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
        $user_id = intval($_GET['id'] ?? 0);

        if ($user_id > 0) {
            // ✅ BUG CORRIGÉ : user_id → id (colonne réelle de la table users)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

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
        $id = intval($_GET['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM suppressions_comptes WHERE id = ?");
            $stmt->execute([$id]);

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
        $user_id = intval($_GET['id'] ?? 0);
        if ($user_id > 0) {
            // On récupère le statut actuel — ✅ BUG CORRIGÉ : user_id → id
            $stmt = $pdo->prepare("SELECT is_approved FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user) {
                $nouveau_statut = $user['is_approved'] ? 0 : 1;
                $update = $pdo->prepare("UPDATE users SET is_approved = ? WHERE id = ?");
                $update->execute([$nouveau_statut, $user_id]);
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
            $user_id = intval($_POST['user_id'] ?? 0);
            $raison = trim($_POST['raison_ban'] ?? '');

            if ($user_id > 0) {
                // On récupère le statut actuel — ✅ BUG CORRIGÉ : user_id → id
                $stmt = $pdo->prepare("SELECT statut FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($user['statut'] === 'actif') {
                        $update = $pdo->prepare("UPDATE users SET statut = 'banni', raison_ban = ? WHERE id = ?");
                        $update->execute([$raison, $user_id]);
                    } else {
                        $update = $pdo->prepare("UPDATE users SET statut = 'actif', raison_ban = NULL WHERE id = ?");
                        $update->execute([$user_id]);
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
            $prestataire_id = intval($_POST['prestataire_id'] ?? 0);
            $id_client = intval($_POST['client_id'] ?? 0);
            $id_plainte = intval($_POST['plainte_id'] ?? 0);

            if ($prestataire_id > 0 && $id_client > 0 && $id_plainte > 0) {
                $pdo->beginTransaction();
                try {
                    // 1. Bannir le coiffeur tricheur
                    $stmt1 = $pdo->prepare("UPDATE users SET statut = 'banni', raison_ban = 'Contournement de plateforme avéré' WHERE id = ?");
                    $stmt1->execute([$prestataire_id]);

                    // 2. Créditer le portefeuille du client dénonciateur (+2000 FCFA) — ✅ BUG CORRIGÉ : user_id → id
                    $stmt2 = $pdo->prepare("UPDATE users SET solde = solde + 2000 WHERE id = ?");
                    $stmt2->execute([$id_client]);

                    // 3. Envoyer une notification in-app au client
                    $msg_notif = "Félicitations ! Votre signalement a été validé par notre équipe. Un bonus de 2 000 FCFA a été ajouté à votre portefeuille.";
                    $stmt3 = $pdo->prepare("INSERT INTO notifications (user_id, message, lu) VALUES (?, ?, 0)");
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
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, lu) SELECT id, ?, 0 FROM users WHERE role != 'admin'");
                    $stmt->execute([$message]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, lu) SELECT id, ?, 0 FROM users WHERE role = ?");
                    $stmt->execute([$message, $cible]);
                }
                header("Location: admin_dashboard.php?success=notifications_envoyees");
                exit();
            }
        }
        break;

    // =================================================================
    // ACTION 10 : Valider/Confirmer un abonnement (Paiement de 1500F accepté)
    // =================================================================
    case 'valider_abonnement':
        $user_id = intval($_GET['id'] ?? 0);
        if ($user_id > 0) {
            // Marquer l'abonnement comme actif et fixer date d'expiration (+1 mois)
            $stmt = $pdo->prepare("
                UPDATE profils_prestataires 
                SET abonnement_status = 'actif', 
                    date_expiration_abo = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);

            // Notification au coiffeur
            require_once __DIR__ . '/../security/notifications.php';
            notifier($pdo, $user_id,
                'Votre abonnement de 1 500 F a été confirmé ! Vous avez accès à toutes les fonctionnalités pendant 1 mois.',
                'success'
            );

            header("Location: admin_dashboard.php?success=abo_valide");
            exit();
        }
        break;

    // =================================================================
    // ACTION 11 : Valider un retrait Mobile Money
    // =================================================================
    case 'valider_retrait':
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            // Récupérer les infos de la transaction
            $stmt = $pdo->prepare("SELECT user_id, montant_net FROM transactions_portefeuille WHERE id = ?");
            $stmt->execute([$id]);
            $transaction = $stmt->fetch();

            if ($transaction) {
                $pdo->beginTransaction();
                try {
                    // 1. Marquer la transaction comme terminée (pas de colonne status, donc commenté)
                    // La table transactions_portefeuille n'a pas de colonne statut_paiement
                    // On ne peut que logger dans un commentaire ou supprimer cette logique
                    
                    // 2. Débiter le solde du coiffeur (retrait effectué)
                    $debit = $pdo->prepare("UPDATE users SET solde = solde - ? WHERE id = ?");
                    $debit->execute([$transaction['montant_net'], $transaction['user_id']]);

                    // 3. Notification au coiffeur
                    require_once __DIR__ . '/../security/notifications.php';
                    notifier($pdo, $transaction['user_id'],
                        'Votre demande de retrait de ' . number_format($transaction['montant_net'], 0, ',', ' ') . ' F a été traitée avec succès. Les fonds arriveront sur votre Mobile Money dans 24h.',
                        'success'
                    );

                    $pdo->commit();
                    header("Location: admin_dashboard.php?success=retrait_valide");
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    header("Location: admin_dashboard.php?error=echec_retrait");
                    exit();
                }
            }
        }
        break;

    // =================================================================
    // ACTION 12 : Approuver le diplôme d'un coiffeur
    // =================================================================
    case 'approuver_diplome':
        $user_id = intval($_GET['id'] ?? 0);
        if ($user_id > 0) {
            $pdo->prepare("UPDATE users SET is_approved = 1 WHERE id = ?")->execute([$user_id]);

            // Notification au coiffeur
            require_once __DIR__ . '/../security/notifications.php';
            notifier($pdo, $user_id,
                'Félicitations ! Votre diplôme a été approuvé par notre équipe. Votre profil est maintenant entièrement visible dans l\'annuaire.',
                'success'
            );

            header("Location: admin_dashboard.php?success=statut_visibilite_maj");
            exit();
        }
        break;

    // =================================================================
    // ACTION 11 : Refuser le diplôme d'un coiffeur
    // =================================================================
    case 'refuser_diplome':
        $user_id = intval($_GET['id'] ?? 0);
        if ($user_id > 0) {
            // Pas de modification is_approved, juste notification
            require_once __DIR__ . '/../security/notifications.php';
            notifier($pdo, $user_id,
                'Votre document de certification n\'a pas pu être validé. Veuillez déposer un document lisible dans votre profil.',
                'danger'
            );
            header("Location: admin_dashboard.php?success=statut_visibilite_maj");
            exit();
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