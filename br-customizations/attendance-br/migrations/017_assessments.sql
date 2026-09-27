-- ============================================================================
-- OrangeHRM BR - Migracao 017: perfil comportamental (Big Five + DISC)
-- ============================================================================
--
-- Convites de inventario comportamental para candidatos (link publico de uso
-- unico, sem login) e para funcionarios (aba Provas do app). O perfil fica no
-- cadastro -- do candidato no Recrutamento nativo e, se contratado, tambem no
-- funcionario que a contratacao cria.
--
-- Inventario comportamental complementar: nao e teste psicologico (Res. CFP
-- 31/2022) nem deve ser criterio unico de decisao.
--
-- Spec: docs/superpowers/specs/2026-09-27-perfil-comportamental-design.md
-- Idempotente.
-- ----------------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ohrm_br_assessment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_type` ENUM('CANDIDATE','EMPLOYEE') NOT NULL,
  `candidate_id` INT NULL DEFAULT NULL,
  `employee_id` INT NULL DEFAULT NULL
    COMMENT 'funcionario avaliado; no convite de candidato, quem ele virou ao ser contratado',
  `vacancy_id` INT NULL DEFAULT NULL,
  `instruments` VARCHAR(40) NOT NULL COMMENT 'ex.: BIG5,DISC',
  `status` ENUM('PENDING','COMPLETED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `token_hash` CHAR(64) NULL DEFAULT NULL COMMENT 'sha256 do link; so no convite de candidato, apagado ao concluir',
  `expires_at` DATETIME NULL DEFAULT NULL,
  `consent_at` DATETIME NULL DEFAULT NULL,
  `consent_ip` VARCHAR(45) NULL DEFAULT NULL,
  `consent_user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `created_by_emp_number` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` DATETIME NULL DEFAULT NULL,
  `completed_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_assessment_token` (`token_hash`),
  INDEX `idx_assessment_candidate` (`candidate_id`),
  INDEX `idx_assessment_employee` (`employee_id`, `status`),
  CONSTRAINT `fk_assessment_candidate`
    FOREIGN KEY (`candidate_id`) REFERENCES `ohrm_job_candidate` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_vacancy`
    FOREIGN KEY (`vacancy_id`) REFERENCES `ohrm_job_vacancy` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Convites de inventario comportamental';

CREATE TABLE IF NOT EXISTS `ohrm_br_assessment_answer` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` INT UNSIGNED NOT NULL,
  `item_code` VARCHAR(12) NOT NULL,
  `value` TINYINT UNSIGNED NOT NULL,
  `answered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_assessment_answer` (`assessment_id`, `item_code`),
  CONSTRAINT `fk_assessment_answer`
    FOREIGN KEY (`assessment_id`) REFERENCES `ohrm_br_assessment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_assessment_result` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` INT UNSIGNED NOT NULL,
  `instrument` VARCHAR(10) NOT NULL,
  `version` VARCHAR(20) NOT NULL,
  `factor` CHAR(1) NOT NULL,
  `raw` SMALLINT UNSIGNED NOT NULL,
  `score` DECIMAL(4,1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_assessment_result` (`assessment_id`, `instrument`, `factor`),
  CONSTRAINT `fk_assessment_result`
    FOREIGN KEY (`assessment_id`) REFERENCES `ohrm_br_assessment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Telas (modulo recruitment) e menu: Recrutamento -> Perfis comportamentais
-- ----------------------------------------------------------------------------
SET @module_recruitment = (SELECT id FROM ohrm_module WHERE `name` = 'recruitment');

INSERT INTO ohrm_screen (`name`, `module_id`, `action_url`, `menu_configurator`)
SELECT t.name, @module_recruitment, t.url, 'OrangeHRM\\Attendance\\Menu\\AssessmentMenuConfigurator' FROM (
  SELECT 'BR Behavioral Profiles' AS name, 'brAssessments' AS url UNION ALL
  SELECT 'BR Behavioral Profile', 'brAssessmentProfile'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_screen s WHERE s.module_id = @module_recruitment AND s.action_url = t.url
);

SET @screen_assessments = (SELECT id FROM ohrm_screen WHERE module_id = @module_recruitment AND action_url = 'brAssessments');
SET @screen_profile = (SELECT id FROM ohrm_screen WHERE module_id = @module_recruitment AND action_url = 'brAssessmentProfile');

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 1, t.screen_id, 1, 1, 1, 0 FROM (
  SELECT @screen_assessments AS screen_id UNION ALL SELECT @screen_profile
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_user_role_screen x WHERE x.user_role_id = 1 AND x.screen_id = t.screen_id
);

-- Recrutamento (65): Candidatos (100), Vagas (200), Perfis comportamentais (300)
SET @menu_recruitment = (SELECT id FROM ohrm_menu_item WHERE menu_title = 'Recruitment' AND level = 1 LIMIT 1);
INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Behavioral Profiles', @screen_assessments, @menu_recruitment, 2, 300, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_assessments);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'Behavioral Profiles', 1, 'Behavioral Profiles', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'Behavioral Profiles' AND group_id = 1);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);
INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Perfis comportamentais', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.unit_id = 'Behavioral Profiles'
  AND NOT EXISTS (SELECT 1 FROM ohrm_i18n_translate t WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br);

-- ----------------------------------------------------------------------------
-- Permissoes das APIs (Papeis: 1 = Admin, 2 = ESS, 3 = Supervisor)
--   RH: AssessmentAPI (convites), AssessmentResultAPI (perfil) -- so Admin
--   Funcionario: MyAssessmentAPI (responder o proprio) -- todos
--   O candidato nao usa estas APIs: a pagina publica e autorizada pelo token.
-- ----------------------------------------------------------------------------
SET @module_attendance = (SELECT id FROM ohrm_module WHERE `name` = 'attendance');

INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT t.name, t.description, t.r, t.c, t.u, 0 FROM (
  SELECT 'apiv2_attendance_br_assessment' AS name, 'API-v2 BR - Behavioral Assessments' AS description, 1 AS r, 1 AS c, 1 AS u UNION ALL
  SELECT 'apiv2_attendance_br_assessment_result', 'API-v2 BR - Behavioral Profiles', 1, 0, 0 UNION ALL
  SELECT 'apiv2_attendance_br_my_assessment', 'API-v2 BR - My Behavioral Assessments', 1, 0, 1
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group x WHERE x.name = t.name);

SET @dg_assessment = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_assessment');
SET @dg_result = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_assessment_result');
SET @dg_mine = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_my_assessment');

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, t.dg, t.api FROM (
  SELECT @dg_assessment AS dg, 'OrangeHRM\\Attendance\\Api\\AssessmentAPI' AS api UNION ALL
  SELECT @dg_result, 'OrangeHRM\\Attendance\\Api\\AssessmentResultAPI' UNION ALL
  SELECT @dg_mine, 'OrangeHRM\\Attendance\\Api\\MyAssessmentAPI'
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_api_permission x WHERE x.api_name = t.api);

INSERT INTO ohrm_user_role_data_group (`user_role_id`, `data_group_id`, `can_read`, `can_create`, `can_update`, `can_delete`, `self`)
SELECT t.role_id, t.dg, t.r, t.c, t.u, 0, 0
FROM (
    SELECT 1 AS role_id, @dg_assessment AS dg, 1 AS r, 1 AS c, 1 AS u UNION ALL
    SELECT 1, @dg_result, 1, 0, 0 UNION ALL
    SELECT 1, @dg_mine, 1, 0, 1 UNION ALL
    SELECT 2, @dg_mine, 1, 0, 1 UNION ALL
    SELECT 3, @dg_mine, 1, 0, 1
) AS t
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_data_group x
    WHERE x.user_role_id = t.role_id AND x.data_group_id = t.dg
);
