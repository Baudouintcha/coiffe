# 📋 Guide de Test Manuel — Corrections Coiffe Chez Toi

**Date** : 17 Août 2026

---

## 🎯 Scénario de test complet

Suivez ce guide pour vérifier que tous les problèmes ont bien été résolus.

---

## ✅ Test 1 : Point d'entrée du site

### Étape 1.1 : Vérifier la page Domizi (invité)
1. **Ouvrir** : `http://localhost/coiffons/`
2. **Résultat attendu** : Page Domizi avec sélection de domaines/métiers
3. **Vérifier** : 
   - ✅ Titre : "Domizi — Bienvenue sur la plateforme"
   - ✅ Bouton/icône "Coiffure" visible
   - ✅ Pas de redirection automatique vers Coiffe Chez Toi

### Étape 1.2 : Accéder à Coiffure
1. **Cliquer** sur "Coiffure" (ou la carte coiffure)
2. **Résultat attendu** : Redirection vers `http://localhost/coiffons/index.php?page=home`
3. **Vérifier** : 
   - ✅ Affichage de la page d'accueil Coiffe Chez Toi (non connecté)
   - ✅ Boutons "Se connecter" et "S'inscrire" visibles

### Étape 1.3 : Redirection automatique après connexion
1. **Se connecter** en tant que **CLIENT**
   - Email : un email de client existant
   - Mot de passe : son mot de passe
2. **Résultat attendu** : Redirection AUTO vers `http://localhost/coiffons/index.php?page=dashboard`
3. **Vérifier** : 
   - ✅ Dashboard client s'affiche
   - ✅ Aucune action supplémentaire requise

### Étape 1.4 : Déconnexion
1. **Cliquer** sur le bouton "Déconnexion" (dans le menu)
2. **Résultat attendu** : Retour à la page Domizi
3. **Vérifier** : 
   - ✅ Page Domizi affichée
   - ✅ Pas de redirection vers Coiffe Chez Toi

**✅ Test 1 RÉUSSI si** : Toutes les redirections fonctionnent comme prévu

---

## ✅ Test 2 : Espace blanc derrière le carrousel

### Étape 2.1 : Visualiser le dashboard client
1. **Se connecter** en tant que CLIENT
2. **URL** : `http://localhost/coiffons/index.php?page=dashboard`
3. **Scrolldown** légèrement après le carrousel

### Étape 2.2 : Vérifier l'harmonie visuelle
1. **Observer** l'espace entre le carrousel et la section "Coiffeurs près de vous"
2. **Résultat attendu** : 
   - ✅ Pas d'espace blanc visuel
   - ✅ Transition fluide (petit padding uniforme)
   - ✅ Fond noir cohérent sur toute la hauteur

### Étape 2.3 : Tester sur mobile
1. **Redimensionner** le navigateur (ou ouvrir sur téléphone)
2. **Vérifier** à différentes résolutions :
   - Desktop (1920px)
   - Tablette (768px)
   - Mobile (375px)
3. **Résultat attendu** : Pas d'espace blanc anormal à aucune résolution

**✅ Test 2 RÉUSSI si** : La transition est harmonieuse et cohérente

---

## ✅ Test 3 : Coiffeurs n'apparaissent pas

### Étape 3.1 : Se connecter en tant que CLIENT
1. **URL** : `http://localhost/coiffons/index.php?page=login`
2. **Données** :
   - Email : un email de client existant (ex: test@client.com)
   - Mot de passe : son mot de passe
3. **Vérifier** : Session établie avec `id_ville`, `id_quartier`

### Étape 3.2 : Consulter le dashboard
1. **Accéder** : Dashboard (redirection automatique après login)
2. **Section** : "Coiffeurs près de vous"
3. **Résultat attendu** :
   - ✅ Au moins 1-9 cartes de coiffeurs affichées
   - ✅ Non pas le message "Aucun coiffeur disponible"

### Étape 3.3 : Vérifier les informations des cartes
Chaque carte doit afficher :
- ✅ Nom du coiffeur
- ✅ Localisation (ville + quartier)
- ✅ Prix minimum (FCFA)
- ✅ Note moyenne (si avis existants)
- ✅ Badge "Certifié" (si approuvé)

### Étape 3.4 : Vérifier le contenu de la BDD (optionnel)
1. Ouvrir un terminal/ligne de commande MySQL
2. Exécuter :
```sql
SELECT u.id, u.nom, u.is_approved, u.abonnement_status, u.statut, v.nom_ville, q.nom_quartier
FROM users u
LEFT JOIN villes v ON u.ville = v.id
LEFT JOIN quartiers q ON u.id_quartier = q.id
WHERE u.role = 'coiffeur'
AND u.is_approved = 1
AND (u.abonnement_status = 1 OR u.abonnement_status = 'actif')
AND (u.statut = 'actif' OR u.statut IS NULL OR u.statut = '')
LIMIT 10;
```
3. **Résultat attendu** : Au moins 1 ligne retournée (sinon, aucun coiffeur approuvé/actif dans la BD)

**✅ Test 3 RÉUSSI si** : Les coiffeurs s'affichent correctement (ou message explicite si BD vide)

---

## ✅ Test 4 : Assistant IA dynamique

### Étape 4.1 : Ouvrir le chat IA
1. **Être sur n'importe quelle page** (connecté ou pas)
2. **Observer** en bas à droite : bulle IA avec icône
3. **Résultat attendu** :
   - ✅ Bulle orange/or visible
   - ✅ Icône flottante animée (légère animation)

### Étape 4.2 : Ouvrir le chat
1. **Cliquer** sur la bulle IA
2. **Résultat attendu** :
   - ✅ Chat s'ouvre avec animation
   - ✅ Message d'accueil s'affiche : "Bienvenue ! Je suis [Prenom], votre assistant personnel"
   - ✅ Icône ✨ flottante
   - ✅ Tagline : "💅 Ici pour faciliter votre parcours. Que recherchez-vous ?"

### Étape 4.3 : Vérifier le design
1. **Observer** les éléments visuels :
   - ✅ Dégradé or subtle
   - ✅ Glassmorphisme (effet flou)
   - ✅ Bordure dorée
   - ✅ Animations fluides
   - ✅ Message engageant et clair

### Étape 4.4 : Tester l'interaction
1. **Cliquer** sur une suggestion rapide (ex: "Coiffeur à Cotonou")
2. **Résultat attendu** :
   - ✅ Message envoyé au serveur
   - ✅ Animation "Réflexion..." s'affiche
   - ✅ Réponse apparaît après quelques secondes

### Étape 4.5 : Fermer le chat
1. **Cliquer** sur le bouton × (croix)
2. **Résultat attendu** :
   - ✅ Chat se ferme proprement
   - ✅ Bulle IA reste visible

**✅ Test 4 RÉUSSI si** : L'IA est dynamique, engageante et interactive

---

## ✅ Test 5 : Notifications (Coiffeur reçoit une réservation)

### Étape 5.1 : Se connecter en tant que CLIENT
1. **URL** : `/coiffons/index.php?page=login`
2. **Role** : Client
3. **Vérifier** : Connecté

### Étape 5.2 : Réserver chez un coiffeur
1. **URL** : `/coiffons/filter/annuaire_coiffeurs.php`
2. **Chercher** un coiffeur (par nom ou ville)
3. **Cliquer** sur une fiche coiffeur → "Voir les prestations"
4. **Sélectionner** un service
5. **Remplir** la date/heure et confirmer
6. **Résultat attendu** :
   - ✅ Page de confirmation : "Demande envoyée !"
   - ✅ Message : "Le coiffeur a été notifié"

### Étape 5.3 : Se connecter en tant que COIFFEUR
1. **Ouvrir** une nouvelle fenêtre/tab
2. **URL** : `/coiffons/index.php?page=login`
3. **Role** : Coiffeur (celui chez qui on a réservé)
4. **Vérifier** : Connecté au dashboard coiffeur

### Étape 5.4 : Vérifier la notification
1. **Observer** le dashboard coiffeur :
   - ✅ Badge rouge avec nombre de demandes (ex: "1")
   - ✅ Section "Demandes en attente" affiche la nouvelle réservation
   - ✅ Affiche : nom du client, service, prix, date
   - ✅ Boutons : "Accepter" et "Refuser" présents

### Étape 5.5 : Vérifier la notification in-app
1. **Cliquer** sur l'icône cloche (notifications) dans la topbar
2. **Résultat attendu** :
   - ✅ Notification affichée : "Nouvelle demande de RDV de [Prénom Nom] Client..."
   - ✅ Marquée comme "non lue" (en gras/surlignée)

### Étape 5.6 : Accepter ou refuser
1. **Cliquer** sur "Accepter" ou "Refuser"
2. **Résultat attendu** :
   - ✅ Demande disparaît de la section
   - ✅ Badge de décompte diminue
   - ✅ Notification est mise à jour

**✅ Test 5 RÉUSSI si** : Le flux complet de réservation → notification → acceptation fonctionne

---

## 🔧 Tests automatisés (optionnel)

### Test de notifications en ligne
1. **URL** : `http://localhost/coiffons/test_notifications.php`
2. **Vérifier** : Tous les tests passent en vert ✓

### Points vérifiés
- ✅ Connexion à la BD
- ✅ Table `notifications` existe
- ✅ Fonction `notifier()` disponible
- ✅ Demandes en attente comptabilisées
- ✅ Notifications non lues affichées

---

## 📊 Checklist finale

Avant de valider :

- [ ] Test 1 : Point d'entrée ✅
- [ ] Test 2 : Espace blanc ✅
- [ ] Test 3 : Coiffeurs affichés ✅
- [ ] Test 4 : IA dynamique ✅
- [ ] Test 5 : Notifications reçues ✅
- [ ] Responsive OK (mobile, tablette, desktop)
- [ ] Pas d'erreurs dans la console JavaScript
- [ ] Pas d'erreurs PHP (logs vérifiés)

---

## 🚀 Validation finale

Si tous les tests passent :

```
✅ Système prêt pour la mise en production
✅ Tous les problèmes résolus
✅ UX améliorée
✅ Notifications fonctionnelles
```

**Bonne chance ! 🎉**
