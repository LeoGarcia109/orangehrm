-- ============================================================================
-- OrangeHRM BR - Multi-empresa (fase 5)
-- Migracao 005: um RH unico atendendo varias empresas (CNPJ distintos).
--   1. CNPJ/CEI por unidade da estrutura organizacional (ohrm_subunit)
--   2. Locais de geofence por unidade (ohrm_attendance_geofence_location);
--      subunit_id NULL = conjunto padrao (fallback para quem nao tem local
--      proprio). Substitui o JSON em hs_hr_config
--      (attendance.br.geofence.locations).
--   3. Tela administrativa de locais de geofence + item de menu.
-- ============================================================================
-- Aplicar apos a instalacao do OrangeHRM 5.9+ (migracoes 001-004)
-- Uso: mysql -u<user> -p<senha> orangehrm < 005_multi_company.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. ohrm_subunit: colunas cnpj / cei
-- ----------------------------------------------------------------------------

SET @has_cnpj = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ohrm_subunit' AND column_name = 'cnpj'
);
SET @ddl = IF(@has_cnpj = 0,
  'ALTER TABLE ohrm_subunit ADD COLUMN cnpj VARCHAR(18) NULL DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_cei = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ohrm_subunit' AND column_name = 'cei'
);
SET @ddl = IF(@has_cei = 0,
  'ALTER TABLE ohrm_subunit ADD COLUMN cei VARCHAR(12) NULL DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 2. Tabela de locais de geofence por unidade
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ohrm_attendance_geofence_location (
  id INT NOT NULL AUTO_INCREMENT,
  subunit_id INT NULL DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  radius INT NOT NULL,
  PRIMARY KEY (id),
  KEY idx_geofence_subunit (subunit_id),
  CONSTRAINT fk_geofence_subunit
    FOREIGN KEY (subunit_id) REFERENCES ohrm_subunit (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 2.1 Migrar locais antigos (JSON da migracao 004, se houver) para a tabela
--     como conjunto padrao (subunit_id NULL). Pula se a tabela ja tiver
--     linhas ou o JSON estiver vazio.
-- ----------------------------------------------------------------------------

SET @legacy_geofence_json = (
  SELECT value FROM hs_hr_config WHERE name = 'attendance.br.geofence.locations'
);

INSERT INTO ohrm_attendance_geofence_location (subunit_id, name, latitude, longitude, radius)
SELECT NULL, jt.loc_name, jt.loc_lat, jt.loc_lng, jt.loc_radius
FROM JSON_TABLE(
  CAST(COALESCE(@legacy_geofence_json, '[]') AS JSON),
  '$[*]' COLUMNS (
    loc_name VARCHAR(100) PATH '$.name',
    loc_lat DECIMAL(10,8) PATH '$.latitude',
    loc_lng DECIMAL(11,8) PATH '$.longitude',
    loc_radius INT PATH '$.radius'
  )
) AS jt
WHERE jt.loc_name IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM ohrm_attendance_geofence_location gl LIMIT 1);

-- ----------------------------------------------------------------------------
-- 3. Tela administrativa de locais de geofence (/attendance/brGeofence)
-- ----------------------------------------------------------------------------

SET @module_attendance = (SELECT id FROM ohrm_module WHERE name = 'attendance');

INSERT INTO ohrm_screen (name, module_id, action_url)
SELECT 'BR Geofence Locations', @module_attendance, 'brGeofence'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brGeofence'
);

SET @screen_geofence = (
  SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brGeofence'
);

-- Somente Admin (role 1) configura os locais
INSERT INTO ohrm_user_role_screen (user_role_id, screen_id, can_read, can_create, can_update, can_delete)
SELECT 1, @screen_geofence, 1, 1, 1, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 1 AND screen_id = @screen_geofence
);

-- Item de menu: Time > Attendance > Locais de Ponto (Geofence)
INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Geofence Locations', @screen_geofence, 56, 3, 600, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_geofence
);

-- ----------------------------------------------------------------------------
-- 4. Traducoes pt_BR das strings novas
--    - grupo 17 (attendance): pagina de locais de geofence
--    - grupo 2  (admin): CNPJ/CEI no dialogo da estrutura organizacional
--    - grupo 1  (general): titulo do item de menu
-- ----------------------------------------------------------------------------

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_locations' AS unit_id, 17 AS group_id, 'Geofence Locations' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_locations' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_scope' AS unit_id, 17 AS group_id, 'Company (structure unit)' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_scope' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_scope_default' AS unit_id, 17 AS group_id, 'Default (all companies without specific locations)' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_scope_default' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_enforce' AS unit_id, 17 AS group_id, 'Enforce geofence validation on punches' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_enforce' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_locations_hint' AS unit_id, 17 AS group_id, 'Locations where employees of this company are allowed to punch in/out' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_locations_hint' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'latitude' AS unit_id, 17 AS group_id, 'Latitude' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'latitude' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'longitude' AS unit_id, 17 AS group_id, 'Longitude' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'longitude' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'radius_meters' AS unit_id, 17 AS group_id, 'Radius (meters)' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'radius_meters' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'location_name' AS unit_id, 17 AS group_id, 'Location name' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'location_name' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'add_location' AS unit_id, 2 AS group_id, 'Add Location' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'add_location' AND group_id = 2);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'cnpj' AS unit_id, 2 AS group_id, 'CNPJ' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'cnpj' AND group_id = 2);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'cei' AS unit_id, 2 AS group_id, 'CEI/CNO' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'cei' AND group_id = 2);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'Geofence Locations' AS unit_id, 1 AS group_id, 'Geofence Locations' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'Geofence Locations' AND group_id = 1);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE
    WHEN ls.group_id = 17 THEN
      CASE ls.unit_id
        WHEN 'geofence_locations' THEN 'Locais de Ponto (Geofence)'
        WHEN 'geofence_scope' THEN 'Empresa (unidade da estrutura)'
        WHEN 'geofence_scope_default' THEN 'Padrão (todas as empresas sem locais próprios)'
        WHEN 'geofence_enforce' THEN 'Ativar validação de localização (geofence)'
        WHEN 'geofence_locations_hint' THEN 'Locais onde os funcionários desta empresa podem bater ponto'
        WHEN 'latitude' THEN 'Latitude'
        WHEN 'longitude' THEN 'Longitude'
        WHEN 'radius_meters' THEN 'Raio (metros)'
        WHEN 'location_name' THEN 'Nome do local'
      END
    WHEN ls.group_id = 2 THEN
      CASE ls.unit_id
        WHEN 'add_location' THEN 'Adicionar Local'
        WHEN 'cnpj' THEN 'CNPJ'
        WHEN 'cei' THEN 'CEI/CNO'
      END
    WHEN ls.group_id = 1 THEN
      CASE ls.unit_id
        WHEN 'Geofence Locations' THEN 'Locais de Ponto (Geofence)'
      END
  END,
  NOW()
FROM ohrm_i18n_lang_string ls
WHERE (
    (ls.group_id = 17 AND ls.unit_id IN (
      'geofence_locations', 'geofence_scope', 'geofence_scope_default',
      'geofence_enforce', 'geofence_locations_hint', 'latitude',
      'longitude', 'radius_meters', 'location_name'
    ))
    OR (ls.group_id = 2 AND ls.unit_id IN ('add_location', 'cnpj', 'cei'))
    OR (ls.group_id = 1 AND ls.unit_id = 'Geofence Locations')
  )
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Invalida o cache ETag do i18n (frontend recarrega as strings)
UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ----------------------------------------------------------------------------
-- FIM DA MIGRACAO 005
-- ----------------------------------------------------------------------------
