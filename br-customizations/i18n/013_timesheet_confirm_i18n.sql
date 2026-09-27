-- ============================================================================
-- OrangeHRM BR - Migracao i18n 013: etapa de confirmacao da assinatura da folha
-- ============================================================================
-- Idempotente.

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_confirm_text' AS unit_id, 17 AS group_id, 'By signing, you confirm the records for this month are correct. If any record is changed afterwards, the signature stops being valid and HR can see it.' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_confirm_text' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_confirm_sign' AS unit_id, 17 AS group_id, 'Confirm signature' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_confirm_sign' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_status_pending' AS unit_id, 17 AS group_id, 'Pending' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_status_pending' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'timesheet_status_signed' AS unit_id, 17 AS group_id, 'Signed' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'timesheet_status_signed' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'timesheet_confirm_text' THEN 'Ao assinar, você confirma que os registros deste mês estão corretos. Se algum registro for alterado depois, a assinatura deixa de valer e isso fica visível para o RH.'
    WHEN 'timesheet_confirm_sign' THEN 'Confirmar assinatura'
    WHEN 'timesheet_status_pending' THEN 'Pendente'
    WHEN 'timesheet_status_signed' THEN 'Assinada'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17 AND ls.unit_id IN ('timesheet_confirm_text', 'timesheet_confirm_sign', 'timesheet_status_pending', 'timesheet_status_signed')
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;
