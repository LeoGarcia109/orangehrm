# Perfil comportamental (Big Five + DISC) para candidatos e funcionários

**Data:** 2026-09-27
**Branch:** `pt-br-customizations`
**Situação:** desenho aprovado em chat. O Leo pediu para implementar e revisar no fim.
**Escopo deste ciclo:** partes B (inventário comportamental) e C (link público do candidato).
Ficam para ciclos próprios: D (perfil do cargo / engenharia de cargo) e E (aderência
candidato × vaga).

## Objetivo

O RH envia um questionário comportamental para **candidatos**, por um link público sem
login, e para **funcionários**, pelo app. O sistema calcula o perfil e o guarda no cadastro.
Candidatos que se inscrevem pela página pública de vagas do OrangeHRM caem no questionário
logo depois da candidatura. O "banco de possíveis contratados" é a lista de candidatos do
**Recrutamento nativo**; este módulo acrescenta o perfil a ela.

## Decisões tomadas

| Tema | Decisão |
|---|---|
| Inventários | **Big Five** pelo IPIP-50 (domínio público, uso comercial livre) e **DISC** com perguntas próprias (o modelo é livre; os comerciais são proprietários). Eneagrama fora: não há instrumento público validado e a base científica é fraca |
| Quem responde | Candidatos (link) e funcionários (app/desktop) |
| Como o candidato chega | Pela página pública de vagas, logo após a candidatura, e por link gerado pelo RH |
| Envio do link | "Copiar link" e "Enviar pelo WhatsApp". O e-mail do OrangeHRM não está configurado |
| Devolutiva | Quem responde **não vê** o resultado; só "Obrigado" |
| Estrutura | Motor próprio de inventários: banco de perguntas **fixo no código e versionado**; tela de resposta com o visual da aba Provas; cálculo em classe pura |
| Onde fica no RH | Recrutamento → **Perfis comportamentais** (só Admin) |
| Contratação | Contratar pelo Recrutamento cria o funcionário (nativo); o perfil do candidato passa a apontar também para esse funcionário |

## Enquadramento legal (vale para textos e telas)

- Pela Resolução CFP 31/2022, **teste psicológico** para avaliação exige instrumento
  aprovado no SATEPSI e aplicação por psicólogo. IPIP, DISC e Eneagrama não constam lá. O
  módulo se apresenta como **inventário comportamental complementar**: não é laudo, não é
  teste psicológico e não deve ser **critério único** de decisão. As telas do RH exibem esse
  aviso. Não houve parecer jurídico; o Leo foi informado.
- **LGPD:** o candidato marca o consentimento antes de começar. O texto explica a
  finalidade, quem vê, o prazo de guarda e como pedir a exclusão. O consentimento grava data,
  IP e navegador, e reaproveita o campo nativo `consentToKeepData` do candidato. A exclusão
  nativa de candidatos (Manutenção → Limpar) apaga o perfil em cascata. O funcionário vê um
  aviso de finalidade.

## 1. Inventários e cálculo

### Big Five (IPIP-50, Goldberg 1992)
- 50 frases, 10 por fator: **Extroversão (E), Amabilidade (A), Conscienciosidade (C),
  Estabilidade emocional (N, o inverso do neuroticismo) e Abertura/Intelecto (O)**.
- Escala de 1 a 5: 1 "Não me descreve nada", 2 "Me descreve pouco", 3 "Mais ou menos",
  4 "Me descreve bem", 5 "Me descreve muito bem".
- Frases **invertidas** valem `6 − resposta`. A soma por fator vai de 10 a 50;
  `score = (soma − 10) / 40 × 100`, com uma casa decimal.
- **Tradução:** usar uma tradução publicada em português, se houver fonte confiável, citada
  no código. Senão, uma tradução própria cuidadosa, marcada como tal. Versão:
  `IPIP50-PT-v1`.

### DISC (próprio)
- 24 frases, 6 por fator: **Dominância (D), Influência (I), Estabilidade (S),
  Conformidade (C)**. Mesma escala de 1 a 5, sem inversão.
- `score = (soma − 6) / 24 × 100`. O estilo **predominante** é o maior score; o
  **secundário**, o segundo. No empate vale a ordem D, I, S, C, para o resultado ser
  estável.
- Versão `DISC-HRR-v1`. Rótulo nas telas: "aproximação do modelo DISC".

### Sem dados de referência
Os 0–100 são a posição na própria escala, não um percentil. As telas mostram faixas
descritivas (0–33 baixo, 34–66 médio, 67–100 alto), avisando que não há referência
populacional.

### Ordem das frases
As 74 frases saem numa ordem fixa, intercalando fatores e inventários, em páginas de 8 a
10 frases.

## 2. Dados (migração 017)

### `ohrm_br_assessment`, um convite
| Coluna | Observação |
|---|---|
| `id` | |
| `subject_type` | ENUM('CANDIDATE','EMPLOYEE') |
| `candidate_id` | FK `ohrm_job_candidate` (CASCADE) |
| `employee_id` | FK `hs_hr_employee` (SET NULL). No convite de funcionário, a pessoa; no de candidato, o funcionário que ele virou ao ser contratado |
| `vacancy_id` | FK `ohrm_job_vacancy` (SET NULL), opcional |
| `instruments` | ex.: `BIG5,DISC` |
| `status` | ENUM('PENDING','COMPLETED','EXPIRED','CANCELLED'); `EXPIRED` é calculado na leitura (`expires_at` no passado e ainda pendente) e gravado ao tocar |
| `token_hash` | CHAR(64) NULL, com `sha256` do token; UNIQUE. Só existe em convite de candidato |
| `expires_at` | 7 dias após gerar ou reenviar (candidato); NULL para funcionário |
| `consent_at`, `consent_ip`, `consent_user_agent` | |
| `created_by_emp_number`, `created_at`, `started_at`, `completed_at` | |

### `ohrm_br_assessment_answer`
`assessment_id` (CASCADE), `item_code` (ex.: `B5-E01`, `DISC-D03`), `value` (1–5),
`answered_at`, com UNIQUE(`assessment_id`, `item_code`). Responder de novo a mesma frase
atualiza a linha.

### `ohrm_br_assessment_result`
`assessment_id` (CASCADE), `instrument`, `version`, `factor`, `raw`, `score`, com
UNIQUE(`assessment_id`, `instrument`, `factor`). Gravado ao concluir.

### Permissões, telas e menu
- APIs do RH: `AssessmentAPI` (listar, criar convite, reenviar, cancelar) e
  `AssessmentResultAPI` (perfil). Só Admin.
- Funcionário: `MyAssessmentAPI` (GET o próprio, PUT respostas, POST concluir), para
  ESS/Admin/Supervisor.
- Tela `brAssessments` no módulo recruitment (Admin), com menu de nível 2 em Recrutamento
  ("Behavioral Profiles" → "Perfis comportamentais"), e `brAssessmentProfile/{id}`.
- **Público**, sem login: controllers `PublicControllerInterface` (página e REST) com token,
  no molde do `ApplyJobVacancyViewController` e do `ApplicantController` nativos.

## 3. Regras (classes puras, testadas sem banco)

- **`InventoryCatalog`**: as frases, fator, invertida, texto pt-BR e versão; a ordem de
  aplicação; a paginação.
- **`InventoryScoring`**: `scoreBigFive(array $answers)` e `scoreDisc(array $answers)`
  devolvem `[factor => [raw, score]]`; mais `discStyles(array $scores)` (predominante e
  secundário) e `band(float $score)` (baixo, médio ou alto). Recusa resposta fora de 1–5
  e frase desconhecida; exige todas as frases do inventário para concluir.
- **`AssessmentRules`**:
  - `assertOpen(status, expiresAt, now)`: aceita só PENDING dentro do prazo;
  - `assertConsent(subjectType, consentAt)`: candidato só responde depois do
    consentimento;
  - `assertComplete(answers, instruments)`.
- **`AssessmentToken`**: `generate()` (32 bytes aleatórios em base64url), `hash(token)`
  (sha256) e comparação em tempo constante.

## 4. Fluxos

### Candidato pelo link
1. `GET /recruitmentApply/assessment/{token}` abre a página pública. Com token inválido,
   vencido, usado ou cancelado, mostra uma mensagem genérica, sem dizer qual caso é.
2. Tela de boas-vindas: primeiro nome e vaga, duração, "sem resposta certa", texto de
   consentimento e o checkbox. `POST consent` grava data, IP e navegador e marca
   `consentToKeepData` no candidato.
3. Páginas de frases, com `PUT answers` a cada página (salva no servidor). Reabrir o link
   continua da primeira frase sem resposta.
4. `POST complete` confere que tudo foi respondido, calcula, grava os resultados, marca
   COMPLETED e apaga o hash do token. Tela final: "Obrigado".

### Pela página pública de vagas
Depois da candidatura nativa (`ApplicantController`), o sistema cria um convite para o
candidato e a vaga e devolve a URL do questionário. A tela de sucesso da candidatura (Vue
nativo) mostra "Próximo passo: questionário comportamental (15 min)". Se o candidato não
seguir, o RH reenvia pelo WhatsApp.

### Funcionário
O RH cria convites para um público (rede, empresa/posto ou pessoa), um por funcionário
ativo alcançado. Eles aparecem na aba **Provas** do mobile e em **Meus formulários**, como
"Perfil comportamental", contando no número do dock. As mesmas páginas, com o aviso de
finalidade no lugar do consentimento.

### Contratação
No `AbstractCandidateActionAPI`, logo depois de criar o funcionário da ação HIRE, os
convites concluídos do candidato recebem esse `employee_id`. O perfil aparece no
funcionário.

## 5. Telas do RH (Recrutamento → Perfis comportamentais)
- **Lista:** pessoa, tipo (candidato ou funcionário), vaga, situação, enviado em e
  concluído em. Filtros por tipo, situação e vaga. Ações:
  - Ver perfil (se concluído);
  - Copiar link e WhatsApp (se pendente, de candidato);
  - Reenviar: gera um token novo, invalida o anterior e renova o prazo;
  - Cancelar.
- **Novo convite:**
  - **candidato:** busca entre os candidatos do Recrutamento, com a vaga preenchida pela
    candidatura dele; ao criar, mostra o link com Copiar e WhatsApp;
  - **funcionários:** mesmo seletor de público dos Formulários.
- **WhatsApp:** `https://wa.me/55{telefone só dígitos}?text={mensagem}`. Sem telefone,
  abre sem destinatário.
- **Perfil:**
  - cabeçalho com pessoa, vaga, data e versões;
  - Big Five com 5 barras, faixa e texto curto do que significa alto ou baixo;
  - DISC com 4 barras, estilo predominante e secundário e a descrição de cada estilo;
  - aviso fixo: "Inventário comportamental complementar. Não é teste psicológico (Res.
    CFP 31/2022) nem deve ser critério único de decisão."
- Ícone e visual no padrão das telas de Formulários.

## 6. Testes e verificação
- **PHPUnit sem banco:**
  - catálogo: 50 + 24 frases, códigos únicos, fatores completos, ordem cobrindo todas;
  - cálculo: invertidas, extremos 0 e 100, empate no DISC, faixas;
  - regras de convite: prazo, consentimento, completo;
  - token;
  - validação das APIs;
  - contrato das rotas.
- **Probes no banco real, com rollback, pelo `GenericRestController`:**
  - convite de candidato: link, consentimento, páginas, conclusão, resultado;
  - token inválido ou vencido;
  - reenviar invalida o link anterior;
  - contratar liga o funcionário;
  - convite de funcionário aparece na aba Provas.
- **Rotas públicas** respondem sem login (200 na página; 404 genérico com token ruim).
- **Jest:** a página de resposta (consentimento, paginação, salvamento, retomar) e a lista e
  o perfil do RH.
- **Fotos:** a página pública em 390px e o perfil em largura de desktop.

## Fora deste ciclo
- D (perfil do cargo / engenharia de cargo) e E (aderência) têm ciclo próprio.
- Eneagrama.
- Envio por e-mail.
- Devolutiva ao respondente.
- Percentis por população.
- Relatórios em PDF.
