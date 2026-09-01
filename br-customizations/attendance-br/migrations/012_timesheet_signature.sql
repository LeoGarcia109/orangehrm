-- ============================================================================
-- OrangeHRM BR - Migracao 012: assinatura mensal da folha de ponto
-- ============================================================================
--
-- Ao fim do mes o funcionario assina a propria folha, confirmando a senha.
-- A assinatura serve como prova em reclamatoria, entao precisa dizer mais que
-- "alguem apertou um botao": ela e um HMAC sobre os hashes dos registros do
-- mes, na ordem do NSR. Batida alterada, incluida, removida ou reordenada
-- depois quebra a assinatura -- uma assinatura que sobrevivesse a edicao da
-- folha nao provaria nada sobre o que foi acordado.
--
-- Usa o mesmo segredo dos registros (attendance.br.signature_secret, 007).
-- Trocar o segredo invalida tambem as folhas assinadas.
--
-- Idempotente.
-- ----------------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ohrm_br_timesheet_signature` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `reference_month` CHAR(7) NOT NULL COMMENT 'AAAA-MM',
  `signed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `signature_hash` VARCHAR(64) NOT NULL COMMENT 'HMAC sobre os hashes dos registros do mes',
  `record_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'total trabalhado no mes, como exibido ao assinar',
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_timesheet_signature_unique` (`employee_id`, `reference_month`),
  INDEX `idx_timesheet_signature_month` (`reference_month`),
  CONSTRAINT `fk_timesheet_signature_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Assinatura mensal da folha de ponto pelo funcionario';

-- Tela do RH: quem assinou, quem nao assinou, e se a folha continua integra
SET @module_attendance = (SELECT id FROM ohrm_module WHERE name = 'attendance');

INSERT INTO ohrm_screen (`name`, `module_id`, `action_url`)
SELECT 'BR Timesheet Signatures', @module_attendance, 'brTimesheets'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brTimesheets');

SET @screen_timesheets = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brTimesheets');

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 1, @screen_timesheets, 1, 0, 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 1 AND screen_id = @screen_timesheets);

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 2, @screen_timesheets, 1, 0, 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 2 AND screen_id = @screen_timesheets);

INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Timesheet Signatures', @screen_timesheets, 56, 3, 900, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_timesheets);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'Timesheet Signatures' AS unit_id, 1 AS group_id, 'Timesheet Signatures' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'Timesheet Signatures' AND group_id = 1);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_signature' AS unit_id, 17 AS group_id, 'Timesheet Signature' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_signature' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_month' AS unit_id, 17 AS group_id, 'Month' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_month' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_sign' AS unit_id, 17 AS group_id, 'Sign timesheet' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_sign' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_signed_at' AS unit_id, 17 AS group_id, 'Signed on' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_signed_at' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_not_signed' AS unit_id, 17 AS group_id, 'Not signed' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_not_signed' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_confirm_password' AS unit_id, 17 AS group_id, 'Confirm your password to sign' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_confirm_password' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_records' AS unit_id, 17 AS group_id, 'Records' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_records' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_total' AS unit_id, 17 AS group_id, 'Total worked' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_total' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_pending_notice' AS unit_id, 17 AS group_id, 'Your timesheet for last month is waiting to be signed' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_pending_notice' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_integrity' AS unit_id, 17 AS group_id, 'Integrity' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_integrity' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_intact' AS unit_id, 17 AS group_id, 'Intact' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_intact' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_broken' AS unit_id, 17 AS group_id, 'Changed after signing' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_broken' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_signed_count' AS unit_id, 17 AS group_id, 'Signed' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_signed_count' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'Timesheet Signatures' THEN 'Assinaturas da Folha'
    WHEN 'timesheet_signature' THEN 'Assinatura da Folha'
    WHEN 'timesheet_month' THEN 'Mês'
    WHEN 'timesheet_sign' THEN 'Assinar folha'
    WHEN 'timesheet_signed_at' THEN 'Assinada em'
    WHEN 'timesheet_not_signed' THEN 'Não assinada'
    WHEN 'timesheet_confirm_password' THEN 'Confirme sua senha para assinar'
    WHEN 'timesheet_records' THEN 'Registros'
    WHEN 'timesheet_total' THEN 'Total trabalhado'
    WHEN 'timesheet_pending_notice' THEN 'Sua folha do mês passado está aguardando assinatura'
    WHEN 'timesheet_integrity' THEN 'Integridade'
    WHEN 'timesheet_intact' THEN 'Íntegra'
    WHEN 'timesheet_broken' THEN 'Alterada após a assinatura'
    WHEN 'timesheet_signed_count' THEN 'Assinaram'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.unit_id IN (
    'Timesheet Signatures', 'timesheet_signature', 'timesheet_month', 'timesheet_sign', 'timesheet_signed_at', 'timesheet_not_signed', 'timesheet_confirm_password', 'timesheet_records', 'timesheet_total', 'timesheet_pending_notice', 'timesheet_integrity', 'timesheet_intact', 'timesheet_broken', 'timesheet_signed_count'
  )
  AND ls.group_id IN (1, 17)
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- Conferencia:
--   SHOW CREATE TABLE ohrm_br_timesheet_signature;
--   SELECT menu_title, order_hint FROM ohrm_menu_item WHERE parent_id = 56 ORDER BY order_hint;
