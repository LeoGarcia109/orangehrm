-- ============================================================================
-- OrangeHRM BR - Strings pt_BR do perfil do cargo e da comparacao de aderencia
-- Migracao i18n 018: telas Perfis de cargo e Comparar perfis.
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> orangehrm < 018_job_fit_i18n.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- Gerado a partir da tabela da Task 6 em
-- docs/superpowers/plans/2026-09-27-aderencia-cargo.md -- mudar la e aqui juntos.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT t.unit_id, 17, t.value, NULL FROM (
  SELECT 'jobfit_title_profiles' AS unit_id, 'Job profiles' AS value UNION ALL
  SELECT 'jobfit_title_compare', 'Compare profiles' UNION ALL
  SELECT 'jobfit_job_title', 'Job title' UNION ALL
  SELECT 'jobfit_has_profile', 'Profile defined' UNION ALL
  SELECT 'jobfit_no_profile', 'No profile' UNION ALL
  SELECT 'jobfit_competencies', 'Competencies' UNION ALL
  SELECT 'jobfit_updated_at', 'Last change' UNION ALL
  SELECT 'jobfit_edit', 'Edit' UNION ALL
  SELECT 'jobfit_new_job_title', 'Add job title' UNION ALL
  SELECT 'jobfit_no_job_titles', 'No job titles yet. Job titles are created in Admin → Job → Job Titles.' UNION ALL
  SELECT 'jobfit_factors', 'Behavioural factors' UNION ALL
  SELECT 'jobfit_min', 'Min' UNION ALL
  SELECT 'jobfit_max', 'Max' UNION ALL
  SELECT 'jobfit_weight_0', 'Ignore' UNION ALL
  SELECT 'jobfit_weight_1', 'Desirable' UNION ALL
  SELECT 'jobfit_weight_2', 'Essential' UNION ALL
  SELECT 'jobfit_suggest', 'Suggest from employees' UNION ALL
  SELECT 'jobfit_suggest_hint', 'Pick the reference employees: each range becomes the mean ± 1 SD of their profiles. Nothing is saved until you save.' UNION ALL
  SELECT 'jobfit_apply', 'Apply' UNION ALL
  SELECT 'jobfit_applied', 'Suggested ranges applied. Review them and save.' UNION ALL
  SELECT 'jobfit_no_reference', 'Choose at least one employee.' UNION ALL
  SELECT 'jobfit_competency_name', 'Competency' UNION ALL
  SELECT 'jobfit_min_level', 'Minimum level' UNION ALL
  SELECT 'jobfit_add_competency', 'Add competency' UNION ALL
  SELECT 'jobfit_behavior_weight', 'Weight of the behavioural profile' UNION ALL
  SELECT 'jobfit_weight_split', '{b}% behavioural · {c}% competencies' UNION ALL
  SELECT 'jobfit_save', 'Save' UNION ALL
  SELECT 'jobfit_saved', 'Profile saved' UNION ALL
  SELECT 'jobfit_company', 'Company' UNION ALL
  SELECT 'jobfit_all_companies', 'All companies' UNION ALL
  SELECT 'jobfit_vacancy', 'Vacancy' UNION ALL
  SELECT 'jobfit_all_vacancies', 'All vacancies' UNION ALL
  SELECT 'jobfit_type_all', 'Candidates and employees' UNION ALL
  SELECT 'jobfit_type_c', 'Candidate' UNION ALL
  SELECT 'jobfit_type_e', 'Employee' UNION ALL
  SELECT 'jobfit_search_name', 'Search by name' UNION ALL
  SELECT 'jobfit_select_all', 'Select all' UNION ALL
  SELECT 'jobfit_clear', 'Clear' UNION ALL
  SELECT 'jobfit_selected', '{n} of {max} selected' UNION ALL
  SELECT 'jobfit_pick_job', 'Choose the job title' UNION ALL
  SELECT 'jobfit_pick_people', 'Choose who to compare' UNION ALL
  SELECT 'jobfit_define_first', 'This job title has no profile yet. Define it before comparing.' UNION ALL
  SELECT 'jobfit_define_profile', 'Define profile' UNION ALL
  SELECT 'jobfit_no_people', 'Nobody with a completed profile test matches the filters.' UNION ALL
  SELECT 'jobfit_tab_radar', 'Radar' UNION ALL
  SELECT 'jobfit_tab_table', 'Ranking and factors' UNION ALL
  SELECT 'jobfit_tab_duel', 'Duel' UNION ALL
  SELECT 'jobfit_overall', 'Fit' UNION ALL
  SELECT 'jobfit_behavior', 'Behavioural' UNION ALL
  SELECT 'jobfit_partial', 'partial' UNION ALL
  SELECT 'jobfit_partial_hint', 'Some competencies have no rating yet' UNION ALL
  SELECT 'jobfit_alert_factor', 'Outside the range in an essential factor' UNION ALL
  SELECT 'jobfit_alert_competency', 'Below the minimum in an essential competency' UNION ALL
  SELECT 'jobfit_show_in_chart', 'Show in chart' UNION ALL
  SELECT 'jobfit_chart_limit', 'The chart shows up to 6 people at a time.' UNION ALL
  SELECT 'jobfit_job_range', 'Job range' UNION ALL
  SELECT 'jobfit_closer', 'Closer to the job' UNION ALL
  SELECT 'jobfit_essential_mark', '★ essential' UNION ALL
  SELECT 'jobfit_color_in', 'Inside the range' UNION ALL
  SELECT 'jobfit_color_near', 'Up to 10 points outside' UNION ALL
  SELECT 'jobfit_color_far', 'More than 10 points outside' UNION ALL
  SELECT 'jobfit_rating', 'Rating' UNION ALL
  SELECT 'jobfit_no_rating', 'No rating' UNION ALL
  SELECT 'jobfit_duel_pick', 'Choose two people' UNION ALL
  SELECT 'jobfit_comparison', 'Comparison' UNION ALL
  SELECT 'jobfit_compare_selected', 'Compare selected' UNION ALL
  SELECT 'jobfit_factor', 'Factor' UNION ALL
  SELECT 'jobfit_person', 'Person'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_i18n_lang_string x WHERE x.unit_id = t.unit_id AND x.group_id = 17
);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'jobfit_title_profiles' THEN 'Perfis de cargo'
    WHEN 'jobfit_title_compare' THEN 'Comparar perfis'
    WHEN 'jobfit_job_title' THEN 'Cargo'
    WHEN 'jobfit_has_profile' THEN 'Perfil definido'
    WHEN 'jobfit_no_profile' THEN 'Sem perfil'
    WHEN 'jobfit_competencies' THEN 'Competências'
    WHEN 'jobfit_updated_at' THEN 'Última alteração'
    WHEN 'jobfit_edit' THEN 'Editar'
    WHEN 'jobfit_new_job_title' THEN 'Cadastrar cargo'
    WHEN 'jobfit_no_job_titles' THEN 'Nenhum cargo cadastrado. Os cargos são criados em Admin → Trabalho → Cargos.'
    WHEN 'jobfit_factors' THEN 'Fatores comportamentais'
    WHEN 'jobfit_min' THEN 'Mín.'
    WHEN 'jobfit_max' THEN 'Máx.'
    WHEN 'jobfit_weight_0' THEN 'Ignorar'
    WHEN 'jobfit_weight_1' THEN 'Desejável'
    WHEN 'jobfit_weight_2' THEN 'Essencial'
    WHEN 'jobfit_suggest' THEN 'Sugerir a partir de funcionários'
    WHEN 'jobfit_suggest_hint' THEN 'Escolha os funcionários de referência: cada faixa vira a média ± 1 DP dos perfis deles. Nada é salvo até você salvar.'
    WHEN 'jobfit_apply' THEN 'Aplicar'
    WHEN 'jobfit_applied' THEN 'Faixas sugeridas aplicadas. Revise e salve.'
    WHEN 'jobfit_no_reference' THEN 'Escolha ao menos um funcionário.'
    WHEN 'jobfit_competency_name' THEN 'Competência'
    WHEN 'jobfit_min_level' THEN 'Nível mínimo'
    WHEN 'jobfit_add_competency' THEN 'Adicionar competência'
    WHEN 'jobfit_behavior_weight' THEN 'Peso do perfil comportamental'
    WHEN 'jobfit_weight_split' THEN '{b}% comportamental · {c}% competências'
    WHEN 'jobfit_save' THEN 'Salvar'
    WHEN 'jobfit_saved' THEN 'Perfil salvo'
    WHEN 'jobfit_company' THEN 'Empresa'
    WHEN 'jobfit_all_companies' THEN 'Todas as empresas'
    WHEN 'jobfit_vacancy' THEN 'Vaga'
    WHEN 'jobfit_all_vacancies' THEN 'Todas as vagas'
    WHEN 'jobfit_type_all' THEN 'Candidatos e funcionários'
    WHEN 'jobfit_type_c' THEN 'Candidato'
    WHEN 'jobfit_type_e' THEN 'Funcionário'
    WHEN 'jobfit_search_name' THEN 'Buscar por nome'
    WHEN 'jobfit_select_all' THEN 'Marcar todos'
    WHEN 'jobfit_clear' THEN 'Limpar'
    WHEN 'jobfit_selected' THEN '{n} de {max} selecionados'
    WHEN 'jobfit_pick_job' THEN 'Escolha o cargo'
    WHEN 'jobfit_pick_people' THEN 'Escolha quem comparar'
    WHEN 'jobfit_define_first' THEN 'Este cargo ainda não tem perfil. Defina o perfil antes de comparar.'
    WHEN 'jobfit_define_profile' THEN 'Definir perfil'
    WHEN 'jobfit_no_people' THEN 'Ninguém com teste de perfil concluído atende aos filtros.'
    WHEN 'jobfit_tab_radar' THEN 'Radar'
    WHEN 'jobfit_tab_table' THEN 'Ranking e fatores'
    WHEN 'jobfit_tab_duel' THEN 'Duelo'
    WHEN 'jobfit_overall' THEN 'Aderência'
    WHEN 'jobfit_behavior' THEN 'Comportamental'
    WHEN 'jobfit_partial' THEN 'parcial'
    WHEN 'jobfit_partial_hint' THEN 'Há competências sem nota'
    WHEN 'jobfit_alert_factor' THEN 'Fora da faixa em fator essencial'
    WHEN 'jobfit_alert_competency' THEN 'Abaixo do mínimo em competência essencial'
    WHEN 'jobfit_show_in_chart' THEN 'Mostrar no gráfico'
    WHEN 'jobfit_chart_limit' THEN 'O gráfico mostra até 6 pessoas por vez.'
    WHEN 'jobfit_job_range' THEN 'Faixa do cargo'
    WHEN 'jobfit_closer' THEN 'Mais perto do cargo'
    WHEN 'jobfit_essential_mark' THEN '★ essencial'
    WHEN 'jobfit_color_in' THEN 'Dentro da faixa'
    WHEN 'jobfit_color_near' THEN 'Até 10 pontos fora'
    WHEN 'jobfit_color_far' THEN 'Mais de 10 pontos fora'
    WHEN 'jobfit_rating' THEN 'Nota'
    WHEN 'jobfit_no_rating' THEN 'Sem nota'
    WHEN 'jobfit_duel_pick' THEN 'Escolha duas pessoas'
    WHEN 'jobfit_comparison' THEN 'Comparação'
    WHEN 'jobfit_compare_selected' THEN 'Comparar selecionados'
    WHEN 'jobfit_factor' THEN 'Fator'
    WHEN 'jobfit_person' THEN 'Pessoa'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17 AND ls.unit_id LIKE 'jobfit\_%'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );


UPDATE ohrm_i18n_lang_string ls SET ls.value = CASE ls.unit_id
    WHEN 'jobfit_title_profiles' THEN 'Job profiles'
    WHEN 'jobfit_title_compare' THEN 'Compare profiles'
    WHEN 'jobfit_job_title' THEN 'Job title'
    WHEN 'jobfit_has_profile' THEN 'Profile defined'
    WHEN 'jobfit_no_profile' THEN 'No profile'
    WHEN 'jobfit_competencies' THEN 'Competencies'
    WHEN 'jobfit_updated_at' THEN 'Last change'
    WHEN 'jobfit_edit' THEN 'Edit'
    WHEN 'jobfit_new_job_title' THEN 'Add job title'
    WHEN 'jobfit_no_job_titles' THEN 'No job titles yet. Job titles are created in Admin → Job → Job Titles.'
    WHEN 'jobfit_factors' THEN 'Behavioural factors'
    WHEN 'jobfit_min' THEN 'Min'
    WHEN 'jobfit_max' THEN 'Max'
    WHEN 'jobfit_weight_0' THEN 'Ignore'
    WHEN 'jobfit_weight_1' THEN 'Desirable'
    WHEN 'jobfit_weight_2' THEN 'Essential'
    WHEN 'jobfit_suggest' THEN 'Suggest from employees'
    WHEN 'jobfit_suggest_hint' THEN 'Pick the reference employees: each range becomes the mean ± 1 SD of their profiles. Nothing is saved until you save.'
    WHEN 'jobfit_apply' THEN 'Apply'
    WHEN 'jobfit_applied' THEN 'Suggested ranges applied. Review them and save.'
    WHEN 'jobfit_no_reference' THEN 'Choose at least one employee.'
    WHEN 'jobfit_competency_name' THEN 'Competency'
    WHEN 'jobfit_min_level' THEN 'Minimum level'
    WHEN 'jobfit_add_competency' THEN 'Add competency'
    WHEN 'jobfit_behavior_weight' THEN 'Weight of the behavioural profile'
    WHEN 'jobfit_weight_split' THEN '{b}% behavioural · {c}% competencies'
    WHEN 'jobfit_save' THEN 'Save'
    WHEN 'jobfit_saved' THEN 'Profile saved'
    WHEN 'jobfit_company' THEN 'Company'
    WHEN 'jobfit_all_companies' THEN 'All companies'
    WHEN 'jobfit_vacancy' THEN 'Vacancy'
    WHEN 'jobfit_all_vacancies' THEN 'All vacancies'
    WHEN 'jobfit_type_all' THEN 'Candidates and employees'
    WHEN 'jobfit_type_c' THEN 'Candidate'
    WHEN 'jobfit_type_e' THEN 'Employee'
    WHEN 'jobfit_search_name' THEN 'Search by name'
    WHEN 'jobfit_select_all' THEN 'Select all'
    WHEN 'jobfit_clear' THEN 'Clear'
    WHEN 'jobfit_selected' THEN '{n} of {max} selected'
    WHEN 'jobfit_pick_job' THEN 'Choose the job title'
    WHEN 'jobfit_pick_people' THEN 'Choose who to compare'
    WHEN 'jobfit_define_first' THEN 'This job title has no profile yet. Define it before comparing.'
    WHEN 'jobfit_define_profile' THEN 'Define profile'
    WHEN 'jobfit_no_people' THEN 'Nobody with a completed profile test matches the filters.'
    WHEN 'jobfit_tab_radar' THEN 'Radar'
    WHEN 'jobfit_tab_table' THEN 'Ranking and factors'
    WHEN 'jobfit_tab_duel' THEN 'Duel'
    WHEN 'jobfit_overall' THEN 'Fit'
    WHEN 'jobfit_behavior' THEN 'Behavioural'
    WHEN 'jobfit_partial' THEN 'partial'
    WHEN 'jobfit_partial_hint' THEN 'Some competencies have no rating yet'
    WHEN 'jobfit_alert_factor' THEN 'Outside the range in an essential factor'
    WHEN 'jobfit_alert_competency' THEN 'Below the minimum in an essential competency'
    WHEN 'jobfit_show_in_chart' THEN 'Show in chart'
    WHEN 'jobfit_chart_limit' THEN 'The chart shows up to 6 people at a time.'
    WHEN 'jobfit_job_range' THEN 'Job range'
    WHEN 'jobfit_closer' THEN 'Closer to the job'
    WHEN 'jobfit_essential_mark' THEN '★ essential'
    WHEN 'jobfit_color_in' THEN 'Inside the range'
    WHEN 'jobfit_color_near' THEN 'Up to 10 points outside'
    WHEN 'jobfit_color_far' THEN 'More than 10 points outside'
    WHEN 'jobfit_rating' THEN 'Rating'
    WHEN 'jobfit_no_rating' THEN 'No rating'
    WHEN 'jobfit_duel_pick' THEN 'Choose two people'
    WHEN 'jobfit_comparison' THEN 'Comparison'
    WHEN 'jobfit_compare_selected' THEN 'Compare selected'
    WHEN 'jobfit_factor' THEN 'Factor'
    WHEN 'jobfit_person' THEN 'Person'
  ELSE ls.value END
WHERE ls.group_id = 17 AND ls.unit_id LIKE 'jobfit\_%';

UPDATE ohrm_i18n_translate t JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = CASE ls.unit_id
    WHEN 'jobfit_title_profiles' THEN 'Perfis de cargo'
    WHEN 'jobfit_title_compare' THEN 'Comparar perfis'
    WHEN 'jobfit_job_title' THEN 'Cargo'
    WHEN 'jobfit_has_profile' THEN 'Perfil definido'
    WHEN 'jobfit_no_profile' THEN 'Sem perfil'
    WHEN 'jobfit_competencies' THEN 'Competências'
    WHEN 'jobfit_updated_at' THEN 'Última alteração'
    WHEN 'jobfit_edit' THEN 'Editar'
    WHEN 'jobfit_new_job_title' THEN 'Cadastrar cargo'
    WHEN 'jobfit_no_job_titles' THEN 'Nenhum cargo cadastrado. Os cargos são criados em Admin → Trabalho → Cargos.'
    WHEN 'jobfit_factors' THEN 'Fatores comportamentais'
    WHEN 'jobfit_min' THEN 'Mín.'
    WHEN 'jobfit_max' THEN 'Máx.'
    WHEN 'jobfit_weight_0' THEN 'Ignorar'
    WHEN 'jobfit_weight_1' THEN 'Desejável'
    WHEN 'jobfit_weight_2' THEN 'Essencial'
    WHEN 'jobfit_suggest' THEN 'Sugerir a partir de funcionários'
    WHEN 'jobfit_suggest_hint' THEN 'Escolha os funcionários de referência: cada faixa vira a média ± 1 DP dos perfis deles. Nada é salvo até você salvar.'
    WHEN 'jobfit_apply' THEN 'Aplicar'
    WHEN 'jobfit_applied' THEN 'Faixas sugeridas aplicadas. Revise e salve.'
    WHEN 'jobfit_no_reference' THEN 'Escolha ao menos um funcionário.'
    WHEN 'jobfit_competency_name' THEN 'Competência'
    WHEN 'jobfit_min_level' THEN 'Nível mínimo'
    WHEN 'jobfit_add_competency' THEN 'Adicionar competência'
    WHEN 'jobfit_behavior_weight' THEN 'Peso do perfil comportamental'
    WHEN 'jobfit_weight_split' THEN '{b}% comportamental · {c}% competências'
    WHEN 'jobfit_save' THEN 'Salvar'
    WHEN 'jobfit_saved' THEN 'Perfil salvo'
    WHEN 'jobfit_company' THEN 'Empresa'
    WHEN 'jobfit_all_companies' THEN 'Todas as empresas'
    WHEN 'jobfit_vacancy' THEN 'Vaga'
    WHEN 'jobfit_all_vacancies' THEN 'Todas as vagas'
    WHEN 'jobfit_type_all' THEN 'Candidatos e funcionários'
    WHEN 'jobfit_type_c' THEN 'Candidato'
    WHEN 'jobfit_type_e' THEN 'Funcionário'
    WHEN 'jobfit_search_name' THEN 'Buscar por nome'
    WHEN 'jobfit_select_all' THEN 'Marcar todos'
    WHEN 'jobfit_clear' THEN 'Limpar'
    WHEN 'jobfit_selected' THEN '{n} de {max} selecionados'
    WHEN 'jobfit_pick_job' THEN 'Escolha o cargo'
    WHEN 'jobfit_pick_people' THEN 'Escolha quem comparar'
    WHEN 'jobfit_define_first' THEN 'Este cargo ainda não tem perfil. Defina o perfil antes de comparar.'
    WHEN 'jobfit_define_profile' THEN 'Definir perfil'
    WHEN 'jobfit_no_people' THEN 'Ninguém com teste de perfil concluído atende aos filtros.'
    WHEN 'jobfit_tab_radar' THEN 'Radar'
    WHEN 'jobfit_tab_table' THEN 'Ranking e fatores'
    WHEN 'jobfit_tab_duel' THEN 'Duelo'
    WHEN 'jobfit_overall' THEN 'Aderência'
    WHEN 'jobfit_behavior' THEN 'Comportamental'
    WHEN 'jobfit_partial' THEN 'parcial'
    WHEN 'jobfit_partial_hint' THEN 'Há competências sem nota'
    WHEN 'jobfit_alert_factor' THEN 'Fora da faixa em fator essencial'
    WHEN 'jobfit_alert_competency' THEN 'Abaixo do mínimo em competência essencial'
    WHEN 'jobfit_show_in_chart' THEN 'Mostrar no gráfico'
    WHEN 'jobfit_chart_limit' THEN 'O gráfico mostra até 6 pessoas por vez.'
    WHEN 'jobfit_job_range' THEN 'Faixa do cargo'
    WHEN 'jobfit_closer' THEN 'Mais perto do cargo'
    WHEN 'jobfit_essential_mark' THEN '★ essencial'
    WHEN 'jobfit_color_in' THEN 'Dentro da faixa'
    WHEN 'jobfit_color_near' THEN 'Até 10 pontos fora'
    WHEN 'jobfit_color_far' THEN 'Mais de 10 pontos fora'
    WHEN 'jobfit_rating' THEN 'Nota'
    WHEN 'jobfit_no_rating' THEN 'Sem nota'
    WHEN 'jobfit_duel_pick' THEN 'Escolha duas pessoas'
    WHEN 'jobfit_comparison' THEN 'Comparação'
    WHEN 'jobfit_compare_selected' THEN 'Comparar selecionados'
    WHEN 'jobfit_factor' THEN 'Fator'
    WHEN 'jobfit_person' THEN 'Pessoa'
  ELSE t.value END
WHERE t.language_id = @lang_pt_br AND ls.group_id = 17 AND ls.unit_id LIKE 'jobfit\_%';

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;
