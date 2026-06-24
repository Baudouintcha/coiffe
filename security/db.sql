-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 24 juin 2026 à 13:18
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de données : `cft`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnements_paiements`
--

CREATE TABLE `abonnements_paiements` (
  `id` int(10) UNSIGNED NOT NULL,
  `coiffeur_id` int(10) UNSIGNED DEFAULT NULL,
  `date_paiement` timestamp NOT NULL DEFAULT current_timestamp(),
  `montant` int(11) DEFAULT NULL,
  `expire_le` date DEFAULT NULL,
  `statut` enum('valide','expire') DEFAULT 'valide'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int(10) UNSIGNED NOT NULL,
  `rdv_id` int(10) UNSIGNED DEFAULT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `note` tinyint(3) UNSIGNED DEFAULT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `boutique`
--

CREATE TABLE `boutique` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom_produit` varchar(100) DEFAULT NULL,
  `prix` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `catalogue`
--

CREATE TABLE `catalogue` (
  `id` int(10) UNSIGNED NOT NULL,
  `coiffeur_id` int(10) UNSIGNED DEFAULT NULL,
  `nom_coiffure` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix` int(11) DEFAULT NULL,
  `prix_teinte_supp` int(11) DEFAULT 0,
  `temps_estime` int(11) DEFAULT NULL,
  `temps_teinte_supp` int(11) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `note_moyenne` decimal(3,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id_commentaire` int(11) NOT NULL,
  `id_rdv` int(10) UNSIGNED DEFAULT NULL,
  `id_client` int(11) NOT NULL,
  `id_coiffeur` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `note` int(11) DEFAULT 5,
  `type_commentaire` enum('public','plainte') NOT NULL DEFAULT 'public',
  `statut_admin` enum('en_attente','traite') NOT NULL DEFAULT 'en_attente',
  `statut_moderation` enum('en_attente','approuve','rejete') NOT NULL DEFAULT 'en_attente',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `disponibilites`
--

CREATE TABLE `disponibilites` (
  `id` int(10) UNSIGNED NOT NULL,
  `coiffeur_id` int(10) UNSIGNED DEFAULT NULL,
  `jour_semaine` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') DEFAULT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ia_sessions`
--

CREATE TABLE `ia_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `dernier_message` text DEFAULT NULL,
  `contexte_json` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id_notification` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_rdv` int(10) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `statut_lecture` enum('non_lu','lu') NOT NULL DEFAULT 'non_lu',
  `date_notification` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `plaintes`
--

CREATE TABLE `plaintes` (
  `id_plainte` int(10) UNSIGNED NOT NULL,
  `id_rendez_vous` int(10) UNSIGNED NOT NULL,
  `id_coiffeur` int(10) UNSIGNED NOT NULL,
  `id_client` int(10) UNSIGNED NOT NULL,
  `motif` text NOT NULL,
  `statut` enum('active','resolue') DEFAULT 'active',
  `date_plainte` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `portefeuilles`
--

CREATE TABLE `portefeuilles` (
  `id_portefeuille` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `solde` int(11) DEFAULT 0,
  `argent_gele` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prestations`
--

CREATE TABLE `prestations` (
  `id_prestation` int(10) UNSIGNED NOT NULL,
  `id_coiffeur` int(10) UNSIGNED NOT NULL,
  `nom_style` varchar(100) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `photo_style` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duree` int(11) DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quartiers`
--

CREATE TABLE `quartiers` (
  `id` int(11) NOT NULL,
  `nom_quartier` varchar(100) NOT NULL,
  `id_ville` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous`
--

CREATE TABLE `rendez_vous` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `coiffeur_id` int(10) UNSIGNED DEFAULT NULL,
  `coiffure_id` int(10) UNSIGNED DEFAULT NULL,
  `date_rdv` date DEFAULT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `statut_paiement` enum('en_attente','paye','echoue') DEFAULT 'en_attente',
  `transaction_id` varchar(100) DEFAULT NULL,
  `statut_rdv` enum('confirme','annule','termine') DEFAULT 'confirme',
  `adresse_prestation` text DEFAULT NULL,
  `date_demande` timestamp NOT NULL DEFAULT current_timestamp(),
  `paiement_statut` enum('non_paye','gele','paye') DEFAULT 'non_paye',
  `whatsapp_envoye` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `suppressions_comptes`
--

CREATE TABLE `suppressions_comptes` (
  `id` int(11) NOT NULL,
  `id_user_supprime` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` varchar(50) NOT NULL,
  `motif` text DEFAULT NULL,
  `supprime_par` int(11) DEFAULT NULL,
  `date_demande` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transactions_portefeuille`
--

CREATE TABLE `transactions_portefeuille` (
  `id_transaction` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_transaction` enum('depot','retrait','blocage_rdv','deblocage_rdv','gain_prestation','commission') NOT NULL,
  `montant` int(11) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transactions_site`
--

CREATE TABLE `transactions_site` (
  `id_transaction` int(10) UNSIGNED NOT NULL,
  `id_coiffeur` int(10) UNSIGNED NOT NULL,
  `montant_commission` decimal(10,2) NOT NULL,
  `montant_abonnement` decimal(10,2) NOT NULL,
  `type_transaction` varchar(50) DEFAULT 'commission_et_abonnement',
  `date_transaction` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `sexe` enum('homme','femme') NOT NULL DEFAULT 'homme',
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `role` enum('client','coiffeur','admin') DEFAULT 'client',
  `valide` int(11) DEFAULT 0,
  `ville` varchar(100) DEFAULT NULL,
  `id_quartier` int(11) DEFAULT NULL,
  `tarif_base` int(11) DEFAULT 0,
  `diplome` varchar(255) DEFAULT NULL,
  `photo_profil` varchar(255) DEFAULT 'uploads/profil/default.png',
  `gps_lat` decimal(10,8) DEFAULT NULL,
  `gps_lng` decimal(11,8) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `abonnement_status` enum('actif','inactif') DEFAULT 'inactif',
  `date_expiration_abo` date DEFAULT NULL,
  `renouvellement_auto` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `telephone_verifie` tinyint(1) DEFAULT 0,
  `code_verification` varchar(10) DEFAULT NULL,
  `portefeuille` int(11) DEFAULT 0,
  `zone` varchar(100) DEFAULT NULL,
  `solde` int(11) DEFAULT 0,
  `statut` enum('actif','banni') DEFAULT 'actif',
  `is_approved` tinyint(1) DEFAULT 0,
  `raison_ban` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `villes`
--

CREATE TABLE `villes` (
  `id` int(11) NOT NULL,
  `nom_ville` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `zones_coiffeur`
--

CREATE TABLE `zones_coiffeur` (
  `id_coiffeur` int(11) NOT NULL,
  `id_quartier` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `abonnements_paiements`
--
ALTER TABLE `abonnements_paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coiffeur_id` (`coiffeur_id`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rdv_id` (`rdv_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `boutique`
--
ALTER TABLE `boutique`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `catalogue`
--
ALTER TABLE `catalogue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coiffeur_id` (`coiffeur_id`);

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id_commentaire`),
  ADD KEY `fk_commentaires_rdv` (`id_rdv`);

--
-- Index pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coiffeur_id` (`coiffeur_id`);

--
-- Index pour la table `ia_sessions`
--
ALTER TABLE `ia_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `fk_notifications_rdv` (`id_rdv`);

--
-- Index pour la table `plaintes`
--
ALTER TABLE `plaintes`
  ADD PRIMARY KEY (`id_plainte`),
  ADD KEY `fk_plainte_rdv` (`id_rendez_vous`),
  ADD KEY `fk_plainte_coiffeur` (`id_coiffeur`),
  ADD KEY `fk_plainte_client` (`id_client`);

--
-- Index pour la table `portefeuilles`
--
ALTER TABLE `portefeuilles`
  ADD PRIMARY KEY (`id_portefeuille`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Index pour la table `prestations`
--
ALTER TABLE `prestations`
  ADD PRIMARY KEY (`id_prestation`),
  ADD KEY `id_coiffeur` (`id_coiffeur`);

--
-- Index pour la table `quartiers`
--
ALTER TABLE `quartiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ville` (`id_ville`);

--
-- Index pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `coiffeur_id` (`coiffeur_id`);

--
-- Index pour la table `suppressions_comptes`
--
ALTER TABLE `suppressions_comptes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `transactions_portefeuille`
--
ALTER TABLE `transactions_portefeuille`
  ADD PRIMARY KEY (`id_transaction`);

--
-- Index pour la table `transactions_site`
--
ALTER TABLE `transactions_site`
  ADD PRIMARY KEY (`id_transaction`),
  ADD KEY `id_coiffeur` (`id_coiffeur`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_quartier` (`id_quartier`);

--
-- Index pour la table `villes`
--
ALTER TABLE `villes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom_ville` (`nom_ville`);

--
-- Index pour la table `zones_coiffeur`
--
ALTER TABLE `zones_coiffeur`
  ADD PRIMARY KEY (`id_coiffeur`,`id_quartier`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `abonnements_paiements`
--
ALTER TABLE `abonnements_paiements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `boutique`
--
ALTER TABLE `boutique`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `catalogue`
--
ALTER TABLE `catalogue`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id_commentaire` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ia_sessions`
--
ALTER TABLE `ia_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notification` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `plaintes`
--
ALTER TABLE `plaintes`
  MODIFY `id_plainte` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `portefeuilles`
--
ALTER TABLE `portefeuilles`
  MODIFY `id_portefeuille` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `prestations`
--
ALTER TABLE `prestations`
  MODIFY `id_prestation` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quartiers`
--
ALTER TABLE `quartiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `suppressions_comptes`
--
ALTER TABLE `suppressions_comptes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `transactions_portefeuille`
--
ALTER TABLE `transactions_portefeuille`
  MODIFY `id_transaction` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `transactions_site`
--
ALTER TABLE `transactions_site`
  MODIFY `id_transaction` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `villes`
--
ALTER TABLE `villes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `abonnements_paiements`
--
ALTER TABLE `abonnements_paiements`
  ADD CONSTRAINT `abonnements_paiements_ibfk_1` FOREIGN KEY (`coiffeur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`rdv_id`) REFERENCES `rendez_vous` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `catalogue`
--
ALTER TABLE `catalogue`
  ADD CONSTRAINT `catalogue_ibfk_1` FOREIGN KEY (`coiffeur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `fk_commentaires_rdv` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD CONSTRAINT `disponibilites_ibfk_1` FOREIGN KEY (`coiffeur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_rdv` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `plaintes`
--
ALTER TABLE `plaintes`
  ADD CONSTRAINT `fk_plainte_client` FOREIGN KEY (`id_client`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_plainte_coiffeur` FOREIGN KEY (`id_coiffeur`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_plainte_rdv` FOREIGN KEY (`id_rendez_vous`) REFERENCES `rendez_vous` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `prestations`
--
ALTER TABLE `prestations`
  ADD CONSTRAINT `prestations_ibfk_1` FOREIGN KEY (`id_coiffeur`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `quartiers`
--
ALTER TABLE `quartiers`
  ADD CONSTRAINT `quartiers_ibfk_1` FOREIGN KEY (`id_ville`) REFERENCES `villes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD CONSTRAINT `rendez_vous_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `rendez_vous_ibfk_2` FOREIGN KEY (`coiffeur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `transactions_site`
--
ALTER TABLE `transactions_site`
  ADD CONSTRAINT `transactions_site_ibfk_1` FOREIGN KEY (`id_coiffeur`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_quartier` FOREIGN KEY (`id_quartier`) REFERENCES `quartiers` (`id`) ON DELETE SET NULL;
COMMIT;
