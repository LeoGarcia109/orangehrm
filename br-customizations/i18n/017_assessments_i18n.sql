-- ============================================================================
-- OrangeHRM BR - Strings pt_BR do perfil comportamental
-- Migracao i18n 017: pagina do candidato, aba Provas e telas do RH.
-- ============================================================================
-- Uso: mysql -u<user> -p<senha> orangehrm < 017_assessments_i18n.sql
-- Idempotente: pode ser reexecutada com seguranca.
-- Gerado a partir da tabela da Task 8 em
-- docs/superpowers/plans/2026-09-27-perfil-comportamental.md -- mudar la e aqui juntos.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO ohrm_i18n_lang_string (unit_id, group_id, value, version)
SELECT t.unit_id, 17, t.value, NULL FROM (
  SELECT 'assessment_title' AS unit_id, 'Behavioral profile' AS value UNION ALL
  SELECT 'assessment_profiles', 'Behavioral profiles' UNION ALL
  SELECT 'assessment_welcome', 'Hello' UNION ALL
  SELECT 'assessment_intro', 'It takes about 15 minutes. There are no right or wrong answers: answer as you are, not as you think you should be.' UNION ALL
  SELECT 'assessment_vacancy', 'Vacancy' UNION ALL
  SELECT 'assessment_applied', 'Application sent! Next step: a short behavioral questionnaire.' UNION ALL
  SELECT 'assessment_consent_title', 'Your data' UNION ALL
  SELECT 'assessment_consent_text', 'Your answers are used only in this selection process and in the company''s candidate pool, and only HR can see them. They are kept while you allow it; to ask for deletion, contact HR. This is a behavioral profile test.' UNION ALL
  SELECT 'assessment_consent_check', 'I have read and I agree' UNION ALL
  SELECT 'assessment_employee_notice', 'HR uses this profile to better understand your working style and support your development. There are no right or wrong answers.' UNION ALL
  SELECT 'assessment_start', 'Start' UNION ALL
  SELECT 'assessment_continue', 'Continue' UNION ALL
  SELECT 'assessment_back', 'Back' UNION ALL
  SELECT 'assessment_finish', 'Finish' UNION ALL
  SELECT 'assessment_part', 'Part' UNION ALL
  SELECT 'assessment_of', 'of' UNION ALL
  SELECT 'assessment_scale_1', 'Doesn''t describe me at all' UNION ALL
  SELECT 'assessment_scale_2', 'Describes me a little' UNION ALL
  SELECT 'assessment_scale_3', 'Somewhat' UNION ALL
  SELECT 'assessment_scale_4', 'Describes me well' UNION ALL
  SELECT 'assessment_scale_5', 'Describes me very well' UNION ALL
  SELECT 'assessment_answer_all', 'Answer every statement on this page' UNION ALL
  SELECT 'assessment_thanks', 'Thank you! Your answers were recorded.' UNION ALL
  SELECT 'assessment_inactive', 'This link is no longer active. Please contact HR.' UNION ALL
  SELECT 'assessment_new', 'New invite' UNION ALL
  SELECT 'assessment_candidate', 'Candidate' UNION ALL
  SELECT 'assessment_employees', 'Employees' UNION ALL
  SELECT 'assessment_copy_link', 'Copy link' UNION ALL
  SELECT 'assessment_copied', 'Link copied' UNION ALL
  SELECT 'assessment_whatsapp', 'Send via WhatsApp' UNION ALL
  SELECT 'assessment_whatsapp_message', 'Hi {name}! To continue in the selection process, please answer this questionnaire (about 15 minutes): {link}' UNION ALL
  SELECT 'assessment_invite_created', 'Invite created. Send the link to the candidate:' UNION ALL
  SELECT 'assessment_employees_invited', 'Invites created:' UNION ALL
  SELECT 'assessment_resend', 'New link' UNION ALL
  SELECT 'assessment_cancel', 'Cancel invite' UNION ALL
  SELECT 'assessment_cancel_confirm', 'Cancel this invite? The link will stop working.' UNION ALL
  SELECT 'assessment_view', 'View profile' UNION ALL
  SELECT 'assessment_download_pdf', 'Download PDF' UNION ALL
  SELECT 'assessment_status_pending', 'Pending' UNION ALL
  SELECT 'assessment_status_completed', 'Answered' UNION ALL
  SELECT 'assessment_status_expired', 'Expired' UNION ALL
  SELECT 'assessment_status_cancelled', 'Cancelled' UNION ALL
  SELECT 'assessment_sent_at', 'Sent' UNION ALL
  SELECT 'assessment_completed_at', 'Answered on' UNION ALL
  SELECT 'assessment_expires_at', 'Link valid until' UNION ALL
  SELECT 'assessment_all', 'All' UNION ALL
  SELECT 'assessment_big5', 'Big Five (IPIP-50)' UNION ALL
  SELECT 'assessment_disc', 'DISC (approximation of the model)' UNION ALL
  SELECT 'assessment_primary_style', 'Predominant style' UNION ALL
  SELECT 'assessment_secondary_style', 'Secondary style' UNION ALL
  SELECT 'assessment_band_low', 'Low' UNION ALL
  SELECT 'assessment_band_mid', 'Medium' UNION ALL
  SELECT 'assessment_band_high', 'High' UNION ALL
  SELECT 'assessment_f_big5_e', 'Extraversion' UNION ALL
  SELECT 'assessment_f_big5_e_high', 'Sociable and talkative; draws energy from people.' UNION ALL
  SELECT 'assessment_f_big5_e_low', 'Reserved; prefers calm settings and focused individual work.' UNION ALL
  SELECT 'assessment_f_big5_a', 'Agreeableness' UNION ALL
  SELECT 'assessment_f_big5_a_high', 'Cooperative and empathetic; avoids conflict.' UNION ALL
  SELECT 'assessment_f_big5_a_low', 'Direct and skeptical; puts objectivity before harmony.' UNION ALL
  SELECT 'assessment_f_big5_c', 'Conscientiousness' UNION ALL
  SELECT 'assessment_f_big5_c_high', 'Organized, reliable, attentive to detail.' UNION ALL
  SELECT 'assessment_f_big5_c_low', 'Flexible and spontaneous; routine and deadlines may be harder.' UNION ALL
  SELECT 'assessment_f_big5_n', 'Emotional stability' UNION ALL
  SELECT 'assessment_f_big5_n_high', 'Calm under pressure, little shaken by stress.' UNION ALL
  SELECT 'assessment_f_big5_n_low', 'Sensitive to stress; may worry and have mood swings.' UNION ALL
  SELECT 'assessment_f_big5_o', 'Openness' UNION ALL
  SELECT 'assessment_f_big5_o_high', 'Curious and creative; enjoys new ideas.' UNION ALL
  SELECT 'assessment_f_big5_o_low', 'Practical; prefers the known and the concrete.' UNION ALL
  SELECT 'assessment_f_disc_d', 'Dominance' UNION ALL
  SELECT 'assessment_f_disc_d_desc', 'Direct and decisive; driven by results and challenges.' UNION ALL
  SELECT 'assessment_f_disc_i', 'Influence' UNION ALL
  SELECT 'assessment_f_disc_i_desc', 'Outgoing and enthusiastic; motivates and persuades people.' UNION ALL
  SELECT 'assessment_f_disc_s', 'Steadiness' UNION ALL
  SELECT 'assessment_f_disc_s_desc', 'Patient, steady and loyal; a good listener.' UNION ALL
  SELECT 'assessment_f_disc_c', 'Conscientiousness (DISC)' UNION ALL
  SELECT 'assessment_f_disc_c_desc', 'Careful and analytical; follows rules and seeks quality.'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM ohrm_i18n_lang_string x WHERE x.unit_id = t.unit_id AND x.group_id = 17
);

SET @lang_pt_br = (SELECT id FROM ohrm_i18n_language WHERE code = 'pt_BR' LIMIT 1);

INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
SELECT ls.id, @lang_pt_br,
  CASE ls.unit_id
    WHEN 'assessment_title' THEN 'Perfil comportamental'
    WHEN 'assessment_profiles' THEN 'Perfis comportamentais'
    WHEN 'assessment_welcome' THEN 'Olá'
    WHEN 'assessment_intro' THEN 'Leva cerca de 15 minutos. Não existe resposta certa ou errada: responda como você é, não como acha que deveria ser.'
    WHEN 'assessment_vacancy' THEN 'Vaga'
    WHEN 'assessment_applied' THEN 'Candidatura enviada! Próximo passo: um questionário comportamental rápido.'
    WHEN 'assessment_consent_title' THEN 'Seus dados'
    WHEN 'assessment_consent_text' THEN 'Suas respostas serão usadas só neste processo seletivo e no banco de candidatos da empresa, e só o RH tem acesso a elas. Elas ficam guardadas enquanto você autorizar; para pedir a exclusão, fale com o RH. Este é um teste de perfil comportamental.'
    WHEN 'assessment_consent_check' THEN 'Li e concordo'
    WHEN 'assessment_employee_notice' THEN 'O RH usa este perfil para conhecer melhor o seu jeito de trabalhar e apoiar o seu desenvolvimento. Não existe resposta certa ou errada.'
    WHEN 'assessment_start' THEN 'Começar'
    WHEN 'assessment_continue' THEN 'Continuar'
    WHEN 'assessment_back' THEN 'Voltar'
    WHEN 'assessment_finish' THEN 'Concluir'
    WHEN 'assessment_part' THEN 'Parte'
    WHEN 'assessment_of' THEN 'de'
    WHEN 'assessment_scale_1' THEN 'Não me descreve nada'
    WHEN 'assessment_scale_2' THEN 'Me descreve pouco'
    WHEN 'assessment_scale_3' THEN 'Mais ou menos'
    WHEN 'assessment_scale_4' THEN 'Me descreve bem'
    WHEN 'assessment_scale_5' THEN 'Me descreve muito bem'
    WHEN 'assessment_answer_all' THEN 'Responda todas as frases desta página'
    WHEN 'assessment_thanks' THEN 'Obrigado! Suas respostas foram registradas.'
    WHEN 'assessment_inactive' THEN 'Este link não está mais ativo. Fale com o RH.'
    WHEN 'assessment_new' THEN 'Novo convite'
    WHEN 'assessment_candidate' THEN 'Candidato'
    WHEN 'assessment_employees' THEN 'Funcionários'
    WHEN 'assessment_copy_link' THEN 'Copiar link'
    WHEN 'assessment_copied' THEN 'Link copiado'
    WHEN 'assessment_whatsapp' THEN 'Enviar pelo WhatsApp'
    WHEN 'assessment_whatsapp_message' THEN 'Olá, {name}! Para seguir no processo seletivo, responda este questionário (cerca de 15 minutos): {link}'
    WHEN 'assessment_invite_created' THEN 'Convite criado. Envie o link para o candidato:'
    WHEN 'assessment_employees_invited' THEN 'Convites criados:'
    WHEN 'assessment_resend' THEN 'Novo link'
    WHEN 'assessment_cancel' THEN 'Cancelar convite'
    WHEN 'assessment_cancel_confirm' THEN 'Cancelar este convite? O link deixa de funcionar.'
    WHEN 'assessment_view' THEN 'Ver perfil'
    WHEN 'assessment_download_pdf' THEN 'Baixar PDF'
    WHEN 'assessment_status_pending' THEN 'Pendente'
    WHEN 'assessment_status_completed' THEN 'Respondido'
    WHEN 'assessment_status_expired' THEN 'Vencido'
    WHEN 'assessment_status_cancelled' THEN 'Cancelado'
    WHEN 'assessment_sent_at' THEN 'Enviado em'
    WHEN 'assessment_completed_at' THEN 'Respondido em'
    WHEN 'assessment_expires_at' THEN 'Link válido até'
    WHEN 'assessment_all' THEN 'Todos'
    WHEN 'assessment_big5' THEN 'Big Five (IPIP-50)'
    WHEN 'assessment_disc' THEN 'DISC (aproximação do modelo)'
    WHEN 'assessment_primary_style' THEN 'Estilo predominante'
    WHEN 'assessment_secondary_style' THEN 'Estilo secundário'
    WHEN 'assessment_band_low' THEN 'Baixo'
    WHEN 'assessment_band_mid' THEN 'Médio'
    WHEN 'assessment_band_high' THEN 'Alto'
    WHEN 'assessment_f_big5_e' THEN 'Extroversão'
    WHEN 'assessment_f_big5_e_high' THEN 'Sociável e comunicativo; ganha energia no contato com as pessoas.'
    WHEN 'assessment_f_big5_e_low' THEN 'Reservado; prefere ambientes calmos e trabalho concentrado.'
    WHEN 'assessment_f_big5_a' THEN 'Amabilidade'
    WHEN 'assessment_f_big5_a_high' THEN 'Cooperativo e empático; evita conflitos.'
    WHEN 'assessment_f_big5_a_low' THEN 'Direto e cético; coloca a objetividade à frente da harmonia.'
    WHEN 'assessment_f_big5_c' THEN 'Conscienciosidade'
    WHEN 'assessment_f_big5_c_high' THEN 'Organizado, cumpridor e atento aos detalhes.'
    WHEN 'assessment_f_big5_c_low' THEN 'Flexível e espontâneo; rotina e prazos podem ser mais difíceis.'
    WHEN 'assessment_f_big5_n' THEN 'Estabilidade emocional'
    WHEN 'assessment_f_big5_n_high' THEN 'Calmo sob pressão, pouco abalado pelo estresse.'
    WHEN 'assessment_f_big5_n_low' THEN 'Sensível ao estresse; pode se preocupar e oscilar de humor.'
    WHEN 'assessment_f_big5_o' THEN 'Abertura'
    WHEN 'assessment_f_big5_o_high' THEN 'Curioso e criativo; gosta de ideias novas.'
    WHEN 'assessment_f_big5_o_low' THEN 'Prático; prefere o conhecido e o concreto.'
    WHEN 'assessment_f_disc_d' THEN 'Dominância'
    WHEN 'assessment_f_disc_d_desc' THEN 'Direto e decidido; movido por resultados e desafios.'
    WHEN 'assessment_f_disc_i' THEN 'Influência'
    WHEN 'assessment_f_disc_i_desc' THEN 'Comunicativo e entusiasmado; motiva e convence as pessoas.'
    WHEN 'assessment_f_disc_s' THEN 'Estabilidade'
    WHEN 'assessment_f_disc_s_desc' THEN 'Paciente, constante e leal; bom ouvinte.'
    WHEN 'assessment_f_disc_c' THEN 'Conformidade'
    WHEN 'assessment_f_disc_c_desc' THEN 'Cuidadoso e analítico; segue regras e busca qualidade.'
  END,
  1, NOW()
FROM ohrm_i18n_lang_string ls
WHERE ls.group_id = 17 AND ls.unit_id LIKE 'assessment\_%'
  AND NOT EXISTS (
    SELECT 1 FROM ohrm_i18n_translate t
    WHERE t.lang_string_id = ls.id AND t.language_id = @lang_pt_br
  );


UPDATE ohrm_i18n_lang_string ls SET ls.value = CASE ls.unit_id
    WHEN 'assessment_title' THEN 'Behavioral profile'
    WHEN 'assessment_profiles' THEN 'Behavioral profiles'
    WHEN 'assessment_welcome' THEN 'Hello'
    WHEN 'assessment_intro' THEN 'It takes about 15 minutes. There are no right or wrong answers: answer as you are, not as you think you should be.'
    WHEN 'assessment_vacancy' THEN 'Vacancy'
    WHEN 'assessment_applied' THEN 'Application sent! Next step: a short behavioral questionnaire.'
    WHEN 'assessment_consent_title' THEN 'Your data'
    WHEN 'assessment_consent_text' THEN 'Your answers are used only in this selection process and in the company''s candidate pool, and only HR can see them. They are kept while you allow it; to ask for deletion, contact HR. This is a behavioral profile test.'
    WHEN 'assessment_consent_check' THEN 'I have read and I agree'
    WHEN 'assessment_employee_notice' THEN 'HR uses this profile to better understand your working style and support your development. There are no right or wrong answers.'
    WHEN 'assessment_start' THEN 'Start'
    WHEN 'assessment_continue' THEN 'Continue'
    WHEN 'assessment_back' THEN 'Back'
    WHEN 'assessment_finish' THEN 'Finish'
    WHEN 'assessment_part' THEN 'Part'
    WHEN 'assessment_of' THEN 'of'
    WHEN 'assessment_scale_1' THEN 'Doesn''t describe me at all'
    WHEN 'assessment_scale_2' THEN 'Describes me a little'
    WHEN 'assessment_scale_3' THEN 'Somewhat'
    WHEN 'assessment_scale_4' THEN 'Describes me well'
    WHEN 'assessment_scale_5' THEN 'Describes me very well'
    WHEN 'assessment_answer_all' THEN 'Answer every statement on this page'
    WHEN 'assessment_thanks' THEN 'Thank you! Your answers were recorded.'
    WHEN 'assessment_inactive' THEN 'This link is no longer active. Please contact HR.'
    WHEN 'assessment_new' THEN 'New invite'
    WHEN 'assessment_candidate' THEN 'Candidate'
    WHEN 'assessment_employees' THEN 'Employees'
    WHEN 'assessment_copy_link' THEN 'Copy link'
    WHEN 'assessment_copied' THEN 'Link copied'
    WHEN 'assessment_whatsapp' THEN 'Send via WhatsApp'
    WHEN 'assessment_whatsapp_message' THEN 'Hi {name}! To continue in the selection process, please answer this questionnaire (about 15 minutes): {link}'
    WHEN 'assessment_invite_created' THEN 'Invite created. Send the link to the candidate:'
    WHEN 'assessment_employees_invited' THEN 'Invites created:'
    WHEN 'assessment_resend' THEN 'New link'
    WHEN 'assessment_cancel' THEN 'Cancel invite'
    WHEN 'assessment_cancel_confirm' THEN 'Cancel this invite? The link will stop working.'
    WHEN 'assessment_view' THEN 'View profile'
    WHEN 'assessment_download_pdf' THEN 'Download PDF'
    WHEN 'assessment_status_pending' THEN 'Pending'
    WHEN 'assessment_status_completed' THEN 'Answered'
    WHEN 'assessment_status_expired' THEN 'Expired'
    WHEN 'assessment_status_cancelled' THEN 'Cancelled'
    WHEN 'assessment_sent_at' THEN 'Sent'
    WHEN 'assessment_completed_at' THEN 'Answered on'
    WHEN 'assessment_expires_at' THEN 'Link valid until'
    WHEN 'assessment_all' THEN 'All'
    WHEN 'assessment_big5' THEN 'Big Five (IPIP-50)'
    WHEN 'assessment_disc' THEN 'DISC (approximation of the model)'
    WHEN 'assessment_primary_style' THEN 'Predominant style'
    WHEN 'assessment_secondary_style' THEN 'Secondary style'
    WHEN 'assessment_band_low' THEN 'Low'
    WHEN 'assessment_band_mid' THEN 'Medium'
    WHEN 'assessment_band_high' THEN 'High'
    WHEN 'assessment_f_big5_e' THEN 'Extraversion'
    WHEN 'assessment_f_big5_e_high' THEN 'Sociable and talkative; draws energy from people.'
    WHEN 'assessment_f_big5_e_low' THEN 'Reserved; prefers calm settings and focused individual work.'
    WHEN 'assessment_f_big5_a' THEN 'Agreeableness'
    WHEN 'assessment_f_big5_a_high' THEN 'Cooperative and empathetic; avoids conflict.'
    WHEN 'assessment_f_big5_a_low' THEN 'Direct and skeptical; puts objectivity before harmony.'
    WHEN 'assessment_f_big5_c' THEN 'Conscientiousness'
    WHEN 'assessment_f_big5_c_high' THEN 'Organized, reliable, attentive to detail.'
    WHEN 'assessment_f_big5_c_low' THEN 'Flexible and spontaneous; routine and deadlines may be harder.'
    WHEN 'assessment_f_big5_n' THEN 'Emotional stability'
    WHEN 'assessment_f_big5_n_high' THEN 'Calm under pressure, little shaken by stress.'
    WHEN 'assessment_f_big5_n_low' THEN 'Sensitive to stress; may worry and have mood swings.'
    WHEN 'assessment_f_big5_o' THEN 'Openness'
    WHEN 'assessment_f_big5_o_high' THEN 'Curious and creative; enjoys new ideas.'
    WHEN 'assessment_f_big5_o_low' THEN 'Practical; prefers the known and the concrete.'
    WHEN 'assessment_f_disc_d' THEN 'Dominance'
    WHEN 'assessment_f_disc_d_desc' THEN 'Direct and decisive; driven by results and challenges.'
    WHEN 'assessment_f_disc_i' THEN 'Influence'
    WHEN 'assessment_f_disc_i_desc' THEN 'Outgoing and enthusiastic; motivates and persuades people.'
    WHEN 'assessment_f_disc_s' THEN 'Steadiness'
    WHEN 'assessment_f_disc_s_desc' THEN 'Patient, steady and loyal; a good listener.'
    WHEN 'assessment_f_disc_c' THEN 'Conscientiousness (DISC)'
    WHEN 'assessment_f_disc_c_desc' THEN 'Careful and analytical; follows rules and seeks quality.'
  ELSE ls.value END
WHERE ls.group_id = 17 AND ls.unit_id LIKE 'assessment\_%';

UPDATE ohrm_i18n_translate t JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
SET t.value = CASE ls.unit_id
    WHEN 'assessment_title' THEN 'Perfil comportamental'
    WHEN 'assessment_profiles' THEN 'Perfis comportamentais'
    WHEN 'assessment_welcome' THEN 'Olá'
    WHEN 'assessment_intro' THEN 'Leva cerca de 15 minutos. Não existe resposta certa ou errada: responda como você é, não como acha que deveria ser.'
    WHEN 'assessment_vacancy' THEN 'Vaga'
    WHEN 'assessment_applied' THEN 'Candidatura enviada! Próximo passo: um questionário comportamental rápido.'
    WHEN 'assessment_consent_title' THEN 'Seus dados'
    WHEN 'assessment_consent_text' THEN 'Suas respostas serão usadas só neste processo seletivo e no banco de candidatos da empresa, e só o RH tem acesso a elas. Elas ficam guardadas enquanto você autorizar; para pedir a exclusão, fale com o RH. Este é um teste de perfil comportamental.'
    WHEN 'assessment_consent_check' THEN 'Li e concordo'
    WHEN 'assessment_employee_notice' THEN 'O RH usa este perfil para conhecer melhor o seu jeito de trabalhar e apoiar o seu desenvolvimento. Não existe resposta certa ou errada.'
    WHEN 'assessment_start' THEN 'Começar'
    WHEN 'assessment_continue' THEN 'Continuar'
    WHEN 'assessment_back' THEN 'Voltar'
    WHEN 'assessment_finish' THEN 'Concluir'
    WHEN 'assessment_part' THEN 'Parte'
    WHEN 'assessment_of' THEN 'de'
    WHEN 'assessment_scale_1' THEN 'Não me descreve nada'
    WHEN 'assessment_scale_2' THEN 'Me descreve pouco'
    WHEN 'assessment_scale_3' THEN 'Mais ou menos'
    WHEN 'assessment_scale_4' THEN 'Me descreve bem'
    WHEN 'assessment_scale_5' THEN 'Me descreve muito bem'
    WHEN 'assessment_answer_all' THEN 'Responda todas as frases desta página'
    WHEN 'assessment_thanks' THEN 'Obrigado! Suas respostas foram registradas.'
    WHEN 'assessment_inactive' THEN 'Este link não está mais ativo. Fale com o RH.'
    WHEN 'assessment_new' THEN 'Novo convite'
    WHEN 'assessment_candidate' THEN 'Candidato'
    WHEN 'assessment_employees' THEN 'Funcionários'
    WHEN 'assessment_copy_link' THEN 'Copiar link'
    WHEN 'assessment_copied' THEN 'Link copiado'
    WHEN 'assessment_whatsapp' THEN 'Enviar pelo WhatsApp'
    WHEN 'assessment_whatsapp_message' THEN 'Olá, {name}! Para seguir no processo seletivo, responda este questionário (cerca de 15 minutos): {link}'
    WHEN 'assessment_invite_created' THEN 'Convite criado. Envie o link para o candidato:'
    WHEN 'assessment_employees_invited' THEN 'Convites criados:'
    WHEN 'assessment_resend' THEN 'Novo link'
    WHEN 'assessment_cancel' THEN 'Cancelar convite'
    WHEN 'assessment_cancel_confirm' THEN 'Cancelar este convite? O link deixa de funcionar.'
    WHEN 'assessment_view' THEN 'Ver perfil'
    WHEN 'assessment_download_pdf' THEN 'Baixar PDF'
    WHEN 'assessment_status_pending' THEN 'Pendente'
    WHEN 'assessment_status_completed' THEN 'Respondido'
    WHEN 'assessment_status_expired' THEN 'Vencido'
    WHEN 'assessment_status_cancelled' THEN 'Cancelado'
    WHEN 'assessment_sent_at' THEN 'Enviado em'
    WHEN 'assessment_completed_at' THEN 'Respondido em'
    WHEN 'assessment_expires_at' THEN 'Link válido até'
    WHEN 'assessment_all' THEN 'Todos'
    WHEN 'assessment_big5' THEN 'Big Five (IPIP-50)'
    WHEN 'assessment_disc' THEN 'DISC (aproximação do modelo)'
    WHEN 'assessment_primary_style' THEN 'Estilo predominante'
    WHEN 'assessment_secondary_style' THEN 'Estilo secundário'
    WHEN 'assessment_band_low' THEN 'Baixo'
    WHEN 'assessment_band_mid' THEN 'Médio'
    WHEN 'assessment_band_high' THEN 'Alto'
    WHEN 'assessment_f_big5_e' THEN 'Extroversão'
    WHEN 'assessment_f_big5_e_high' THEN 'Sociável e comunicativo; ganha energia no contato com as pessoas.'
    WHEN 'assessment_f_big5_e_low' THEN 'Reservado; prefere ambientes calmos e trabalho concentrado.'
    WHEN 'assessment_f_big5_a' THEN 'Amabilidade'
    WHEN 'assessment_f_big5_a_high' THEN 'Cooperativo e empático; evita conflitos.'
    WHEN 'assessment_f_big5_a_low' THEN 'Direto e cético; coloca a objetividade à frente da harmonia.'
    WHEN 'assessment_f_big5_c' THEN 'Conscienciosidade'
    WHEN 'assessment_f_big5_c_high' THEN 'Organizado, cumpridor e atento aos detalhes.'
    WHEN 'assessment_f_big5_c_low' THEN 'Flexível e espontâneo; rotina e prazos podem ser mais difíceis.'
    WHEN 'assessment_f_big5_n' THEN 'Estabilidade emocional'
    WHEN 'assessment_f_big5_n_high' THEN 'Calmo sob pressão, pouco abalado pelo estresse.'
    WHEN 'assessment_f_big5_n_low' THEN 'Sensível ao estresse; pode se preocupar e oscilar de humor.'
    WHEN 'assessment_f_big5_o' THEN 'Abertura'
    WHEN 'assessment_f_big5_o_high' THEN 'Curioso e criativo; gosta de ideias novas.'
    WHEN 'assessment_f_big5_o_low' THEN 'Prático; prefere o conhecido e o concreto.'
    WHEN 'assessment_f_disc_d' THEN 'Dominância'
    WHEN 'assessment_f_disc_d_desc' THEN 'Direto e decidido; movido por resultados e desafios.'
    WHEN 'assessment_f_disc_i' THEN 'Influência'
    WHEN 'assessment_f_disc_i_desc' THEN 'Comunicativo e entusiasmado; motiva e convence as pessoas.'
    WHEN 'assessment_f_disc_s' THEN 'Estabilidade'
    WHEN 'assessment_f_disc_s_desc' THEN 'Paciente, constante e leal; bom ouvinte.'
    WHEN 'assessment_f_disc_c' THEN 'Conformidade'
    WHEN 'assessment_f_disc_c_desc' THEN 'Cuidadoso e analítico; segue regras e busca qualidade.'
  ELSE t.value END
WHERE t.language_id = @lang_pt_br AND ls.group_id = 17 AND ls.unit_id LIKE 'assessment\_%';

DELETE t FROM ohrm_i18n_translate t JOIN ohrm_i18n_lang_string ls ON ls.id = t.lang_string_id
WHERE ls.group_id = 17 AND ls.unit_id IN ('assessment_disclaimer');
DELETE FROM ohrm_i18n_lang_string WHERE group_id = 17 AND unit_id IN ('assessment_disclaimer');

UPDATE ohrm_i18n_language SET modified_at = NOW() WHERE id = @lang_pt_br;
