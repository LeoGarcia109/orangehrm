-- ============================================================================
-- OrangeHRM BR - Migracao 015: modelos prontos de formularios para postos
-- ============================================================================
--
-- Tres modelos (is_template = 1), um para cada modo do modulo:
--   1. Autoavaliacao de desempenho -- Frentista/Pista  (pesquisa identificada)
--   2. Prova: Seguranca e procedimentos na pista       (prova, nota minima 70%)
--   3. Pesquisa de clima -- Posto                      (pesquisa anonima)
--
-- O RH usa com "Usar modelo", que cria uma copia em rascunho para ajustar
-- publico e prazo. Modelo nunca e publicado.
--
-- Fontes conferidas em 27/09/2026 para a prova:
--   - NR-20, Anexo IV (benzeno em postos): item 9.5.4 proibe completar o tanque
--     apos o desarme do bico automatico; itens 9.6/9.7 proibem flanela/estopa
--     para conter respingos; item 12.1.1 exige protecao respiratoria de face
--     inteira com filtro para vapores organicos e protecao da pele nas
--     atividades criticas (descarga, medicao com regua).
--     https://www.guiatrabalhista.com.br/legislacao/nr/nr20.htm
--   - Resolucao ANP n. 898/2022: o revendedor e obrigado a fazer as analises
--     de qualidade sempre que o consumidor pedir.
-- Conteudo tecnico deve ser revisado pelo RH/seguranca antes do uso real.
-- O bloco "Antes de comecar" fica sem video: cole o do treinamento da empresa.
--
-- Gerado por script; idempotente (modelo, bloco e opcao so entram se faltarem).
-- ----------------------------------------------------------------------------

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- Autoavaliação de desempenho — Frentista/Pista
-- ----------------------------------------------------------------------------
INSERT INTO ohrm_br_form (title, description, kind, anonymous, pass_percent, scope, status, is_template)
SELECT 'Autoavaliação de desempenho — Frentista/Pista', 'Avalie seu próprio trabalho no último período. Seja sincero: esta avaliação serve para conversar com sua liderança sobre o que está bom e o que pode melhorar.', 'SURVEY', 0, NULL, 'NETWORK', 'DRAFT', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form WHERE is_template = 1 AND title = 'Autoavaliação de desempenho — Frentista/Pista');
SET @form = (SELECT id FROM ohrm_br_form WHERE is_template = 1 AND title = 'Autoavaliação de desempenho — Frentista/Pista' LIMIT 1);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 1, 'CONTENT', 'Como responder', '1 = precisa melhorar muito · 3 = dentro do esperado · 5 = excelente', 0, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 1);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 2, 'SCALE', 'Atendimento ao cliente (cordialidade, atenção, tirar dúvidas)', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 2);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 3, 'SCALE', 'Agilidade no abastecimento, sem perder a atenção', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 3);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 4, 'SCALE', 'Conferência de caixa e troco (fechamento sem diferenças)', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 4);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 5, 'SCALE', 'Uso correto de EPI e uniforme', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 5);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 6, 'SCALE', 'Procedimentos de segurança no abastecimento (motor desligado, ninguém fumando, bico automático)', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 6);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 7, 'SCALE', 'Oferta de produtos e serviços (aditivos, calibragem, troca de óleo, conveniência)', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 7);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 8, 'SCALE', 'Pontualidade e assiduidade', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 8);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 9, 'SCALE', 'Trabalho em equipe e colaboração com os colegas', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 9);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 10, 'SCALE', 'Comunicação com a liderança', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 10);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 11, 'SCALE', 'Iniciativa (organização e limpeza da pista, antecipar problemas)', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 11);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 12, 'YES_NO', 'Recebi treinamento suficiente para exercer minha função?', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 12);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 13, 'LONG_TEXT', 'Meus pontos fortes', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 13);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 14, 'LONG_TEXT', 'O que preciso melhorar', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 14);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 15, 'LONG_TEXT', 'O que a empresa pode fazer para me ajudar', NULL, 0, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 15);

-- ----------------------------------------------------------------------------
-- Prova: Segurança e procedimentos na pista
-- ----------------------------------------------------------------------------
INSERT INTO ohrm_br_form (title, description, kind, anonymous, pass_percent, scope, status, is_template)
SELECT 'Prova: Segurança e procedimentos na pista', 'Prova de conhecimentos sobre segurança e rotina da pista. Nota mínima: 70%.', 'QUIZ', 0, 70, 'NETWORK', 'DRAFT', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form WHERE is_template = 1 AND title = 'Prova: Segurança e procedimentos na pista');
SET @form = (SELECT id FROM ohrm_br_form WHERE is_template = 1 AND title = 'Prova: Segurança e procedimentos na pista' LIMIT 1);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 1, 'CONTENT', 'Antes de começar', 'A NR-20 trata da segurança no trabalho com inflamáveis e combustíveis. O Anexo IV cuida da exposição ao benzeno, presente na gasolina: bico automático, proibição de completar o tanque depois que o bico desarma, proibição de flanela e estopa para conter respingos, e EPI nas atividades críticas, como a descarga. Leia com atenção e responda às questões abaixo.', 0, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 1);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 2, 'SINGLE', 'Qual extintor é indicado para fogo em combustível líquido (gasolina, diesel, etanol)?', NULL, 1, 1, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 2);
SET @item = (SELECT id FROM ohrm_br_form_item WHERE form_id = @form AND position = 2);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 1, 'Pó químico (BC ou ABC)', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 1);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 2, 'Água pressurizada', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 2);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 3, 'Qualquer extintor serve', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 3);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 3, 'SINGLE', 'O cliente chega fumando ou com o motor ligado. O que você faz?', NULL, 1, 1, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 3);
SET @item = (SELECT id FROM ohrm_br_form_item WHERE form_id = @form AND position = 3);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 1, 'Peço, com educação, que apague o cigarro e desligue o motor antes de abastecer', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 1);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 2, 'Abasteço rápido para não criar atrito', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 2);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 3, 'Abasteço e aviso depois', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 3);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 4, 'SINGLE', 'O bico automático desarmou com o tanque cheio. O que fazer?', 'NR-20, Anexo IV, item 9.5.4.', 1, 1, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 4);
SET @item = (SELECT id FROM ohrm_br_form_item WHERE form_id = @form AND position = 4);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 1, 'Parar: é proibido completar depois que o bico desarma', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 1);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 2, 'Completar até a boca do tanque', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 2);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 3, 'Arredondar o valor apertando mais algumas vezes', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 3);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 5, 'YES_NO', 'Posso usar flanela ou estopa para conter respingos durante o abastecimento?', 'NR-20, Anexo IV, itens 9.6 e 9.7.', 1, 1, 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 5);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 6, 'MULTIPLE', 'Na descarga do caminhão-tanque, quais cuidados fazem parte do procedimento?', 'Marque todas as corretas.', 1, 1, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 6);
SET @item = (SELECT id FROM ohrm_br_form_item WHERE form_id = @form AND position = 6);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 1, 'Aterrar o caminhão antes de conectar as mangueiras', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 1);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 2, 'Isolar e sinalizar a área', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 2);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 3, 'Manter extintor próximo', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 3);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 4, 'Conectar a mangueira antes de aterrar o caminhão', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 4);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 7, 'MULTIPLE', 'Na descarga e na medição com régua, quais EPIs são obrigatórios?', 'NR-20, Anexo IV, item 12.1.1. Marque todas as corretas.', 1, 1, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 7);
SET @item = (SELECT id FROM ohrm_br_form_item WHERE form_id = @form AND position = 7);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 1, 'Máscara de face inteira com filtro para vapores orgânicos', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 1);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 2, 'Proteção para a pele (luvas e vestimenta adequadas)', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 2);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 3, 'Máscara de tecido comum', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 3);
INSERT INTO ohrm_br_form_option (item_id, position, label, is_correct)
SELECT @item, 4, 'Nenhum, se for rápido', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_option WHERE item_id = @item AND position = 4);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 8, 'YES_NO', 'Se o cliente pedir, o posto é obrigado a fazer o teste de qualidade do combustível?', 'Resolução ANP nº 898/2022.', 1, 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 8);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 9, 'SHORT_TEXT', 'Descreva em poucas palavras o que fazer em caso de derramamento de combustível na pista.', NULL, 1, 2, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 9);

-- ----------------------------------------------------------------------------
-- Pesquisa de clima — Posto
-- ----------------------------------------------------------------------------
INSERT INTO ohrm_br_form (title, description, kind, anonymous, pass_percent, scope, status, is_template)
SELECT 'Pesquisa de clima — Posto', 'Pesquisa anônima: ninguém, nem o RH, consegue ver quem respondeu o quê. Os resultados só aparecem a partir de 3 respostas.', 'SURVEY', 1, NULL, 'NETWORK', 'DRAFT', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form WHERE is_template = 1 AND title = 'Pesquisa de clima — Posto');
SET @form = (SELECT id FROM ohrm_br_form WHERE is_template = 1 AND title = 'Pesquisa de clima — Posto' LIMIT 1);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 1, 'CONTENT', 'Como responder', '1 = discordo totalmente · 3 = neutro · 5 = concordo totalmente', 0, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 1);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 2, 'SCALE', 'Me sinto respeitado pela minha liderança', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 2);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 3, 'SCALE', 'Tenho um bom relacionamento com os colegas', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 3);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 4, 'SCALE', 'Minha escala e minhas folgas são justas', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 4);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 5, 'SCALE', 'Tenho boas condições de trabalho na pista (equipamentos, uniforme, estrutura)', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 5);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 6, 'SCALE', 'Me sinto seguro no meu trabalho', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 6);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 7, 'SCALE', 'Meu trabalho é reconhecido', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 7);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 8, 'SCALE', 'Eu recomendaria este posto para um amigo trabalhar', NULL, 1, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 8);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 9, 'LONG_TEXT', 'O que mais te incomoda hoje no trabalho?', NULL, 0, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 9);

INSERT INTO ohrm_br_form_item (form_id, position, type, prompt, help_text, required, points, correct_yes_no)
SELECT @form, 10, 'LONG_TEXT', 'Uma sugestão para melhorar o posto', NULL, 0, 0, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form_item WHERE form_id = @form AND position = 10);
