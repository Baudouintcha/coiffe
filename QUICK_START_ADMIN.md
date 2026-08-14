# 🚀 QUICK START: REMPLIR L'ANNUAIRE

**Objectif**: Approuver les coiffeurs pour qu'ils apparaissent dans l'annuaire  
**Temps estimé**: 5 minutes

---

## ⏱️ 3 ÉTAPES SIMPLES

### ÉTAPE 1: Se connecter en admin
```
URL: http://localhost/coiffons/index.php
Email: (votre email admin)
Password: (votre mot de passe)
```

### ÉTAPE 2: Aller au dashboard admin
Vous êtes automatiquement redirigé après login vers:
```
http://localhost/coiffons/first/admin_dashboard.php
```

### ÉTAPE 3: Approuver les coiffeurs

#### 🔍 Cherchez l'onglet "Validations & Flux"
Le dashboard a 4 onglets:
1. Analyses & BI
2. Relances WhatsApp
3. **Validations & Flux** ← C'EST ICI
4. Modération & Comptes

#### 📋 Dans l'onglet "Validations & Flux", vous verrez:
- **Section 1: "Diplômes en Attente d'Approbation"** (EN HAUT)
  - Liste tous les coiffeurs avec diplômes non approuvés
  - 2 colonnes de boutons: "Approuver" (vert) et "Refuser" (rouge)

- **Section 2: "Abonnements en attente"** (EN BAS À GAUCHE)
  - Liste les coiffeurs qui ont payé l'abo mais pas validé
  - Bouton "Confirmer" pour chacun

- **Section 3: "Retraits Mobile Money"** (EN BAS À DROITE)
  - Demandes de retrait en attente
  - Bouton "Effectué" pour traiter

#### ✅ Pour approuver un coiffeur:
1. Trouvez le coiffeur dans la section "Diplômes en Attente"
2. (Optionnel) Cliquez sur "Voir" pour vérifier le diplôme
3. Cliquez sur le bouton vert **"Approuver"**
4. Attendez l'alerte verte confirmant l'approbation

#### 💚 Alerte de Succès
Après chaque approbation, une alerte verte s'affiche:
```
✅ Système mis à jour : L'approbation a été modifiée avec succès.
```

---

## 🔄 FLUX COMPLET (Schéma)

```
COIFFEUR                          ADMIN                          ANNUAIRE
   |                               |                               |
   |-- S'inscrit                   |                               |
   |                               |                               |
   |-- Upload diplôme              |                               |
   |                               |                               |
   |-- S'abonne (1500F)            |                               |
   |                               |                               |
   |                        ← Voit diplômes en attente            |
   |                        ← Clique "Approuver"                  |
   |                               |-- is_approved = 1 (BD)       |
   |                               |                               |
   |← Notification                 |                               |
   |                               |                       ← Affiche le coiffeur
   |                               |
   └────────────────────→ VISIBLE DANS L'ANNUAIRE
```

---

## 📊 RÉSULTATS ATTENDUS

### Avant approbation
- Annuaire: **VIDE** (0 résultats)
- Admin dashboard: Diplômes listed mais aucun approuvé

### Après approbation de 3 coiffeurs
- Annuaire: **3 coiffeurs** aparaissent
- Admin dashboard: 3 approuvés, reste en attente diminue

---

## 🎯 COMMANDES RAPIDES

### Vérifier l'état rapidement
```
http://localhost/coiffons/test_admin_approval.php
```

Vous verrez:
- Nombre de diplômes en attente
- État de tous les coiffeurs (approuvés vs en attente)
- Diagnostic du code

### Tester l'annuaire
```
http://localhost/coiffons/filter/annuaire_coiffeurs.php
```

Les coiffeurs approuvés doivent apparaître dans:
- **Onglet 1 "Rechercher par nom"** (tape le nom)
- **Onglet 2 "Rechercher par ville"** (sélectionne la ville)

---

## ❓ FAQ RAPIDE

### Q: Pourquoi je vois "Aucun diplôme en attente" ?
**R**: Tous les coiffeurs sont déjà approuvés! ✅

### Q: Le coiffeur approuvé n'apparaît pas dans l'annuaire?
**R**: Attendez quelques secondes et rafraîchissez (`F5`). Vérifiez aussi qu'il a vraiment `is_approved = 1` dans la BD.

### Q: Où vérifier en Base de Données?
**R**: 
```sql
SELECT id, nom, prenom, is_approved, abonnement_status 
FROM users 
WHERE role = 'coiffeur';
```

### Q: Je veux refuser un diplôme?
**R**: Même section, cliquez sur le bouton rouge "Refuser". Le coiffeur recevra une notification.

### Q: Comment ajouter manuellement un coiffeur en BD?
**R**: 
```sql
UPDATE users SET is_approved = 1 WHERE id = 17;
```
Remplacez 17 par l'ID du coiffeur.

---

## 🆘 TROUBLESHOOTING

### Problème: "Accès refusé"
- ✅ Vérifiez que vous êtes connecté en tant qu'admin
- ✅ Vérifiez que votre rôle en BD est `role = 'admin'`

### Problème: Boutons "Approuver" ne fonctionnent pas
- ✅ Videz le cache navigateur (Ctrl+Maj+Del)
- ✅ Rafraîchissez la page
- ✅ Vérifiez que `admin_actions.php` a les actions implémentées

### Problème: L'annuaire reste vide
- ✅ Allez à `test_admin_approval.php` pour voir le diagnostic
- ✅ Vérifiez manuellement en BD: `SELECT COUNT(*) FROM users WHERE is_approved = 1;`
- ✅ Rafraîchissez l'annuaire

---

## 📝 CHECKLIST

Avant de démarrer le test:
- ✅ Apache est lancé (XAMPP)
- ✅ MySQL est lancé (XAMPP)
- ✅ Base de données `domizi_v2` existe
- ✅ Vous avez un compte admin

Pendant le test:
- ✅ Vous êtes connecté en admin
- ✅ Dashboard chargé sans erreurs
- ✅ Section "Diplômes en Attente" visible
- ✅ Au moins 1 coiffeur en attente

Après approbation:
- ✅ Alerte verte apparaît
- ✅ Coiffeur disparaît de "Diplômes en attente"
- ✅ Coiffeur apparaît dans l'annuaire
- ✅ Coiffeur reçoit notification

---

## 🎬 LIENS DIRECTS

| Page | URL |
|------|-----|
| Dashboard Admin | `/first/admin_dashboard.php` |
| Annuaire | `/filter/annuaire_coiffeurs.php` |
| Test Approval | `/test_admin_approval.php` |
| Homepage | `/index.php` |

---

**Vous êtes prêt!** 🚀 Allez approuver les coiffeurs!
