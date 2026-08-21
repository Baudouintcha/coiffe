-- Migration : Support des tiers d'abonnement (Basique vs Premium)
-- Date: 2026-08-21
-- Description: Remplace le modèle binaire (payant/non-payant) par un système de tiers

-- 1. Ajouter la colonne statut_abonnement à la table users
ALTER TABLE `users` 
ADD COLUMN `statut_abonnement` ENUM('aucun', 'basique', 'premium') 
DEFAULT 'aucun' 
AFTER `is_approved`;

-- 2. Initialiser les coiffeurs approuvés existants avec le statut 'basique'
-- (Ceux qui avaient déjà payé ou qui étaient visibles)
UPDATE `users` 
SET `statut_abonnement` = 'basique'
WHERE `role` = 'coiffeur' 
  AND `is_approved` = 1 
  AND `statut_abonnement` = 'aucun';

-- 3. Index pour optimiser les requêtes de recherche/tri
ALTER TABLE `users` 
ADD INDEX `idx_visibility` (`role`, `is_approved`, `statut_abonnement`);

-- 4. Index pour tri dans l'annuaire
ALTER TABLE `users` 
ADD INDEX `idx_tier_search` (`statut_abonnement`, `created_at`);

-- Vérification
SELECT 
  id, 
  nom, 
  prenom,
  role,
  is_approved,
  statut_abonnement
FROM users 
WHERE role = 'coiffeur' 
LIMIT 10;
