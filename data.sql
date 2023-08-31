-- --------------------------------------------------------
-- Hôte:                         127.0.0.1
-- Version du serveur:           8.0.30 - MySQL Community Server - GPL
-- SE du serveur:                Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Listage de la structure de la base pour animeprojetelan
CREATE DATABASE IF NOT EXISTS `animeprojetelan` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `animeprojetelan`;

-- Listage de la structure de table animeprojetelan. anime
CREATE TABLE IF NOT EXISTS `anime` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_api` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.anime : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. anime_discussion
CREATE TABLE IF NOT EXISTS `anime_discussion` (
  `anime_id` int NOT NULL,
  `discussion_id` int NOT NULL,
  PRIMARY KEY (`anime_id`,`discussion_id`),
  KEY `IDX_C70F631F794BBE89` (`anime_id`),
  KEY `IDX_C70F631F1ADED311` (`discussion_id`),
  CONSTRAINT `FK_C70F631F1ADED311` FOREIGN KEY (`discussion_id`) REFERENCES `discussion` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C70F631F794BBE89` FOREIGN KEY (`anime_id`) REFERENCES `anime` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.anime_discussion : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. discussion
CREATE TABLE IF NOT EXISTS `discussion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `est_verrouiller` tinyint(1) NOT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C0B9F90FA76ED395` (`user_id`),
  CONSTRAINT `FK_C0B9F90FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.discussion : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. doctrine_migration_versions
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Listage des données de la table animeprojetelan.doctrine_migration_versions : ~0 rows (environ)
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
	('DoctrineMigrations\\Version20230830145452', '2023-08-30 14:55:20', 169);

-- Listage de la structure de table animeprojetelan. evenement
CREATE TABLE IF NOT EXISTS `evenement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_evenement` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B26681EA76ED395` (`user_id`),
  CONSTRAINT `FK_B26681EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.evenement : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. messenger_messages
CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.messenger_messages : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. personnage
CREATE TABLE IF NOT EXISTS `personnage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_api` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.personnage : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. post
CREATE TABLE IF NOT EXISTS `post` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_creation` datetime NOT NULL,
  `date_derniere_modification` datetime NOT NULL,
  `contenu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `discussion_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5A8A6C8DA76ED395` (`user_id`),
  KEY `IDX_5A8A6C8D1ADED311` (`discussion_id`),
  CONSTRAINT `FK_5A8A6C8D1ADED311` FOREIGN KEY (`discussion_id`) REFERENCES `discussion` (`id`),
  CONSTRAINT `FK_5A8A6C8DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.post : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. user
CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pseudo` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image_profil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `pays` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `est_visible` tinyint(1) NOT NULL DEFAULT '1',
  `roles` json NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint(1) NOT NULL,
  `est_banni` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.user : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. user_anime
CREATE TABLE IF NOT EXISTS `user_anime` (
  `user_id` int NOT NULL,
  `anime_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`anime_id`),
  KEY `IDX_F1C6A21AA76ED395` (`user_id`),
  KEY `IDX_F1C6A21A794BBE89` (`anime_id`),
  CONSTRAINT `FK_F1C6A21A794BBE89` FOREIGN KEY (`anime_id`) REFERENCES `anime` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F1C6A21AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.user_anime : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. user_personnage
CREATE TABLE IF NOT EXISTS `user_personnage` (
  `user_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`personnage_id`),
  KEY `IDX_CD8D8E81A76ED395` (`user_id`),
  KEY `IDX_CD8D8E815E315342` (`personnage_id`),
  CONSTRAINT `FK_CD8D8E815E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_CD8D8E81A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.user_personnage : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. user_post
CREATE TABLE IF NOT EXISTS `user_post` (
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`post_id`),
  KEY `IDX_200B2044A76ED395` (`user_id`),
  KEY `IDX_200B20444B89032C` (`post_id`),
  CONSTRAINT `FK_200B20444B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_200B2044A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.user_post : ~0 rows (environ)

-- Listage de la structure de table animeprojetelan. user_regarder_anime
CREATE TABLE IF NOT EXISTS `user_regarder_anime` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `anime_id` int NOT NULL,
  `etat` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_episode_vu` int NOT NULL,
  `date_debut_visionnage` date DEFAULT NULL,
  `date_fin_visionnage` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_57428AE9A76ED395` (`user_id`),
  KEY `IDX_57428AE9794BBE89` (`anime_id`),
  CONSTRAINT `FK_57428AE9794BBE89` FOREIGN KEY (`anime_id`) REFERENCES `anime` (`id`),
  CONSTRAINT `FK_57428AE9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table animeprojetelan.user_regarder_anime : ~0 rows (environ)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
