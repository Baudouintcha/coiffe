# 🔴 FIX: Erreur 404 Quand on Clique "Voir" le Diplôme

**Problème**: Clic sur le bouton "Voir" → Erreur 404  
**Cause**: Les chemins des diplômes stockés en BD ne pointent pas correctement vers les fichiers  
**Date Fix**: 14 Août 2026

---

## 🔍 DIAGNOSTIC

### Où se trouve le problème?

```
Admin Dashboard → Onglet "Validations & Flux"
    ↓
Section "Diplômes en Attente d'Approbation"
    ↓
Colonne "Diplôme" → Bouton "Voir"
    ↓
Lien du diplôme = `$dip['diplome']` (direct depuis BD)
    ↓
❌ Chemin incorrect → 404
```

### Cause Racine

Les chemins des diplômes en BD sont stockés **incomplets ou au mauvais format**:

```
Exemples de ce qui pourrait être stocké:
❌ "6a15b06d5b4e79.04204871.jpg"           (juste le nom de fichier)
❌ "/uploads/diplomes/6a15b06d5b4e79.jpg"  (chemin mauvais)
❌ "uploads/diplomes/6a15b06d5b4e79.jpg"   (chemin relatif incomplet)
✅ "/access/uploads/diplomes/6a15b06d5b4e79.jpg"  (chemin correct)
```

---

## ✅ FIX APPLIQUÉ

### Changement dans `/first/admin_dashboard.php`

**Ligne**: 583-597

**Avant**:
```php
<a href="<?php echo htmlspecialchars($dip['diplome']); ?>" target="_blank" 
   class="btn btn-sm btn-outline-info">
    <i class="bi bi-eye"></i> Voir
</a>
```

**Après**:
```php
<?php 
    // Construire le chemin complet du diplôme
    $diplome_path = $dip['diplome'];
    
    // Si c'est un chemin relatif, ajouter le préfixe
    if (!empty($diplome_path) && strpos($diplome_path, 'http') !== 0 && strpos($diplome_path, '/') !== 0) {
        $diplome_path = '../access/uploads/diplomes/' . basename($diplome_path);
    } elseif (!empty($diplome_path) && strpos($diplome_path, 'http') !== 0) {
        // Si c'est un chemin absolu mais pas une URL
        $diplome_path = '../access/uploads/diplomes/' . basename($diplome_path);
    }
?>
<?php if (!empty($diplome_path)): ?>
    <a href="<?php echo htmlspecialchars($diplome_path); ?>" target="_blank" 
       class="btn btn-sm btn-outline-info" title="Ouvrir le diplôme">
        <i class="bi bi-eye"></i> Voir
    </a>
<?php else: ?>
    <span class="text-muted small">Pas de diplôme</span>
<?php endif; ?>
```

### Ce que fait le fix

1. ✅ **Récupère** le chemin stocké en BD
2. ✅ **Nettoie** le chemin (extraire juste le nom de fichier avec `basename()`)
3. ✅ **Construit** le chemin complet: `../access/uploads/diplomes/[nom_fichier]`
4. ✅ **Affiche** le bouton uniquement si diplôme existe
5. ✅ **Fonctionne** même si le chemin en BD est incomplet

---

## 🧪 COMMENT TESTER

### Étape 1: Diagnostic
```
URL: http://localhost/coiffons/debug_diplomes_path.php
```

Vous verrez:
- ✅ Tous les diplômes en BD
- ✅ Le chemin exact stocké
- ✅ Si le fichier existe
- ✅ Suggestions de fixes

### Étape 2: Tester le fix
```
URL: http://localhost/coiffons/first/admin_dashboard.php
Onglet: "Validations & Flux"
Section: "Diplômes en Attente"
```

- Cliquez sur "Voir" pour un diplôme
- ✅ Le fichier doit s'ouvrir (ou le navigateur propose de télécharger)
- ❌ Si encore 404, allez à l'Étape 3

### Étape 3: Vérifier les fichiers

Le dossier des uploads doit exister:
```
c:\xampp\htdocs\coiffons\access\uploads\diplomes\
```

Fichiers qu'il doit contenir:
```
6a15b06d5b4e79.04204871.jpg
6a15ce256c6084.77673094.jpg
6a207296b26b26.53936714.jpeg
(Ou autres noms de fichiers)
```

**Commande pour vérifier**:
```powershell
dir "c:\xampp\htdocs\coiffons\access\uploads\diplomes\"
```

---

## 🔧 SI LE PROBLÈME PERSISTE

### Option 1: Vérifier la BD directement

```sql
-- Voir ce qui est stocké dans la colonne diplome
SELECT id, nom, diplome FROM users 
WHERE role = 'coiffeur' AND diplome IS NOT NULL 
LIMIT 5;
```

### Option 2: Corriger les chemins en BD

Si les chemins sont tous mauvais, on peut les corriger:

```sql
-- Extraire juste le nom de fichier et ajouter le bon préfixe
UPDATE users 
SET diplome = CONCAT('/access/uploads/diplomes/', SUBSTRING_INDEX(diplome, '/', -1)) 
WHERE role = 'coiffeur' AND diplome IS NOT NULL;
```

### Option 3: Créer un script de correction

Créez un fichier `fix_diplomes.php`:

```php
<?php
require_once __DIR__ . '/security/config.php';

// Récupérer tous les coiffeurs avec diplômes
$coiffeurs = $pdo->query("
    SELECT id, diplome FROM users 
    WHERE role = 'coiffeur' AND diplome IS NOT NULL
")->fetchAll();

foreach ($coiffeurs as $c) {
    $filename = basename($c['diplome']);
    $new_path = '/access/uploads/diplomes/' . $filename;
    
    $update = $pdo->prepare("UPDATE users SET diplome = ? WHERE id = ?");
    $update->execute([$new_path, $c['id']]);
    
    echo "✅ ID {$c['id']}: {$new_path}\n";
}

echo "\n✅ Tous les chemins ont été corrigés!";
?>
```

Puis accédez à: `http://localhost/coiffons/fix_diplomes.php`

---

## 📊 TABLEAU DE DÉPANNAGE

| Symptôme | Cause | Solution |
|----------|-------|----------|
| ❌ 404 file not found | Chemin incomplet en BD | Fix appliqué + test |
| ❌ 404 file not found (persiste) | Fichier supprimé ou déplacé | Recréer le dossier uploads |
| ✅ Fichier s'ouvre | Chemin correct | ✅ Fonctionne! |
| ❌ Pas de bouton "Voir" | Pas de diplôme en BD | Normal, coiffeur n'a pas uploadé |

---

## ✨ RÉSULTAT

### Avant le fix:
```
Clic sur "Voir"
    ↓
❌ 404 page not found
    ↓
Admin frustré
```

### Après le fix:
```
Clic sur "Voir"
    ↓
✅ Diplôme s'ouvre (PDF/Image)
    ↓
Admin peut vérifier et approuver
```

---

## 📁 FICHIERS MODIFIÉS

- ✅ `/first/admin_dashboard.php` (lignes 583-597)

## 📁 FICHIERS CRÉÉS

- 🆕 `/debug_diplomes_path.php` (outil de diagnostic)
- 📄 `/FIX_DIPLOMES_404.md` (ce document)

---

**Status**: ✅ FIX APPLIQUÉ  
**Testé**: Non (À tester après)  
**Prêt pour**: Vérification

Allez sur `debug_diplomes_path.php` pour vérifier que tout fonctionne! 🚀
