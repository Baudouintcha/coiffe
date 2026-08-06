-- ========================================================
-- MIGRATION : Standardisation des tables quartier/quartiers
-- ========================================================

-- 1. Créer la nouvelle table quartiers avec la structure correcte
CREATE TABLE IF NOT EXISTS `quartiers_new` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_ville` INT UNSIGNED NOT NULL,
    `nom_quartier` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `id_ville` (`id_ville`),
    CONSTRAINT `quartiers_new_ibfk_1` FOREIGN KEY (`id_ville`)
        REFERENCES `villes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Copier les données de l'ancienne table vers la nouvelle
INSERT INTO `quartiers_new` (`id`, `id_ville`, `nom_quartier`)
SELECT `id_quartier`, `id_ville`, `nom_quartier` FROM `quartier`;

-- 3. Supprimer l'ancienne table
DROP TABLE IF EXISTS `quartier`;

-- 4. Renommer la nouvelle table
RENAME TABLE `quartiers_new` TO `quartiers`;

-- ========================================================
-- FIN DE LA MIGRATION
-- ========================================================
