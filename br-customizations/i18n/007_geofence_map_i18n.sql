-- ============================================================================
-- OrangeHRM BR - Strings pt_BR do mini-mapa de geofence
-- Migracao 007: traducoes da tela de selecao de local no mapa (Leaflet).
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> orangehrm < 007_geofence_map_i18n.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_map_title' AS unit_id, 17 AS group_id, 'Select Location on Map' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_map_title' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_search_address' AS unit_id, 17 AS group_id, 'Search address' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_search_address' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_search' AS unit_id, 17 AS group_id, 'Search' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_search' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_map_hint' AS unit_id, 17 AS group_id, 'Click on the map to set the exact point' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_map_hint' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_no_results' AS unit_id, 17 AS group_id, 'No results found' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_no_results' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_pick_on_map' AS unit_id, 17 AS group_id, 'Pick on map' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_pick_on_map' AND group_id = 17);

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'geofence_use_my_location' AS unit_id, 17 AS group_id, 'Use my location' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'geofence_use_my_location' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'geofence_map_title' THEN 'Selecionar Local no Mapa'
    WHEN 'geofence_search_address' THEN 'Buscar endereço'
    WHEN 'geofence_search' THEN 'Buscar'
    WHEN 'geofence_map_hint' THEN 'Clique no mapa para marcar o ponto exato'
    WHEN 'geofence_no_results' THEN 'Nenhum resultado encontrado'
    WHEN 'geofence_pick_on_map' THEN 'Marcar no mapa'
    WHEN 'geofence_use_my_location' THEN 'Usar minha localização'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17
  AND ls.unit_id IN (
    'geofence_map_title', 'geofence_search_address', 'geofence_search',
    'geofence_map_hint', 'geofence_no_results', 'geofence_pick_on_map',
    'geofence_use_my_location'
  )
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ============================================================================
-- FIM DA MIGRACAO 007
-- ============================================================================
