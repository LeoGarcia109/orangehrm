-- ============================================================================
-- OrangeHRM BR - Migracao 018: perfil do cargo e comparacao de aderencia
-- ============================================================================
--
-- O perfil ideal de cada cargo nativo (ohrm_job_title): faixa e importancia
-- de cada fator do Big Five e do DISC, as competencias do cargo e as notas que
-- o RH da a cada pessoa nelas. A aderencia nao e gravada: e calculada na hora
-- a partir do teste de perfil concluido mais recente de cada pessoa.
--
-- Spec: docs/superpowers/specs/2026-09-27-aderencia-cargo-design.md
-- Idempotente.
-- ----------------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ohrm_br_job_profile` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_title_id` INT NOT NULL,
  `behavior_weight` TINYINT UNSIGNED NOT NULL DEFAULT 50
    COMMENT '% da aderencia que vem do perfil comportamental; o resto vem das competencias',
  `updated_by_emp_number` INT NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_job_profile_title` (`job_title_id`),
  CONSTRAINT `fk_job_profile_title`
    FOREIGN KEY (`job_title_id`) REFERENCES `ohrm_job_title` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Perfil ideal do cargo';

CREATE TABLE IF NOT EXISTS `ohrm_br_job_profile_factor` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_profile_id` INT UNSIGNED NOT NULL,
  `instrument` VARCHAR(10) NOT NULL,
  `factor` CHAR(1) NOT NULL,
  `min_score` TINYINT UNSIGNED NOT NULL,
  `max_score` TINYINT UNSIGNED NOT NULL,
  `weight` TINYINT UNSIGNED NOT NULL COMMENT '0 ignorar, 1 desejavel, 2 essencial',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_job_profile_factor` (`job_profile_id`, `instrument`, `factor`),
  CONSTRAINT `fk_job_profile_factor`
    FOREIGN KEY (`job_profile_id`) REFERENCES `ohrm_br_job_profile` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_job_competency` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_profile_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `weight` TINYINT UNSIGNED NOT NULL COMMENT '1 desejavel, 2 essencial',
  `min_level` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_job_competency_profile` (`job_profile_id`, `sort_order`),
  CONSTRAINT `fk_job_competency_profile`
    FOREIGN KEY (`job_profile_id`) REFERENCES `ohrm_br_job_profile` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_competency_rating` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `competency_id` INT UNSIGNED NOT NULL,
  `candidate_id` INT NULL DEFAULT NULL,
  `employee_id` INT NULL DEFAULT NULL
    COMMENT 'funcionario avaliado; na nota de candidato, quem ele virou ao ser contratado',
  `rating` TINYINT UNSIGNED NOT NULL COMMENT '1 a 5',
  `rated_by_emp_number` INT NULL DEFAULT NULL,
  `rated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_rating_candidate` (`competency_id`, `candidate_id`),
  UNIQUE INDEX `idx_rating_employee` (`competency_id`, `employee_id`),
  CONSTRAINT `fk_rating_competency`
    FOREIGN KEY (`competency_id`) REFERENCES `ohrm_br_job_competency` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_candidate`
    FOREIGN KEY (`candidate_id`) REFERENCES `ohrm_job_candidate` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nota do RH (1 a 5) de uma pessoa numa competencia do cargo';

-- ----------------------------------------------------------------------------
-- Telas (modulo recruitment) e menu: Recrutamento -> Perfis de cargo,
-- Comparar perfis
-- ----------------------------------------------------------------------------
SET @module_recruitment = (SELECT id FROM ohrm_module WHERE `name` = 'recruitment');

INSERT INTO ohrm_screen (`name`, `module_id`, `action_url`, `menu_configurator`)
SELECT t.name, @module_recruitment, t.url, 'OrangeHRM\\Attendance\\Menu\\AssessmentMenuConfigurator' FROM (
  SELECT 'BR Job Profiles' AS name, 'brJobProfiles' AS url UNION ALL
  SELECT 'BR Job Profile', 'brJobProfile' UNION ALL
  SELECT 'BR Compare Profiles', 'brProfileCompare'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_screen s WHERE s.module_id = @module_recruitment AND s.action_url = t.url
);

SET @screen_job_profiles = (SELECT id FROM ohrm_screen WHERE module_id = @module_recruitment AND action_url = 'brJobProfiles');
SET @screen_job_profile = (SELECT id FROM ohrm_screen WHERE module_id = @module_recruitment AND action_url = 'brJobProfile');
SET @screen_compare = (SELECT id FROM ohrm_screen WHERE module_id = @module_recruitment AND action_url = 'brProfileCompare');

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 1, t.screen_id, 1, 1, 1, 0 FROM (
  SELECT @screen_job_profiles AS screen_id UNION ALL SELECT @screen_job_profile UNION ALL SELECT @screen_compare
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_user_role_screen x WHERE x.user_role_id = 1 AND x.screen_id = t.screen_id
);

-- Recrutamento (65): Candidatos (100), Vagas (200), Perfis comportamentais (300),
-- Perfis de cargo (400), Comparar perfis (500)
SET @menu_recruitment = (SELECT id FROM ohrm_menu_item WHERE menu_title = 'Recruitment' AND level = 1 LIMIT 1);
INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT t.title, t.screen_id, @menu_recruitment, 2, t.order_hint, 1 FROM (
  SELECT 'Job Profiles' AS title, @screen_job_profiles AS screen_id, 400 AS order_hint UNION ALL
  SELECT 'Compare Profiles', @screen_compare, 500
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_menu_item m WHERE m.screen_id = t.screen_id);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT t.v, 1, t.v, NULL FROM (
  SELECT 'Job Profiles' AS v UNION ALL SELECT 'Compare Profiles'
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string x WHERE x.unit_id = t.v AND x.group_id = 1);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);
INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id WHEN 'Job Profiles' THEN 'Perfis de cargo' ELSE 'Comparar perfis' END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.unit_id IN ('Job Profiles', 'Compare Profiles')
  AND NOT EXISTS (SELECT 1 FROM ohrm_i18n_translate t WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br);

-- ----------------------------------------------------------------------------
-- Permissoes das APIs -- so Admin (papel 1)
-- ----------------------------------------------------------------------------
SET @module_attendance = (SELECT id FROM ohrm_module WHERE `name` = 'attendance');

INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT t.name, t.description, 1, 0, t.u, 0 FROM (
  SELECT 'apiv2_attendance_br_job_profile' AS name, 'API-v2 BR - Job Profiles' AS description, 1 AS u UNION ALL
  SELECT 'apiv2_attendance_br_job_profile_suggestion', 'API-v2 BR - Job Profile Suggestion', 0 UNION ALL
  SELECT 'apiv2_attendance_br_profile_people', 'API-v2 BR - Profile People', 0 UNION ALL
  SELECT 'apiv2_attendance_br_profile_comparison', 'API-v2 BR - Profile Comparison', 0 UNION ALL
  SELECT 'apiv2_attendance_br_competency_rating', 'API-v2 BR - Competency Ratings', 1
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group x WHERE x.name = t.name);

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, dg.id, t.api FROM (
  SELECT 'apiv2_attendance_br_job_profile' AS dg_name, 'OrangeHRM\\Attendance\\Api\\JobProfileAPI' AS api UNION ALL
  SELECT 'apiv2_attendance_br_job_profile_suggestion', 'OrangeHRM\\Attendance\\Api\\JobProfileSuggestionAPI' UNION ALL
  SELECT 'apiv2_attendance_br_profile_people', 'OrangeHRM\\Attendance\\Api\\ProfilePeopleAPI' UNION ALL
  SELECT 'apiv2_attendance_br_profile_comparison', 'OrangeHRM\\Attendance\\Api\\ProfileComparisonAPI' UNION ALL
  SELECT 'apiv2_attendance_br_competency_rating', 'OrangeHRM\\Attendance\\Api\\CompetencyRatingAPI'
) AS t
JOIN ohrm_data_group dg ON dg.name = t.dg_name
WHERE NOT EXISTS (SELECT 1 FROM ohrm_api_permission x WHERE x.api_name = t.api);

INSERT INTO ohrm_user_role_data_group (`user_role_id`, `data_group_id`, `can_read`, `can_create`, `can_update`, `can_delete`, `self`)
SELECT 1, dg.id, 1, 0, dg.can_update, 0, 0
FROM ohrm_data_group dg
WHERE dg.name IN (
    'apiv2_attendance_br_job_profile',
    'apiv2_attendance_br_job_profile_suggestion',
    'apiv2_attendance_br_profile_people',
    'apiv2_attendance_br_profile_comparison',
    'apiv2_attendance_br_competency_rating'
  )
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_data_group x WHERE x.user_role_id = 1 AND x.data_group_id = dg.id
  );
