# Harmonisation des Fichiers - Setup Complet

## Résumé des Changements ✅

Tu as renommé les fichiers pour créer une architecture à deux niveaux :

### Architecture Finale
```
index.php
  ├─ Affiche la page d'accueil Domizi
  ├─ Gère la sélection de domaine/métier
  ├─ Appelle ?metier=coiffure
  └─ Require app.php quand métier choisi

app.php  
  ├─ Routeur interne "Coiffe Chez Toi"
  ├─ Gère les pages (?page=dashboard, ?page=login, etc)
  └─ Affiche les vues appropriées
```

---

## Configuration de Base de Données ✅

### .env (Configuré Correctement)
```env
DB_HOST=localhost
DB_NAME=domizi_v2        # ← C'est la bonne base
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

### Tables Présentes dans `domizi_v2`
✅ **domaines** - Contient les domaines de service  
✅ **metiers** - Contient les métiers (Coiffure, Maquillage, etc)  
✅ **users** - Clients et coiffeurs  
✅ **prestations** - Services offerts  

### Données Insérées
- **Domaines** (1 actif + 2 inactifs pour futur)
  - Beauté & Bien-être ✓
  - Maison & Services
  - Santé & Soins
  
- **Métiers du domaine Beauté** (5 au total)
  - Coiffure ✓ (actif)
  - Maquillage ✓ (actif)
  - Ongles ✓ (actif)
  - Massage ✓ (actif)
  - Soins ✓ (actif)

---

## Flux de Navigation ✅

### 1. Invité visite le site
```
http://localhost/coiffons/index.php
     ↓
Affiche page d'accueil Domizi + choix de domaine
     ↓
L'utilisateur clique sur "Beauté & Bien-être"
     ↓
AJAX requête: index.php?action=api_metiers&domaine=1
     ↓
Affiche les métiers (Coiffure, Maquillage, Ongles, Massage, Soins)
     ↓
L'utilisateur clique sur "Coiffure"
     ↓
Redirection: index.php?metier=coiffure
     ↓
index.php require app.php (le routeur Coiffe Chez Toi)
```

### 2. Dans l'app (app.php)
```
app.php reçoit contrôle
     ↓
?page=home (par défaut) → Affiche home.php (page d'accueil Coiffe Chez Toi)
?page=login → Affiche connexion.php
?page=register → Affiche inscription.php
?page=dashboard → Affiche dashboard client (si connecté)
?page=dashboard_coiffeur → Affiche dashboard coiffeur (si connecté)
```

---

## Corrections Appliquées ✅

### 1. index.php
- ✓ Charge le .env et prépare les variables
- ✓ Affiche les domaines et métiers
- ✓ Gère l'API ?action=api_metiers&domaine=X
- ✓ Redirige vers app.php quand métier choisi
- ✓ Affiche vue domizi/home.php

### 2. app.php
- ✓ Charge la configuration via security/config.php OU vendor/autoload
- ✓ Routage interne (?page=)
- ✓ Redirection des utilisateurs connectés vers leurs dashboards
- ✓ Affiche vue home.php pour invités

### 3. Database.php
- ✓ Singleton PDO qui se connecte via .env
- ✓ DB_NAME=domizi_v2 (où sont les métiers/domaines)

### 4. security/config.php
- ⚠️ **Obsolète mais toujours là** (certains vieux fichiers l'utilisent)
- Utilise 'cft' comme base - À IGNORER
- La vraie config est dans Database.php qui lit le .env

---

## Tests à Faire ✅

### 1. **Test Rapide des Données**
Visite : `http://localhost/coiffons/test_metiers.php`

Ce script affiche :
- ✓ État de connexion
- ✓ Liste des domaines
- ✓ Liste des métiers
- ✓ Exemple de réponse API
- ✓ Vérification des fichiers

### 2. **Test du Flux Complet**
1. Va sur `/coiffons/index.php`
2. Clique sur un domaine (devrait afficher les métiers)
3. Clique sur "Coiffure" (devrait aller sur app.php?metier=coiffure)
4. Essaie de te connecter/inscrire
5. Vérifie que tu as accès au dashboard

### 3. **Test API Métiers**
Appel direct : `/coiffons/index.php?action=api_metiers&domaine=1`

Devrait retourner JSON :
```json
{
  "success": true,
  "metiers": [
    {
      "id": "1",
      "nom": "Coiffure",
      "slug": "coiffure",
      "icone": "bi-scissors",
      "description": "Coiffure homme et femme à domicile"
    },
    ...
  ]
}
```

---

## Fichiers à Ne Pas Toucher ⚠️

- ❌ `security/config.php` - Gardé pour compatibilité, mais .env est la source unique de vérité
- ❌ Vieux fichiers qui `require __DIR__ . '/security/config.php'` - Ils continueront de marcher

---

## Prochaines Étapes 🚀

1. **Exécute le test** : `/coiffons/test_metiers.php`
2. **Valide le flux** : Clique sur les domaines et métiers
3. **Teste la navigation** : Login, inscription, dashboard
4. **Optionnel** : Si tu veux ajouter plus de métiers → Édite `domizi_v2.sql` et réimporte

---

## Commandes Utiles 📝

### Si tu dois réinitialiser la base :
```sql
-- Dans phpMyAdmin ou CLI :
mysql -u root -e "DROP DATABASE domizi_v2; CREATE DATABASE domizi_v2 DEFAULT CHARSET utf8mb4;"
mysql -u root domizi_v2 < security/domizi_v2.sql
```

### Vérifier rapidement les données :
```sql
SELECT COUNT(*) FROM domaines WHERE actif = 1;
SELECT COUNT(*) FROM metiers WHERE actif = 1;
SELECT * FROM metiers WHERE slug = 'coiffure';
```

---

## Résumé ✅

✅ **index.php** = Page d'accueil + sélection métier  
✅ **app.php** = Routeur interne Coiffe Chez Toi  
✅ **Base de données** = domizi_v2 (correcte)  
✅ **Métiers/Domaines** = Insérés et prêts à l'emploi  
✅ **Configuration** = Harmonisée via .env  

**Tu es prêt à tester !** 🎉
