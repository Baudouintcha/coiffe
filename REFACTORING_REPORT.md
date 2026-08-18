# Rapport de Refactoring CFT → DOMIZI

## État actuel

**12 fichiers refactorisés** avec 235 remplacements automatiques effectués.

## Fichiers encore à corriger manuellement

### 🔴 CRITIQUE - app.php (7 violations majeures)

Ce fichier est crucial car il génère le catalogue et la page d'accueil.

**Problèmes identifiés :**

```php
// LIGNE ~63-65 : Requêtes sur l'ancienne table 'prestations'
(SELECT MIN(p2.prix) FROM prestations p2 WHERE p2.id_coiffeur = u.id)
(SELECT p3.photo_style FROM prestations p3 WHERE p3.id_coiffeur = u.id)
(SELECT ROUND(AVG(c.note), 1) FROM commentaires c WHERE c.id_coiffeur = u.id)
(SELECT COUNT(c.id_commentaire) FROM commentaires c WHERE c.id_coiffeur = u.id)
LEFT JOIN zones_coiffeur z ON u.id = z.id_coiffeur
```

**À corriger en :**

```php
(SELECT MIN(s2.prix) FROM services s2 WHERE s2.id_prestataire = u.id)
(SELECT s3.photo FROM services s3 WHERE s3.id_prestataire = u.id)
(SELECT ROUND(AVG(av.note), 1) FROM avis av WHERE av.prestataire_id = u.id)
(SELECT COUNT(av.id) FROM avis av WHERE av.prestataire_id = u.id)
LEFT JOIN zones_prestataire z ON u.id = z.id_prestataire
```

**Occurrences :** 6 fois (lignes 62-96, 92-94, 120-124, 189-194, 210-215, 225-228, 239-242, 256-260)

---

### 🟡 IMPORTANT - layout/header_coiffeur.php

**Ligne ~27 :**
```php
$q = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE id_user = ? AND statut_lecture = 'non_lu'");
```

**Doit être :**
```php
$q = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = 0");
```

---

### 🟡 IMPORTANT - coiffeurs/mes_avis.php

Vérifier les références à `commentaires` et `type_commentaire`

---

### 🟡 IMPORTANT - coiffeurs/valider_rendezvous.php

Vérifier les références à `rendez_vous.coiffeur_id` (doit être `prestataire_id`)

---

### 🟡 IMPORTANT - security/marquer_notifs_lu.php

Vérifier les updates sur `statut_lecture` (doit être `lu`)

---

### 🟡 IMPORTANT - views/client/dashboard.php

Vérifier les SELECTs sur `rendez_vous` et les colonnes associées

---

### 🟡 IMPORTANT - filter/annuaire_coiffeurs.php

Vérifier les filtres sur `prestations` et `zones_coiffeur`

---

## Checklist de vérification

- [ ] app.php : remplacer toutes les requêtes prestations → services
- [ ] app.php : remplacer zones_coiffeur → zones_prestataire
- [ ] app.php : remplacer commentaires → avis
- [ ] layout/header_coiffeur.php : corriger notifications
- [ ] coiffeurs/mes_avis.php : vérifier avis
- [ ] coiffeurs/valider_rendezvous.php : vérifier rendez_vous
- [ ] security/marquer_notifs_lu.php : vérifier notifications
- [ ] views/client/dashboard.php : vérifier rendez_vous
- [ ] filter/annuaire_coiffeurs.php : vérifier services et zones
- [ ] Tester l'application complète

## Résumé des mappings

| Ancien | Nouveau |
|--------|---------|
| `prestations` | `services` |
| `zones_coiffeur` | `zones_prestataire` |
| `commentaires` | `avis` |
| `id_prestation` | `id_service` |
| `id_coiffeur` | `id_prestataire` |
| `nom_style` | `nom_service` |
| `photo_style` | `photo` |
| `type_commentaire` | `type` |
| `statut_lecture` | `lu` |
| `coiffeur_id` | `prestataire_id` |
| `coiffure_id` | `service_id` |
| `heure_début` | `heure_debut` |

