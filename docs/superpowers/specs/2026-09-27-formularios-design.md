# Formulários: provas e pesquisas para os colaboradores

**Data:** 2026-09-27
**Branch:** `pt-br-customizations`
**Situação:** desenho aprovado, falta revisão desta especificação

## Objetivo

O RH monta, campo a campo, provas e pesquisas no estilo do Google Forms e as envia
para um funcionário, para todos de uma empresa ou posto, ou para a rede inteira. O
funcionário responde pelo app mobile ou pelo desktop, com a própria conta. O RH
acompanha quem respondeu, corrige o que for texto e vê os resultados.

O módulo já vem com **modelos prontos** voltados a postos de combustíveis, que o RH
copia e ajusta.

## Decisões tomadas

| Tema | Decisão |
|---|---|
| Tipos de formulário | **Prova** (gabarito, nota automática, nota mínima) e **Pesquisa** (sem certo ou errado; pode ser anônima) |
| Onde se cria | Só no desktop, só o Admin |
| Onde se responde | App mobile (nova aba no dock) **e** desktop ("Meus formulários"), com o mesmo componente |
| Público | O mesmo dos Avisos: `NETWORK` (rede), `SUBUNIT` (empresa ou posto e os níveis abaixo) e `EMPLOYEE` (uma pessoa) |
| Tentativas | 1 por pessoa; o RH pode liberar mais. Vale a última |
| O que o funcionário vê | A nota e "Aprovado" ou "Não aprovado". **Não vê o gabarito** |
| Aviso | Publicar cria um Aviso para o mesmo público, com botão **Responder** |
| Armazenamento | Tabelas normalizadas no plugin de Ponto, ao lado de Avisos, Faltas e Folha |
| Mídia | Imagem nas questões (no banco, até 2 MB, JPG/PNG/WebP) e vídeo do YouTube (só o ID) |

## 1. Dados (migração 014)

Todas as tabelas seguem o padrão `ohrm_br_*` e a migração é idempotente.

### `ohrm_br_form`
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `title` | VARCHAR(150) | |
| `description` | TEXT NULL | |
| `kind` | ENUM('QUIZ','SURVEY') | |
| `anonymous` | TINYINT(1) | só pode ser 1 em `SURVEY` |
| `pass_percent` | TINYINT UNSIGNED NULL | 0–100; obrigatório em `QUIZ` |
| `scope` | ENUM('NETWORK','SUBUNIT','EMPLOYEE') | |
| `subunit_id` | INT NULL | quando `scope = SUBUNIT` |
| `employee_id` | INT NULL | quando `scope = EMPLOYEE` |
| `due_at` | DATETIME NULL | depois dele, nenhum envio é aceito |
| `status` | ENUM('DRAFT','PUBLISHED','CLOSED') | |
| `is_template` | TINYINT(1) | um modelo nunca é publicado |
| `published_at`, `closed_at` | DATETIME NULL | |
| `created_by_emp_number` | INT NULL | |
| `created_at` | DATETIME | |

### `ohrm_br_form_item`
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `form_id` | FK → form, ON DELETE CASCADE | |
| `position` | SMALLINT UNSIGNED | ordem na tela |
| `type` | ENUM('CONTENT','SINGLE','MULTIPLE','SHORT_TEXT','LONG_TEXT','SCALE','YES_NO') | |
| `prompt` | TEXT | enunciado (ou título do bloco de conteúdo) |
| `help_text` | TEXT NULL | |
| `required` | TINYINT(1) | ignorado em `CONTENT` |
| `points` | DECIMAL(5,2) | usado em `QUIZ`; 0 em `SCALE` e `CONTENT` |
| `image_id` | FK NULL → form_image | |
| `youtube_id` | CHAR(11) NULL | nunca a URL, só o ID validado |
| `correct_yes_no` | TINYINT(1) NULL | gabarito do `YES_NO` numa prova |

### `ohrm_br_form_option`
`id`, `item_id` (FK, CASCADE), `position`, `label` VARCHAR(255), `is_correct` TINYINT(1).
Usada por `SINGLE` e `MULTIPLE`.

### `ohrm_br_form_image`
`id`, `form_id` (FK, CASCADE), `filename`, `file_type`, `file_size`, `content` MEDIUMBLOB,
`uploaded_at`. Segue o mesmo padrão de `ohrm_br_absence_attachment`.

### `ohrm_br_form_completion`
Registra **quem** respondeu e quando. Existe para todo formulário.
`id`, `form_id`, `employee_id`, `attempt` TINYINT, `completed_at`.
UNIQUE(`form_id`, `employee_id`, `attempt`).

### `ohrm_br_form_submission`
Guarda **o que** foi respondido.
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | CHAR(32) PK | aleatório (`bin2hex(random_bytes(16))`), **nunca sequencial** |
| `form_id` | FK | |
| `employee_id` | INT NULL | **NULL em pesquisa anônima** |
| `attempt` | TINYINT NULL | NULL em anônima |
| `submitted_at` | DATETIME NULL | **NULL em anônima** |
| `score_points`, `max_points` | DECIMAL(6,2) NULL | só em `QUIZ` |
| `status` | ENUM('GRADED','PENDING_REVIEW','RECORDED') | `RECORDED` = pesquisa |
| `reviewed_by_emp_number`, `reviewed_at` | NULL | correção manual |

### `ohrm_br_form_answer`
`id`, `submission_id` (FK, CASCADE), `item_id`, `option_id` NULL, `text_value` TEXT NULL,
`scale_value` TINYINT NULL, `yes_no_value` TINYINT(1) NULL, `points_awarded` DECIMAL(5,2) NULL.
A múltipla escolha grava uma linha por opção marcada; a pontuação vai só na primeira linha
da questão.

### `ohrm_br_form_retake`
`id`, `form_id`, `employee_id`, `granted_by_emp_number`, `granted_at`.
Tentativas permitidas = 1 + número de linhas.

### Alteração em `ohrm_br_announcement`
Nova coluna `form_id` INT UNSIGNED NULL. Quando está preenchida, o aviso ganha o botão
**Responder**.

## 2. Regras de negócio (classes puras, testadas sem banco)

### `FormGrading`
| Tipo | Na prova |
|---|---|
| `SINGLE` | ganha todos os pontos se marcou a opção certa; senão 0 |
| `YES_NO` | ganha todos os pontos se acertou `correct_yes_no`; senão 0 |
| `MULTIPLE` | ganha todos os pontos só se o conjunto marcado for **igual** ao conjunto certo; sem ponto parcial |
| `SHORT_TEXT` / `LONG_TEXT` | `points_awarded = NULL` e o envio fica `PENDING_REVIEW` |
| `SCALE`, `CONTENT` | não pontuam |

- Nota = `score_points / max_points`. Aprovado quando a nota × 100 ≥ `pass_percent`.
- Se `max_points` for 0, não há nota: o envio é tratado como pesquisa, com status `RECORDED`.
- A nota só aparece para o funcionário com status `GRADED`.

### `FormPublication` (validação antes de publicar)
Recusa, apontando o bloco com o problema, quando:
- não há nenhuma questão (só conteúdo não basta);
- `SINGLE`/`MULTIPLE` tem menos de 2 opções;
- em `QUIZ`: `SINGLE` sem exatamente 1 certa, `MULTIPLE` sem ao menos 1 certa, `YES_NO`
  sem gabarito, ou falta `pass_percent`;
- `anonymous = 1` com `kind = QUIZ`;
- `anonymous = 1` com `scope = EMPLOYEE`;
- `due_at` no passado;
- `is_template = 1`.

### `FormSubmissionRules` (validação do envio, sempre no servidor)
Recusa quando:
- o formulário não está `PUBLISHED`;
- o prazo passou;
- a pessoa não está no público (`AnnouncementAudience::reaches`);
- não há tentativa disponível;
- uma obrigatória ficou em branco;
- uma opção não pertence à questão;
- há mais de uma opção numa `SINGLE`;
- a escala está fora de 1–5;
- um texto passa do limite (curto: 255; longo: 5000).

### `YoutubeLink`
Extrai o ID de 11 caracteres (`[A-Za-z0-9_-]`) de `youtube.com/watch?v=`, `youtu.be/`,
`youtube.com/shorts/`, `youtube.com/embed/` e `m.youtube.com`. Recusa qualquer outro host,
incluindo imitações como `youtube.com.evil.com` e `evilyoutube.com`. O vídeo é exibido por
`https://www.youtube-nocookie.com/embed/{id}`.

### Anonimato
- Na pesquisa anônima, o envio é gravado sem `employee_id`, sem `attempt` e sem
  `submitted_at`, com ID aleatório. O que liga a pessoa ao formulário existe só em
  `completion`, e não há como cruzar as duas tabelas.
- Os resultados da pesquisa anônima só são exibidos a partir de **3 respostas**.
- Limite que o código não resolve: numa resposta de texto a pessoa pode se identificar pelo
  que escreveu.

### Travamento
- Enquanto está em `DRAFT`, o formulário pode ser editado à vontade. Salvar substitui todos
  os blocos numa transação.
- Um formulário `PUBLISHED` ou `CLOSED` não pode ser editado; o RH só encerra ou duplica.
- "Usar modelo" e "Duplicar" copiam o formulário, os blocos, as opções e as imagens para
  um novo `DRAFT`, sem público e sem prazo.

## 3. API (plugin de Ponto)

| API | Verbos | Quem |
|---|---|---|
| `FormAPI` — `/api/v2/attendance/br/forms` | GET lista e item, POST, PUT (rascunho), ações `publish`/`close`/`duplicate` | Admin |
| `FormImageAPI` — `/api/v2/attendance/br/forms/images` | POST (upload), GET (conteúdo) | POST: Admin; GET: todos, se o formulário os alcança |
| `MyFormAPI` — `/api/v2/attendance/br/my-forms` | GET lista (pendentes e respondidos), GET item **sem gabarito** | Todos (ESS) |
| `FormSubmissionAPI` — `/api/v2/attendance/br/my-forms/{id}/submissions` | POST | Todos (ESS) |
| `FormResultAPI` — `/api/v2/attendance/br/forms/{id}/results` | GET resumo, por questão, por pessoa, CSV; PUT correção; POST liberar nova tentativa | Admin |

- Cada API entra em `ohrm_data_group`, `ohrm_api_permission` e `ohrm_user_role_data_group`,
  que é a lição da migração 013.
- A lista de pessoas passa por `BrAccessScope`.
- O item entregue para responder é montado por um serializador próprio, que **não inclui**
  `is_correct` nem `correct_yes_no`. Um teste garante isso.

## 4. Telas

### RH (desktop, só Admin)
- **Formulários:** a lista mostra título, tipo, situação, público, prazo e
  "respondidos / público", com as ações Editar, Duplicar, Encerrar e Resultados. Uma
  seção **Modelos** traz o botão "Usar modelo".
- **Construtor:**
  - **Cabeçalho:** título, descrição, Prova ou Pesquisa, anônima (só em Pesquisa), nota
    mínima (só em Prova), público e prazo.
  - **Blocos:** cada bloco é um card. A barra "+ Adicionar" tem um botão por tipo.
  - **Card de questão:** enunciado, ajuda, obrigatório e pontos; opções com a marcação de
    "certa" (em Prova); imagem com miniatura; link do YouTube com prévia; botões ↑ ↓,
    duplicar e excluir.
  - **Rodapé:** Pré-visualizar, Salvar rascunho e Publicar. Os erros de publicação
    aparecem no card com problema.
- **Resultados:**
  - **Resumo:** público, andamento, lista de quem ainda não respondeu e, na prova, média,
    % de aprovados e quantas aguardam correção.
  - **Por questão:** barras por opção e % de acerto; média e distribuição da escala;
    lista de textos.
  - **Por pessoa** (não existe na pesquisa anônima): Corrigir, Liberar nova tentativa e
    Exportar CSV.

### Funcionário (mobile e desktop)
- **Componente `FormFiller`:** é compartilhado.
  - O vídeo só carrega quando a pessoa toca nele.
  - As obrigatórias são marcadas com `*`.
  - A pesquisa anônima mostra uma faixa: *"Esta pesquisa é anônima: suas respostas não
    ficam ligadas ao seu nome."*
  - O rascunho é guardado em `localStorage` por formulário, dentro de try/catch, e é
    apagado ao enviar.
  - O envio pede confirmação: *"Depois de enviado não é possível alterar."*
- **Mobile:** 5ª aba no dock, **"Provas"**, com contador de pendentes. Mostra as listas de
  pendentes e de respondidos e, depois do envio, a nota com a situação, "Aguardando
  correção" ou "Obrigado".
- **Desktop:** o menu **"Meus formulários"** (tela do papel ESS) mostra as mesmas listas com
  o mesmo `FormFiller`.
- **Aviso:** o botão Responder abre o formulário.

## 5. Modelos prontos para postos de combustíveis (migração 015)

Entram como `is_template = 1`. O **conteúdo técnico e normativo deve ser revisado pelo
Leo** antes de o formulário ir para uso real; itens marcados com ⚠ dependem de norma
vigente e são conferidos na implementação.

1. **Autoavaliação de desempenho — Frentista/Pista** (pesquisa identificada)
   - **Escala 1–5:**
     - atendimento ao cliente;
     - agilidade na pista;
     - conferência de caixa e troco;
     - uso de EPI e uniforme;
     - procedimentos de segurança no abastecimento;
     - oferta de produtos e serviços (aditivos, troca de óleo, conveniência);
     - pontualidade e assiduidade;
     - trabalho em equipe;
     - comunicação com a liderança;
     - iniciativa.
   - **Sim/Não:** "Recebi treinamento suficiente para minha função?"
   - **Texto longo:** "Meus pontos fortes", "O que preciso melhorar" e "O que a empresa pode
     fazer para me ajudar".
2. **Prova: Segurança e procedimentos na pista** (prova, nota mínima de 70%)
   - **Escolha única:**
     - extintor adequado para fogo em líquidos inflamáveis;
     - o que fazer se o cliente estiver com o motor ligado ou fumando;
     - o que fazer quando o bico automático desarma;
     - uso de celular junto à bomba.
   - **Múltipla escolha:** EPIs obrigatórios na descarga de combustível; cuidados na descarga
     do caminhão-tanque (aterramento, isolamento da área, extintor próximo).
   - **Sim/Não:** "O posto é obrigado a fazer o teste de qualidade da gasolina quando o
     cliente pede?" ⚠
   - **Conteúdo:** orientação de NR-20 ⚠. O vídeo do YouTube só entra se houver um
     público e conferido; senão o bloco fica sem vídeo para o Leo colar o link.
3. **Pesquisa de clima — Posto** (pesquisa anônima)
   - **Escala 1–5:** relação com a liderança, com os colegas, escala e folgas, condições de
     trabalho na pista, segurança, reconhecimento e se recomendaria o posto para trabalhar.
   - **Texto longo:** "O que mais te incomoda hoje?" e "Uma sugestão".

Depois do deploy, para o Leo testar, os modelos 1 e 2 são publicados como cópias para o
funcionário de teste (emp_number 1), com prazo de 30 dias.

## 6. Testes e verificação

- **PHPUnit (sem banco):** `FormGrading`, `FormPublication`, `FormSubmissionRules`,
  `YoutubeLink` (incluindo hosts falsos), anonimato do envio, serializador sem gabarito e a
  camada de validação das APIs.
- **Jest:** construtor (adicionar, reordenar, marcar a certa, erro de publicação) e
  `FormFiller` (rascunho, obrigatórias, envio, faixa de anônimo).
- **Ponta a ponta no banco real, com rollback:** publicar → responder → corrigir → nova
  tentativa → resultados.
- **Visual:** foto em 390px do `FormFiller` e da aba, e foto do construtor em largura de
  desktop.
- **Deploy:** `yarn build`, conferir o JS servido pela URL pública e aplicar as migrações 014
  e 015.

## Fora da primeira versão
- montar formulário pelo celular;
- arrastar e soltar;
- seções ou páginas e ramificação ("se responder X, pule para Y");
- imagem nas opções;
- ponto parcial na múltipla escolha;
- Supervisor criando ou corrigindo;
- fila offline das respostas;
- notificação push.
