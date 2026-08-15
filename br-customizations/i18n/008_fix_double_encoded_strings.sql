-- ============================================================================
-- OrangeHRM BR - Corrige strings pt_BR com encoding dupla (Ã§Ã£)
-- Migracao 008: a migracao 005 foi aplicada sem --default-character-set=utf8mb4
-- e tres traducoes do grupo attendance ficaram com caracteres corrompidos.
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> --default-character-set=utf8mb4 orangehrm \
--        < 008_fix_double_encoded_strings.sql
-- Idempotente.
-- ============================================================================

SET NAMES utf8mb4;
SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Ativar validação de localização (geofence)', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 17 AND ls.unit_id = 'geofence_enforce';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Locais onde os funcionários desta empresa podem bater ponto', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 17 AND ls.unit_id = 'geofence_locations_hint';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Padrão (todas as empresas sem locais próprios)', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 17 AND ls.unit_id = 'geofence_scope_default';

-- Invalida o cache de i18n
UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ============================================================================
-- FIM DA MIGRACAO 008
-- ============================================================================
