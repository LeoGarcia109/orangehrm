-- ============================================================================
-- OrangeHRM BR - Migracao 011: telas do RH da caixa de entrada
-- ============================================================================
--
-- Itens de menu das duas telas e as strings que so aparecem nelas.
-- As telas e as permissoes foram criadas na migracao 010.
--
-- Idempotente.
-- ----------------------------------------------------------------------------

SET NAMES utf8mb4;

SET @module_attendance = (SELECT id FROM ohrm_module WHERE name = 'attendance');
SET @screen_announcements = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brAnnouncements');
SET @screen_absences = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brAbsences');

-- Menu do modulo Ponto (parent 56), depois dos Locais de Ponto (600)
INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Notices', @screen_announcements, 56, 3, 700, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_announcements);

INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Absence Justifications', @screen_absences, 56, 3, 800, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_absences);

-- Titulos do menu (grupo 1 = general, que e onde o menu lateral busca)
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'Notices' AS unit_id, 1 AS group_id, 'Notices' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'Notices' AND group_id = 1);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'Absence Justifications' AS unit_id, 1 AS group_id, 'Absence Justifications' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'Absence Justifications' AND group_id = 1);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_title' AS unit_id, 17 AS group_id, 'Title' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_title' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_body' AS unit_id, 17 AS group_id, 'Message' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_body' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_scope' AS unit_id, 17 AS group_id, 'Audience' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_scope' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_scope_network' AS unit_id, 17 AS group_id, 'Whole network' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_scope_network' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_scope_subunit' AS unit_id, 17 AS group_id, 'Company / site' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_scope_subunit' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_scope_employee' AS unit_id, 17 AS group_id, 'One employee' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_scope_employee' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_employee_id' AS unit_id, 17 AS group_id, 'Employee number' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_employee_id' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_requires_ack' AS unit_id, 17 AS group_id, 'Require acknowledgement' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_requires_ack' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_expires_at' AS unit_id, 17 AS group_id, 'Remove from inbox after' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_expires_at' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_publish' AS unit_id, 17 AS group_id, 'Publish' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_publish' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_published' AS unit_id, 17 AS group_id, 'Published notices' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_published' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_read_count' AS unit_id, 17 AS group_id, 'Read' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_read_count' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'announcement_ack_count' AS unit_id, 17 AS group_id, 'Acknowledged' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'announcement_ack_count' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_approve' AS unit_id, 17 AS group_id, 'Approve' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_approve' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_reject' AS unit_id, 17 AS group_id, 'Reject' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_reject' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'absence_reject_reason' AS unit_id, 17 AS group_id, 'Reason for rejecting' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'absence_reject_reason' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'Notices' THEN 'Avisos'
    WHEN 'Absence Justifications' THEN 'Justificativas de Falta'
    WHEN 'announcement_title' THEN 'Título'
    WHEN 'announcement_body' THEN 'Mensagem'
    WHEN 'announcement_scope' THEN 'Destinatários'
    WHEN 'announcement_scope_network' THEN 'Toda a rede'
    WHEN 'announcement_scope_subunit' THEN 'Empresa / posto'
    WHEN 'announcement_scope_employee' THEN 'Um funcionário'
    WHEN 'announcement_employee_id' THEN 'Número do funcionário'
    WHEN 'announcement_requires_ack' THEN 'Exigir ciência'
    WHEN 'announcement_expires_at' THEN 'Sair da caixa depois de'
    WHEN 'announcement_publish' THEN 'Publicar'
    WHEN 'announcement_published' THEN 'Avisos publicados'
    WHEN 'announcement_read_count' THEN 'Leram'
    WHEN 'announcement_ack_count' THEN 'Deram ciência'
    WHEN 'absence_approve' THEN 'Aprovar'
    WHEN 'absence_reject' THEN 'Recusar'
    WHEN 'absence_reject_reason' THEN 'Motivo da recusa'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.unit_id IN (
    'Notices', 'Absence Justifications', 'announcement_title', 'announcement_body', 'announcement_scope', 'announcement_scope_network', 'announcement_scope_subunit', 'announcement_scope_employee', 'announcement_employee_id', 'announcement_requires_ack', 'announcement_expires_at', 'announcement_publish', 'announcement_published', 'announcement_read_count', 'announcement_ack_count', 'absence_approve', 'absence_reject', 'absence_reject_reason'
  )
  AND ls.group_id IN (1, 17)
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- Conferencia:
--   SELECT menu_title, order_hint FROM ohrm_menu_item WHERE parent_id = 56 ORDER BY order_hint;
