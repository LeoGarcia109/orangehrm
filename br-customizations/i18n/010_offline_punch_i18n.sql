-- ============================================================================
-- OrangeHRM BR - Strings pt_BR da fila offline de ponto
-- Migracao 010: avisos da tela mobile quando a batida fica guardada.
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> orangehrm < 010_offline_punch_i18n.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'offline_punch_saved' AS unit_id, 17 AS group_id,
         'No signal - punch saved and will be sent when you reconnect' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'offline_punch_saved' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'offline_punch_pending' AS unit_id, 17 AS group_id,
         'punch(es) waiting to be sent' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'offline_punch_pending' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'offline_punch_sync_now' AS unit_id, 17 AS group_id, 'Send now' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'offline_punch_sync_now' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'offline_punch_synced' AS unit_id, 17 AS group_id, 'Offline punches sent' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'offline_punch_synced' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'offline_punch_not_stored' AS unit_id, 17 AS group_id,
         'Could not store the punch on this device. Try again with signal.' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'offline_punch_not_stored' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'offline_punch_too_old' AS unit_id, 17 AS group_id,
         'This punch is older than the sync window. Ask HR to register a correction.' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'offline_punch_too_old' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'offline_punch_saved' THEN 'Sem sinal — batida guardada e será enviada quando reconectar'
    WHEN 'offline_punch_pending' THEN 'batida(s) aguardando envio'
    WHEN 'offline_punch_sync_now' THEN 'Enviar agora'
    WHEN 'offline_punch_synced' THEN 'Batidas offline enviadas'
    WHEN 'offline_punch_not_stored' THEN 'Não foi possível guardar a batida neste aparelho. Tente de novo com sinal.'
    WHEN 'offline_punch_too_old' THEN 'Esta batida é mais antiga que a janela de sincronização. Peça ao RH para registrar uma correção.'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17
  AND ls.unit_id IN (
    'offline_punch_saved', 'offline_punch_pending', 'offline_punch_sync_now',
    'offline_punch_synced', 'offline_punch_not_stored', 'offline_punch_too_old'
  )
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ============================================================================
-- FIM DA MIGRACAO 010
-- ============================================================================
