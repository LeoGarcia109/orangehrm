-- ============================================================================
-- OrangeHRM BR - Strings pt_BR da caixa de entrada
-- Migracao 011: avisos do RH e justificativa de falta na tela mobile.
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> orangehrm < 011_inbox_i18n.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcements' AS unit_id, 17 AS group_id, 'Notices' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcements' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absences' AS unit_id, 17 AS group_id, 'Absences' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absences' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'no_announcements' AS unit_id, 17 AS group_id, 'No notices' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'no_announcements' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_ack' AS unit_id, 17 AS group_id, 'I acknowledge' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_ack' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_acknowledged' AS unit_id, 17 AS group_id, 'Acknowledged on' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_acknowledged' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_pending_ack' AS unit_id, 17 AS group_id, 'Waiting for your acknowledgement' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_pending_ack' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_new' AS unit_id, 17 AS group_id, 'Report an absence' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_new' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_reason' AS unit_id, 17 AS group_id, 'Reason' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_reason' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_from' AS unit_id, 17 AS group_id, 'From' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_from' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_to' AS unit_id, 17 AS group_id, 'To' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_to' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_note' AS unit_id, 17 AS group_id, 'Note' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_note' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_attach' AS unit_id, 17 AS group_id, 'Attach document' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_attach' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_submit' AS unit_id, 17 AS group_id, 'Send' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_submit' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'no_absences' AS unit_id, 17 AS group_id, 'No absences reported' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'no_absences' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_reason_atestado_medico' AS unit_id, 17 AS group_id, 'Medical certificate' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_reason_atestado_medico' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_reason_declaracao_comparecimento' AS unit_id, 17 AS group_id, 'Attendance declaration' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_reason_declaracao_comparecimento' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_reason_falta_justificada' AS unit_id, 17 AS group_id, 'Justified absence' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_reason_falta_justificada' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_reason_outro' AS unit_id, 17 AS group_id, 'Other' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_reason_outro' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_status_pending' AS unit_id, 17 AS group_id, 'Pending' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_status_pending' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_status_approved' AS unit_id, 17 AS group_id, 'Approved' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_status_approved' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_status_rejected' AS unit_id, 17 AS group_id, 'Rejected' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_status_rejected' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'announcements' THEN 'Avisos'
    WHEN 'absences' THEN 'Faltas'
    WHEN 'no_announcements' THEN 'Nenhum aviso'
    WHEN 'announcement_ack' THEN 'Estou ciente'
    WHEN 'announcement_acknowledged' THEN 'Ciente em'
    WHEN 'announcement_pending_ack' THEN 'Aguardando sua ciência'
    WHEN 'absence_new' THEN 'Justificar falta'
    WHEN 'absence_reason' THEN 'Motivo'
    WHEN 'absence_from' THEN 'De'
    WHEN 'absence_to' THEN 'Até'
    WHEN 'absence_note' THEN 'Observação'
    WHEN 'absence_attach' THEN 'Anexar documento'
    WHEN 'absence_submit' THEN 'Enviar'
    WHEN 'no_absences' THEN 'Nenhuma falta justificada'
    WHEN 'absence_reason_atestado_medico' THEN 'Atestado médico'
    WHEN 'absence_reason_declaracao_comparecimento' THEN 'Declaração de comparecimento'
    WHEN 'absence_reason_falta_justificada' THEN 'Falta justificada'
    WHEN 'absence_reason_outro' THEN 'Outro'
    WHEN 'absence_status_pending' THEN 'Em análise'
    WHEN 'absence_status_approved' THEN 'Aprovada'
    WHEN 'absence_status_rejected' THEN 'Recusada'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17
  AND ls.unit_id IN (
    'announcements', 'absences', 'no_announcements', 'announcement_ack', 'announcement_acknowledged', 'announcement_pending_ack', 'absence_new', 'absence_reason', 'absence_from', 'absence_to', 'absence_note', 'absence_attach', 'absence_submit', 'no_absences', 'absence_reason_atestado_medico', 'absence_reason_declaracao_comparecimento', 'absence_reason_falta_justificada', 'absence_reason_outro', 'absence_status_pending', 'absence_status_approved', 'absence_status_rejected'
  )
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ============================================================================
-- FIM DA MIGRACAO 011
-- ============================================================================
