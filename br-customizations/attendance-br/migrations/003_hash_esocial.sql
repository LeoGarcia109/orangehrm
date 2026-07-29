-- ============================================================================
-- OrangeHRM BR - Fase 3: Assinatura Digital + e-Social
-- Migracao 003: Hash SHA-256 nos registros + campos para e-Social
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Hash de integridade em cada registro de ponto
--    Garante inviolabilidade: qualquer alteracao no registro invalida o hash.
--    O hash e calculado sobre: NSR + emp_number + punch_in_utc + punch_out_utc + state
-- ----------------------------------------------------------------------------
ALTER TABLE `ohrm_attendance_record`
  ADD COLUMN `record_hash` VARCHAR(64) NULL DEFAULT NULL AFTER `is_rectified`,
  ADD COLUMN `hash_created_at` DATETIME NULL DEFAULT NULL AFTER `record_hash`;

ALTER TABLE `ohrm_attendance_record`
  ADD INDEX `idx_attendance_hash` (`record_hash`);

-- ----------------------------------------------------------------------------
-- 2. Tabela de eventos e-Social gerados
--    Registra cada evento XML gerado para envio ao e-Social.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_esocial_event` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `event_type` VARCHAR(10) NOT NULL COMMENT 'S-1200, S-1210, S-2200, etc.',
  `reference_period` VARCHAR(7) NOT NULL COMMENT 'Periodo de referencia (AAAA-MM)',
  `xml_content` LONGTEXT NOT NULL COMMENT 'XML do evento gerado',
  `receipt_number` VARCHAR(40) NULL DEFAULT NULL COMMENT 'Numero do recibo (apos envio)',
  `status` ENUM('DRAFT','READY','SENT','ACKNOWLEDGED','ERROR') NOT NULL DEFAULT 'DRAFT',
  `sent_at` DATETIME NULL DEFAULT NULL,
  `acknowledged_at` DATETIME NULL DEFAULT NULL,
  `error_message` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_esocial_employee` (`employee_id`),
  INDEX `idx_esocial_type_period` (`event_type`, `reference_period`),
  INDEX `idx_esocial_status` (`status`),
  CONSTRAINT `fk_esocial_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Eventos e-Social gerados para envio';

-- ----------------------------------------------------------------------------
-- 3. Configuracao do e-Social por empresa
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ohrm_br_esocial_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_tax_id` VARCHAR(14) NOT NULL COMMENT 'CNPJ da empresa',
  `environment` ENUM('PRODUCTION','RESTRICTED') NOT NULL DEFAULT 'RESTRICTED',
  `tp_amb` TINYINT NOT NULL DEFAULT 2 COMMENT '1=Producao, 2=Producao Restrita',
  `tp_emis` TINYINT NOT NULL DEFAULT 1 COMMENT 'Tipo de emissao (1=normal)',
  `ind_apuracao` TINYINT NOT NULL DEFAULT 1 COMMENT '1=Mensal, 2=Decendial',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_esocial_config_cnpj` (`company_tax_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuracao e-Social da empresa';
