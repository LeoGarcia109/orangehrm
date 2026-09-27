# Perfil comportamental (Big Five + DISC) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** O RH envia inventários comportamentais (Big Five IPIP-50 + DISC próprio) para candidatos, por link público de uso único, e para funcionários, pelo app. O sistema calcula e guarda o perfil no cadastro.

**Architecture:** Tudo no `orangehrmAttendancePlugin`, a casa das customizações BR, em `Service/Assessment/`. As classes puras recebem arrays e cobrem catálogo, cálculo, regras e token, testadas sem banco.
- **Serviço:** grava e liga ao Recrutamento nativo (`Candidate`, `Vacancy`) e ao funcionário.
- **Público:** controllers `PublicControllerInterface` autorizados só pelo token.
- **Frontend:** um `AssessmentRunner` compartilhado pela página pública e pela aba Provas.

**Tech Stack:** PHP 8.3, Symfony, Doctrine, OrangeHRM API v2, Vue 3, Jest, MySQL 8.

**Spec:** `docs/superpowers/specs/2026-09-27-perfil-comportamental-design.md`

## Global Constraints
As mesmas do plano de Formulários (`docs/superpowers/plans/2026-09-27-formularios.md`):
- harness `br-customizations/tests/phpunit-nodb.xml`;
- `yarn build`;
- cabeçalho GPL;
- mensagens sem acento no PHP;
- i18n no grupo 17;
- papéis 1/2/3;
- permissão de API em 3 tabelas;
- `menu_configurator` em tela nova;
- `orm:generate-proxies` para entidade com relação;
- probe pelo `GenericRestController`;
- foto das telas;
- commits com `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`, nunca `web/.htaccess`.

Valores da spec:
- token de 32 bytes aleatórios em base64url, guardado como sha256 hex;
- validade de 7 dias;
- escala de 1 a 5;
- Big Five `score = (soma−10)/40×100`, com invertidas = `6−v`;
- DISC `score = (soma−6)/24×100`, empate na ordem D, I, S, C;
- faixas 0–33, 34–66 e 67–100;
- páginas de 10 frases;
- versões `IPIP50-PT-v1` e `DISC-HRR-v1`.

---

### Task 1: InventoryCatalog (puro)
**Files:** `Service/Assessment/InventoryCatalog.php`, `test/Service/Assessment/InventoryCatalogTest.php`.
Cada item: `['code' => 'B5-E01', 'instrument' => 'BIG5', 'factor' => 'E', 'reverse' => false, 'text' => '...']`. As 50 frases do IPIP vêm de `ipip.ori.org` (tradução brasileira de Keila Brockveld, 100 marcadores; "Am exacting in my work" pela tradução de Portugal de João P. Oliveira; correções ortográficas anotadas). As 24 do DISC são próprias.

**API:** `items(string $instrument): array`, `item(string $code): ?array`, `sequence(array $instruments): string[]` (ordem de aplicação intercalada, determinística), `pages(array $instruments, int $size = 10): string[][]`, `factors(string $instrument): string[]`, `version(string $instrument): string`, `INSTRUMENTS = ['BIG5', 'DISC']`.

**Testes:**
- 50 e 24 itens;
- códigos únicos;
- 10 por fator no Big Five e 6 no DISC;
- a quantidade de invertidas bate com a chave oficial (E 5, A 4, C 4, N 8, O 3);
- `sequence` cobre todos sem repetir e é estável;
- `pages` tem 8 páginas, com 4 frases na última;
- nenhum texto vazio.

### Task 2: InventoryScoring (puro)
**Files:** `Service/Assessment/InventoryScoring.php` + teste.

**API:**
- `score(string $instrument, array $answers /* code => 1..5 */): array /* factor => ['raw' => int, 'score' => float] */`;
- `discStyles(array $scores): array{primary: string, secondary: string}`;
- `band(float $score): string /* LOW|MID|HIGH */`.

Lança `AssessmentRuleException` para item faltando, valor fora de 1–5 ou código de outro inventário.

**Testes:**
- tudo 5 dá E=?: sai de extremos coerentes considerando as invertidas;
- respostas "perfeitas" dão 100 e as opostas dão 0;
- as invertidas contam `6−v`;
- casas decimais;
- empate no DISC resolvido por D, I, S, C;
- as faixas nos limites 33,3 / 33,4 / 66,6 / 66,7;
- as recusas.

### Task 3: AssessmentToken + AssessmentRules (puros)
**Files:** `Service/Assessment/AssessmentToken.php`, `Service/Assessment/AssessmentRules.php`, `Exception/AssessmentRuleException.php` (extends `AttendanceServiceException`) + testes.

**API:**
- `AssessmentToken::generate(): string`, `hash(string): string`, `looksValid(string): bool` (43 caracteres base64url);
- `AssessmentRules::assertOpen(string $status, ?DateTime $expiresAt, DateTime $now)`, `assertConsent(string $subjectType, ?DateTime $consentAt)`, `isExpired(...)`, `assertInstruments(array)`.

**Testes:**
- o token tem 43 caracteres base64url e não se repete em 1000 geradas;
- o hash é um sha256 hex estável;
- `looksValid` recusa lixo;
- `assertOpen` recusa COMPLETED, CANCELLED e vencido, e aceita no limite exato;
- `assertConsent` exige consentimento só de CANDIDATE;
- `assertInstruments` recusa desconhecido e vazio.

### Task 4: Migração 017 + entidades
**Files:** `br-customizations/attendance-br/migrations/017_assessments.sql`, `entity/Assessment.php`, `AssessmentAnswer.php`, `AssessmentResult.php` (gerados como em Formulários).

**Conteúdo da migração:**
- as tabelas da spec;
- a tela `brAssessments` e a `brAssessmentProfile` no módulo `recruitment` (Admin), com `menu_configurator` = um `AssessmentMenuConfigurator` que devolve o item de menu nível 2 "Behavioral Profiles";
- o menu nível 2 sob Recrutamento (parent 65, order 300);
- as APIs `AssessmentAPI` e `AssessmentResultAPI` (Admin) e `MyAssessmentAPI` (1, 2, 3 leitura e atualização);
- lang string de grupo 1 com pt_BR.

**Probe:** persistir, reler e apagar em cascata a partir do candidato.

### Task 5: AssessmentService
**Files:** `Service/Assessment/AssessmentService.php` + probe.

**API:**
- `inviteCandidate(Candidate, ?Vacancy, ?int createdBy): array{assessment, token}`;
- `inviteEmployees(scope, subunitId, employeeId, createdBy): Assessment[]` (reaproveita `FormService::audience` por meio de uma pequena classe de público compartilhada, ou chamando o método; sem duplicar a regra);
- `resend(Assessment): string` (novo token);
- `cancel(Assessment)`;
- `findByToken(string): ?Assessment` (compara pelo hash; recusa vencido e fechado);
- `giveConsent(Assessment, ip, ua)`;
- `saveAnswers(Assessment, array code => value)` (upsert; valida os códigos contra os instrumentos do convite; marca `started_at`);
- `progress(Assessment): array{answered, total, nextPage}`;
- `complete(Assessment): void` (confere que está completo, grava os results, marca COMPLETED e apaga `token_hash`);
- `profile(Assessment): array` (os results, os estilos, as faixas, as versões);
- `linkHiredEmployee(Candidate, Employee)`;
- `pendingFor(Employee): Assessment[]`.

**Probe (rollback):** o fluxo completo do candidato; token errado; token antigo depois de reenviar; convites de funcionários pelo público; ligação na contratação.

### Task 6: Público (sem login)
**Files:**
- `Controller/Public/AssessmentPublicViewController.php`: `GET /recruitmentApply/assessment/{token}`, template `no_header.html.twig`, componente `br-assessment-public` com prop `token`. Token inválido: a página renderiza o estado "link inativo" (200), sem distinguir o motivo.
- `Controller/Public/AssessmentPublicRestController.php`: `GET|PUT|POST /recruitmentApply/assessment/{token}/api`:
  - GET devolve `{firstName, vacancyName, consentGiven, instruments, pages: [[{code, text}]], answers: {code: v}}` ou 404 genérico;
  - PUT `{consent: true}` ou `{answers: {...}}`;
  - POST `{complete: true}`.
  - Resposta JSON `{data}` ou `{error: {message}}` com 400/404.
- **Hook** no `ApplicantController` nativo: depois do `commitTransaction`, `try { inviteCandidate } catch { log }`. Sucesso redireciona para `/recruitmentApply/assessment/{token}?applied=1`; falha cai no redirecionamento nativo de sempre.
- Rotas em `config/routes.yaml` do plugin de Ponto.

**Verificação por HTTP real** (sem login): página com token bom dá 200 com o componente; com token ruim, 200 e estado inativo; API GET com token ruim, 404 com mensagem genérica; o fluxo completo pela API pública com um convite criado por probe.

### Task 7: APIs do RH e do funcionário
**Files:**
- `Api/AssessmentAPI.php` (Crud):
  - GET lista com filtros `subjectType`, `status` e `vacancyId`;
  - POST `{candidateId, vacancyId?}` devolve `{id, link}`; POST `{scope, subunitId?, employeeId?}` devolve `{created}`;
  - PUT `{id}` com `{action: resend|cancel}` (resend devolve `{link}`).
- `Api/AssessmentResultAPI.php`: GET `?assessmentId`, o perfil.
- `Api/MyAssessmentAPI.php`:
  - GET lista dos pendentes do funcionário;
  - GET `{id}` na mesma forma da API pública;
  - PUT `{id}` com `{answers}` ou `{complete: true}`.
- Integração na aba Provas: `MyFormAPI::getAll` inclui os convites pendentes como itens `section: 'pending', kind: 'ASSESSMENT'` e soma no `pendingCount`.

**Testes:** validação (sem banco) e o `ApiRouteContractTest`, que pega sozinho.

### Task 8: i18n
Chaves `assessment_*` na tabela abaixo, geradas por script como na migração `014_forms_i18n.sql`, em `br-customizations/i18n/017_assessments_i18n.sql`.

### Task 9: Frontend de quem responde
**Files:**
- `components/assessment/AssessmentRunner.vue` + `.scss` + spec: páginas, 1–5, progresso, salvar por página, retomar na próxima página incompleta, concluir;
- `pages/assessment/AssessmentPublic.vue` + spec: estado inativo, boas-vindas com a vaga, consentimento obrigatório, runner, obrigado; `?applied=1` mostra "Candidatura enviada";
- a aba Provas (`MobileForms`) abre o item `ASSESSMENT` no runner usando `MyAssessmentAPI`.

### Task 10: Frontend do RH
**Files:**
- `pages/assessment/BrAssessments.vue` + spec:
  - lista e filtros;
  - novo convite (candidato por busca em `/api/v2/recruitment/candidates`, ou público de funcionários);
  - link com Copiar e WhatsApp (`https://wa.me/55{dígitos}?text=`);
  - reenviar e cancelar.
- `pages/assessment/BrAssessmentProfile.vue` + spec: barras, faixas, estilos DISC, textos de fator e o aviso legal fixo.
- Controllers e registro no `index.ts`.

### Task 11: Contratação
**Files:**
- `AbstractCandidateActionAPI` nativo: depois do `saveEmployee` da ação HIRE, chamar `(new AssessmentService())->linkHiredEmployee($candidate, $employee)`;
- probe pela API nativa de contratação, com rollback.

### Task 12: Deploy, ponta a ponta, fotos, docs, push
- `yarn build` e deploy do plugin (proxies);
- migrações 017 e i18n;
- conferir o JS servido;
- rotas;
- ponta a ponta pelo `GenericRestController` e pela API pública real;
- fotos (pública em 390px, perfil em 1150px);
- README e TODO;
- memória;
- commit e push.

## Textos (Task 8)
Gerados pela tabela; as chaves ficam em `docs` e no SQL.
