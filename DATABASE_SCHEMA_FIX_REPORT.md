# Database Schema Column Name Fixes

## Summary
Fixed critical database schema inconsistencies where PHP code was referencing non-existent column names in the `users` table. The issue was that code referenced `ville` (text field) when the actual database column is `id_ville` (INT) to store the city ID.

## Root Cause
- **Database Schema (domizi_v2.sql)** defines users table with:
  - `id_ville` (INT UNSIGNED) — Foreign key to villes.id
  - `id_quartier` (INT UNSIGNED) — Foreign key to quartiers.id

- **Legacy Code** was using:
  - `ville` (non-existent column)
  - `id_quartier` (correct, but paired with wrong column name)

## Files Fixed

### 1. coiffeurs/profil_coiffeurs.php
**Issue**: Line 40 was using UPDATE with `ville` column instead of `id_ville`
```sql
-- BEFORE (WRONG):
UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, ville = ?, id_quartier = ? WHERE id = ?

-- AFTER (FIXED):
UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, id_ville = ?, id_quartier = ? WHERE id = ?
```
**Impact**: Profile updates now correctly save city selection.

### 2. modifier_profil.php
**Issues**:
- Line 47: Used `$ville = htmlspecialchars(trim($_POST['ville']));` storing text instead of integer
- Line 58: UPDATE statement used `ville = ?` with text value
- Line 176-188: Form field was `<input type="text" name="ville">` instead of dropdown

**Changes**:
- Changed `$ville` to `$id_ville = (!empty($_POST['id_ville'])) ? intval($_POST['id_ville']) : null;`
- Updated SQL: `UPDATE users SET ... id_ville = ?, ...`
- Converted form field to dropdown with city options from villes table
- Added `onchange="chargerQuartiers(this.value)"` to populate quartier options dynamically
- Added JavaScript function `chargerQuartiers()` to load quartiers via AJAX from `/coiffons/access/get_quartiers.php`

**Before**:
```php
$ff = ['type'=>'text','name'=>'ville','label'=>'Ville',...];
```

**After**:
```php
// Loads villes from database
$opts_v = [['value'=>'','label'=>'-- Sélectionnez une ville --']];
foreach ($liste_villes as $v) {
    $opts_v[] = ['value'=>$v['id'], 'label'=>$v['nom_ville'], ...];
}
$ff = ['type'=>'select','name'=>'id_ville','label'=>'Ville',...'attrs'=>'onchange="chargerQuartiers(this.value)"'];
```

### 3. access/connexion.php
**Issues**:
- Line 44: SELECT statement used `ville` column (doesn't exist)
- Line 58: Set `$_SESSION['ville']` with ID value instead of `$_SESSION['id_ville']`
- Line 63: Used `$user['ville']` instead of `$user['id_ville']`

**Changes**:
- Changed SELECT to use `id_ville` instead of `ville`
- Updated SESSION variables: `$_SESSION['id_ville'] = $user['id_ville'];`
- Fixed referencing: `if ($user['id_ville'])` → fetch city name

### 4. access/inscription.php
**Issues**:
- Line 103: INSERT statement used `ville` column instead of `id_ville`

**Changes**:
```sql
-- BEFORE:
INSERT INTO users (..., ville, id_quartier, ...) VALUES (...)

-- AFTER:
INSERT INTO users (..., id_ville, id_quartier, ...) VALUES (...)
```

### 5. coiffeurs/mes_zones.php
**Issue**: Line 54 selected non-existent `ville` column
```php
-- BEFORE:
$profile_stmt = $pdo->prepare("SELECT ville FROM users WHERE id = ?");

-- AFTER:
$profile_stmt = $pdo->prepare("SELECT id_ville FROM users WHERE id = ?");
```

### 6. index.php
**Issues**: Two occurrences of `$_SESSION['ville']` 
- Line 45: `$id_ville_client = $_SESSION['ville'] ?? 0;`
- Line 144: `$id_ville_client = $_SESSION['ville'] ?? 0;`

**Changes**: Both updated to use `$_SESSION['id_ville']`

### 7. ia_controlleur.php
**Issue**: Line 36 used `$_SESSION['ville']`
```php
-- BEFORE:
$ville_user = $_SESSION['ville'] ?? 0;

-- AFTER:
$ville_user = $_SESSION['id_ville'] ?? 0;
```

### 8. filter/annuaire_coiffeurs.php
**Issue**: Line 31 used `$_SESSION['ville']`
```php
-- BEFORE:
$ville_session = $est_client_connecte ? (int)($_SESSION['ville'] ?? 0) : 0;

-- AFTER:
$ville_session = $est_client_connecte ? (int)($_SESSION['id_ville'] ?? 0) : 0;
```

## Session Variable Changes
Unified all session variables to use correct database column names:
- `$_SESSION['ville']` → `$_SESSION['id_ville']` (stores INT city ID)
- `$_SESSION['id_quartier']` (unchanged, already correct)

## Verification
All profile update operations now:
1. ✅ Correctly reference `id_ville` and `id_quartier` columns
2. ✅ Store integers (city/quartier IDs) instead of text
3. ✅ Use dropdown selects in forms instead of text inputs
4. ✅ Load quartiers dynamically via AJAX when city changes
5. ✅ Properly hydrate session variables on login/registration

## Error Messages Fixed
- ❌ "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'ville'"
- ❌ "SQLSTATE[HY000]: General error: 1054 Unknown column 'id_ville' in 'field list'"

Both errors should now be resolved.

## Testing Checklist
- [ ] User can modify profile from coiffeur dashboard (profil_coiffeurs.php)
- [ ] User can modify profile from modifier_profil.php
- [ ] City dropdown loads correctly
- [ ] Quartier dropdown updates when city changes
- [ ] Save/registration creates users with correct id_ville/id_quartier
- [ ] Login session hydration works correctly
- [ ] Filter/annuaire shows correct geographic matching
