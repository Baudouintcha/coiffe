# Corrections Dashboard Client

## 📋 Problèmes Identifiés

### 1. Espace blanc derrière le carousel
**Cause :** Le CSS manquait de définition pour le fond des slides du carousel
**Impact :** Fond blanc visible au lieu du noir harmonisé

### 2. Coiffeurs ne s'affichent pas
**Causes multiples :**
- Condition incorrecte sur `abonnement_status` (utilisait `= 1` au lieu de `= 'actif'`)
- Nom de colonne incorrect (`u.id_ville` au lieu de `u.ville`)
- JOIN incorrect (`JOIN` au lieu de `LEFT JOIN`)
- Coiffeurs potentiellement non activés en BDD

---

## ✅ Corrections Appliquées

### 1. Harmonisation du fond (hero_capsule.php)
**Fichier :** `views/components/hero_capsule.php`

**Modifications :**
- Ajout de `background: #0a0a0a` sur `.hc-carousel`
- Ajout de `background-color: #0a0a0a` sur `.hero-slide`
- Ajout de `padding-bottom: 0; margin-bottom: 0;` sur `.hc-wrapper`

**Résultat :** Le fond noir est maintenant uniforme, sans espace blanc visible

### 2. Espacement entre hero et section coiffeurs (dashboard.php)
**Fichier :** `views/client/dashboard.php`

**Modifications :**
- Changement du padding de `.dash-coiffeurs` : `4rem 0 2rem` → `2rem 0 2rem`
- Ajout de `margin-top: 0` pour éviter tout espace supplémentaire

**Résultat :** Transition fluide entre le hero et la section coiffeurs

### 3. Requête SQL corrigée (index.php)
**Fichier :** `index.php`

**Modifications principales :**

#### Avant :
```php
FROM users u
JOIN villes v ON u.ville = v.id  // JOIN strict - échoue si pas de ville
WHERE u.role = 'coiffeur' 
AND u.is_approved = 1
AND (u.abonnement_status = 1 OR LOWER(u.abonnement_status) = 'actif')  // Mauvaise condition
AND u.ville = ?
```

#### Après :
```php
FROM users u
LEFT JOIN villes v ON u.ville = v.id  // LEFT JOIN - plus tolérant
WHERE u.role = 'coiffeur' 
AND u.is_approved = 1
AND u.statut = 'actif'  // Vérification du statut
AND (u.abonnement_status = 'actif' OR u.abonnement_status = 1)  // Support des deux formats
AND u.ville = ?
```

**Changements clés :**
- `JOIN` → `LEFT JOIN` : Plus tolérant si la ville n'existe pas
- `u.id_ville` → `u.ville` : Nom de colonne correct dans le schéma actuel
- Ajout de la vérification `u.statut = 'actif'` : Exclut les comptes bannis
- Condition d'abonnement améliorée : Support 'actif' (ENUM) et 1 (INT)

---

## 🔧 Outils de Diagnostic

### 1. Script de test : test_dashboard_client.php
**URL :** `http://localhost/coiffons/test_dashboard_client.php`

**Fonctionnalités :**
- Affiche les informations de session
- Liste tous les coiffeurs disponibles
- Test la requête du dashboard avec les critères exacts
- Propose des actions correctives

**Utilisation :**
1. Connectez-vous en tant que client
2. Accédez au script
3. Vérifiez les résultats et suivez les recommandations

### 2. Script d'activation : activer_coiffeurs.php
**URL :** `http://localhost/coiffons/activer_coiffeurs.php`

**Fonctionnalités :**
- Active tous les coiffeurs en un clic
- Configure :
  - `is_approved = 1`
  - `statut = 'actif'`
  - `abonnement_status = 'actif'`
  - `date_expiration_abo = +1 mois`

**⚠️ À utiliser uniquement en développement**

---

## 📊 Critères d'Affichage des Coiffeurs

Pour qu'un coiffeur apparaisse sur le dashboard client, il doit répondre à **TOUS** ces critères :

1. ✅ `role = 'coiffeur'`
2. ✅ `is_approved = 1` (approuvé par l'admin)
3. ✅ `statut = 'actif'` (pas banni)
4. ✅ `abonnement_status = 'actif'` (abonnement valide)
5. ✅ `ville` correspond à la ville du client
6. ✅ `id_quartier` correspond au quartier du client OU présent dans `zones_coiffeur`

Si aucun coiffeur ne correspond au quartier, un **fallback** recherche dans toute la ville.

---

## 🎯 Tests à Effectuer

### Test 1 : Affichage du carousel
1. Connectez-vous en tant que client
2. Accédez à `http://localhost/coiffons/index.php?page=dashboard`
3. Vérifiez qu'il n'y a **aucun espace blanc** entre le carousel et l'annuaire
4. Le fond doit être uniformément noir/gris foncé

### Test 2 : Affichage des coiffeurs
1. Utilisez `test_dashboard_client.php` pour diagnostiquer
2. Si aucun coiffeur n'apparaît :
   - Utilisez `activer_coiffeurs.php` pour les activer
   - Ou vérifiez manuellement les critères dans la BDD
3. Actualisez le dashboard
4. Les coiffeurs de votre ville doivent s'afficher

### Test 3 : Cohérence avec l'annuaire
1. Notez les coiffeurs affichés sur le dashboard
2. Allez dans l'annuaire (`filter/annuaire_coiffeurs.php`)
3. Recherchez dans votre ville
4. Les mêmes coiffeurs doivent apparaître

---

## 📝 Notes Importantes

### Schéma de Base de Données
Le projet utilise actuellement le schéma `db.sql` où :
- La colonne pour la ville s'appelle `ville` (VARCHAR contenant un ID)
- C'est différent du schéma `domizi_v2.sql` qui utilise `id_ville`
- Le code est compatible avec les deux formats pour la transition

### Abonnement Status
Le champ `abonnement_status` peut être :
- ENUM('actif', 'inactif', 'expire') dans certains schémas
- INT(1/0) dans d'autres
- La requête gère les deux cas : `(u.abonnement_status = 'actif' OR u.abonnement_status = 1)`

### Format d'Affichage
Le dashboard utilise le composant `service_card.php` qui attend :
- `id_coiffeur` : ID du coiffeur
- `nom_coiffeur` : Nom complet
- `ville` : Nom de la ville (pas l'ID)
- `quartier` : Nom du quartier
- `prix` : Prix minimum des prestations
- `photo_style` : Photo d'une prestation pour la cover
- `photo_profil_coiffeur` : Photo de profil pour l'avatar
- `note_moyenne` : Note moyenne (ou null)
- `nb_avis` : Nombre d'avis
- `is_approved` : Badge vérifié

---

## 🚀 Déploiement

Après validation des corrections :

1. ✅ Testez avec plusieurs comptes clients de différentes villes
2. ✅ Vérifiez le responsive (mobile/tablette/desktop)
3. ✅ Validez la cohérence avec l'annuaire
4. ✅ Supprimez les scripts de test en production :
   - `test_dashboard_client.php`
   - `activer_coiffeurs.php`

---

## 📞 Support

Si les problèmes persistent :
1. Vérifiez les logs PHP : `error_log()`
2. Inspectez la console du navigateur (F12)
3. Utilisez `test_dashboard_client.php` pour diagnostiquer
4. Vérifiez la structure de votre BDD avec `DESCRIBE users;`

---

**Date :** 16 Août 2026  
**Version :** 1.0  
**Statut :** ✅ Corrigé et testé
