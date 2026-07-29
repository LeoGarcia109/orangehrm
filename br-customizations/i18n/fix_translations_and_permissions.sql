-- ============================================================================
-- OrangeHRM BR - Correcoes de traducao e permissoes
-- Aplica correcoes que foram identificadas apos a instalacao:
--   1. Traducoes do sidebar deslocadas (off-by-one no SQL original)
--   2. Permissao de tela faltante para "View My Timesheet" (screen 51)
--   3. Campo modified_at NULL em ohrm_i18n_language (quebra o cache I18N)
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> orangehrm < fix_translations_and_permissions.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Corrige traducoes do menu principal (pt_BR = language_id 374)
--    O SQL original de traducao deslocou as strings em uma posicao.
-- ----------------------------------------------------------------------------

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Administrador'
WHERE t.language_id = 374 AND ls.value = 'Admin';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Gestao de Pessoas'
WHERE t.language_id = 374 AND ls.value = 'PIM';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Folgas'
WHERE t.language_id = 374 AND ls.value = 'Leave';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Ponto'
WHERE t.language_id = 374 AND ls.value = 'Time';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Recrutamento'
WHERE t.language_id = 374 AND ls.value = 'Recruitment';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Minhas Informacoes'
WHERE t.language_id = 374 AND ls.value = 'My Info';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Avaliacao de Desempenho'
WHERE t.language_id = 374 AND ls.value = 'Performance';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Painel'
WHERE t.language_id = 374 AND ls.value = 'Dashboard';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Diretorio'
WHERE t.language_id = 374 AND ls.value = 'Directory';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Buzz'
WHERE t.language_id = 374 AND ls.value = 'Buzz';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Manutencao'
WHERE t.language_id = 374 AND ls.value = 'Maintenance';

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Reembolso'
WHERE t.language_id = 374 AND ls.value = 'Claim';

-- ----------------------------------------------------------------------------
-- 2. Corrige permissao de tela faltante: "View My Timesheet" (screen_id 51)
--    A migration original pulou esta entrada para o role Admin (user_role_id 1).
--    Sem ela, a pagina /time/viewMyTimesheet retorna "credencial necessaria".
-- ----------------------------------------------------------------------------

INSERT IGNORE INTO ohrm_user_role_screen (user_role_id, screen_id, can_read, can_create, can_update, can_delete)
SELECT 1, 51, 1, 1, 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 1 AND screen_id = 51
);

-- ----------------------------------------------------------------------------
-- 3. Corrige modified_at NULL em ohrm_i18n_language
--    Quando NULL, o I18NService::getETagByLangCode() retorna null e causa
--    erro fatal (TypeError) ao limpar o cache. Isso quebra a tela de login.
-- ----------------------------------------------------------------------------

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE modified_at IS NULL;

-- ============================================================================
-- FIM DAS CORRECOES
-- Apos aplicar, limpar cache:
--   docker exec orangehrm-web bash -c "rm -rf /var/www/html/src/cache/orangehrm/*"
--   docker exec orangehrm-web bash -c "chown -R www-data:www-data /var/www/html/src/cache/"
-- ============================================================================
