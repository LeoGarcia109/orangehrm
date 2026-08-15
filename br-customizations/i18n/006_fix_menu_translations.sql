-- ============================================================================
-- OrangeHRM BR - Correcao das traducoes pt_BR do menu (todos os niveis)
-- ============================================================================
-- O import original (br-customizations/i18n/pt_br_translations.sql) inseriu
-- as traducoes por ID fixo da lang_string e ficou deslocado (off-by-one):
-- "Organization" virou "Usuarios", "Structure" virou "Dom", etc.
--
-- Este script casa pelo TEXTO da string (ohrm_i18n_lang_string.value), nao
-- por ID, e e idempotente: cria a lang_string quando nao existe, atualiza a
-- traducao existente e insere quando falta.
--
-- Uso: mysql -u<user> -p<senha> orangehrm < 006_fix_menu_translations.sql
-- ============================================================================

SET NAMES utf8mb4;
SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

-- Admin -> Administrador
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'admin', 1, 'Admin', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Admin' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Administrador', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Admin';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Administrador', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Admin'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- PIM -> Gestão de Pessoas
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'pim', 1, 'PIM', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'PIM' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Gestão de Pessoas', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'PIM';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Gestão de Pessoas', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'PIM'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Leave -> Folgas
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'leave', 1, 'Leave', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Leave' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Folgas', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Leave';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Folgas', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Leave'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Time -> Ponto
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'time', 1, 'Time', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Time' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Ponto', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Time';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Ponto', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Time'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Recruitment -> Recrutamento
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'recruitment', 1, 'Recruitment', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Recruitment' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Recrutamento', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Recruitment';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Recrutamento', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Recruitment'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Info -> Minhas Informações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_info', 1, 'My Info', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Info' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Minhas Informações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Info';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Minhas Informações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Info'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Performance -> Avaliação de Desempenho
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'performance', 1, 'Performance', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Performance' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Avaliação de Desempenho', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Performance';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Avaliação de Desempenho', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Performance'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Dashboard -> Painel
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'dashboard', 1, 'Dashboard', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Dashboard' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Painel', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Dashboard';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Painel', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Dashboard'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Directory -> Diretório
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'directory', 1, 'Directory', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Directory' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Diretório', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Directory';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Diretório', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Directory'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Maintenance -> Manutenção
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'maintenance', 1, 'Maintenance', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Maintenance' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Manutenção', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Maintenance';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Manutenção', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Maintenance'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Claim -> Solicitações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'claim', 1, 'Claim', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Claim' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Solicitações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Claim';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Solicitações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Claim'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Buzz -> Buzz
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'buzz', 1, 'Buzz', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Buzz' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Buzz', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Buzz';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Buzz', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Buzz'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- User Management -> Gestão de Usuários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'user_management', 1, 'User Management', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'User Management' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Gestão de Usuários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'User Management';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Gestão de Usuários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'User Management'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Job -> Cargos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'job', 1, 'Job', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Job' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Cargos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Job';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Cargos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Job'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Organization -> Organização
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'organization', 1, 'Organization', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Organization' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Organização', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Organization';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Organização', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Organization'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Qualifications -> Qualificações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'qualifications', 1, 'Qualifications', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Qualifications' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Qualificações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Qualifications';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Qualificações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Qualifications'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Nationalities -> Nacionalidades
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'nationalities', 1, 'Nationalities', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Nationalities' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Nacionalidades', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Nationalities';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Nacionalidades', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Nationalities'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Corporate Branding -> Identidade Visual da Empresa
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'corporate_branding', 1, 'Corporate Branding', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Corporate Branding' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Identidade Visual da Empresa', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Corporate Branding';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Identidade Visual da Empresa', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Corporate Branding'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Configuration -> Configuração
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'configuration', 1, 'Configuration', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Configuration' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Configuração', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Configuration';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Configuração', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Configuration'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Users -> Usuários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'users', 1, 'Users', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Users' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Usuários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Users';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Usuários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Users'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Customers -> Clientes
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'customers', 1, 'Customers', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Customers' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Clientes', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Customers';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Clientes', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Customers'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Projects -> Projetos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'projects', 1, 'Projects', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Projects' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Projetos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Projects';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Projetos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Projects'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Job Titles -> Cargos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'job_titles', 1, 'Job Titles', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Job Titles' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Cargos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Job Titles';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Cargos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Job Titles'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Pay Grades -> Níveis Salariais
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'pay_grades', 1, 'Pay Grades', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Pay Grades' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Níveis Salariais', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Pay Grades';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Níveis Salariais', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Pay Grades'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employment Status -> Situação de Emprego
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employment_status', 1, 'Employment Status', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employment Status' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Situação de Emprego', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employment Status';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Situação de Emprego', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employment Status'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Job Categories -> Categorias de Cargo
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'job_categories', 1, 'Job Categories', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Job Categories' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Categorias de Cargo', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Job Categories';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Categorias de Cargo', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Job Categories'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Work Shifts -> Turnos de Trabalho
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'work_shifts', 1, 'Work Shifts', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Work Shifts' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Turnos de Trabalho', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Work Shifts';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Turnos de Trabalho', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Work Shifts'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- General Information -> Informações Gerais
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'general_information', 1, 'General Information', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'General Information' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Informações Gerais', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'General Information';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Informações Gerais', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'General Information'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Locations -> Localizações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'locations', 1, 'Locations', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Locations' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Localizações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Locations';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Localizações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Locations'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Structure -> Estrutura
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'structure', 1, 'Structure', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Structure' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Estrutura', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Structure';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Estrutura', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Structure'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Skills -> Habilidades
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'skills', 1, 'Skills', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Skills' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Habilidades', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Skills';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Habilidades', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Skills'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Education -> Escolaridade
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'education', 1, 'Education', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Education' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Escolaridade', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Education';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Escolaridade', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Education'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Licenses -> Licenças
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'licenses', 1, 'Licenses', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Licenses' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Licenças', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Licenses';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Licenças', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Licenses'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Languages -> Idiomas
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'languages', 1, 'Languages', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Languages' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Idiomas', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Languages';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Idiomas', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Languages'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Memberships -> Associações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'memberships', 1, 'Memberships', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Memberships' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Associações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Memberships';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Associações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Memberships'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Email Configuration -> Configuração de E-mail
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'email_configuration', 1, 'Email Configuration', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Email Configuration' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Configuração de E-mail', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Email Configuration';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Configuração de E-mail', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Email Configuration'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Email Subscriptions -> Assinaturas de E-mail
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'email_subscriptions', 1, 'Email Subscriptions', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Email Subscriptions' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Assinaturas de E-mail', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Email Subscriptions';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Assinaturas de E-mail', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Email Subscriptions'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Localization -> Localização
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'localization', 1, 'Localization', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Localization' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Localização', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Localization';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Localização', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Localization'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Language Packages -> Pacotes de Idioma
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'language_packages', 1, 'Language Packages', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Language Packages' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Pacotes de Idioma', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Language Packages';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Pacotes de Idioma', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Language Packages'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Modules -> Módulos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'modules', 1, 'Modules', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Modules' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Módulos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Modules';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Módulos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Modules'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Social Media Authentication -> Autenticação por Redes Sociais
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'social_media_authentication', 1, 'Social Media Authentication', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Social Media Authentication' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Autenticação por Redes Sociais', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Social Media Authentication';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Autenticação por Redes Sociais', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Social Media Authentication'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Register OAuth Client -> Registrar Cliente OAuth
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'register_oauth_client', 1, 'Register OAuth Client', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Register OAuth Client' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Registrar Cliente OAuth', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Register OAuth Client';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Registrar Cliente OAuth', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Register OAuth Client'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- LDAP Configuration -> Configuração LDAP
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'ldap_configuration', 1, 'LDAP Configuration', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'LDAP Configuration' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Configuração LDAP', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'LDAP Configuration';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Configuração LDAP', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'LDAP Configuration'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Workspace Notification Configuration -> Configuração de Notificações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'workspace_notification_configuration', 1, 'Workspace Notification Configuration', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Workspace Notification Configuration' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Configuração de Notificações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Workspace Notification Configuration';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Configuração de Notificações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Workspace Notification Configuration'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee List -> Lista de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_list', 1, 'Employee List', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee List' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Lista de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee List';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Lista de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee List'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Add Employee -> Adicionar Funcionário
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'add_employee', 1, 'Add Employee', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Add Employee' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Adicionar Funcionário', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Add Employee';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Adicionar Funcionário', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Add Employee'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Reports -> Relatórios
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'reports', 1, 'Reports', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Reports' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Relatórios', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Reports';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Relatórios', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Reports'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Optional Fields -> Campos Opcionais
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'optional_fields', 1, 'Optional Fields', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Optional Fields' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Campos Opcionais', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Optional Fields';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Campos Opcionais', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Optional Fields'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Custom Fields -> Campos Personalizados
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'custom_fields', 1, 'Custom Fields', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Custom Fields' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Campos Personalizados', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Custom Fields';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Campos Personalizados', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Custom Fields'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Data Import -> Importação de Dados
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'data_import', 1, 'Data Import', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Data Import' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Importação de Dados', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Data Import';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Importação de Dados', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Data Import'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Reporting Methods -> Métodos de Relatório
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'reporting_methods', 1, 'Reporting Methods', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Reporting Methods' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Métodos de Relatório', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Reporting Methods';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Métodos de Relatório', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Reporting Methods'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Termination Reasons -> Motivos de Desligamento
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'termination_reasons', 1, 'Termination Reasons', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Termination Reasons' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Motivos de Desligamento', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Termination Reasons';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Motivos de Desligamento', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Termination Reasons'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Apply -> Solicitar
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'apply', 1, 'Apply', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Apply' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Solicitar', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Apply';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Solicitar', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Apply'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Leave -> Minhas Folgas
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_leave', 1, 'My Leave', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Leave' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Minhas Folgas', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Leave';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Minhas Folgas', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Leave'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Entitlements -> Direitos de Licença
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'entitlements', 1, 'Entitlements', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Entitlements' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Direitos de Licença', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Entitlements';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Direitos de Licença', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Entitlements'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Assign Leave -> Atribuir Licença
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'assign_leave', 1, 'Assign Leave', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Assign Leave' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Atribuir Licença', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Assign Leave';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Atribuir Licença', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Assign Leave'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Leave List -> Lista de Licenças
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'leave_list', 1, 'Leave List', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Leave List' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Lista de Licenças', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Leave List';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Lista de Licenças', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Leave List'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Configure -> Configurar
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'configure', 1, 'Configure', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Configure' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Configurar', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Configure';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Configurar', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Configure'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Leave Period -> Período de Licença
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'leave_period', 1, 'Leave Period', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Leave Period' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Período de Licença', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Leave Period';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Período de Licença', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Leave Period'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Leave Types -> Tipos de Licença
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'leave_types', 1, 'Leave Types', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Leave Types' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Tipos de Licença', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Leave Types';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Tipos de Licença', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Leave Types'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Work Week -> Semana de Trabalho
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'work_week', 1, 'Work Week', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Work Week' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Semana de Trabalho', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Work Week';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Semana de Trabalho', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Work Week'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Holidays -> Feriados
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'holidays', 1, 'Holidays', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Holidays' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Feriados', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Holidays';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Feriados', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Holidays'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Add Entitlements -> Adicionar Direitos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'add_entitlements', 1, 'Add Entitlements', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Add Entitlements' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Adicionar Direitos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Add Entitlements';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Adicionar Direitos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Add Entitlements'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee Entitlements -> Direitos de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_entitlements', 1, 'Employee Entitlements', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee Entitlements' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Direitos de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee Entitlements';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Direitos de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee Entitlements'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Entitlements -> Meus Direitos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_entitlements', 1, 'My Entitlements', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Entitlements' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Meus Direitos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Entitlements';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Meus Direitos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Entitlements'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Leave Entitlements and Usage Report -> Relatório de Direitos e Uso de Licenças
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'leave_entitlements_and_usage_report', 1, 'Leave Entitlements and Usage Report', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Leave Entitlements and Usage Report' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Relatório de Direitos e Uso de Licenças', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Leave Entitlements and Usage Report';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Relatório de Direitos e Uso de Licenças', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Leave Entitlements and Usage Report'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Leave Entitlements and Usage Report -> Meu Relatório de Direitos e Uso de Licenças
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_leave_entitlements_and_usage_report', 1, 'My Leave Entitlements and Usage Report', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Leave Entitlements and Usage Report' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Meu Relatório de Direitos e Uso de Licenças', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Leave Entitlements and Usage Report';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Meu Relatório de Direitos e Uso de Licenças', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Leave Entitlements and Usage Report'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Timesheets -> Folhas de Ponto
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'timesheets', 1, 'Timesheets', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Timesheets' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Folhas de Ponto', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Timesheets';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Folhas de Ponto', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Timesheets'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Attendance -> Frequência
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'attendance', 1, 'Attendance', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Attendance' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Frequência', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Attendance';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Frequência', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Attendance'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Project Info -> Informações de Projetos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'project_info', 1, 'Project Info', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Project Info' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Informações de Projetos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Project Info';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Informações de Projetos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Project Info'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Timesheets -> Minhas Folhas de Ponto
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_timesheets', 1, 'My Timesheets', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Timesheets' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Minhas Folhas de Ponto', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Timesheets';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Minhas Folhas de Ponto', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Timesheets'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee Timesheets -> Folhas de Ponto de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_timesheets', 1, 'Employee Timesheets', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee Timesheets' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Folhas de Ponto de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee Timesheets';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Folhas de Ponto de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee Timesheets'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Records -> Meus Registros
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_records', 1, 'My Records', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Records' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Meus Registros', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Records';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Meus Registros', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Records'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Punch In/Out -> Registrar Entrada/Saída
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'punch_in_out', 1, 'Punch In/Out', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Punch In/Out' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Registrar Entrada/Saída', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Punch In/Out';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Registrar Entrada/Saída', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Punch In/Out'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee Records -> Registros de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_records', 1, 'Employee Records', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee Records' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Registros de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee Records';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Registros de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee Records'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Relatorio de Jornada -> Relatório de Jornada
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'relatorio_de_jornada', 1, 'Relatorio de Jornada', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Relatorio de Jornada' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Relatório de Jornada', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Relatorio de Jornada';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Relatório de Jornada', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Relatorio de Jornada'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Geofence Locations -> Locais de Ponto (Geofence)
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'geofence_locations', 1, 'Geofence Locations', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Geofence Locations' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Locais de Ponto (Geofence)', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Geofence Locations';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Locais de Ponto (Geofence)', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Geofence Locations'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Project Reports -> Relatórios de Projetos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'project_reports', 1, 'Project Reports', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Project Reports' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Relatórios de Projetos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Project Reports';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Relatórios de Projetos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Project Reports'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee Reports -> Relatórios de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_reports', 1, 'Employee Reports', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee Reports' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Relatórios de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee Reports';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Relatórios de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee Reports'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Attendance Summary -> Resumo de Frequência
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'attendance_summary', 1, 'Attendance Summary', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Attendance Summary' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Resumo de Frequência', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Attendance Summary';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Resumo de Frequência', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Attendance Summary'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Candidates -> Candidatos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'candidates', 1, 'Candidates', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Candidates' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Candidatos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Candidates';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Candidatos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Candidates'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Vacancies -> Vagas
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'vacancies', 1, 'Vacancies', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Vacancies' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Vagas', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Vacancies';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Vagas', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Vacancies'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Manage Reviews -> Gerenciar Avaliações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'manage_reviews', 1, 'Manage Reviews', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Manage Reviews' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Gerenciar Avaliações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Manage Reviews';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Gerenciar Avaliações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Manage Reviews'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Trackers -> Meus Acompanhamentos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_trackers', 1, 'My Trackers', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Trackers' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Meus Acompanhamentos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Trackers';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Meus Acompanhamentos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Trackers'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee Trackers -> Acompanhamentos de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_trackers', 1, 'Employee Trackers', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee Trackers' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Acompanhamentos de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee Trackers';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Acompanhamentos de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee Trackers'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- KPIs -> KPIs
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'kpis', 1, 'KPIs', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'KPIs' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'KPIs', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'KPIs';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'KPIs', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'KPIs'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Trackers -> Acompanhamentos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'trackers', 1, 'Trackers', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Trackers' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Acompanhamentos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Trackers';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Acompanhamentos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Trackers'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Reviews -> Minhas Avaliações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_reviews', 1, 'My Reviews', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Reviews' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Minhas Avaliações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Reviews';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Minhas Avaliações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Reviews'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee Reviews -> Avaliações de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_reviews', 1, 'Employee Reviews', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee Reviews' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Avaliações de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee Reviews';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Avaliações de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee Reviews'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Purge Records -> Expurgar Registros
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'purge_records', 1, 'Purge Records', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Purge Records' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Expurgar Registros', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Purge Records';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Expurgar Registros', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Purge Records'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Access Records -> Acessar Registros
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'access_records', 1, 'Access Records', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Access Records' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Acessar Registros', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Access Records';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Acessar Registros', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Access Records'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Candidate Records -> Registros de Candidatos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'candidate_records', 1, 'Candidate Records', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Candidate Records' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Registros de Candidatos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Candidate Records';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Registros de Candidatos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Candidate Records'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Submit Claim -> Enviar Solicitação
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'submit_claim', 1, 'Submit Claim', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Submit Claim' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Enviar Solicitação', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Submit Claim';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Enviar Solicitação', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Submit Claim'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- My Claims -> Minhas Solicitações
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'my_claims', 1, 'My Claims', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'My Claims' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Minhas Solicitações', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'My Claims';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Minhas Solicitações', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'My Claims'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Employee Claims -> Solicitações de Funcionários
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'employee_claims', 1, 'Employee Claims', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Employee Claims' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Solicitações de Funcionários', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Employee Claims';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Solicitações de Funcionários', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Employee Claims'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Assign Claim -> Atribuir Solicitação
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'assign_claim', 1, 'Assign Claim', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Assign Claim' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Atribuir Solicitação', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Assign Claim';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Atribuir Solicitação', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Assign Claim'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Events -> Eventos
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'events', 1, 'Events', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Events' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Eventos', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Events';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Eventos', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Events'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Expense Types -> Tipos de Despesa
INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT 'expense_types', 1, 'Expense Types', NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_i18n_lang_string WHERE value = 'Expense Types' AND group_id = 1);

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = 'Tipos de Despesa', t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br AND ls.group_id = 1 AND ls.value = 'Expense Types';

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, 'Tipos de Despesa', 1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 1 AND ls.value = 'Expense Types'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

-- Invalida o cache de i18n (frontend recarrega as strings)
UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ============================================================================
-- FIM DA MIGRACAO 006
-- ============================================================================
