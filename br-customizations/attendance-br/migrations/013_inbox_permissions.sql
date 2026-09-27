-- ============================================================================
-- OrangeHRM BR - Migracao 013: permissoes da caixa de entrada
-- ============================================================================
--
-- Corrige dois erros das migracoes 010 e 012:
--
-- 1. As quatro APIs novas foram registradas nas rotas mas nao em
--    ohrm_api_permission, entao o OrangeHRM recusava todo mundo com 403 --
--    inclusive o Admin. Era o "nao autorizado" que aparecia no mobile.
--
-- 2. As telas do RH foram liberadas para o papel 2 achando que era o
--    Supervisor. O papel 2 e o ESS -- todo funcionario. Qualquer um abria a
--    fila de justificativas (com permissao de alterar) e a lista de assinaturas.
--    O Supervisor e o papel 3.
--
-- Papeis: 1 = Admin, 2 = ESS (todo funcionario), 3 = Supervisor.
-- Os papeis se somam: um Admin com cadastro de funcionario tambem e ESS.
--
-- A permissao do OrangeHRM e por API e por verbo, nao por parametro. As APIs
-- servem tanto o funcionario (a propria caixa) quanto o RH (a fila de todos),
-- entao o ESS precisa de leitura -- e o recorte do "modo RH" e feito no codigo
-- (BrAccessScope): Admin ve todos, Supervisor ve a equipe, ESS nao ve a fila.
--
-- Idempotente.
-- ----------------------------------------------------------------------------

SET @module_attendance = (SELECT id FROM ohrm_module WHERE `name` = 'attendance');

-- ----------------------------------------------------------------------------
-- 1. Data groups e vinculo com as APIs
-- ----------------------------------------------------------------------------
INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 'apiv2_attendance_br_announcement', 'API-v2 Attendance BR - Announcements', 1, 1, 0, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_announcement');

INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 'apiv2_attendance_br_announcement_receipt', 'API-v2 Attendance BR - Announcement Receipts', 0, 1, 0, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_announcement_receipt');

INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 'apiv2_attendance_br_absence', 'API-v2 Attendance BR - Absence Justifications', 1, 1, 1, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_absence');

INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 'apiv2_attendance_br_timesheet_signature', 'API-v2 Attendance BR - Timesheet Signature', 1, 1, 0, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_timesheet_signature');

SET @dg_announcement = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_announcement');
SET @dg_receipt = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_announcement_receipt');
SET @dg_absence = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_absence');
SET @dg_timesheet = (SELECT id FROM ohrm_data_group WHERE `name` = 'apiv2_attendance_br_timesheet_signature');

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, @dg_announcement, 'OrangeHRM\\Attendance\\Api\\AnnouncementAPI'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_api_permission WHERE api_name = 'OrangeHRM\\Attendance\\Api\\AnnouncementAPI');

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, @dg_receipt, 'OrangeHRM\\Attendance\\Api\\AnnouncementAckAPI'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_api_permission WHERE api_name = 'OrangeHRM\\Attendance\\Api\\AnnouncementAckAPI');

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, @dg_absence, 'OrangeHRM\\Attendance\\Api\\AbsenceJustificationAPI'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_api_permission WHERE api_name = 'OrangeHRM\\Attendance\\Api\\AbsenceJustificationAPI');

INSERT INTO ohrm_api_permission (`module_id`, `data_group_id`, `api_name`)
SELECT @module_attendance, @dg_timesheet, 'OrangeHRM\\Attendance\\Api\\TimesheetSignatureAPI'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_api_permission WHERE api_name = 'OrangeHRM\\Attendance\\Api\\TimesheetSignatureAPI');

-- ----------------------------------------------------------------------------
-- 2. Quem pode o que
--    (role, data group, read, create, update, delete)
-- ----------------------------------------------------------------------------
INSERT INTO ohrm_user_role_data_group (`user_role_id`, `data_group_id`, `can_read`, `can_create`, `can_update`, `can_delete`, `self`)
SELECT t.role_id, t.dg, t.r, t.c, t.u, 0, 0
FROM (
    -- Avisos: todos leem a propria caixa; so o Admin publica
    SELECT 1 AS role_id, @dg_announcement AS dg, 1 AS r, 1 AS c, 0 AS u UNION ALL
    SELECT 2, @dg_announcement, 1, 0, 0 UNION ALL
    SELECT 3, @dg_announcement, 1, 0, 0 UNION ALL
    -- Leitura e ciencia: todos registram a propria
    SELECT 1, @dg_receipt, 0, 1, 0 UNION ALL
    SELECT 2, @dg_receipt, 0, 1, 0 UNION ALL
    SELECT 3, @dg_receipt, 0, 1, 0 UNION ALL
    -- Faltas: todos enviam e leem as proprias; so Admin e Supervisor decidem
    SELECT 1, @dg_absence, 1, 1, 1 UNION ALL
    SELECT 2, @dg_absence, 1, 1, 0 UNION ALL
    SELECT 3, @dg_absence, 1, 1, 1 UNION ALL
    -- Folha: todos leem e assinam a propria
    SELECT 1, @dg_timesheet, 1, 1, 0 UNION ALL
    SELECT 2, @dg_timesheet, 1, 1, 0 UNION ALL
    SELECT 3, @dg_timesheet, 1, 1, 0
) AS t
WHERE NOT EXISTS (
    SELECT 1 FROM ohrm_user_role_data_group x
    WHERE x.user_role_id = t.role_id AND x.data_group_id = t.dg
);

-- ----------------------------------------------------------------------------
-- 3. Telas do RH: tira o ESS, poe o Supervisor
-- ----------------------------------------------------------------------------
SET @screen_absences = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brAbsences');
SET @screen_timesheets = (SELECT id FROM ohrm_screen WHERE module_id = @module_attendance AND action_url = 'brTimesheets');

DELETE FROM ohrm_user_role_screen
 WHERE user_role_id = 2 AND screen_id IN (@screen_absences, @screen_timesheets);

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 3, @screen_absences, 1, 0, 1, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 3 AND screen_id = @screen_absences);

INSERT INTO ohrm_user_role_screen (`user_role_id`, `screen_id`, `can_read`, `can_create`, `can_update`, `can_delete`)
SELECT 3, @screen_timesheets, 1, 0, 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_user_role_screen WHERE user_role_id = 3 AND screen_id = @screen_timesheets);

-- ----------------------------------------------------------------------------
-- Conferencia:
--   SELECT ap.api_name, r.name, g.can_read, g.can_create, g.can_update
--     FROM ohrm_api_permission ap
--     JOIN ohrm_user_role_data_group g ON g.data_group_id = ap.data_group_id
--     JOIN ohrm_user_role r ON r.id = g.user_role_id
--    WHERE ap.api_name LIKE '%Announcement%' OR ap.api_name LIKE '%Absence%'
--       OR ap.api_name LIKE '%Timesheet%';
-- ----------------------------------------------------------------------------
