-- ============================================================================
-- OrangeHRM BR - Strings pt_BR dos formularios (provas e pesquisas)
-- Migracao i18n 014: telas do RH, "Meus formularios" e a aba "Provas" do mobile.
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> orangehrm < 014_forms_i18n.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- Gerado a partir da tabela da Task 11 em
-- docs/superpowers/plans/2026-09-27-formularios.md -- mudar la e aqui juntos.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT t.unit_id, 17, t.value, NULL FROM (
  SELECT 'form_forms' AS unit_id, 'Forms' AS value UNION ALL
  SELECT 'form_my_forms', 'My forms' UNION ALL
  SELECT 'form_tab', 'Tests' UNION ALL
  SELECT 'form_new', 'New form' UNION ALL
  SELECT 'form_templates', 'Templates' UNION ALL
  SELECT 'form_use_template', 'Use template' UNION ALL
  SELECT 'form_kind_quiz', 'Test' UNION ALL
  SELECT 'form_kind_survey', 'Survey' UNION ALL
  SELECT 'form_anonymous', 'Anonymous' UNION ALL
  SELECT 'form_pass_percent', 'Pass mark (%)' UNION ALL
  SELECT 'form_due_at', 'Deadline' UNION ALL
  SELECT 'form_status_draft', 'Draft' UNION ALL
  SELECT 'form_status_published', 'Published' UNION ALL
  SELECT 'form_status_closed', 'Closed' UNION ALL
  SELECT 'form_add', 'Add' UNION ALL
  SELECT 'form_type_content', 'Content' UNION ALL
  SELECT 'form_type_single', 'Single choice' UNION ALL
  SELECT 'form_type_multiple', 'Multiple choice' UNION ALL
  SELECT 'form_type_short_text', 'Short text' UNION ALL
  SELECT 'form_type_long_text', 'Long text' UNION ALL
  SELECT 'form_type_scale', 'Scale 1–5' UNION ALL
  SELECT 'form_type_yes_no', 'Yes/No' UNION ALL
  SELECT 'form_prompt', 'Question' UNION ALL
  SELECT 'form_help_text', 'Help text' UNION ALL
  SELECT 'form_required', 'Required' UNION ALL
  SELECT 'form_points', 'Points' UNION ALL
  SELECT 'form_option', 'Option' UNION ALL
  SELECT 'form_add_option', 'Add option' UNION ALL
  SELECT 'form_correct', 'Correct' UNION ALL
  SELECT 'form_image', 'Image' UNION ALL
  SELECT 'form_youtube', 'YouTube link' UNION ALL
  SELECT 'form_youtube_invalid', 'Not a YouTube link' UNION ALL
  SELECT 'form_preview', 'Preview' UNION ALL
  SELECT 'form_save_draft', 'Save draft' UNION ALL
  SELECT 'form_publish', 'Publish' UNION ALL
  SELECT 'form_close', 'Close' UNION ALL
  SELECT 'form_duplicate', 'Duplicate' UNION ALL
  SELECT 'form_results', 'Results' UNION ALL
  SELECT 'form_responded', 'Responded' UNION ALL
  SELECT 'form_audience', 'Audience' UNION ALL
  SELECT 'form_not_responded', 'Not responded yet' UNION ALL
  SELECT 'form_average', 'Average' UNION ALL
  SELECT 'form_passed_percent', 'Passed' UNION ALL
  SELECT 'form_pending_review', 'Awaiting review' UNION ALL
  SELECT 'form_review', 'Review' UNION ALL
  SELECT 'form_grant_retake', 'Allow another attempt' UNION ALL
  SELECT 'form_export_csv', 'Export CSV' UNION ALL
  SELECT 'form_hidden_anonymous', 'Results appear after 3 responses, to protect anonymity.' UNION ALL
  SELECT 'form_pending', 'To answer' UNION ALL
  SELECT 'form_answered', 'Answered' UNION ALL
  SELECT 'form_no_pending', 'Nothing to answer' UNION ALL
  SELECT 'form_due_in', 'Due' UNION ALL
  SELECT 'form_answer', 'Answer' UNION ALL
  SELECT 'form_submit', 'Submit answers' UNION ALL
  SELECT 'form_submit_confirm', 'Once submitted, answers cannot be changed.' UNION ALL
  SELECT 'form_anonymous_banner', 'This survey is anonymous: your answers are not linked to your name.' UNION ALL
  SELECT 'form_result_passed', 'Passed' UNION ALL
  SELECT 'form_result_failed', 'Not passed' UNION ALL
  SELECT 'form_thanks', 'Thank you for answering' UNION ALL
  SELECT 'form_required_missing', 'Answer the required questions' UNION ALL
  SELECT 'form_draft_restored', 'Your previous answers were restored' UNION ALL
  SELECT 'form_watch_video', 'Watch video' UNION ALL
  SELECT 'form_yes', 'Yes' UNION ALL
  SELECT 'form_no', 'No'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_i18n_lang_string x WHERE x.unit_id = t.unit_id AND x.group_id = 17
);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'form_forms' THEN 'Formulários'
    WHEN 'form_my_forms' THEN 'Meus formulários'
    WHEN 'form_tab' THEN 'Provas'
    WHEN 'form_new' THEN 'Novo formulário'
    WHEN 'form_templates' THEN 'Modelos'
    WHEN 'form_use_template' THEN 'Usar modelo'
    WHEN 'form_kind_quiz' THEN 'Prova'
    WHEN 'form_kind_survey' THEN 'Pesquisa'
    WHEN 'form_anonymous' THEN 'Anônima'
    WHEN 'form_pass_percent' THEN 'Nota mínima (%)'
    WHEN 'form_due_at' THEN 'Prazo'
    WHEN 'form_status_draft' THEN 'Rascunho'
    WHEN 'form_status_published' THEN 'Publicado'
    WHEN 'form_status_closed' THEN 'Encerrado'
    WHEN 'form_add' THEN 'Adicionar'
    WHEN 'form_type_content' THEN 'Conteúdo'
    WHEN 'form_type_single' THEN 'Escolha única'
    WHEN 'form_type_multiple' THEN 'Múltipla escolha'
    WHEN 'form_type_short_text' THEN 'Texto curto'
    WHEN 'form_type_long_text' THEN 'Texto longo'
    WHEN 'form_type_scale' THEN 'Escala 1–5'
    WHEN 'form_type_yes_no' THEN 'Sim/Não'
    WHEN 'form_prompt' THEN 'Enunciado'
    WHEN 'form_help_text' THEN 'Texto de ajuda'
    WHEN 'form_required' THEN 'Obrigatória'
    WHEN 'form_points' THEN 'Pontos'
    WHEN 'form_option' THEN 'Opção'
    WHEN 'form_add_option' THEN 'Adicionar opção'
    WHEN 'form_correct' THEN 'Certa'
    WHEN 'form_image' THEN 'Imagem'
    WHEN 'form_youtube' THEN 'Link do YouTube'
    WHEN 'form_youtube_invalid' THEN 'Link do YouTube inválido'
    WHEN 'form_preview' THEN 'Pré-visualizar'
    WHEN 'form_save_draft' THEN 'Salvar rascunho'
    WHEN 'form_publish' THEN 'Publicar'
    WHEN 'form_close' THEN 'Encerrar'
    WHEN 'form_duplicate' THEN 'Duplicar'
    WHEN 'form_results' THEN 'Resultados'
    WHEN 'form_responded' THEN 'Responderam'
    WHEN 'form_audience' THEN 'Público'
    WHEN 'form_not_responded' THEN 'Ainda não responderam'
    WHEN 'form_average' THEN 'Média'
    WHEN 'form_passed_percent' THEN 'Aprovados'
    WHEN 'form_pending_review' THEN 'Aguardando correção'
    WHEN 'form_review' THEN 'Corrigir'
    WHEN 'form_grant_retake' THEN 'Liberar nova tentativa'
    WHEN 'form_export_csv' THEN 'Exportar CSV'
    WHEN 'form_hidden_anonymous' THEN 'Os resultados aparecem a partir de 3 respostas, para proteger o anonimato.'
    WHEN 'form_pending' THEN 'Para responder'
    WHEN 'form_answered' THEN 'Respondidos'
    WHEN 'form_no_pending' THEN 'Nada para responder'
    WHEN 'form_due_in' THEN 'Vence'
    WHEN 'form_answer' THEN 'Responder'
    WHEN 'form_submit' THEN 'Enviar respostas'
    WHEN 'form_submit_confirm' THEN 'Depois de enviado não é possível alterar.'
    WHEN 'form_anonymous_banner' THEN 'Esta pesquisa é anônima: suas respostas não ficam ligadas ao seu nome.'
    WHEN 'form_result_passed' THEN 'Aprovado'
    WHEN 'form_result_failed' THEN 'Não aprovado'
    WHEN 'form_thanks' THEN 'Obrigado por responder'
    WHEN 'form_required_missing' THEN 'Responda as questões obrigatórias'
    WHEN 'form_draft_restored' THEN 'Suas respostas anteriores foram recuperadas'
    WHEN 'form_watch_video' THEN 'Assistir ao vídeo'
    WHEN 'form_yes' THEN 'Sim'
    WHEN 'form_no' THEN 'Não'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17 AND ls.unit_id LIKE 'form\_%'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;
