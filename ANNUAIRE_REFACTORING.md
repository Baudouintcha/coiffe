# Refactoring de l'Annuaire - Deux Recherches Indépendantes

**Date** : 14 Août 2026  
**Branche** : verse1  
**Statut** : ✅ Complété - Prêt pour tests

## Résumé des Modifications

L'annuaire des coiffeurs a été refactorisé pour offrir **deux options de recherche indépendantes** et claires :

### 1. **Recherche par Nom** (Onglet 1)
- L'utilisateur entre le nom, prénom ou nom complet d'un coiffeur
- Minimum 2 caractères pour déclencher les suggestions (debounce 260ms)
- Autocomplete en temps réel avec avatars et villes
- Recherche insensible aux accents et tolérante aux variantes de saisie
- **Actions disponibles** : Cliquer sur une suggestion ou soumettre le formulaire

### 2. **Recherche par Ville** (Onglet 2)
- Sélection obligatoire d'une **ville** dans un dropdown
- Affiche tous les coiffeurs de la ville sélectionnée
- Tri par note moyenne DESC, puis nombre d'avis DESC
- **Simple et direct** → Un seul dropdown à sélectionner

## Changements Techniques

### Logique PHP (`filter/annuaire_coiffeurs.php`)
```php
// Nouveau système de paramètre
$search_type = $_GET['search_type'] ?? ''; // 'name' ou 'ville'

// Deux requêtes SQL indépendantes
if ($search_type === 'name') { 
    // Recherche par nom/prénom avec LIKE %term%
}
elseif ($search_type === 'ville') { 
    // Recherche par ville avec WHERE u.ville = ?
}
```

### Interface Utilisateur
- **Onglets TAB UI** : Bascule entre les deux modes via boutons radio visuels
- **Recherche par Nom** : Autocomplete + suggestions en temps réel
- **Recherche par Ville** : Dropdown simple avec validation required
- **Validation Requise** : Tous les champs doivent être complétés avant soumission
- **Feedback Instant** : Suggestions autocomplete en direct (recherche nom)

### JavaScript
- Gestion des onglets avec `data-tab` et `.active` classes
- Débounce sur l'autocomplete (260ms)
- Navigation clavier supportée (↑↓ Enter Escape)
- **Pas de AJAX dynamique** pour l'onglet ville (simple dropdown)

## États de Recherche

### État Initial (Pas de recherche)
- Affichage du bloc **"Votre recherche commence ici"**
- Onglets visibles pour guider l'utilisateur
- Pour les clients connectés : affichage optionnel de leur zone détectée

### Recherche par Nom
- URL : `/filter/annuaire_coiffeurs.php?search_type=name&q=value`
- Retourne coiffeurs matchant nom/prenom
- Triés par note moyenne DESC, puis nb_avis DESC

### Recherche par Ville
- URL : `/filter/annuaire_coiffeurs.php?search_type=ville&ville=value`
- Retourne tous les coiffeurs de la ville
- Même tri que recherche par nom

## Tests à Effectuer

### ✅ Test 1 : Interface Onglets
- [ ] Cliquer sur onglet "Par nom" → formulaire nom visible
- [ ] Cliquer sur onglet "Par ville" → formulaire ville visible
- [ ] Onglets réactifs et feedback visuel (couleur or, underline)

### ✅ Test 2 : Recherche par Nom
- [ ] Taper 2+ caractères → suggestions apparaissent
- [ ] Cliquer sur suggestion → redirection vers profil public
- [ ] Soumettre formulaire → résultats affichés
- [ ] Navigation clavier (↑↓ pour naviguer, Enter pour valider)
- [ ] Escape ferme les suggestions

### ✅ Test 3 : Recherche par Ville
- [ ] Sélectionner une ville → afficher tous les coiffeurs de la ville
- [ ] Validation required fonctionne (impossible de soumettre sans ville)
- [ ] Résultats triés par note moyenne

### ✅ Test 4 : Résultats
- [ ] Cartes coiffeur s'affichent correctement
- [ ] Notes moyennes et nb_avis visibles
- [ ] Hover effects fonctionnels
- [ ] Nom de la ville affiché sur chaque carte

### ✅ Test 5 : Edge Cases
- [ ] Pas de résultats → empty state
- [ ] Ville sans coiffeurs → empty state
- [ ] Basculer entre onglets → état préservé correctement
- [ ] Back/Forward browser → URL et contenu synchronisés

## Fichiers Modifiés

1. **`/filter/annuaire_coiffeurs.php`** - Refactoring principal
   - ✅ Logique de recherche à deux branches (name / ville)
   - ✅ Onglets TAB UI
   - ✅ JavaScript gestion onglets (simplifié, pas de AJAX quartiers)

2. **`/access/search_coiffeurs.php`** - Inchangé (fonctionnel)
   - Continue de fournir l'autocomplete par nom

3. **`/access/get_quartiers.php`** - Non utilisé dans cette version
   - Conservé pour usage futur

## Guide de Test

### En Local
```bash
# Accéder à la page
http://localhost/coiffons/filter/annuaire_coiffeurs.php

# Test recherche par nom
# Taper "Jean" ou "Martin" dans l'onglet 1

# Test recherche par ville
# Sélectionner "Cotonou" ou autre ville dans l'onglet 2
```

### Checklist de Déploiement
- [x] Vérifier aucune erreur PHP (get_diagnostics ✅)
- [ ] Tester sur mobile (responsive design)
- [ ] Vérifier autocomplete accessible
- [ ] Valider avec WAI-ARIA (role, aria-selected, etc.)
- [ ] Tester avec navigateur sans JavaScript (dégradation gracieuse)

## Notes

- ❌ Ancien formulaire combiné avec ville + quartier optionnel → **Supprimé**
- ❌ Recherche par quartier (onglet 2 initial) → **Remplacé par recherche par ville**
- ✅ Zone auto client → **Conservée et fonctionnelle**
- ✅ Onglet "Modifier ma zone" → **Conservé pour clients connectés**
- ✨ Logique complètement décorrélée (recherche par nom ≠ recherche par ville)
- ✨ Interface simplifiée : pas de AJAX dynamique pour l'onglet ville

---

**Statut Tests** : En attente de feedback utilisateur
