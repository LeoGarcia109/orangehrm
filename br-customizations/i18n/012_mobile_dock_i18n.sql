-- ============================================================================
-- OrangeHRM BR - Migracao i18n 012: rotulo curto do dock do mobile
-- ============================================================================
-- O dock de baixo tem quatro itens com icone sobre o rotulo; "Registrar
-- Entrada/Saida" nao cabe. Idempotente.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT tmp.unit_id, tmp.group_id, tmp.value, NULL FROM (
  SELECT 'tab_punch' AS unit_id, 17 AS group_id, 'Punch' AS value
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE unit_id = 'tab_punch' AND group_id = 17);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Ponto', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17 AND ls.unit_id = 'tab_punch'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;
