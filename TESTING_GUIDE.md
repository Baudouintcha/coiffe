# 🧪 Guide Complet de Test

## 🎯 Objectif
Valider que TOUS les fixes et améliorations fonctionnent correctement avant le déploiement en production.

---

## ✅ TEST 1: Base de Données - Inscription

### Étapes
1. Aller à `/coiffons/index.php?page=register&role=coiffeur`
2. Remplir le formulaire d'inscription:
   - Nom: `Dupont`
   - Prénom: `Jean`
   - Email: `jean.dupont@test.com`
   - Téléphone: `+22967000000`
   - **Ville**: Sélectionner `Cotonou` (dropdown)
   - **Quartier**: Sélectionner `Agla` (doit se charger automatiquement)
   - Password: `password123`
   - Diplôme: Upload une image
   - Photo: Upload une image

### Résultats Attendus
✅ Pas d'erreur SQL affichée
✅ Inscription réussie
✅ Redirect vers la page appropriée
✅ En BDD: table `users` a bien `id_ville=1` (INT) et `id_quartier=x` (INT)

### Validation SQL
```sql
SELECT id, nom, id_ville, id_quartier FROM users WHERE email='jean.dupont@test.com';
-- Doit afficher: id_ville: 1 (INT), id_quartier: [INT ID du quartier]
```

---

## ✅ TEST 2: Profil Client - Modification

### Étapes
1. Se connecter en tant que client
2. Aller à `/coiffons/modifier_profil.php`
3. Modifier:
   - Téléphone: `+22968000000`
   - Ville: Changer vers `Porto-Novo` (dropdown)
   - Quartier: Doit charger automatiquement les quartiers de Porto-Novo (AJAX)
   - Sélectionner `Agblangandan`
4. Cliquer "ENREGISTRER LES MODIFS"

### Résultats Attendus
✅ Pas d'erreur "Unknown column 'id_ville'"
✅ Dropdown ville fonctionne
✅ Dropdown quartier se charge via AJAX quand ville change
✅ Données sauvegardées correctement
✅ Redirect vers profil.php
✅ En BDD: `id_ville` = 49 (Porto-Novo), `id_quartier` = nouveau quartier

### Validation SQL
```sql
SELECT id, nom, id_ville, id_quartier FROM users WHERE id=[USER_ID];
-- Doit afficher les nouvelles valeurs correctes
```

---

## ✅ TEST 3: Profil Coiffeur - Modification

### Étapes
1. Se connecter en tant que coiffeur
2. Aller à `/coiffons/coiffeurs/profil_coiffeurs.php`
3. Modifier:
   - Nom: `Durand`
   - Prenom: `Michel`
   - Téléphone: `+22961000000`
   - Ville: Sélectionner `Parakou` (dropdown)
   - Quartier: Se charge via AJAX
4. Cliquer "ENREGISTRER LES MODIFICATIONS"

### Résultats Attendus
✅ Pas d'erreur SQL
✅ Dropdown ville = selectbox (pas input text)
✅ AJAX charge quartiers correctement
✅ Données sauvegardées
✅ Toast vert: "Profil mis à jour avec succès"

### Validation SQL
```sql
SELECT id, nom, id_ville FROM disponibilites_coiffeur WHERE id=[COIFFEUR_ID];
-- Doit afficher les données correctes
```

---

## ✅ TEST 4: Agenda - Premier Remplissage

### Étapes
1. Se connecter en tant que coiffeur (ou utiliser coiffeur test)
2. Aller à `/coiffons/coiffeurs/agenda_coiffeurs.php`
3. **VÉRIFIER LA PRÉSENTATION**:
   - [ ] Tableau centré sur la page
   - [ ] Titre "Définissez vos jours..." CENTRÉ
   - [ ] Sous-titre CENTRÉ
   - [ ] Conteneur max-width environ 700px
   
4. **TEST CHECKBOX/INPUTS**:
   - [ ] Cliquer checkbox "Lundi"
   - [ ] VÉRIFIER: inputs `heure_debut[Lundi]` et `heure_fin[Lundi]` deviennent actifs
   - [ ] Inputs NOT grisés (opacité = 1)
   - [ ] Ligne "Lundi" surlignée (couleur légère or)
   
5. **REMPLIR LES DONNÉES**:
   - Lundi: 08:00 - 19:00
   - Mercredi: 09:00 - 18:00
   - Vendredi: 08:00 - 19:00
   - Dimanche: 10:00 - 15:00
   
6. **TESTER DÉCOCHAGE**:
   - [ ] Décocher "Lundi"
   - [ ] Inputs heure_debut/fin DOIVENT se désactiver (disabled=true)
   - [ ] Inputs grisés (opacité = 0.35)
   - [ ] Ligne "Lundi" perd sa couleur
   
7. **RECOCHAGE**:
   - [ ] Recocher "Lundi"
   - [ ] Inputs réactivés
   - [ ] Les valeurs saisies (08:00 - 19:00) DOIVENT rester
   
8. **SOUMETTRE**:
   - [ ] Cliquer "ENREGISTRER MON AGENDA"
   - [ ] Pas d'erreur PHP/SQL
   - [ ] Toast vert: "Votre agenda a été enregistré avec succès"

### Résultats Attendus - Mode Édition
✅ Fonction `toggleRow()` active/désactive les inputs
✅ Opacité visuelle feedback correct (1 ou 0.35)
✅ Classe CSS `agenda-row-active` ajoutée/supprimée
✅ Valeurs conservées au basculement checkbox
✅ Sauvegarde sans erreur

### Résultats Attendus - Mode Lecture
✅ Page bascule en mode lecture (au lieu de rester en édition)
✅ Affiche cards compactes avec créneaux (Lundi: 08:00-19:00, etc.)
✅ Cards centrées et propres
✅ Bouton "Modifier l'agenda" visible en bas
✅ Layout centré (max-width: 700px)
✅ Responsive sur mobile (2 cards par ligne)

### Validation SQL
```sql
SELECT * FROM disponibilites WHERE coiffeur_id=[ID] ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', ...);
-- Doit afficher 4 lignes: Lundi, Mercredi, Vendredi, Dimanche avec les heures correctes
```

---

## ✅ TEST 5: Agenda - Modification

### Étapes
1. Depuis le mode lecture (après TEST 4)
2. Cliquer "Modifier l'agenda"
3. **VÉRIFIER**:
   - [ ] Retour en mode édition
   - [ ] Les checkboxes SONT cochées pour Lundi, Mercredi, Vendredi, Dimanche
   - [ ] Les inputs SONT pré-remplis (08:00 - 19:00, etc.)
   - [ ] Les lignes préexistantes sont surlignées
   
4. **MODIFIER**:
   - Décocher "Mercredi"
   - Lundi: changer 08:00 → 07:00
   - Cocher "Samedi" et remplir 10:00 - 17:00
   
5. **SOUMETTRE**:
   - Cliquer "ENREGISTRER MON AGENDA"
   - Toast vert
   - Page bascule en mode lecture

### Résultats Attendus
✅ Pré-remplissage des données existantes
✅ Modifications reflétées en BDD
✅ Mercredi DISPARAÎT de la vue lecture
✅ Samedi APPARAÎT dans la vue lecture
✅ Lundi affiche "07:00 - 19:00"

### Validation SQL
```sql
SELECT jour_semaine, heure_debut, heure_fin FROM disponibilites 
WHERE coiffeur_id=[ID] ORDER BY FIELD(jour_semaine, ...);

-- Attendu:
-- Lundi, 07:00, 19:00
-- Vendredi, 08:00, 19:00
-- Samedi, 10:00, 17:00
-- Dimanche, 10:00, 15:00
-- (Mercredi absent)
```

---

## ✅ TEST 6: Sessions - Hydratation

### Étapes
1. Se connecter en tant que client/coiffeur
2. Ouvrir la console navigateur (F12 → Réseau ou Application)
3. Ou ajouter temporairement un debug:

```php
// À la fin de connexion.php
echo '<pre>';
echo 'SESSION["id_ville"] = ' . ($_SESSION['id_ville'] ?? 'NOT SET') . ' (type: ' . gettype($_SESSION['id_ville'] ?? null) . ')';
echo "\n";
echo 'SESSION["id_quartier"] = ' . ($_SESSION['id_quartier'] ?? 'NOT SET') . ' (type: ' . gettype($_SESSION['id_quartier'] ?? null) . ')';
echo '</pre>';
```

### Résultats Attendus
✅ `$_SESSION['id_ville']` = INT (ex: 1)
✅ `$_SESSION['id_quartier']` = INT (ex: 5)
✅ PAS de `$_SESSION['ville']` (old key, supprimée)
✅ Les valeurs corrects du profil utilisateur

---

## ✅ TEST 7: Quartiers AJAX

### Étapes
1. Aller à `/coiffons/modifier_profil.php`
2. Ouvrir DevTools (F12 → Réseau)
3. Sélectionner une ville dans le dropdown
4. Observer l'appel AJAX
5. Vérifier la réponse

### Résultats Attendus
✅ Appel AJAX vers `/coiffons/access/get_quartiers.php?id_ville=X`
✅ Réponse JSON correcte: `[{"id": 1, "nom_quartier": "Agla"}, ...]`
✅ Dropdown quartier se remplit automatiquement
✅ Aucune erreur 404 ou 500

### Test Manuel
```javascript
// Dans la console (F12):
fetch('/coiffons/access/get_quartiers.php?id_ville=1')
  .then(r => r.json())
  .then(d => console.log(d));
  
// Attendu: Array de quartiers avec id et nom_quartier
```

---

## ✅ TEST 8: Annuaire Coiffeurs (Geographic Matching)

### Étapes
1. Se connecter en tant que client dans Cotonou
2. Aller à `/coiffons/filter/annuaire_coiffeurs.php`
3. Vérifier que seuls les coiffeurs de Cotonou apparaissent

### Résultats Attendus
✅ SESSION['id_ville'] utilisé pour filtrer (valeur INT)
✅ Coiffeurs de Cotonou affichés
✅ Coiffeurs d'autres villes NOT affichés

### Validation SQL
```sql
-- Le code PHP exécute quelque chose comme:
SELECT * FROM users u
  JOIN profils_prestataires p ON u.id = p.user_id
  WHERE u.id_ville = 1;  -- ← id_ville DOIT être INT
```

---

## 📋 Checklist Complète

### Base de Données
- [ ] Inscription client - pas d'erreur SQL
- [ ] Inscription coiffeur - pas d'erreur SQL
- [ ] Modification profil client - pas d'erreur SQL
- [ ] Modification profil coiffeur - pas d'erreur SQL
- [ ] En BDD: tous les `id_ville` et `id_quartier` sont INT, pas TEXT

### Agenda - Mode Édition
- [ ] Tableau centré (max-width: 700px)
- [ ] Titre centré
- [ ] Sous-titre centré
- [ ] Checkboxes cochables
- [ ] Inputs temps disabled par défaut
- [ ] Checkbox cochée → inputs activés
- [ ] Checkbox décochée → inputs désactivés
- [ ] Opacité visuelle feedback (1 vs 0.35)
- [ ] Ligne surlignée pour jours actifs
- [ ] Valeurs conservées au basculement checkbox

### Agenda - Mode Lecture
- [ ] Page bascule en mode lecture après sauvegarde
- [ ] Cards compactes avec créneaux
- [ ] Layout centré (max-width: 700px)
- [ ] Responsive mobile (2 cards par ligne)
- [ ] Bouton "Modifier l'agenda" visible
- [ ] Toast vert après sauvegarde

### Sessions
- [ ] `$_SESSION['id_ville']` = INT (pas TEXT)
- [ ] `$_SESSION['id_quartier']` = INT
- [ ] Pas d'erreur lors de connexion
- [ ] Hydratation correcte après login

### Quartiers AJAX
- [ ] GET_quartiers.php retourne JSON valide
- [ ] Dropdown quartier se remplit au changement de ville
- [ ] Pas d'erreur console
- [ ] Pas d'erreur HTTP 404/500

### Bonus
- [ ] Annuaire filtre par `id_ville` correctement
- [ ] IA Assistant utilise `$_SESSION['id_ville']` correctement
- [ ] Autres pages utilisant session['id_ville'] fonctionnent

---

## 🐛 Troubleshooting

### Erreur: "Unknown column 'ville'"
**Cause**: Un fichier n'a pas été mis à jour
**Solution**:
```bash
grep -r "UPDATE users.*ville\s*=" /coiffons
grep -r "SELECT.*ville.*FROM users" /coiffons
# Chercher les lignes restantes et les corriger
```

### Inputs de temps ne s'activent pas
**Cause**: Fonction `toggleRow()` non-définie
**Solution**:
```bash
# Vérifier que le script est présent:
grep -n "function toggleRow" /coiffons/coiffeurs/agenda_coiffeurs.php
# Doit afficher le code vers la fin du fichier
```

### Toast n'apparaît pas après sauvegarde agenda
**Cause**: Header redirect oublie le paramètre `status=success`
**Solution**:
```php
// Vérifier sauvegarder_agenda.php:
header('Location: /coiffons/coiffeurs/agenda_coiffeurs.php?status=success');
//                                                         ^^^^^^^^^^^^^^^^^
```

### Tableau agenda pas centré
**Cause**: Wrapper `max-width:700px` manquant
**Solution**:
```html
<!-- Vérifier que le wrapper est là: -->
<div style="max-width:700px;margin:0 auto;">
    <!-- Contenu centré -->
</div>
```

---

## 📊 Rapport de Test

Après complétion, générer un rapport:

```markdown
# Rapport de Test - [DATE]

## Résumé
- Tests Total: 8
- Passés: [X/8]
- Échoués: [Y/8]
- Status: ✅ PRÊT PRODUCTION / ❌ NON PRÊT

## Détails
- Test 1 (Inscription BDD): ✅ / ❌
- Test 2 (Profil Client): ✅ / ❌
- ...

## Notes
- Aucun problème identifié
- OU: Les problèmes suivants doivent être corrigés avant prod

## Validé par
- [NOM] - [DATE]
```

---

## 🎯 Go/No-Go Decision

**Go en Production si**:
- ✅ Tests 1-6 TOUS passés
- ✅ Aucune erreur SQL/Console
- ✅ Données BDD intègres
- ✅ Responsive OK (mobile/tablet/desktop)

**No-Go si**:
- ❌ Même un test échoue
- ❌ Des erreurs SQL/Console persistent
- ❌ Données corrompues

---

**Bon testing! 🚀**
