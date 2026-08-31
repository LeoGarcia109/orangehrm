-- ============================================================================
-- OrangeHRM BR - Migracao 009: fila offline de ponto
-- ============================================================================
--
-- Batida sem sinal e, por definicao, retroativa: so o aparelho a presenciou.
-- O punch proprio normalmente e preso ao relogio do servidor com margem de
-- 180 segundos (MyAttendanceRecordAPI::isCurrantDateTimeValid), que e o que
-- impede o funcionario de escolher o proprio horario. Aceitar a fila offline
-- afrouxa esse controle.
--
-- O afrouxamento e limitado e opcional:
--   * o empregador liga a chave abaixo;
--   * a janela diz ate quando a batida pode ser retroativa;
--   * toda batida que chega por essa via fica marcada na trilha
--     (action = 'OFFLINE_SYNC').
--
-- Passada a janela, deixa de ser sincronizacao e vira correcao -- que pertence
-- ao fluxo de retificacao, com pedido e aprovacao.
--
-- Idempotente.
-- ----------------------------------------------------------------------------

-- 1. Chaves de configuracao
INSERT INTO `hs_hr_config` (`name`, `value`)
SELECT 'attendance.br.offline_punch.enabled', 'true'
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `name` FROM `hs_hr_config`) AS c
    WHERE c.`name` = 'attendance.br.offline_punch.enabled'
);

INSERT INTO `hs_hr_config` (`name`, `value`)
SELECT 'attendance.br.offline_punch.max_hours', '24'
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `name` FROM `hs_hr_config`) AS c
    WHERE c.`name` = 'attendance.br.offline_punch.max_hours'
);

-- 2. Marca na trilha de auditoria
ALTER TABLE `ohrm_attendance_audit_log`
  MODIFY COLUMN `action`
    ENUM('CREATE','UPDATE','DELETE','RECTIFY','PROXY_PUNCH','OFFLINE_SYNC') NOT NULL;

-- Conferencia:
-- SELECT name, value FROM hs_hr_config WHERE name LIKE 'attendance.br.offline_punch%';
-- SHOW COLUMNS FROM ohrm_attendance_audit_log LIKE 'action';
