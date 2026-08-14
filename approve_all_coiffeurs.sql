-- ================================================================
-- SCRIPT SQL: Approuver tous les coiffeurs rapidement
-- ================================================================
-- 
-- Usage: Exécutez ce script dans phpMyAdmin ou MySQL CLI
-- Situation d'urgence: Quand on veut remplir l'annuaire rapidement
--
-- ⚠️ ATTENTION: Cela approuve TOUS les coiffeurs sans vérification!
-- À utiliser seulement si vous êtes sûr.
--
-- ================================================================

-- 1. VÉRIFIER AVANT (combien de coiffeurs en attente)
SELECT 'AVANT APPROBATION' as Phase;
SELECT COUNT(*) as 'Coiffeurs en attente (is_approved=0)' FROM users WHERE role = 'coiffeur' AND is_approved = 0;
SELECT COUNT(*) as 'Coiffeurs approuvés (is_approved=1)' FROM users WHERE role = 'coiffeur' AND is_approved = 1;

-- 2. APPROUVER TOUS LES COIFFEURS
UPDATE users SET is_approved = 1 WHERE role = 'coiffeur';

-- 3. VÉRIFIER APRÈS
SELECT 'APRÈS APPROBATION' as Phase;
SELECT COUNT(*) as 'Coiffeurs en attente (is_approved=0)' FROM users WHERE role = 'coiffeur' AND is_approved = 0;
SELECT COUNT(*) as 'Coiffeurs approuvés (is_approved=1)' FROM users WHERE role = 'coiffeur' AND is_approved = 1;

-- 4. AFFICHER TOUS LES COIFFEURS ET LEUR STATUT
SELECT id, nom, prenom, telephone, is_approved, abonnement_status, created_at 
FROM users 
WHERE role = 'coiffeur' 
ORDER BY id DESC;

-- ================================================================
-- SCRIPTS ALTERNATIFS
-- ================================================================

-- ✅ Approuver un coiffeur spécifique (remplacez 17 par l'ID)
-- UPDATE users SET is_approved = 1 WHERE id = 17;

-- ✅ Refuser un coiffeur spécifique
-- UPDATE users SET is_approved = 0 WHERE id = 17;

-- ✅ Valider les abonnements aussi
-- UPDATE users SET abonnement_status = 1, date_expiration_abo = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE role = 'coiffeur';

-- ✅ Vérifier qui n'a pas d'abonnement
-- SELECT id, nom, prenom, abonnement_status FROM users WHERE role = 'coiffeur' AND abonnement_status = 0;

-- ✅ Vérifier qui a un diplôme
-- SELECT id, nom, prenom, diplome FROM users WHERE role = 'coiffeur' AND diplome IS NOT NULL AND diplome != '';
