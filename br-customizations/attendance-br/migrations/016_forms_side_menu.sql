-- ============================================================================
-- OrangeHRM BR - Migracao 016: "Formularios" na barra lateral e o menu de
-- cima de volta nas telas BR de Ponto
-- ============================================================================
--
-- 1. Formularios ganha item proprio na barra lateral (icone "training"),
--    em vez de ficar escondido em Ponto -> Frequencia. O item que estava la
--    (nivel 3) vira o filho de nivel 2 do novo item.
--    As telas do RH (lista, construtor, resultados) usam o
--    FormsMenuConfigurator: a URL delas e do modulo attendance, e sem ele a
--    barra lateral nao destacaria nada.
--    "Meus formularios" continua em Ponto -> Frequencia (e na aba Provas do
--    mobile), porque o item lateral so aparece para quem ve a tela do RH.
--
-- 2. As telas BR de Ponto (relatorio de jornada, geofence, avisos, faltas,
--    folha, meus formularios) nao tinham menu_configurator: nelas nenhum item
--    lateral ficava ativo e o menu de cima sumia. Passam a usar o mesmo
--    AttendanceMenuConfigurator das telas originais de Ponto.
--
-- Idempotente.
-- ----------------------------------------------------------------------------

SET NAMES utf8mb4;

SET @module_attendance = (SELECT id FROM ohrm_module WHERE `name` = 'attendance');
SET @screen_forms = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brForms');

-- 1. Item de nivel 1, logo depois de Ponto (400) e antes de Recrutamento (500)
INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status, additional_params)
SELECT 'Forms', @screen_forms, NULL, 1, 450, 1, '{"icon":"training"}' FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_menu_item WHERE screen_id = @screen_forms AND level = 1
);
SET @menu_forms_root = (SELECT id FROM ohrm_menu_item WHERE screen_id = @screen_forms AND level = 1);

-- O item antigo (nivel 3, em Ponto) passa a ser o filho de nivel 2
UPDATE ohrm_menu_item
   SET parent_id = @menu_forms_root, level = 2, order_hint = 100
 WHERE screen_id = @screen_forms AND level = 3;

UPDATE ohrm_screen
   SET menu_configurator = 'OrangeHRM\\Attendance\\Menu\\FormsMenuConfigurator'
 WHERE module_id = @module_attendance
   AND action_url IN ('brForms', 'brFormBuilder', 'brFormResults');

-- 2. Telas BR de Ponto: mesmo configurador das telas originais
UPDATE ohrm_screen
   SET menu_configurator = 'OrangeHRM\\Attendance\\Menu\\AttendanceMenuConfigurator'
 WHERE module_id = @module_attendance
   AND action_url IN ('brWorkTimeReport', 'brGeofence', 'brAnnouncements', 'brAbsences', 'brTimesheets', 'brMyForms')
   AND menu_configurator IS NULL;

-- ----------------------------------------------------------------------------
-- Conferencia:
--   SELECT m.id, m.menu_title, m.parent_id, m.level, s.action_url
--     FROM ohrm_menu_item m JOIN ohrm_screen s ON s.id = m.screen_id
--    WHERE s.action_url = 'brForms';
--   SELECT action_url, menu_configurator FROM ohrm_screen WHERE action_url LIKE 'br%';
-- Depois de aplicar, limpar src/cache/orangehrm (o menu e cacheado).
-- ----------------------------------------------------------------------------
