-- com_challenges : schéma (les tables `secteurs` et `societes` du site sont lues en lecture seule, jamais créées ni modifiées ici)
-- Dates en UTC (convention Joomla). Pas de clés étrangères (convention Joomla,
-- compatibilité installation/MyISAM éventuelle) : intégrité assurée par les Tables/modèles.

CREATE TABLE IF NOT EXISTS `#__challenges_types` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(150) NOT NULL,
  `alias`       VARCHAR(190) NOT NULL,
  `description` TEXT NULL,
  `ordering`    INT NOT NULL DEFAULT 0,
  `published`   TINYINT NOT NULL DEFAULT 1,
  `created`     DATETIME NOT NULL,
  `modified`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_alias` (`alias`),
  KEY `idx_published` (`published`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- status : 0 brouillon, 1 en attente, 2 publié, 3 refusé, 4 annulé, 5 archivé
CREATE TABLE IF NOT EXISTS `#__challenges` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`                 VARCHAR(255) NOT NULL,
  `alias`                 VARCHAR(190) NOT NULL,
  `description`           MEDIUMTEXT NULL,
  `societe_id`            INT UNSIGNED NULL,       -- id de la société (table externe `societes`), lecture seule
  `organizer`             VARCHAR(255) NOT NULL,   -- nom de la société, copié à l'enregistrement
  `commune`               VARCHAR(150) NOT NULL,
  `venue`                 VARCHAR(255) NULL,
  `secteur_id`            INT UNSIGNED NOT NULL,   -- id du secteur (table externe `secteurs`, colonne `id`), lecture seule
  `type_id`               INT UNSIGNED NOT NULL,
  `start_datetime`        DATETIME NOT NULL,
  `registration_deadline` DATETIME NULL,
  `formula`               TEXT NULL,
  `conditions`            TEXT NULL,
  `fee`                   DECIMAL(8,2) NULL,
  `max_teams`             SMALLINT UNSIGNED NULL,
  -- Contact PUBLIC (affiché sur la fiche, selon ce que la société a choisi de fournir)
  `contact_name`          VARCHAR(150) NOT NULL,
  `contact_email`         VARCHAR(190) NULL,
  `contact_phone`         VARCHAR(40) NULL,
  -- Déclarant PRIVÉ (jamais affiché publiquement ; suivi et notifications)
  `declarant_name`        VARCHAR(150) NOT NULL,
  `declarant_email`       VARCHAR(190) NULL,
  `declarant_phone`       VARCHAR(40) NULL,
  -- Documents (noms générés ; nom d'origine conservé pour l'affichage uniquement)
  `poster_file`           VARCHAR(255) NULL,
  `poster_original_name`  VARCHAR(255) NULL,
  `rules_file`            VARCHAR(255) NULL,
  `rules_original_name`   VARCHAR(255) NULL,
  -- Workflow
  `status`                TINYINT NOT NULL DEFAULT 1,
  `status_reason`         TEXT NULL,
  `validated_by`          INT UNSIGNED NULL,
  `validated_at`          DATETIME NULL,
  `cancelled_at`          DATETIME NULL,
  `archived_at`           DATETIME NULL,
  -- Traçabilité
  `created`               DATETIME NOT NULL,
  `created_by`            INT UNSIGNED NULL,
  `modified`              DATETIME NULL,
  `modified_by`           INT UNSIGNED NULL,
  `privacy_accepted_at`   DATETIME NULL,
  `submission_hash`       CHAR(64) NULL,
  `submitter_ip_hash`     CHAR(64) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_alias` (`alias`),
  KEY `idx_status_start` (`status`, `start_datetime`),
  KEY `idx_secteur` (`secteur_id`),
  KEY `idx_societe` (`societe_id`),
  KEY `idx_type` (`type_id`),
  KEY `idx_submission_hash` (`submission_hash`),
  KEY `idx_submitter_ip` (`submitter_ip_hash`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Historique minimal : qui a changé quoi (dont modifications de date)
CREATE TABLE IF NOT EXISTS `#__challenges_history` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `challenge_id` INT UNSIGNED NOT NULL,
  `action`       VARCHAR(30) NOT NULL, -- submitted, edited, published, refused, cancelled, archived, date_changed
  `user_id`      INT UNSIGNED NULL,    -- NULL = déclarant public
  `changed_at`   DATETIME NOT NULL,
  `changes`      TEXT NULL,            -- JSON : {champ: [ancien, nouveau]}
  `note`         TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_challenge` (`challenge_id`, `changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Journal du récapitulatif hebdomadaire (anti-doublon : une ligne par période)
CREATE TABLE IF NOT EXISTS `#__challenges_digest_log` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `period_key`       VARCHAR(20) NOT NULL, -- ex. 2026-W43
  `run_at`           DATETIME NOT NULL,
  `recipients_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status`           VARCHAR(20) NOT NULL, -- success, partial, error
  `error`            TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_period` (`period_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
