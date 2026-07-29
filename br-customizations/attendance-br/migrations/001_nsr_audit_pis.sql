-- ============================================================================
-- OrangeHRM BR - Ponto Eletronico conforme Portaria SEPRT 673/2021
-- Migracao 001: NSR + Audit Trail + Retificacao + PIS/NIS
-- ============================================================================
-- Aplicar apos a instalacao do OrangeHRM 5.9+
-- Uso: mysql -u<user> -p<senha> <database> < 001_nsr_audit_pis.sql
-- ============================================================================

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

-- ----------------------------------------------------------------------------
-- 1. NSR (Numero Sequencial de Registro) na tabela de attendance
--    Cada batida recebe um numero unico e sequencial, conforme art. 23 da
--    Portaria 673/2021. O NSR e global (nao por funcionario).
-- ----------------------------------------------------------------------------
ALTER TABLE `ohrm_attendance_record`
  ADD COLUMN `nsr` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`;

-- Preenche NSR para registros existentes (ordem cronologica por id)
SET @nsr_counter = 0;
UPDATE `ohrm_attendance_record`
SET `nsr` = (@nsr_counter := @nsr_counter + 1)
ORDER BY `id` ASC;

-- Agora torna NOT NULL e UNIQUE
ALTER TABLE `ohrm_attendance_record`
  MODIFY COLUMN `nsr` BIGINT UNSIGNED NOT NULL,
  ADD UNIQUE INDEX `idx_attendance_nsr` (`nsr`);

-- ----------------------------------------------------------------------------
-- 2. PIS/NIS no cadastro do funcionario
--    Campo dedicado para o numero PIS/NIS (12 digitos), usado no AFD e
--    no e-Social. Mantemos separado de other_id para clareza legal.
-- ----------------------------------------------------------------------------
ALTER TABLE `hs_hr_employee`
  ADD COLUMN `pis_number` VARCHAR(12) NULL DEFAULT NULL AFTER `other_id`;

ALTER TABLE `hs_hr_employee`
  ADD INDEX `idx_employee_pis` (`pis_number`);

-- ----------------------------------------------------------------------------
-- 3. Audit Trail de alteracoes em registros de ponto
--    Toda modificacao (edicao, exclusao logica) gera uma entrada aqui.
--    O registro original NUNCA e alterado diretamente.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_attendance_audit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attendance_record_id` INT NOT NULL COMMENT 'FK para ohrm_attendance_record.id',
  `employee_id` INT NOT NULL COMMENT 'FK para hs_hr_employee.emp_number',
  `action` ENUM('CREATE','UPDATE','DELETE','RECTIFY') NOT NULL,
  `field_name` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Campo alterado (para UPDATE)',
  `old_value` TEXT NULL DEFAULT NULL,
  `new_value` TEXT NULL DEFAULT NULL,
  `changed_by_user_id` INT NULL DEFAULT NULL COMMENT 'FK para ohrm_user.id',
  `changed_by_emp_number` INT NULL DEFAULT NULL COMMENT 'FK para hs_hr_employee.emp_number',
  `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `note` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Justificativa da alteracao',
  PRIMARY KEY (`id`),
  INDEX `idx_audit_record` (`attendance_record_id`),
  INDEX `idx_audit_employee` (`employee_id`),
  INDEX `idx_audit_changed_at` (`changed_at`),
  INDEX `idx_audit_action` (`action`),
  CONSTRAINT `fk_audit_attendance_record`
    FOREIGN KEY (`attendance_record_id`) REFERENCES `ohrm_attendance_record` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Trilha de auditoria para registros de ponto (Portaria 673/2021)';

-- ----------------------------------------------------------------------------
-- 4. Retificacoes de ponto
--    Quando um registro precisa ser corrigido, o original permanece intocado
--    e uma retificacao e criada referenciando-o. O campo is_rectified no
--    registro original marca que existe uma correcao aplicada.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_attendance_retification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `original_record_id` INT NOT NULL COMMENT 'FK para o registro original',
  `employee_id` INT NOT NULL,
  `rectified_punch_in_utc_time` DATETIME NULL DEFAULT NULL,
  `rectified_punch_in_note` VARCHAR(255) NULL DEFAULT NULL,
  `rectified_punch_in_time_offset` VARCHAR(255) NULL DEFAULT NULL,
  `rectified_punch_in_timezone_name` VARCHAR(100) NULL DEFAULT NULL,
  `rectified_punch_in_user_time` DATETIME NULL DEFAULT NULL,
  `rectified_punch_out_utc_time` DATETIME NULL DEFAULT NULL,
  `rectified_punch_out_note` VARCHAR(255) NULL DEFAULT NULL,
  `rectified_punch_out_time_offset` VARCHAR(255) NULL DEFAULT NULL,
  `rectified_punch_out_timezone_name` VARCHAR(100) NULL DEFAULT NULL,
  `rectified_punch_out_user_time` DATETIME NULL DEFAULT NULL,
  `reason` VARCHAR(500) NOT NULL COMMENT 'Motivo da retificacao (obrigatorio)',
  `requested_by_emp_number` INT NOT NULL COMMENT 'Quem solicitou',
  `approved_by_emp_number` INT NULL DEFAULT NULL COMMENT 'Quem aprovou (se aplicavel)',
  `status` ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decided_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_rect_original` (`original_record_id`),
  INDEX `idx_rect_employee` (`employee_id`),
  INDEX `idx_rect_status` (`status`),
  CONSTRAINT `fk_rect_original_record`
    FOREIGN KEY (`original_record_id`) REFERENCES `ohrm_attendance_record` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rect_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Retificacoes de registros de ponto (original permanece inalterado)';

-- ----------------------------------------------------------------------------
-- 5. Flag no registro original indicando que foi retificado
-- ----------------------------------------------------------------------------
ALTER TABLE `ohrm_attendance_record`
  ADD COLUMN `is_rectified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `state`;

-- ----------------------------------------------------------------------------
-- 6. Campos de geolocalizacao (opcional, Fase 3, mas criamos agora)
-- ----------------------------------------------------------------------------
ALTER TABLE `ohrm_attendance_record`
  ADD COLUMN `punch_in_latitude` DECIMAL(10, 8) NULL DEFAULT NULL AFTER `punch_in_user_time`,
  ADD COLUMN `punch_in_longitude` DECIMAL(11, 8) NULL DEFAULT NULL AFTER `punch_in_latitude`,
  ADD COLUMN `punch_out_latitude` DECIMAL(10, 8) NULL DEFAULT NULL AFTER `punch_out_user_time`,
  ADD COLUMN `punch_out_longitude` DECIMAL(11, 8) NULL DEFAULT NULL AFTER `punch_out_latitude`;

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

-- ============================================================================
-- FIM DA MIGRACAO 001
-- ============================================================================
