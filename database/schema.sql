-- =============================================================================
-- Techrappy SEO — Schéma de base de données
-- Version : 1.0.0
--
-- Ce fichier est fourni à titre de documentation.
-- La création effective des tables est gérée par includes/Core/Installer.php
-- via dbDelta() de WordPress, qui est idempotent (safe à rejouer).
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Table : {prefix}techrappy_seo_jobs
-- Responsabilité : stocker tous les jobs de génération (single et bulk).
-- -----------------------------------------------------------------------------

CREATE TABLE `{prefix}techrappy_seo_jobs` (
  `id`               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`           VARCHAR(36)         NOT NULL COMMENT 'UUID v4 unique du job',
  `mode`             ENUM('single','bulk') NOT NULL DEFAULT 'single' COMMENT 'Mode de génération',
  `type`             ENUM('page','post')   NOT NULL DEFAULT 'page'   COMMENT 'Type de post WordPress cible',
  `template_post_id` BIGINT(20) UNSIGNED  NOT NULL DEFAULT 0         COMMENT 'ID du post Divi utilisé comme template',
  `keyword`          TEXT                 NOT NULL                    COMMENT 'Mot-clé principal de génération',
  `city`             VARCHAR(255)         NOT NULL DEFAULT ''         COMMENT 'Ville (pour mode local)',
  `publish_status`   ENUM('draft','publish') NOT NULL DEFAULT 'draft' COMMENT 'Statut de publication WordPress',
  `wp_params`        LONGTEXT             NOT NULL DEFAULT '{}'       COMMENT 'Paramètres WP en JSON (catégorie, auteur, etc.)',
  `slug_rule`        ENUM('from_keyword','from_h1') NOT NULL DEFAULT 'from_keyword' COMMENT 'Règle de génération du slug',
  `steps_data`       LONGTEXT             NOT NULL DEFAULT '{}'       COMMENT 'Données des étapes pipeline en JSON',
  `result_data`      TEXT                 NOT NULL DEFAULT '{}'       COMMENT 'Résultat final en JSON (post_id, permalink, slug)',
  `logs`             LONGTEXT             NOT NULL DEFAULT '[]'       COMMENT 'Logs d exécution en JSON',
  `status`           ENUM('pending','running','done','error') NOT NULL DEFAULT 'pending' COMMENT 'Statut du job',
  `parent_job_id`    VARCHAR(36)          NOT NULL DEFAULT ''         COMMENT 'UUID du job parent (mode bulk)',
  `created_at`       DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_id`          (`job_id`),
  KEY        `status`          (`status`),
  KEY        `parent_job_id`   (`parent_job_id`),
  KEY        `template_post_id`(`template_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table : {prefix}techrappy_prompts
-- Responsabilité : stocker les prompts IA éditables avec versionnage.
-- -----------------------------------------------------------------------------

CREATE TABLE `{prefix}techrappy_prompts` (
  `id`              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prompt_key`      VARCHAR(100)        NOT NULL COMMENT 'Clé unique du prompt (ex: intent, plan, intro...)',
  `content`         LONGTEXT            NOT NULL COMMENT 'Contenu du template du prompt avec variables {{var}}',
  `response_format` VARCHAR(50)         NOT NULL DEFAULT 'json_object' COMMENT 'Format de réponse attendu : json_object ou text',
  `version`         INT(11) UNSIGNED    NOT NULL DEFAULT 1   COMMENT 'Numéro de version (incrémenté à chaque modification)',
  `is_active`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1   COMMENT '1 = prompt actif, 0 = désactivé',
  `updated_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by`      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0   COMMENT 'ID de l utilisateur ayant fait la dernière modification',
  PRIMARY KEY (`id`),
  UNIQUE KEY `prompt_key` (`prompt_key`),
  KEY        `is_active`  (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Notes d'implémentation
-- =============================================================================
--
-- 1. Les champs LONGTEXT (wp_params, steps_data, logs) stockent du JSON.
--    Les valeurs par défaut '{}' et '[]' garantissent un JSON valide.
--
-- 2. Le champ job_id est un UUID v4 généré par UuidGenerator::generate().
--    Il est utilisé comme identifiant public (jamais l'id auto-increment).
--
-- 3. Le champ parent_job_id lie les jobs enfants (villes) à leur job bulk parent.
--    Un job est bulk si mode='bulk' et parent_job_id=''.
--    Un job est enfant si parent_job_id est renseigné.
--
-- 4. Les prompts ont 11 clés standards définis dans DefaultPrompts::get_defaults() :
--    system, intent, plan, blocks_list, intro, block_write, conclusion_cta,
--    meta, faq, internal_links, anti_duplicate, qa.
-- =============================================================================
