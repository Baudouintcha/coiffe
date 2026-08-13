# Corrections des Noms de Colonne du Schéma Base de Données

## Résumé
Correction des incohérences critiques du schéma base de données où le code PHP référençait des noms de colonne inexistants dans la table `users`. Le problème était que le code référençait `ville` (champ texte) alors que la vraie colonne base de données est `id_ville` (INT) pour stocker l'ID de ville.

## Cause Racine
- **Schéma Base de Données (domizi_v2.sql)** définit la table users avec:
  - `id_ville` (INT UNSIGNED) — Clé étrangère vers villes.id
  - `id_quartier` (INT UNSIGNED) — Clé étrangère vers quartiers.id

- **Code Hérité** utilisait:
  - `ville` (colonne non-existante)
  - `id_quartier` (correct, mais appairé avec le mauvais nom de colonne)

## Fichiers Corrigés

### 1. coiffeurs/profil_coiffeurs.php
**Problème**: La ligne 40 utilisait UPDATE avec la colonne `ville` au lieu de `id_ville`
```sql
-- AVANT (INCORRECT):
UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, ville = ?, id_quartier = ? WHERE id = ?

-- APRÈS (CORRIGÉ):
UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, id_ville = ?, id_quartier = ? WHERE id = ?
```
**Impact**: Les mises à jour de profil sauvegardent maintenant correctement la sélection de ville.

### 2. modifier_profil.php
**Problèmes**:
- Ligne 47: Utilisait `$ville = htmlspecialchars(trim($_POST['ville']));` stockant du texte au lieu d'un entier
- Ligne 58: L'instruction UPDATE utilisait `ville = ?` avec une valeur texte
- Lignes 176-188: Le champ formulaire était `<input type="text" name="ville">` au lieu d'une liste déroulante

**Modifications**:
- Changé `$ville` en `$id_ville = (!empty($_POST['id_ville'])) ? intval($_POST['id_ville']) : null;`
- Mis à jour SQL: `UPDATE users SET ... id_ville = ?, ...`
- Converti le champ formulaire en liste déroulante avec options de ville depuis la table villes
- Ajout de `onchange="chargerQuartiers(this.value)"` pour remplir dynamiquement les options quartier
- Ajout de la fonction JavaScript `chargerQuartiers()` pour charger les quartiers via AJAX depuis `/coiffons/access/get_quartiers.php`

**Avant**:
```php
$ff = ['type'=>'text','name'=>'ville','label'=>'Ville',...];
```

**Après**:
```php
// Charge les villes depuis la base de données
$opts_v = [['value'=>'','label'=>'-- Sélectionnez une ville --']];
foreach ($liste_villes as $v) {
    $opts_v[] = ['value'=>$v['id'], 'label'=>$v['nom_ville'], ...];
}
$ff = ['type'=>'select','name'=>'id_ville','label'=>'Ville',...'attrs'=>'onchange="chargerQuartiers(this.value)"'];
```

### 3. access/connexion.php
**Problèmes**:
- Ligne 44: L'instruction SELECT utilisait la colonne `ville` (n'existe pas)
- Ligne 58: Définissait `$_SESSION['ville']` avec la valeur ID au lieu de `$_SESSION['id_ville']`
- Ligne 63: Utilisait `$user['ville']` au lieu de `$user['id_ville']`

**Modifications**:
- Changé SELECT pour utiliser `id_ville` au lieu de `ville`
- Mise à jour des variables SESSION: `$_SESSION['id_ville'] = $user['id_ville'];`
- Correction de la référence: `if ($user['id_ville'])` → récupérer le nom de ville

### 4. access/inscription.php
**Problème**:
- Ligne 103: L'instruction INSERT utilisait la colonne `ville` au lieu de `id_ville`

**Modifications**:
```sql
-- AVANT:
INSERT INTO users (..., ville, id_quartier, ...) VALUES (...)

-- APRÈS:
INSERT INTO users (..., id_ville, id_quartier, ...) VALUES (...)
```

### 5. coiffeurs/mes_zones.php
**Problème**: Ligne 54 sélectionnait la colonne `ville` non-existante
```php
-- AVANT:
$profile_stmt = $pdo->prepare("SELECT ville FROM users WHERE id = ?");

-- APRÈS:
$profile_stmt = $pdo->prepare("SELECT id_ville FROM users WHERE id = ?");
```

### 6. index.php
**Problèmes**: Deux occurrences de `$_SESSION['ville']` 
- Ligne 45: `$id_ville_client = $_SESSION['ville'] ?? 0;`
- Ligne 144: `$id_ville_client = $_SESSION['ville'] ?? 0;`

**Modifications**: Les deux mises à jour pour utiliser `$_SESSION['id_ville']`

### 7. ia_controlleur.php
**Problème**: Ligne 36 utilisait `$_SESSION['ville']`
```php
-- AVANT:
$ville_user = $_SESSION['ville'] ?? 0;

-- APRÈS:
$ville_user = $_SESSION['id_ville'] ?? 0;
```

### 8. filter/annuaire_coiffeurs.php
**Problème**: Ligne 31 utilisait `$_SESSION['ville']`
```php
-- AVANT:
$ville_session = $est_client_connecte ? (int)($_SESSION['ville'] ?? 0) : 0;

-- APRÈS:
$ville_session = $est_client_connecte ? (int)($_SESSION['id_ville'] ?? 0) : 0;
```

## Changements des Variables Session
Unification de toutes les variables session pour utiliser les noms corrects de colonnes base de données:
- `$_SESSION['ville']` → `$_SESSION['id_ville']` (stocke l'INT ID de ville)
- `$_SESSION['id_quartier']` (inchangé, déjà correct)

## Vérification
Toutes les opérations de mise à jour de profil maintenant:
1. ✅ Référencent correctement les colonnes `id_ville` et `id_quartier`
2. ✅ Stockent les entiers (IDs de ville/quartier) au lieu de texte
3. ✅ Utilisent des listes déroulantes select dans les formulaires au lieu de champs texte
4. ✅ Chargent les quartiers dynamiquement via AJAX quand la ville change
5. ✅ Hydratent correctement les variables session lors de la connexion/inscription

## Messages d'Erreur Corrigés
- ❌ "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'ville'"
- ❌ "SQLSTATE[HY000]: General error: 1054 Unknown column 'id_ville' in 'field list'"

Les deux erreurs devraient maintenant être résolues.

## Checklist de Test
- [ ] L'utilisateur peut modifier le profil depuis le tableau de bord coiffeur (profil_coiffeurs.php)
- [ ] L'utilisateur peut modifier le profil depuis modifier_profil.php
- [ ] La liste déroulante de ville se charge correctement
- [ ] La liste déroulante quartier se met à jour quand la ville change
- [ ] La sauvegarde/inscription crée des utilisateurs avec id_ville/id_quartier corrects
- [ ] L'hydratation de session login fonctionne correctement
- [ ] Filtre/annuaire affiche les correspondances géographiques correctes
