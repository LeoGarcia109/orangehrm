-- ============================================================================
-- OrangeHRM BR - Migracao i18n 015: traducoes pt_BR deslocadas em uma posicao
-- ============================================================================
-- O import original (pt_br_translations.sql) gravava por lang_string_id fixo.
-- Na lista dele faltava a traducao de "Size" e "Em Breve" aparecia duas vezes,
-- entao do id 203 ao 333 (grupo general) cada valor caiu na string anterior:
-- "Date" virou "Segunda-feira", "Back" virou "Confirmar", "No, Cancel" virou
-- "Gestao de Usuarios", "(Deleted)" virou "Adicionar Comentario" etc.
--
-- Casa por (ohrm_i18n_group.name, unit_id), nunca por id numerico.
-- So troca a traducao que ainda tem EXATAMENTE o valor errado deixado pelo
-- import: traducao editada na tela ou por outra migracao fica como esta.
-- Idempotente: na segunda execucao nenhum valor errado sobra para trocar.
--
-- Uso: mysql -u<user> -p<senha> --default-character-set=utf8mb4 orangehrm \
--        < 015_fix_shifted_pt_br_translations.sql
-- ============================================================================

SET NAMES utf8mb4;
SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

DROP TEMPORARY TABLE IF EXISTS tmp_br_i18n_015;
CREATE TEMPORARY TABLE tmp_br_i18n_015 (
  group_name  VARCHAR(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  unit_id     VARCHAR(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  wrong_value TEXT CHARACTER SET utf8mb4 NOT NULL,
  right_value TEXT CHARACTER SET utf8mb4 NOT NULL
);

-- (grupo, unit_id, valor errado deixado pelo import, traducao certa)
INSERT INTO tmp_br_i18n_015 (group_name, unit_id, wrong_value, right_value) VALUES
  ('general', 'size', 'Aceita jpg, .png, .gif até 1MB. Dimensões recomendadas: 200px X 200px', 'Tamanho'),
  ('general', 'accept_jpg_png_upto_1mb_recomended_dimentions_200px_x_200px', 'A data inicial deve ser anterior à data final', 'Aceita jpg, .png, .gif até 1MB. Dimensões recomendadas: 200px X 200px'),
  ('general', 'from_date_should_be_before_to_date', 'Deve ser menor que o limite superior', 'A data inicial deve ser anterior à data final'),
  ('general', 'should_be_less_than_upper_bound', 'Incluir Cabeçalho', 'Deve ser menor que o limite superior'),
  ('general', 'include_header', 'Adicionar Anexo', 'Incluir Cabeçalho'),
  ('general', 'add_attachment', 'Digite aqui', 'Adicionar Anexo'),
  ('general', 'type_here', 'Data', 'Digite aqui'),
  ('general', 'date', 'Segunda-feira', 'Data'),
  ('general', 'monday', 'Terça-feira', 'Segunda-feira'),
  ('general', 'tuesday', 'Quarta-feira', 'Terça-feira'),
  ('general', 'wednesday', 'Quinta-feira', 'Quarta-feira'),
  ('general', 'thursday', 'Sexta-feira', 'Quinta-feira'),
  ('general', 'friday', 'Sábado', 'Sexta-feira'),
  ('general', 'saturday', 'Domingo', 'Sábado'),
  ('general', 'sunday', 'Funcionário', 'Domingo'),
  ('general', 'employee', 'Gerar', 'Funcionário'),
  ('general', 'generate', 'Data Inicial', 'Gerar'),
  ('general', 'from_date', 'Data Final', 'Data Inicial'),
  ('general', 'to_date', 'Duração', 'Data Final'),
  ('general', 'duration', 'Aplicar', 'Duração'),
  ('general', 'apply', 'Voltar', 'Aplicar'),
  ('general', 'back', 'Confirmar', 'Voltar'),
  ('general', 'confirm', 'Nenhum funcionário encontrado', 'Confirmar'),
  ('general', 'no_matching_employees', 'Nenhum funcionário corresponde aos filtros selecionados', 'Nenhum funcionário encontrado'),
  ('general', 'no_employees_match_filters', 'Ok', 'Nenhum funcionário corresponde aos filtros selecionados'),
  ('general', 'ok', 'Folga', 'Ok'),
  ('general', 'leave', 'Comentário aqui', 'Folga'),
  ('general', 'comment_here', 'A hora de início deve ser anterior à hora de término', 'Comentário aqui'),
  ('general', 'from_time_should_be_before_to_time', ' (Excluído)', 'A hora de início deve ser anterior à hora de término'),
  ('general', 'deleted', 'Adicionar Comentário', ' (Excluído)'),
  ('general', 'add_comment', 'Aprovar', 'Adicionar Comentário'),
  ('general', 'approve', 'Rejeitar', 'Aprovar'),
  ('general', 'reject', '{count,plural, =0{Nenhum Registro Encontrado} one{(1) Registro Encontrado} other{ (#) Registros Encontrados}}', 'Rejeitar'),
  ('general', 'n_records_found', '{count,plural, =0{Nenhum Registro Selecionado} one{(1) Registro Selecionado} other{(#) Registros Selecionados}}', '{count,plural, =0{Nenhum Registro Encontrado} one{(1) Registro Encontrado} other{ (#) Registros Encontrados}}'),
  ('general', 'n_records_selected', 'Senha', '{count,plural, =0{Nenhum Registro Selecionado} one{(1) Registro Selecionado} other{(#) Registros Selecionados}}'),
  ('general', 'password', 'Para uma senha forte, use uma combinação difícil de adivinhar com letras maiúsculas e minúsculas, símbolos e números', 'Senha'),
  ('general', 'password_strength_message', 'Confirmar Senha', 'Para uma senha forte, use uma combinação difícil de adivinhar com letras maiúsculas e minúsculas, símbolos e números'),
  ('general', 'confirm_password', 'As senhas não coincidem', 'Confirmar Senha'),
  ('general', 'passwords_do_not_match', 'Fraca', 'As senhas não coincidem'),
  ('general', 'weak', 'Muito Fraca', 'Fraca'),
  ('general', 'very_weak', 'Melhor', 'Muito Fraca'),
  ('general', 'better', 'Muito Forte', 'Melhor'),
  ('general', 'strongest', 'Sobre', 'Muito Forte'),
  ('general', 'about', 'Suporte', 'Sobre'),
  ('general', 'support', 'Alterar Senha', 'Suporte'),
  ('general', 'change_password', 'Sair', 'Alterar Senha'),
  ('general', 'logout', 'Nenhum Registro Encontrado', 'Sair'),
  ('general', 'no_records_found', 'Alterar Senha?', 'Nenhum Registro Encontrado'),
  ('general', 'change_password_question', 'Digite a descrição aqui', 'Alterar Senha?'),
  ('general', 'type_description_here', 'Adicionar observação', 'Digite a descrição aqui'),
  ('general', 'add_note', 'Atualizado com Sucesso', 'Adicionar observação'),
  ('general', 'successfully_updated', 'Salvo com Sucesso', 'Atualizado com Sucesso'),
  ('general', 'successfully_saved', 'Observações', 'Salvo com Sucesso'),
  ('general', 'notes', 'Digite aqui ...', 'Observações'),
  ('general', 'type_here_message', 'Trabalho', 'Digite aqui ...'),
  ('general', 'job', 'Não, Cancelar', 'Trabalho'),
  ('general', 'no_cancel', 'Gestão de Usuários', 'Não, Cancelar'),
  ('general', 'user_management', 'Informações do Projeto', 'Gestão de Usuários'),
  ('general', 'project_info', 'Organização', 'Informações do Projeto'),
  ('general', 'organization', 'Usuários', 'Organização'),
  ('general', 'users', 'Configuração', 'Usuários'),
  ('general', 'configuration', 'Pacotes de Idioma', 'Configuração'),
  ('general', 'language_packages', 'Módulos', 'Pacotes de Idioma'),
  ('general', 'modules', 'Registrar Cliente OAuth', 'Módulos'),
  ('general', 'register_oauth_client', 'Autenticação por Redes Sociais', 'Registrar Cliente OAuth'),
  ('general', 'social_media_authentication', 'Lista de Funcionários', 'Autenticação por Redes Sociais'),
  ('general', 'employee_list', 'Relatórios', 'Lista de Funcionários'),
  ('general', 'reports', 'PIM', 'Relatórios'),
  ('general', 'pim', 'Recrutamento', 'PIM'),
  ('general', 'recruitment', 'Ponto', 'Recrutamento'),
  ('general', 'time', 'Minhas Informações', 'Ponto'),
  ('general', 'my_info', 'Avaliação de Desempenho', 'Minhas Informações'),
  ('general', 'performance', 'Painel', 'Avaliação de Desempenho'),
  ('general', 'dashboard', 'Diretório', 'Painel'),
  ('general', 'directory', 'Buzz', 'Diretório'),
  ('general', 'buzz', 'Manutenção', 'Buzz'),
  ('general', 'maintenance', 'Minhas Folhas de Ponto', 'Manutenção'),
  ('general', 'my_timesheets', 'Meus Registros', 'Minhas Folhas de Ponto'),
  ('general', 'my_records', 'Folhas de Ponto de Funcionários', 'Meus Registros'),
  ('general', 'employee_timesheets', 'Folhas de Ponto', 'Folhas de Ponto de Funcionários'),
  ('general', 'timesheets', 'Registrar Entrada/Saída', 'Folhas de Ponto'),
  ('general', 'punch_in_out', 'Registros de Funcionários', 'Registrar Entrada/Saída'),
  ('general', 'employee_records', 'Ponto', 'Registros de Funcionários'),
  ('general', 'attendance', 'Clientes', 'Ponto'),
  ('general', 'customers', 'Projetos', 'Clientes'),
  ('general', 'projects', 'Relatórios de Projetos', 'Projetos'),
  ('general', 'project_reports', 'Vagas', 'Relatórios de Projetos'),
  ('general', 'vacancies', 'Candidatos', 'Vagas'),
  ('general', 'candidates', 'KPIs', 'Candidatos'),
  ('general', 'kpis', 'Gerenciar Avaliações', 'KPIs'),
  ('general', 'manage_reviews', 'Minhas Avaliações', 'Gerenciar Avaliações'),
  ('general', 'my_reviews', 'Meus Acompanhamentos', 'Minhas Avaliações'),
  ('general', 'my_trackers', 'Lista de Avaliações', 'Meus Acompanhamentos'),
  ('general', 'review_list', 'Acompanhamentos', 'Lista de Avaliações'),
  ('general', 'trackers', 'Acompanhamentos de Funcionários', 'Acompanhamentos'),
  ('general', 'employee_trackers', 'Registros de Candidatos', 'Acompanhamentos de Funcionários'),
  ('general', 'candidate_records', 'Acessar Registros', 'Registros de Candidatos'),
  ('general', 'access_records', 'Expurgar Registros', 'Acessar Registros'),
  ('general', 'purge_records', 'Estrutura', 'Expurgar Registros'),
  ('general', 'structure', 'Dom', 'Estrutura'),
  ('general', 'sun', 'Seg', 'Dom'),
  ('general', 'mon', 'Ter', 'Seg'),
  ('general', 'tue', 'Qua', 'Ter'),
  ('general', 'wed', 'Qui', 'Qua'),
  ('general', 'thu', 'Sex', 'Qui'),
  ('general', 'fri', 'Sáb', 'Sex'),
  ('general', 'sat', 'Realizado Por', 'Sáb'),
  ('general', 'performed_by', 'Adicionar outro', 'Realizado Por'),
  ('general', 'add_another', 'Visualizar', 'Adicionar outro'),
  ('general', 'view', 'Resumo de Ponto', 'Visualizar'),
  ('general', 'attendance_summary', 'Minhas Folgas', 'Resumo de Ponto'),
  ('general', 'my_leave', 'Meus Direitos', 'Minhas Folgas'),
  ('general', 'my_entitlements', 'Adicionar Direitos', 'Meus Direitos'),
  ('general', 'add_entitlements', 'Configurar', 'Adicionar Direitos'),
  ('general', 'configure', 'Direitos de Funcionários', 'Configurar'),
  ('general', 'employee_entitlements', 'Direitos', 'Direitos de Funcionários'),
  ('general', 'entitlements', 'E-mail de Trabalho', 'Direitos'),
  ('general', 'work_email', 'Outro E-mail', 'E-mail de Trabalho'),
  ('general', 'other_email', 'Nome da Empresa', 'Outro E-mail'),
  ('general', 'company_name', 'Versão', 'Nome da Empresa'),
  ('general', 'version', 'Funcionários Ativos', 'Versão'),
  ('general', 'active_employees', 'Funcionários Demitidos', 'Funcionários Ativos'),
  ('general', 'employees_terminated', 'Tem Certeza?', 'Funcionários Demitidos'),
  ('general', 'are_you_sure', 'O registro selecionado será excluído permanentemente. Tem certeza de que deseja continuar?', 'Tem Certeza?'),
  ('general', 'delete_confirmation_message', 'Sim, Excluir', 'O registro selecionado será excluído permanentemente. Tem certeza de que deseja continuar?'),
  ('general', 'yes_delete', 'Manter Atual', 'Sim, Excluir'),
  ('general', 'keep_current', 'Excluir Atual', 'Manter Atual'),
  ('general', 'delete_current', 'Substituir Atual', 'Excluir Atual'),
  ('general', 'replace_current', 'Módulo Proibido', 'Substituir Atual'),
  ('general', 'module_forbidden', 'A página que você está tentando acessar tem acesso restrito', 'Módulo Proibido'),
  ('general', 'module_access_restriction', 'Em Breve', 'A página que você está tentando acessar tem acesso restrito');

UPDATE ohrm_i18n_translate t
JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
JOIN ohrm_i18n_group g ON g.id = ls.group_id
JOIN tmp_br_i18n_015 f ON f.group_name = g.name AND f.unit_id = ls.unit_id
SET t.value = f.right_value, t.modified_at = NOW()
WHERE t.language_id = @lang_pt_br
  AND BINARY t.value = BINARY f.wrong_value;

-- Instalacao sem a traducao: insere a certa.
INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br, f.right_value, 0, NOW()
FROM tmp_br_i18n_015 f
JOIN ohrm_i18n_group g ON g.name = f.group_name
JOIN ohrm_i18n_lang_string ls ON ls.group_id = g.id AND ls.unit_id = f.unit_id
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_i18n_translate t
  WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
);

DROP TEMPORARY TABLE tmp_br_i18n_015;

-- Invalida o cache de i18n (o frontend guarda as strings pelo modified_at)
UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;

-- ============================================================================
-- FIM DA MIGRACAO 015
-- ============================================================================
