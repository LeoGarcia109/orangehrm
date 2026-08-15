-- ============================================================================
-- OrangeHRM BR - Geofence + Pagina Mobile (PWA)
-- Migracao 004: permissoes da API de geofence, tela da pagina mobile e
-- traducoes pt_BR das novas strings.
-- ============================================================================
-- Aplicar apos a instalacao do OrangeHRM 5.9+ (migracoes 001-003)
-- Uso: mysql -u<user> -p<senha> orangehrm < 004_geofence_mobile.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Config: geofence desativado por padrao (ativa via API PUT depois)
-- ----------------------------------------------------------------------------

INSERT INTO hs_hr_config (`name`, `value`)
VALUES ('attendance.br.geofence.enabled', 'false')
ON DUPLICATE KEY UPDATE `name` = `name`;

INSERT INTO hs_hr_config (`name`, `value`)
VALUES ('attendance.br.geofence.locations', '[]')
ON DUPLICATE KEY UPDATE `name` = `name`;

-- ----------------------------------------------------------------------------
-- 2. Data group + API permission para o endpoint de geofence
--    GET para todos (a pagina mobile precisa ler), PUT apenas Admin.
-- ----------------------------------------------------------------------------

INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 'apiv2_attendance_br_geofence', 'API-v2 Attendance BR - Geofence Configuration', 1, 0, 1, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_geofence');

SET @dg_geofence = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_geofence');
SET @module_attendance = (SELECT id FROM ohrm_module WHERE `name` = 'attendance');

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, @dg_geofence, 'OrangeHRM\\Attendance\\Api\\GeofenceConfigurationAPI'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_api_permission
    WHERE api_name = 'OrangeHRM\\Attendance\\Api\\GeofenceConfigurationAPI'
);

-- Admin (role 1): ler e atualizar
INSERT INTO ohrm_user_role_data_group (`user_role_id`, `data_group_id`, `can_read`, `can_create`, `can_update`, `can_delete`, `self`)
SELECT 1, @dg_geofence, 1, 0, 1, 0, 0
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_data_group WHERE user_role_id = 1 AND data_group_id = @dg_geofence
);

-- ESS (role 2): somente leitura (a pagina de punch precisa da config)
INSERT INTO ohrm_user_role_data_group (`user_role_id`, `data_group_id`, `can_read`, `can_create`, `can_update`, `can_delete`, `self`)
SELECT 2, @dg_geofence, 1, 0, 0, 0, 0
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_data_group WHERE user_role_id = 2 AND data_group_id = @dg_geofence
);

-- Supervisor (role 3): somente leitura
INSERT INTO ohrm_user_role_data_group (`user_role_id`, `data_group_id`, `can_read`, `can_create`, `can_update`, `can_delete`, `self`)
SELECT 3, @dg_geofence, 1, 0, 0, 0, 0
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_data_group WHERE user_role_id = 3 AND data_group_id = @dg_geofence
);

-- ----------------------------------------------------------------------------
-- 3. Tela da pagina mobile (/attendance/mobile) para Admin e ESS
-- ----------------------------------------------------------------------------

INSERT INTO ohrm_screen (`name`, `module_id`, `action_url`)
SELECT 'Mobile Attendance', @module_attendance, 'mobile'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'mobile'
);

SET @screen_mobile = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'mobile');

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 1, @screen_mobile, 1, 1, 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 1 AND screen_id = @screen_mobile
);

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 2, @screen_mobile, 1, 1, 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 2 AND screen_id = @screen_mobile
);

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 3, @screen_mobile, 1, 1, 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 3 AND screen_id = @screen_mobile
);

-- ----------------------------------------------------------------------------
-- 4. Traducoes pt_BR das strings novas (grupo attendance, unit prefixo
--    geofence_/punched_*). O i18n do frontend usa unit_id como chave.
-- ----------------------------------------------------------------------------

-- punched_in / punched_out ja existem desde a 5.2.0 — nao duplicar;
-- inserir somente as strings novas.
INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (
  SELECT 'geofence_active' AS unit_id, 17 AS group_id, 'Geofence active' AS value, NULL AS version
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_active' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (SELECT 'location_captured', 17, 'Location captured', NULL) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'location_captured' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (SELECT 'location_denied', 17, 'Location unavailable', NULL) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'location_denied' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (SELECT 'locating', 17, 'Locating...', NULL) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'locating' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (SELECT 'today_records', 17, 'Records today', NULL) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'today_records' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (SELECT 'note_placeholder', 17, 'Optional note', NULL) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'note_placeholder' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (SELECT 'geofence_validation_failed_location_coordinates_required', 17, 'Geofence Validation Failed - Location Coordinates Required', NULL) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_validation_failed_location_coordinates_required' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT * FROM (SELECT 'geofence_validation_failed_location_outside_allowed_area', 17, 'Geofence Validation Failed - Location Outside Allowed Area', NULL) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_validation_failed_location_outside_allowed_area' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT tmp.unit_id, tmp.group_id, tmp.value, tmp.version FROM (SELECT 'history' AS unit_id, 17 AS group_id, 'History' AS value, NULL AS version) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'history' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (`unit_id`, `group_id`, `value`, `version`)
SELECT tmp.unit_id, tmp.group_id, tmp.value, tmp.version FROM (SELECT 'history_loading' AS unit_id, 17 AS group_id, 'Loading...' AS value, NULL AS version) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'history_loading' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (`lang_string_id`, `language_id`, `value`, `modified_at`)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'punched_in' THEN 'Registrado (entrada)'
    WHEN 'punched_out' THEN 'Sem registro ativo'
    WHEN 'geofence_active' THEN 'Area restrita ativa'
    WHEN 'location_captured' THEN 'Localizacao capturada'
    WHEN 'location_denied' THEN 'Localizacao indisponivel'
    WHEN 'locating' THEN 'Obtendo localizacao...'
    WHEN 'today_records' THEN 'Registros de hoje'
    WHEN 'note_placeholder' THEN 'Observacao (opcional)'
    WHEN 'geofence_validation_failed_location_coordinates_required' THEN 'Registro fora da area permitida - autorize a localizacao do dispositivo'
    WHEN 'geofence_validation_failed_location_outside_allowed_area' THEN 'Registro fora da area permitida - voce nao esta em um local autorizado'
  END,
  NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17
  AND ls.unit_id IN (
    'punched_in', 'punched_out', 'geofence_active', 'location_captured',
    'location_denied', 'locating', 'today_records', 'note_placeholder',
    'geofence_validation_failed_location_coordinates_required',
    'geofence_validation_failed_location_outside_allowed_area'
  )
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

INSERT INTO ohrm_i18n_translate (`lang_string_id`, `language_id`, `value`, `modified_at`)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'history' THEN 'Histórico'
    WHEN 'history_loading' THEN 'Carregando...'
  END,
  NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17
  AND ls.unit_id IN ('history', 'history_loading')
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Corrige traducao quebrada do SQL original (off-by-one):
-- general.no_records_found tinha virado "Alterar Senha?"
UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Nenhum registro encontrado', t.modified_at = NOW()
WHERE ls.unit_id = 'no_records_found' AND ls.group_id = 1
  AND t.language_id = @lang_pt_br;

-- ----------------------------------------------------------------------------
-- FIM DA MIGRACAO 004
-- ----------------------------------------------------------------------------
