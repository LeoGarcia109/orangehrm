-- ============================================================================
-- OrangeHRM BR - Migracao 010: caixa de entrada (comunicados + justificativas)
-- ============================================================================
--
-- Duas conversas entre RH e funcionario, na mesma caixa de entrada do mobile:
--
--   1. Comunicado: RH -> funcionarios. Alcance pela arvore da estrutura
--      organizacional (rede inteira, uma empresa/posto, ou individual). Pode
--      exigir ciencia -- o funcionario clica "estou ciente" e isso fica
--      registrado com data, que e o que transforma "avisamos" em demonstravel.
--
--   2. Justificativa de falta: funcionario -> RH. Data ou periodo, motivo,
--      observacao e documento anexado (atestado, declaracao). O RH aprova ou
--      recusa; so a aprovada marca o dia como falta justificada.
--
-- O AFD nao e tocado: ele registra marcacoes, nao ausencias. A justificativa e
-- registro paralelo, para o espelho de ponto.
--
-- Idempotente.
-- ----------------------------------------------------------------------------

-- ----------------------------------------------------------------------------
-- 1. Comunicados
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_announcement` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `body` TEXT NOT NULL,
  `scope` ENUM('NETWORK','SUBUNIT','EMPLOYEE') NOT NULL DEFAULT 'NETWORK'
    COMMENT 'NETWORK = rede inteira; SUBUNIT = empresa/posto e tudo abaixo; EMPLOYEE = individual',
  `subunit_id` INT NULL DEFAULT NULL COMMENT 'preenchido quando scope = SUBUNIT',
  `employee_id` INT NULL DEFAULT NULL COMMENT 'preenchido quando scope = EMPLOYEE',
  `requires_ack` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL DEFAULT NULL COMMENT 'some da caixa depois desta data',
  `created_by_emp_number` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_announcement_scope` (`scope`, `subunit_id`, `employee_id`),
  INDEX `idx_announcement_published` (`published_at`),
  CONSTRAINT `fk_announcement_subunit`
    FOREIGN KEY (`subunit_id`) REFERENCES `ohrm_subunit` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_announcement_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Comunicados do RH para a rede, uma unidade ou um funcionario';

-- ----------------------------------------------------------------------------
-- 2. Recibo por funcionario: quem abriu, quem deu ciencia, e quando
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_announcement_receipt` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` INT UNSIGNED NOT NULL,
  `employee_id` INT NOT NULL,
  `read_at` DATETIME NULL DEFAULT NULL,
  `acknowledged_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_receipt_unique` (`announcement_id`, `employee_id`),
  INDEX `idx_receipt_employee` (`employee_id`),
  CONSTRAINT `fk_receipt_announcement`
    FOREIGN KEY (`announcement_id`) REFERENCES `ohrm_br_announcement` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receipt_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Leitura e ciencia de cada comunicado, por funcionario';

-- ----------------------------------------------------------------------------
-- 3. Justificativas de falta
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_absence_justification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `reason_type` ENUM('ATESTADO_MEDICO','DECLARACAO_COMPARECIMENTO','FALTA_JUSTIFICADA','OUTRO')
    NOT NULL DEFAULT 'OUTRO',
  `note` TEXT NULL DEFAULT NULL,
  `status` ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `decided_by_emp_number` INT NULL DEFAULT NULL,
  `decided_at` DATETIME NULL DEFAULT NULL,
  `decision_note` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_justification_employee` (`employee_id`, `from_date`),
  INDEX `idx_justification_status` (`status`),
  CONSTRAINT `fk_justification_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Justificativas de falta enviadas pelo funcionario';

-- ----------------------------------------------------------------------------
-- 4. Documento anexado (atestado, declaracao)
--    Blob no banco, como hs_hr_emp_attachment ja faz -- mesmo padrao de backup.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_absence_attachment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `justification_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(200) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `content` MEDIUMBLOB NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_attachment_justification` (`justification_id`),
  CONSTRAINT `fk_absence_attachment_justification`
    FOREIGN KEY (`justification_id`) REFERENCES `ohrm_br_absence_justification` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Documento que acompanha a justificativa de falta';

-- ----------------------------------------------------------------------------
-- 5. Telas e permissoes
--    brAnnouncements  - RH cria comunicados e ve quem deu ciencia (Admin)
--    brAbsences       - RH decide justificativas (Admin/Supervisor)
--    A caixa do funcionario vive dentro da tela mobile, ja registrada na 004.
-- ----------------------------------------------------------------------------
SET @module_attendance = (SELECT id FROM ohrm_module WHERE name = 'attendance');

INSERT INTO ohrm_screen (`name`, `module_id`, `action_url`)
SELECT 'BR Announcements', @module_attendance, 'brAnnouncements'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brAnnouncements'
);

INSERT INTO ohrm_screen (`name`, `module_id`, `action_url`)
SELECT 'BR Absence Justifications', @module_attendance, 'brAbsences'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brAbsences'
);

SET @screen_announcements = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brAnnouncements');
SET @screen_absences = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brAbsences');

-- Admin (1) nas duas telas
INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 1, @screen_announcements, 1, 1, 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 1 AND screen_id = @screen_announcements);

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 1, @screen_absences, 1, 1, 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 1 AND screen_id = @screen_absences);

-- Supervisor (2) decide justificativas, mas nao publica comunicado
INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 2, @screen_absences, 1, 0, 1, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 2 AND screen_id = @screen_absences);

-- ----------------------------------------------------------------------------
-- Conferencia:
--   SHOW TABLES LIKE 'ohrm_br_a%';
--   SELECT s.action_url, rs.user_role_id FROM ohrm_screen s
--     JOIN ohrm_user_role_screen rs ON rs.screen_id = s.id
--    WHERE s.action_url IN ('brAnnouncements','brAbsences');
-- ----------------------------------------------------------------------------
