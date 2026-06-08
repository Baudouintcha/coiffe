ENSEMBLE DES FONCTIONNALITés à implimenter 




Moteur de recherche et Profil Public : Interface permettant au client de voir les horaires saisis par le coiffeur et de cliquer sur un créneau.
Système de Réservation (Backend) : Logique de blocage d'un créneau horaire lorsqu'un client réserve, pour éviter les doublons.
Notifications / Alertes : Système pour informer le coiffeur qu'un nouveau rendez-vous a été enregistré.
Module de Double Sécurité (OTP Email) : Génération et envoi d'un code de vérification à 6 chiffres par email lors de la connexion ou d'actions sensibles, bloquant l'accès tant que le jeton (token) n'est pas validé en base de données.
Passerelle de Paiement Mobile Money / Carte via kkiapay : Finalisation de l'intégration des webhooks et scripts de callback pour valider l'activation automatique de l'abonnement du coiffeur et sécuriser les dépôts.
Module de Notification & Facturation WhatsApp : Connexion à une API de messagerie pour pousser automatiquement le reçu financier et le récapitulatif du rendez-vous directement sur le numéro WhatsApp du client dès la confirmation de la prestation.
Système de Relance Automatique à J+14 (Rétention Client) :
Mise en place d'un script automatisé sur le serveur (Tâche Cron) s'exécutant quotidiennement.
Détection automatique des rendez-vous terminés depuis exactement 2 semaines en base de données.
Envoi automatisé d'un message de courtoisie personnalisé via l'API WhatsApp pour inciter le client à rafraîchir sa coupe et générer des rendez-vous récurrents sans action manuelle du coiffeur.