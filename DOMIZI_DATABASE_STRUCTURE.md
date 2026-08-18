# Structure de la Base de Données DOMIZI

## Vue d'ensemble

DOMIZI est organisée en 5 groupes logiques :

### 1. GÉOGRAPHIE (Foundation)
- `pays` → `departements` → `villes` → `quartiers`
- Hiérarchie stricte pour la localisation

### 2. UTILISATEURS & PROFILS
- `users` : Tous les utilisateurs (client, prestataire, admin)
- `profils_prestataires` : Données spécifiques aux prestataires
- `zones_prestataire` : Quartiers d'intervention (lié à users via profils_prestataires)

### 3. SERVICES & DISPONIBILITÉS
- `services` : Catalogue des services (coiffures, tarifs)
- `disponibilites` : Horaires de travail par jour

### 4. RENDEZ-VOUS & FINANCES
- `rendez_vous` : Commandes (client → prestataire → service)
- `avis` : Notes et commentaires après RDV
- `plaintes` : Signalements de problèmes
- `transactions_portefeuille` : Mouvements d'argent
- `abonnements_paiements` : Paiements des abonnements

### 5. COMMUNICATION
- `notifications` : Messages système
- `email_actions` : Trace des emails
- `otp_codes` : Codes de vérification
- `ia_sessions` : Sessions chatbot

---

## Relations entre tables

```
PAYS (id)
  ↓
  ├─ DEPARTEMENTS (id, id_pays*)
  ├─ VILLES (id, id_pays*, id_departement*)
  │   ↓
  │   QUARTIERS (id, id_ville*)
  │       ↓
  │       ├─ USERS.id_quartier* (localisation client)
  │       └─ ZONES_PRESTATAIRE (id_quartier*)
  │
  └─ USERS.id_pays* (localisation)
        ↓
        USERS.id_ville* (localisation)

USERS (id)
  ├─ PROFILS_PRESTATAIRES (user_id*, id_metier*)
  ├─ SERVICES (id_prestataire*, id_metier*)
  ├─ DISPONIBILITES (prestataire_id*)
  ├─ ZONES_PRESTATAIRE (id_prestataire*)
  ├─ RENDEZ_VOUS (client_id*, prestataire_id*)
  ├─ AVIS (client_id*, prestataire_id*)
  ├─ PLAINTES (client_id*, prestataire_id*)
  ├─ TRANSACTIONS_PORTEFEUILLE (user_id*)
  ├─ ABONNEMENTS_PAIEMENTS (prestataire_id*)
  ├─ NOTIFICATIONS (user_id*)
  ├─ EMAIL_ACTIONS (user_id*)
  ├─ OTP_CODES (user_id*)
  └─ IA_SESSIONS (user_id*)

DOMAINES (id)
  └─ METIERS (id_domaine*)
        └─ PROFILS_PRESTATAIRES (id_metier*)
            ├─ SERVICES (id_metier*)
            └─ USERS (prestataire = FK users.id)

RENDEZ_VOUS (id)
  ├─ SERVICES (id_service*)
  ├─ AVIS (rdv_id*, unique)
  ├─ PLAINTES (rdv_id*)
  ├─ TRANSACTIONS (rdv_id*)
  └─ NOTIFICATIONS (rdv_id*)
```

---

## Clés étrangères par table

| Table | Colonne | Référence | Cascade |
|-------|---------|-----------|---------|
| departements | id_pays | pays.id | CASCADE |
| villes | id_pays, id_departement | pays.id, departements.id | CASCADE |
| quartiers | id_ville | villes.id | CASCADE |
| users | id_pays, id_ville, id_quartier | pays.id, villes.id, quartiers.id | SET NULL |
| profils_prestataires | user_id, id_metier | users.id, metiers.id | CASCADE |
| zones_prestataire | id_prestataire, id_quartier | users.id, quartiers.id | CASCADE |
| services | id_prestataire, id_metier | users.id, metiers.id | CASCADE |
| disponibilites | prestataire_id | users.id | CASCADE |
| rendez_vous | client_id, prestataire_id, service_id | users.id, users.id, services.id_service | - |
| avis | rdv_id, client_id, prestataire_id | rendez_vous.id, users.id, users.id | CASCADE |
| plaintes | rdv_id, client_id, prestataire_id | rendez_vous.id, users.id, users.id | CASCADE |
| transactions_portefeuille | user_id, rdv_id | users.id, rendez_vous.id | CASCADE |
| abonnements_paiements | prestataire_id | users.id | CASCADE |
| notifications | user_id, rdv_id | users.id, rendez_vous.id | CASCADE |
| email_actions | user_id | users.id | CASCADE |
| otp_codes | user_id | users.id | CASCADE |
| ia_sessions | user_id | users.id | CASCADE |

---

## Flux de données principal

### Créer un rendez-vous

```
CLIENT (user.id)
  ↓
RENDEZ_VOUS (client_id→user.id, prestataire_id→user.id, service_id→services.id_service)
  ├─ Cherche la DISPONIBILITE du prestataire
  ├─ Récupère le TARIF du SERVICE
  ├─ BLOQUE le montant dans users.solde_gele
  └─ Crée une NOTIFICATION
  
PAIEMENT CONFIRMÉ
  ├─ Déplace solde_gele → solde (prestataire reçoit sa part)
  ├─ Enregistre dans TRANSACTIONS_PORTEFEUILLE (type: gain)
  └─ Met à jour RENDEZ_VOUS.statut_paiement = 'paye'

APRÈS RDV
  ├─ CLIENT crée un AVIS
  ├─ Si problème : CLIENT crée une PLAINTE
  └─ ADMIN modère et répond
```

### Gérer un prestataire

```
PRESTATAIRE (user.id, role='prestataire')
  ├─ PROFILS_PRESTATAIRES (user_id→users.id, id_metier→metiers.id)
  ├─ SERVICES (id_prestataire→users.id, id_metier→metiers.id)
  ├─ DISPONIBILITES (prestataire_id→users.id)
  ├─ ZONES_PRESTATAIRE (id_prestataire→users.id, id_quartier→quartiers.id)
  └─ ABONNEMENTS_PAIEMENTS (prestataire_id→users.id)
```

---

## Requêtes courantes

### Trouver les prestataires disponibles
```sql
SELECT DISTINCT u.id, u.nom, u.prenom, s.nom_service, s.prix
FROM users u
JOIN profils_prestataires pp ON pp.user_id = u.id
JOIN services s ON s.id_prestataire = u.id
JOIN disponibilites d ON d.prestataire_id = u.id
WHERE u.role = 'prestataire' 
  AND u.statut = 'actif'
  AND d.jour_semaine = 'Lundi'
  AND u.id_quartier = ?
```

### Historique des revenus d'un prestataire
```sql
SELECT SUM(montant) as total, type_transaction
FROM transactions_portefeuille
WHERE user_id = ? AND DATE(date_creation) >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
GROUP BY type_transaction
```

### Avis non traités par l'admin
```sql
SELECT a.id, a.commentaire, a.note, u_client.nom, u_presta.nom
FROM avis a
JOIN users u_client ON u_client.id = a.client_id
JOIN users u_presta ON u_presta.id = a.prestataire_id
WHERE a.statut_admin = 'en_attente'
```

---

## Notes importantes

- **Cascade DELETE** : Supprimer un user supprime tous ses RDV, services, transactions
- **Solde** : Toujours dans `users` (solde + solde_gele), pas de table `portefeuille` séparée
- **Avis unique par RDV** : Un client ne peut noter qu'une fois par rendez-vous
- **Prestataire** : Doit avoir `profils_prestataires` + au moins 1 SERVICE
- **Abonnement** : Géré via `abonnements_paiements`, statut reflété dans `profils_prestataires.abonnement_status`

---

## Migration de CFT → DOMIZI

Tables converties :
- `prestations` → `services`
- `coiffeur_id` → `prestataire_id`
- `zones_coiffeur` → `zones_prestataire`
- `portefeuilles` → `users.solde + users.solde_gele`

Tables ignorées (inutiles) :
- `boutique`, `catalogue`, `transactions_site`
