<?php
/**
 * security/ValidationService.php — Service de validation des transactions
 * Valide tous les paramètres avant l'exécution d'une transaction
 * 
 * Vérifie :
 * - user_id
 * - action (recharge, abonnement, gel, etc.)
 * - montant (min/max autorisés)
 * - durée (pour abonnement)
 * - motif
 * - type de transaction
 */

class ValidationService
{
    private PDO $pdo;

    // Montants autorisés
    const MIN_RECHARGE = 500;       // Minimum 500 FCFA
    const MAX_RECHARGE = 500000;    // Maximum 500 000 FCFA
    const ABONNEMENT_MONTANT = 1500; // Prix fixe abonnement 1500 FCFA
    const MAX_SERVICE_PRICE = 100000; // Prix max d'un service

    // Durées autorisées
    const MIN_SUBSCRIPTION_DAYS = 30;
    const MAX_SUBSCRIPTION_DAYS = 365;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Valide les paramètres d'une recharge de portefeuille
     * 
     * @param int $user_id ID de l'utilisateur
     * @param float $montant Montant à recharger
     * @return array ['valid'=>bool, 'errors'=>array]
     */
    public function validateRecharge(int $user_id, float $montant): array
    {
        $errors = [];

        if (!$this->isValidUserId($user_id)) {
            $errors[] = 'ID utilisateur invalide';
        }

        if ($montant < self::MIN_RECHARGE) {
            $errors[] = "Montant minimum : " . number_format(self::MIN_RECHARGE, 0, ',', ' ') . " FCFA";
        }

        if ($montant > self::MAX_RECHARGE) {
            $errors[] = "Montant maximum : " . number_format(self::MAX_RECHARGE, 0, ',', ' ') . " FCFA";
        }

        if (!is_numeric($montant) || $montant <= 0) {
            $errors[] = 'Montant invalide';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Valide les paramètres d'activation d'abonnement
     * 
     * @param int $prestataire_id ID du prestataire
     * @param float|null $montant Montant (doit être 1500)
     * @param int|null $duree Durée en jours
     * @return array ['valid'=>bool, 'errors'=>array]
     */
    public function validateAbonnement(int $prestataire_id, ?float $montant = null, ?int $duree = null): array
    {
        $errors = [];

        if (!$this->isValidUserId($prestataire_id)) {
            $errors[] = 'ID prestataire invalide';
        }

        // Vérifier que c'est bien un prestataire
        if (!$this->isPrestataire($prestataire_id)) {
            $errors[] = 'Utilisateur n\'est pas un prestataire';
        }

        // Montant fixe
        if ($montant !== null && $montant != self::ABONNEMENT_MONTANT) {
            $errors[] = "Montant fixe : " . self::ABONNEMENT_MONTANT . " FCFA";
        }

        // Durée
        if ($duree === null) {
            $duree = 30; // Par défaut 30 jours
        }

        if ($duree < self::MIN_SUBSCRIPTION_DAYS || $duree > self::MAX_SUBSCRIPTION_DAYS) {
            $errors[] = "Durée entre " . self::MIN_SUBSCRIPTION_DAYS . " et " . self::MAX_SUBSCRIPTION_DAYS . " jours";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Valide les paramètres d'un service
     * 
     * @param int $prestataire_id ID du prestataire
     * @param string $nom Nom du service
     * @param float $prix Prix du service
     * @param int|null $duree Durée en minutes
     * @return array ['valid'=>bool, 'errors'=>array]
     */
    public function validateService(int $prestataire_id, string $nom, float $prix, ?int $duree = null): array
    {
        $errors = [];

        if (!$this->isValidUserId($prestataire_id)) {
            $errors[] = 'ID prestataire invalide';
        }

        if (empty(trim($nom)) || strlen($nom) > 150) {
            $errors[] = 'Nom du service invalide (1-150 caractères)';
        }

        if ($prix <= 0 || $prix > self::MAX_SERVICE_PRICE) {
            $errors[] = "Prix entre 1 et " . number_format(self::MAX_SERVICE_PRICE, 0, ',', ' ') . " FCFA";
        }

        if ($duree !== null && ($duree < 15 || $duree > 480)) {
            $errors[] = 'Durée entre 15 et 480 minutes';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Valide les paramètres d'une réservation
     * 
     * @param int $client_id ID du client
     * @param int $prestataire_id ID du prestataire
     * @param float $montant Montant total
     * @param string $date_rdv Date du RDV (YYYY-MM-DD)
     * @param string $heure_debut Heure début (HH:MM)
     * @return array ['valid'=>bool, 'errors'=>array]
     */
    public function validateReservation(int $client_id, int $prestataire_id, float $montant, string $date_rdv, string $heure_debut): array
    {
        $errors = [];

        if (!$this->isValidUserId($client_id)) {
            $errors[] = 'ID client invalide';
        }

        if (!$this->isValidUserId($prestataire_id)) {
            $errors[] = 'ID prestataire invalide';
        }

        if ($client_id === $prestataire_id) {
            $errors[] = 'Un client ne peut pas être son propre prestataire';
        }

        if ($montant <= 0 || $montant > self::MAX_SERVICE_PRICE) {
            $errors[] = "Montant invalide";
        }

        // Vérifier la date
        if (!$this->isValidDate($date_rdv)) {
            $errors[] = 'Date invalide (format YYYY-MM-DD)';
        } else {
            $date = strtotime($date_rdv);
            if ($date < strtotime('today')) {
                $errors[] = 'La date doit être dans le futur';
            }
        }

        // Vérifier l'heure
        if (!$this->isValidTime($heure_debut)) {
            $errors[] = 'Heure invalide (format HH:MM)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Vérifie si un montant est valide
     * 
     * @param float $montant Montant
     * @param float $min Minimum
     * @param float $max Maximum
     * @return bool
     */
    public function isValidMontant(float $montant, float $min = 0, float $max = PHP_FLOAT_MAX): bool
    {
        return is_numeric($montant) && $montant >= $min && $montant <= $max;
    }

    /**
     * Vérifie si un ID utilisateur est valide
     * 
     * @param int $user_id ID de l'utilisateur
     * @return bool
     */
    public function isValidUserId(int $user_id): bool
    {
        return $user_id > 0;
    }

    /**
     * Vérifie si un utilisateur est prestataire
     * 
     * @param int $user_id ID de l'utilisateur
     * @return bool
     */
    public function isPrestataire(int $user_id): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user && $user['role'] === 'prestataire';
        } catch (Exception $e) {
            error_log("ValidationService::isPrestataire() erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si une date est valide
     * 
     * @param string $date Date (YYYY-MM-DD)
     * @return bool
     */
    public function isValidDate(string $date): bool
    {
        $format = 'Y-m-d';
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Vérifie si une heure est valide
     * 
     * @param string $time Heure (HH:MM ou HH:MM:SS)
     * @return bool
     */
    public function isValidTime(string $time): bool
    {
        return preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time) === 1;
    }
}
?>
