-- ============================================================================
-- OrangeHRM BR - Migracao 008: batida por terceiro na trilha de auditoria
-- ============================================================================
--
-- Batida registrada por admin/supervisor em nome de outro funcionario nao pode
-- ser validada pelo geofence: as coordenadas sao de quem opera a tela, nao do
-- trabalhador. Em empresa que exige geofence essa batida passa a precisar de
-- justificativa, e a justificativa vira uma linha propria na trilha, para o
-- desvio ser consultavel e nao apenas dedutivel de changed_by <> employee_id.
--
-- Idempotente: reaplicar apenas reescreve o mesmo ENUM.
-- ----------------------------------------------------------------------------

ALTER TABLE `ohrm_attendance_audit_log`
  MODIFY COLUMN `action`
    ENUM('CREATE','UPDATE','DELETE','RECTIFY','PROXY_PUNCH') NOT NULL;

-- Conferencia:
-- SHOW COLUMNS FROM ohrm_attendance_audit_log LIKE 'action';
