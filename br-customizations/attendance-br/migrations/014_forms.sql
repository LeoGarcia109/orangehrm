-- ============================================================================
-- OrangeHRM BR - Migracao 014: formularios (provas e pesquisas)
-- ============================================================================
--
-- O RH monta provas e pesquisas campo a campo e envia para uma pessoa, uma
-- empresa/posto ou a rede inteira -- o mesmo publico dos Avisos. O funcionario
-- responde pelo mobile ou pelo desktop.
--
--   Prova    (QUIZ)   gabarito, nota automatica, nota minima.
--   Pesquisa (SURVEY) sem certo ou errado; pode ser anonima.
--
-- Anonimato: "quem respondeu" (form_completion) e "o que respondeu"
-- (form_submission) ficam em tabelas separadas. Na pesquisa anonima o envio e
-- gravado sem funcionario, sem tentativa e sem horario, com id aleatorio --
-- id sequencial ou horario deixariam cruzar as duas tabelas.
--
-- Publicar trava o formulario (as notas sao dadas contra ele); para mudar,
-- o RH duplica.
--
-- Spec: docs/superpowers/specs/2026-09-27-formularios-design.md
--
-- Idempotente.
-- ----------------------------------------------------------------------------

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1. Formulario
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_form` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `kind` ENUM('QUIZ','SURVEY') NOT NULL,
  `anonymous` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'so em SURVEY',
  `pass_percent` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT '0-100; obrigatorio em QUIZ',
  `scope` ENUM('NETWORK','SUBUNIT','EMPLOYEE') NOT NULL DEFAULT 'NETWORK',
  `subunit_id` INT NULL DEFAULT NULL,
  `employee_id` INT NULL DEFAULT NULL,
  `due_at` DATETIME NULL DEFAULT NULL COMMENT 'depois dele nenhum envio e aceito',
  `status` ENUM('DRAFT','PUBLISHED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  `is_template` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'modelo pronto: nunca publicado, so copiado',
  `published_at` DATETIME NULL DEFAULT NULL,
  `closed_at` DATETIME NULL DEFAULT NULL,
  `created_by_emp_number` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_form_status` (`status`, `is_template`),
  CONSTRAINT `fk_form_subunit`
    FOREIGN KEY (`subunit_id`) REFERENCES `ohrm_subunit` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_form_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Provas e pesquisas para os funcionarios';

-- ----------------------------------------------------------------------------
-- 2. Imagens das questoes (no banco, como o atestado)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_form_image` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(200) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `content` MEDIUMBLOB NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_form_image_form`
    FOREIGN KEY (`form_id`) REFERENCES `ohrm_br_form` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Blocos (questoes e conteudo), em ordem
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_form_item` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `position` SMALLINT UNSIGNED NOT NULL,
  `type` ENUM('CONTENT','SINGLE','MULTIPLE','SHORT_TEXT','LONG_TEXT','SCALE','YES_NO') NOT NULL,
  `prompt` TEXT NOT NULL,
  `help_text` TEXT NULL DEFAULT NULL,
  `required` TINYINT(1) NOT NULL DEFAULT 0,
  `points` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `image_id` INT UNSIGNED NULL DEFAULT NULL,
  `youtube_id` CHAR(11) NULL DEFAULT NULL COMMENT 'so o id validado, nunca a URL',
  `correct_yes_no` TINYINT(1) NULL DEFAULT NULL COMMENT 'gabarito do YES_NO numa prova',
  PRIMARY KEY (`id`),
  INDEX `idx_form_item_form` (`form_id`, `position`),
  CONSTRAINT `fk_form_item_form`
    FOREIGN KEY (`form_id`) REFERENCES `ohrm_br_form` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_item_image`
    FOREIGN KEY (`image_id`) REFERENCES `ohrm_br_form_image` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_form_option` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT UNSIGNED NOT NULL,
  `position` SMALLINT UNSIGNED NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_form_option_item` (`item_id`, `position`),
  CONSTRAINT `fk_form_option_item`
    FOREIGN KEY (`item_id`) REFERENCES `ohrm_br_form_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Quem respondeu (sempre) e o que respondeu (sem nome, na anonima)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_form_completion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `employee_id` INT NOT NULL,
  `attempt` TINYINT UNSIGNED NOT NULL,
  `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_form_completion_unique` (`form_id`, `employee_id`, `attempt`),
  INDEX `idx_form_completion_employee` (`employee_id`),
  CONSTRAINT `fk_form_completion_form`
    FOREIGN KEY (`form_id`) REFERENCES `ohrm_br_form` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_completion_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_form_submission` (
  `id` CHAR(32) NOT NULL COMMENT 'aleatorio, nunca sequencial',
  `form_id` INT UNSIGNED NOT NULL,
  `employee_id` INT NULL DEFAULT NULL COMMENT 'NULL na pesquisa anonima',
  `attempt` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL na pesquisa anonima',
  `submitted_at` DATETIME NULL DEFAULT NULL COMMENT 'NULL na pesquisa anonima',
  `score_points` DECIMAL(6,2) NULL DEFAULT NULL,
  `max_points` DECIMAL(6,2) NULL DEFAULT NULL,
  `status` ENUM('GRADED','PENDING_REVIEW','RECORDED') NOT NULL,
  `reviewed_by_emp_number` INT NULL DEFAULT NULL,
  `reviewed_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_form_submission_form` (`form_id`, `employee_id`),
  CONSTRAINT `fk_form_submission_form`
    FOREIGN KEY (`form_id`) REFERENCES `ohrm_br_form` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_submission_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_form_answer` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` CHAR(32) NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NULL DEFAULT NULL,
  `text_value` TEXT NULL DEFAULT NULL,
  `scale_value` TINYINT UNSIGNED NULL DEFAULT NULL,
  `yes_no_value` TINYINT(1) NULL DEFAULT NULL,
  `points_awarded` DECIMAL(5,2) NULL DEFAULT NULL
    COMMENT 'NULL = aguardando correcao; na multipla, so na primeira linha da questao',
  PRIMARY KEY (`id`),
  INDEX `idx_form_answer_submission` (`submission_id`),
  INDEX `idx_form_answer_item` (`item_id`),
  CONSTRAINT `fk_form_answer_submission`
    FOREIGN KEY (`submission_id`) REFERENCES `ohrm_br_form_submission` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_answer_item`
    FOREIGN KEY (`item_id`) REFERENCES `ohrm_br_form_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_answer_option`
    FOREIGN KEY (`option_id`) REFERENCES `ohrm_br_form_option` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nova tentativa liberada pelo RH. Tentativas permitidas = 1 + linhas aqui.
CREATE TABLE IF NOT EXISTS `ohrm_br_form_retake` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `employee_id` INT NOT NULL,
  `granted_by_emp_number` INT NULL DEFAULT NULL,
  `granted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_form_retake` (`form_id`, `employee_id`),
  CONSTRAINT `fk_form_retake_form`
    FOREIGN KEY (`form_id`) REFERENCES `ohrm_br_form` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_retake_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Aviso ligado ao formulario (botao "Responder")
-- ----------------------------------------------------------------------------
SET @has_form_id = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ohrm_br_announcement' AND column_name = 'form_id'
);
SET @ddl = IF(@has_form_id = 0,
  'ALTER TABLE ohrm_br_announcement
     ADD COLUMN form_id INT UNSIGNED NULL DEFAULT NULL,
     ADD CONSTRAINT fk_announcement_form FOREIGN KEY (form_id) REFERENCES ohrm_br_form (id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 6. Telas e menu
--    brForms, brFormBuilder, brFormResults: so o Admin.
--    brMyForms: todo funcionario (ESS), mais Admin e Supervisor.
-- ----------------------------------------------------------------------------
SET @module_attendance = (SELECT id FROM ohrm_module WHERE `name` = 'attendance');

INSERT INTO ohrm_screen (`name`, `module_id`, `action_url`)
SELECT t.name, @module_attendance, t.url FROM (
  SELECT 'BR Forms' AS name, 'brForms' AS url UNION ALL
  SELECT 'BR Form Builder', 'brFormBuilder' UNION ALL
  SELECT 'BR Form Results', 'brFormResults' UNION ALL
  SELECT 'BR My Forms', 'brMyForms'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_screen s WHERE s.module_id = @module_attendance AND s.action_url = t.url
);

SET @screen_forms = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brForms');
SET @screen_builder = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brFormBuilder');
SET @screen_results = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brFormResults');
SET @screen_my_forms = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brMyForms');

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT t.role_id, t.screen_id, t.r, t.c, t.u, t.d FROM (
  SELECT 1 AS role_id, @screen_forms AS screen_id, 1 AS r, 1 AS c, 1 AS u, 1 AS d UNION ALL
  SELECT 1, @screen_builder, 1, 1, 1, 1 UNION ALL
  SELECT 1, @screen_results, 1, 1, 1, 0 UNION ALL
  SELECT 1, @screen_my_forms, 1, 0, 0, 0 UNION ALL
  SELECT 2, @screen_my_forms, 1, 0, 0, 0 UNION ALL
  SELECT 3, @screen_my_forms, 1, 0, 0, 0
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_user_role_screen x WHERE x.user_role_id = t.role_id AND x.screen_id = t.screen_id
);

-- Menu do modulo Ponto (parent 56): "Meus formularios" logo depois de
-- "My Records" (100); "Formularios" do RH depois da Folha (900).
INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'My Forms', @screen_my_forms, 56, 3, 150, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_my_forms);

INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Forms', @screen_forms, 56, 3, 1000, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_forms);

-- Titulos do menu (grupo 1 = general, onde o menu lateral busca) e pt_BR
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT t.unit_id, 1, t.unit_id, NULL FROM (
  SELECT 'Forms' AS unit_id UNION ALL SELECT 'My Forms'
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string x WHERE x.unit_id = t.unit_id AND x.group_id = 1);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id WHEN 'Forms' THEN 'Formulários' WHEN 'My Forms' THEN 'Meus formulários' END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.unit_id IN ('Forms', 'My Forms')
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ----------------------------------------------------------------------------
-- 7. Permissoes das APIs (sem elas, 403 para todos -- ver migracao 013)
--    Papeis: 1 = Admin, 2 = ESS (todo funcionario), 3 = Supervisor.
--    A imagem que o funcionario ve nao passa por API: e servida por
--    /attendance/brFormImage/{id}, com checagem propria de publico.
-- ----------------------------------------------------------------------------
INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT t.name, t.description, t.r, t.c, t.u, 0 FROM (
  SELECT 'apiv2_attendance_br_form' AS name, 'API-v2 Attendance BR - Forms' AS description, 1 AS r, 1 AS c, 1 AS u UNION ALL
  SELECT 'apiv2_attendance_br_form_image', 'API-v2 Attendance BR - Form Images', 0, 1, 0 UNION ALL
  SELECT 'apiv2_attendance_br_my_form', 'API-v2 Attendance BR - My Forms', 1, 0, 0 UNION ALL
  SELECT 'apiv2_attendance_br_form_submission', 'API-v2 Attendance BR - Form Submissions', 0, 1, 0 UNION ALL
  SELECT 'apiv2_attendance_br_form_result', 'API-v2 Attendance BR - Form Results', 1, 1, 1
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group x WHERE x.name = t.name);

SET @dg_form = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_form');
SET @dg_image = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_form_image');
SET @dg_my_form = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_my_form');
SET @dg_submission = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_form_submission');
SET @dg_result = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_form_result');

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, t.dg, t.api FROM (
  SELECT @dg_form AS dg, 'OrangeHRM\\Attendance\\Api\\FormAPI' AS api UNION ALL
  SELECT @dg_image, 'OrangeHRM\\Attendance\\Api\\FormImageAPI' UNION ALL
  SELECT @dg_my_form, 'OrangeHRM\\Attendance\\Api\\MyFormAPI' UNION ALL
  SELECT @dg_submission, 'OrangeHRM\\Attendance\\Api\\FormSubmissionAPI' UNION ALL
  SELECT @dg_result, 'OrangeHRM\\Attendance\\Api\\FormResultAPI'
) AS t
WHERE NOT EXISTS (SELECT 1 FROM ohrm_api_permission x WHERE x.api_name = t.api);

INSERT INTO ohrm_user_role_data_group (`user_role_id`, `data_group_id`, `can_read`, `can_create`, `can_update`, `can_delete`, `self`)
SELECT t.role_id, t.dg, t.r, t.c, t.u, 0, 0
FROM (
    -- Montar, publicar, encerrar, duplicar: so o Admin
    SELECT 1 AS role_id, @dg_form AS dg, 1 AS r, 1 AS c, 1 AS u UNION ALL
    SELECT 1, @dg_image, 0, 1, 0 UNION ALL
    -- Responder: todos
    SELECT 1, @dg_my_form, 1, 0, 0 UNION ALL
    SELECT 2, @dg_my_form, 1, 0, 0 UNION ALL
    SELECT 3, @dg_my_form, 1, 0, 0 UNION ALL
    SELECT 1, @dg_submission, 0, 1, 0 UNION ALL
    SELECT 2, @dg_submission, 0, 1, 0 UNION ALL
    SELECT 3, @dg_submission, 0, 1, 0 UNION ALL
    -- Resultados, correcao e nova tentativa: so o Admin
    SELECT 1, @dg_result, 1, 1, 1
) AS t
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_data_group x
    WHERE x.user_role_id = t.role_id AND x.data_group_id = t.dg
);

-- ----------------------------------------------------------------------------
-- Conferencia:
--   SHOW TABLES LIKE 'ohrm_br_form%';
--   SELECT ap.api_name, g.user_role_id, g.can_read, g.can_create, g.can_update
--     FROM ohrm_api_permission ap
--     JOIN ohrm_user_role_data_group g ON g.data_group_id = ap.data_group_id
--    WHERE ap.api_name LIKE '%Form%';
-- ----------------------------------------------------------------------------
