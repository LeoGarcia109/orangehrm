-- ============================================================================
-- OrangeHRM BR - Geofence obrigatorio por empresa
-- Migracao 006: o liga/desliga do geofence passa a ser por empresa.
--   1. ohrm_subunit.geofence_required: a unidade (e tudo abaixo dela) exige
--      geofence. Fica na empresa; os funcionarios podem estar em
--      departamentos filhos, e a validacao sobe a arvore ate achar a flag.
--   2. Migra o estado atual: se o geofence global estiver ligado, marca as
--      unidades que ja tem local cadastrado, para nao afrouxar quem ja
--      estava sendo validado.
-- ============================================================================
-- A chave global attendance.br.geofence.enabled continua existindo como
-- chave-mestra: desligada, nada e validado, independente das flags.
--
-- ATENCAO: a partir desta migracao, empresa marcada SEM local cadastrado
-- RECUSA o ponto (antes liberava). Cadastre os locais antes de marcar.
--
-- Uso: mysql -u<user> -p<senha> orangehrm < 006_geofence_per_company.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1. ohrm_subunit: coluna geofence_required
-- ----------------------------------------------------------------------------

SET @has_geofence_required = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'ohrm_subunit'
    AND column_name = 'geofence_required'
);
SET @ddl = IF(@has_geofence_required = 0,
  'ALTER TABLE ohrm_subunit ADD COLUMN geofence_required TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 2. Preserva o comportamento atual das unidades ja validadas
--
-- Antes desta migracao, com a chave global ligada, quem tinha local proprio
-- era validado. Marcar essas unidades mantem exatamente esse conjunto sob
-- validacao. Roda so na primeira aplicacao (quando nada esta marcado ainda),
-- para nao remarcar unidades que o admin tenha desmarcado depois.
-- ----------------------------------------------------------------------------

SET @global_enabled = (
  SELECT COUNT(*) FROM hs_hr_config
  WHERE name = 'attendance.br.geofence.enabled' AND value = 'true'
);
SET @already_marked = (SELECT COUNT(*) FROM ohrm_subunit WHERE geofence_required = 1);

UPDATE ohrm_subunit s
SET s.geofence_required = 1
WHERE @global_enabled = 1
  AND @already_marked = 0
  AND EXISTS (
    SELECT 1 FROM ohrm_attendance_geofence_location gl
    WHERE gl.subunit_id = s.id
  );

-- ----------------------------------------------------------------------------
-- 3. i18n pt_BR das novas mensagens
-- ----------------------------------------------------------------------------

SET @lang_id = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);
SET @group_id = (SELECT id FROM ohrm_i18n_group WHERE name = 'attendance' LIMIT 1);

INSERT INTO ohrm_i18n_lang_string (unit_id, `group_id`, `value`, note, version)
SELECT * FROM (
  SELECT 'geofence_required_here' AS unit_id, @group_id AS g,
         'Exigir geofence nesta empresa' AS v, NULL AS n, '5.9' AS ver
  UNION ALL SELECT 'geofence_required_hint', @group_id,
         'Funcionarios desta empresa e das unidades abaixo dela so conseguem bater ponto dentro dos locais cadastrados.', NULL, '5.9'
  UNION ALL SELECT 'geofence_no_location_warning', @group_id,
         'Esta empresa exige geofence mas nao tem nenhum local cadastrado. Enquanto estiver assim, ninguem consegue bater ponto.', NULL, '5.9'
  UNION ALL SELECT 'geofence_missing_subunit', @group_id,
         'Funcionario sem unidade definida. Peca ao RH para vincular voce a uma empresa antes de bater o ponto.', NULL, '5.9'
  UNION ALL SELECT 'geofence_not_configured', @group_id,
         'Sua empresa exige registro por localizacao, mas nenhum local foi cadastrado. Procure o RH.', NULL, '5.9'
) AS novos
WHERE @group_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_lang_string ls
    WHERE ls.unit_id = novos.unit_id AND ls.group_id = novos.g
  );

INSERT INTO ohrm_i18n_translate (language_id, lang_string_id, `value`, customized, version)
SELECT @lang_id, ls.id, ls.value, 1, '5.9'
FROM ohrm_i18n_lang_string ls
WHERE @lang_id IS NOT NULL
  AND ls.group_id = @group_id
  AND ls.unit_id IN (
    'geofence_required_here', 'geofence_required_hint', 'geofence_no_location_warning',
    'geofence_missing_subunit', 'geofence_not_configured'
  )
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.language_id = @lang_id AND t.lang_string_id = ls.id
  );
