# Setup des tables manquantes — Domizi v2

## Erreur : Table 'domizi.zones_coiffeur' doesn't exist

### Cause
La migration v2 de Domizi a renommé les tables pour une cohérence de nommage :
- `zones_coiffeur` → `zones_prestataire`
- `id_coiffeur` → `id_prestataire`

Cependant, la table **n'existe pas** en base de données. Elle doit être créée manuellement.

### Solution

Exécutez ce SQL dans phpmyadmin ou mysql CLI :

```sql
-- Création de la table zones_prestataire
-- Gère les zones géographiques (quartiers) d'intervention de chaque prestataire

CREATE TABLE IF NOT EXISTS `zones_prestataire` (
    `id_prestataire` INT UNSIGNED NOT NULL,
    `id_quartier` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_prestataire`, `id_quartier`),
    CONSTRAINT `fk_zones_prestataire_user` 
        FOREIGN KEY (`id_prestataire`) 
        REFERENCES `users`(`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_zones_prestataire_quartier` 
        FOREIGN KEY (`id_quartier`) 
        REFERENCES `quartiers`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Après création

Une fois la table créée, les fichiers suivants fonctionneront correctement :
- ✓ `/coiffons/coiffeurs/mes_zones.php` — Permet à l'utilisateur de configurer ses zones d'intervention
- ✓ `/coiffons/filter/annuaire_coiffeurs.php` — Cherche les coiffeurs par zones
- ✓ `debug_dashboard_coiffeurs.php` — Affiche les zones d'un coiffeur

### Fichiers corrigés
- ✓ `mes_zones.php` : Utilise maintenant `zones_prestataire` (anciennement `zones_coiffeur`)
- ✓ `filter/annuaire_coiffeurs.php` : Jointure corrigée
- ✓ `test_dashboard_client.php` : Jointure corrigée  
- ✓ `debug_dashboard_coiffeurs.php` : Jointure corrigée
- ✓ `portefeuille.php` : Ligne 174 — syntaxe corrigée (if statement)

### État des migrations
| Table | Ancien nom | Nouveau nom | Status |
|-------|-----------|------------|--------|
| Zones | `zones_coiffeur` | `zones_prestataire` | ❌ À créer |
| Disponibilités | `disponibilites` | `disponibilites` | ✓ OK |
| Quartiers | `quartier` + `quartiers` | `quartiers` | ✓ Consolidée |
