-- ============================================================================
-- OrangeHRM BR - Banco de Horas (Time Bank)
-- Migracao 002: Tabela de saldo de banco de horas por funcionario/periodo
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ohrm_br_time_bank` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL COMMENT 'FK para hs_hr_employee.emp_number',
  `period_start` DATE NOT NULL COMMENT 'Inicio do periodo de apuracao',
  `period_end` DATE NOT NULL COMMENT 'Fim do periodo de apuracao',
  `expected_seconds` INT NOT NULL DEFAULT 0 COMMENT 'Segundos esperados (jornada contratual)',
  `worked_seconds` INT NOT NULL DEFAULT 0 COMMENT 'Segundos efetivamente trabalhados',
  `balance_seconds` INT NOT NULL DEFAULT 0 COMMENT 'Saldo (positivo=credito, negativo=debito)',
  `overtime_seconds` INT NOT NULL DEFAULT 0 COMMENT 'Total de horas extras no periodo',
  `night_bonus_seconds` INT NOT NULL DEFAULT 0 COMMENT 'Total de horas noturnas (reduzidas)',
  `status` ENUM('OPEN','CLOSED','COMPENSATED') NOT NULL DEFAULT 'OPEN',
  `closed_at` DATETIME NULL DEFAULT NULL,
  `notes` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_time_bank_employee_period` (`employee_id`, `period_start`, `period_end`),
  INDEX `idx_time_bank_status` (`status`),
  INDEX `idx_time_bank_balance` (`balance_seconds`),
  CONSTRAINT `fk_time_bank_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Banco de horas - saldo por funcionario e periodo (CLT art. 59)';
