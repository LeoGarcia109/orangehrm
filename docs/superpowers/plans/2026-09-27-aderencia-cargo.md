# Perfil do cargo e comparação de aderência — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** O RH define o perfil ideal de cada cargo (faixas Big Five/DISC, importância, competências) e compara até 20 pessoas contra ele: radar sobreposto, ranking com tabela fator por fator e duelo X × Y em barras de RPG.

**Architecture:** Tudo no `orangehrmAttendancePlugin`, em `Service/JobFit/`.
- **Classes puras:** `JobFit`, `JobProfileSuggestion` e `JobProfileRules` fazem o cálculo e a validação, testadas sem banco.
- **Serviço:** `JobFitService` lê os testes concluídos (`ohrm_br_assessment*`), grava o perfil e as notas e liga ao cargo nativo (`JobTitle`).
- **APIs:** só Admin.
- **Frontend:** o `RadarChart` ganha várias séries e a faixa do cargo; entram telas e componentes novos em `pages/jobfit/` e `components/jobfit/`.

**Tech Stack:** PHP 8.3, Symfony, Doctrine, OrangeHRM API v2, Vue 3, Jest, MySQL 8.

**Spec:** `docs/superpowers/specs/2026-09-27-aderencia-cargo-design.md`

## Global Constraints
Herdadas dos planos anteriores:
- harness `br-customizations/tests/phpunit-nodb.xml`, rodando no `docker run ... orangehrm/orangehrm:latest`;
- `yarn build` sempre;
- cabeçalho GPL em arquivo novo;
- mensagens de exceção PHP sem acento;
- i18n no grupo 17;
- papéis 1 = Admin, 2 = ESS, 3 = Supervisor;
- permissão de API nas 3 tabelas (`ohrm_data_group`, `ohrm_api_permission`, `ohrm_user_role_data_group`);
- `menu_configurator` em toda tela nova;
- `orm:generate-proxies` no deploy (`$SP/deploy_plugin.sh`);
- sondas pela `GenericRestController`;
- foto das telas pelo `chrome-headless-shell`;
- commits com `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`, nunca `web/.htaccess`.

Valores da spec:
- escala dos fatores de 0 a 100;
- faixa em múltiplos de 5, com `min ≤ max`;
- pesos: ignorar 0, desejável 1, essencial 2;
- nota do fator: `max(0, 100 − 2,5 × d)`;
- cores: IN com d = 0, NEAR com 0 < d ≤ 10, FAR com d > 10;
- nota de competência `(r − 1)/4 × 100`, com peso 1 ou 2 e nível mínimo de 1 a 5 (padrão 3);
- geral = `p × comportamental + (1 − p) × competências`, com `p` padrão 50;
- marca "parcial";
- alertas `ESSENTIAL_FACTOR` e `ESSENTIAL_COMPETENCY`;
- sugestão: média ± DP amostral, piso/teto de 5, largura mínima 10, uma referência dá ± 10, de 1 a 20 referências;
- comparação com 1 a 20 pessoas; radar com até 6 linhas; duelo com 2;
- até 30 competências, nome com até 100 caracteres;
- pessoa `c{id}` ou `e{id}`;
- telas sem ressalvas psicométricas, falando em "teste de perfil".

---

### Task 1: JobFit (puro)

**Files:**
- Create: `src/plugins/orangehrmAttendancePlugin/Service/JobFit/JobFit.php`
- Test: `src/plugins/orangehrmAttendancePlugin/test/Service/JobFit/JobFitTest.php`
- Modify: `br-customizations/tests/phpunit-nodb.xml` (acrescentar `<directory>/app/src/plugins/orangehrmAttendancePlugin/test/Service/JobFit</directory>` e `<directory>/app/src/plugins/orangehrmAttendancePlugin/test/Api/JobFit</directory>`)

**Interfaces (Produces):**
```php
final class JobFit {
    public const WEIGHT_IGNORE = 0; WEIGHT_DESIRABLE = 1; WEIGHT_ESSENTIAL = 2;
    public const IN = 'IN'; NEAR = 'NEAR'; FAR = 'FAR';
    public const ALERT_FACTOR = 'ESSENTIAL_FACTOR'; ALERT_COMPETENCY = 'ESSENTIAL_COMPETENCY';
    public static function distance(float $score, int $min, int $max): float;
    public static function factorFit(float $score, int $min, int $max): float;   // 0..100, 1 casa
    public static function color(float $distance): string;
    public static function ratingFit(int $rating): float;                         // 1..5 -> 0..100
    /**
     * $profile = ['behaviorWeight' => int, 'factors' => [['instrument','factor','min','max','weight']],
     *             'competencies' => [['id','name','weight','minLevel']]]
     * $scores  = ['BIG5' => ['E' => 75.0, ...], 'DISC' => ['D' => 20.0, ...]]
     * $ratings = [competencyId => 1..5]
     * returns ['factors' => [['instrument','factor','score','distance','fit','color']],
     *          'competencies' => [['id','rating','fit','belowMin']],
     *          'behavior' => float, 'competency' => ?float, 'overall' => float,
     *          'partial' => bool, 'alerts' => string[]]
     */
    public static function evaluate(array $profile, array $scores, array $ratings): array;
    /** rows each with 'name' and 'overall'; overall desc, then name asc (strcoll-free: mb_strtolower compare) */
    public static function rank(array $rows): array;
}
```
Regras:
- Fator com peso 0 aparece em `factors`, mas fica fora do bloco.
- Fator sem score (teste antigo sem o instrumento) sai de `factors`.
- Sem fator com peso > 0 e com score, `behavior = 0`.
- Sem competências no cargo: `competency = null`, `overall = behavior`, `partial = false`.
- Com competências e nenhuma nota: `competency = null`, `overall = behavior`, `partial = true`.
- Com parte das notas: o bloco usa as que têm nota e `partial = true`.
- `overall = round(p/100*behavior + (1-p/100)*competency, 1)`.
- Valores com 1 casa decimal.

- [ ] **Step 1: testes.**
  - `distance`: 70 em [60,90] dá 0; 50 dá 10; 95 dá 5.
  - `factorFit`: dentro dá 100; d = 10 dá 75; d = 40 dá 0; d = 55 dá 0.
  - `color`: 0 dá IN; 10 dá NEAR; 10,5 dá FAR.
  - `ratingFit`: 1 → 0, 3 → 50, 5 → 100.
  - `evaluate`, perfil com C(BIG5) [60,90] peso 2, E [50,85] peso 1, O peso 0 e competências id 1 (peso 2, mín. 4) e id 2 (peso 1, mín. 3):
    - scores C = 50, E = 70, O = 10 dão behavior = (75×2 + 100×1)/3 = 83,3 e o alerta ESSENTIAL_FACTOR;
    - sem notas: `overall = 83,3`, `partial = true`, `competency = null`;
    - notas {1: 3, 2: 5}: competency = (50×2 + 100×1)/3 = 66,7; overall com p = 50 dá 75,0; alerta ESSENTIAL_COMPETENCY (3 < 4); `partial = false`;
    - p = 100 dá overall = behavior;
    - cargo sem competências: `partial = false`.
  - `rank`: ordena por overall e desempata pelo nome.
- [ ] **Step 2:** rodar e ver falhar:
  `docker run --rm -v /home/leo/orangehrm-repo:/app -w /app orangehrm/orangehrm:latest php /app/src/vendor/bin/phpunit -c /app/br-customizations/tests/phpunit-nodb.xml --filter JobFit`
- [ ] **Step 3:** implementar.
- [ ] **Step 4:** rodar e ver passar.
- [ ] **Step 5:** commit `feat(br): aderência ao cargo - cálculo puro (JobFit)`.

### Task 2: JobProfileSuggestion + JobProfileRules (puros)

**Files:**
- Create: `Service/JobFit/JobProfileSuggestion.php`, `Service/JobFit/JobProfileRules.php`, `Exception/JobFitRuleException.php` (extends `AttendanceServiceException`, com `because(string)` como `AssessmentRuleException`).
- Test: `test/Service/JobFit/JobProfileSuggestionTest.php`, `test/Service/JobFit/JobProfileRulesTest.php`.

**Interfaces (Produces):**
```php
final class JobProfileSuggestion {
    /** @param array[] $scoreSets list of ['BIG5'=>[f=>score], 'DISC'=>[f=>score]], 1..20
     *  @return array[] 9 rows ['instrument','factor','min','max'] na ordem de InventoryCatalog::factors */
    public static function suggest(array $scoreSets): array;   // JobFitRuleException se vazio ou > 20
}
final class JobProfileRules {
    public const MAX_PEOPLE = 20; MAX_COMPETENCIES = 30;
    /** valida e normaliza o corpo do PUT; devolve ['behaviorWeight'=>int,'factors'=>[9],'competencies'=>[...]] */
    public static function normalizeProfile(array $payload): array;
    /** "c12,e5" -> [['type'=>'c','id'=>12],['type'=>'e','id'=>5]]; 1..20, sem repetição */
    public static function parseSubjects(string $csv): array;
    /** "1,2,3" -> [1,2,3]; 1..20 */
    public static function parseIds(string $csv): array;
    /** o perfil de cargo novo: 9 fatores 0..100 peso 1, behaviorWeight 50, sem competências */
    public static function defaultProfile(): array;
}
```
Sugestão: DP amostral (n−1), com n = 1 → ± 10. `min = max(0, floor((m − dp)/5)*5)` e `max = min(100, ceil((m + dp)/5)*5)`. Largura < 10: alarga 5 de cada lado, respeitando 0 e 100, até dar 10.

`normalizeProfile`:
- exige exatamente os 9 pares instrumento+fator (`InventoryCatalog::factors`);
- `min`/`max` inteiros de 0 a 100, múltiplos de 5, `min ≤ max`;
- peso em {0, 1, 2}, com ao menos um > 0;
- `behaviorWeight` de 0 a 100;
- competências: `name` com trim, não vazio, até 100 caracteres e único sem diferenciar maiúsculas; peso em {1, 2}; `minLevel` de 1 a 5; até 30;
- `id` opcional (inteiro) para manter as notas de uma competência existente;
- a ordem da lista vira `sortOrder`.

- [ ] **Step 1: testes.**
  - Sugestão:
    - uma referência com E = 62 dá [50, 75]: 52 e 72 viram piso e teto de 5;
    - três referências E = 60, 70, 80 (média 70, DP 10) dão [60, 80];
    - todas iguais a 98 dão largura mínima dentro de 100: [90, 100];
    - vazio lança exceção; 21 lança exceção.
  - Regras:
    - perfil válido passa;
    - falta um fator, `min` 12, `min > max`, todos os pesos 0, `behaviorWeight` 101, competência sem nome, nome repetido ("Caixa"/"caixa"), `minLevel` 6 e 31 competências: tudo lança exceção.
  - `parseSubjects`:
    - "c1,e2" funciona;
    - "x1", "c0", "", repetição e 21 itens lançam exceção.
- [ ] **Step 2:** rodar e ver falhar (`--filter JobProfile`).
- [ ] **Step 3:** implementar.
- [ ] **Step 4:** rodar e ver passar.
- [ ] **Step 5:** commit `feat(br): aderência ao cargo - sugestão e regras`.

### Task 3: Migração 018 + entidades

**Files:**
- Create: `br-customizations/attendance-br/migrations/018_job_fit.sql`
- Create: `src/plugins/orangehrmAttendancePlugin/entity/JobProfile.php`, `JobProfileFactor.php`, `JobCompetency.php`, `CompetencyRating.php`

**Migração** (idempotente, `SET NAMES utf8mb4`):
```sql
CREATE TABLE IF NOT EXISTS `ohrm_br_job_profile` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_title_id` INT NOT NULL,
  `behavior_weight` TINYINT UNSIGNED NOT NULL DEFAULT 50,
  `updated_by_emp_number` INT NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_job_profile_title` (`job_title_id`),
  CONSTRAINT `fk_job_profile_title` FOREIGN KEY (`job_title_id`) REFERENCES `ohrm_job_title` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Perfil ideal do cargo';

CREATE TABLE IF NOT EXISTS `ohrm_br_job_profile_factor` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_profile_id` INT UNSIGNED NOT NULL,
  `instrument` VARCHAR(10) NOT NULL,
  `factor` CHAR(1) NOT NULL,
  `min_score` TINYINT UNSIGNED NOT NULL,
  `max_score` TINYINT UNSIGNED NOT NULL,
  `weight` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_job_profile_factor` (`job_profile_id`, `instrument`, `factor`),
  CONSTRAINT `fk_job_profile_factor` FOREIGN KEY (`job_profile_id`) REFERENCES `ohrm_br_job_profile` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_job_competency` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_profile_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `weight` TINYINT UNSIGNED NOT NULL,
  `min_level` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_job_competency_profile` (`job_profile_id`, `sort_order`),
  CONSTRAINT `fk_job_competency_profile` FOREIGN KEY (`job_profile_id`) REFERENCES `ohrm_br_job_profile` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ohrm_br_competency_rating` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `competency_id` INT UNSIGNED NOT NULL,
  `candidate_id` INT NULL DEFAULT NULL,
  `employee_id` INT NULL DEFAULT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `rated_by_emp_number` INT NULL DEFAULT NULL,
  `rated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_rating_candidate` (`competency_id`, `candidate_id`),
  UNIQUE INDEX `idx_rating_employee` (`competency_id`, `employee_id`),
  CONSTRAINT `fk_rating_competency` FOREIGN KEY (`competency_id`) REFERENCES `ohrm_br_job_competency` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `ohrm_job_candidate` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_employee` FOREIGN KEY (`employee_id`) REFERENCES `hs_hr_employee` (`emp_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Telas, menu e permissões, no molde da 017:
- telas `BR Job Profiles` (`brJobProfiles`), `BR Job Profile` (`brJobProfile`) e `BR Compare Profiles` (`brProfileCompare`), módulo recruitment, `menu_configurator` `OrangeHRM\\Attendance\\Menu\\AssessmentMenuConfigurator`, Admin com r/c/u = 1;
- menu `Job Profiles` (400) e `Compare Profiles` (500) sob Recruitment, com strings no grupo 1 e pt_BR "Perfis de cargo" / "Comparar perfis";
- data groups:

| data group | API | permissões |
|---|---|---|
| `apiv2_attendance_br_job_profile` | `JobProfileAPI` | r/u |
| `apiv2_attendance_br_job_profile_suggestion` | `JobProfileSuggestionAPI` | r |
| `apiv2_attendance_br_profile_people` | `ProfilePeopleAPI` | r |
| `apiv2_attendance_br_profile_comparison` | `ProfileComparisonAPI` | r |
| `apiv2_attendance_br_competency_rating` | `CompetencyRatingAPI` | r/u |

  Tudo só para o papel 1.

**Entidades:**
- Anotações Doctrine no molde de `AssessmentResult`.
- `JobProfile`:
  - `ManyToOne JobTitle` com `unique`, e `OneToMany factors` e `competencies`, ambos com `cascade={"persist","remove"}` e `orphanRemoval=true`;
  - `competencies` ordenado por `sortOrder`;
  - `addFactor`, `addCompetency` e `removeCompetency`.
- `JobProfileFactor`: `ManyToOne JobProfile`, `instrument`, `factor`, `minScore`, `maxScore`, `weight`.
- `JobCompetency`: `ManyToOne JobProfile`, `name`, `weight`, `minLevel`, `sortOrder`.
- `CompetencyRating`: `ManyToOne JobCompetency`, `?Candidate`, `?Employee`, `rating`, `?ratedByEmpNumber` e `ratedAt`.

- [ ] **Step 1:** escrever a migração e aplicar duas vezes (idempotência):
  `docker exec -i orangehrm-db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" orangehrm' < br-customizations/attendance-br/migrations/018_job_fit.sql`
- [ ] **Step 2:** escrever as 4 entidades; deploy (`$SP/deploy_plugin.sh`, que gera os proxies).
- [ ] **Step 3:** sonda `probe_jobfit_entities.php`, em transação com rollback: criar um cargo, um perfil com 9 fatores e 2 competências, uma nota; reler; apagar o cargo e ver a cascata. Rodar como www-data no container.
- [ ] **Step 4:** commit `feat(br): aderência ao cargo - tabelas, telas e permissões (migração 018)`.

### Task 4: JobFitService

**Files:**
- Create: `Service/JobFit/JobFitService.php`
- Modify: `Service/Assessment/AssessmentService.php` (`linkHiredEmployee` chama `(new JobFitService())->moveRatingsToEmployee($candidate, $employee)` antes do `flush`)

**Interfaces (Produces):**
```php
class JobFitService {
    /** cargos não excluídos: [['id','name','hasProfile','competencies','updatedAt' => 'Y-m-d H:i'|null]] por nome */
    public function jobTitles(): array;
    /** perfil gravado ou JobProfileRules::defaultProfile(); + 'exists' => bool, 'jobTitle' => ['id','name'] */
    public function profileFor(JobTitle $jobTitle): array;
    /** normaliza (JobProfileRules), cria ou substitui fatores; competências: mantém as que vêm com id (atualiza), apaga as ausentes, cria as novas */
    public function saveProfile(JobTitle $jobTitle, array $payload, ?int $by): array;
    /** teste concluído mais recente por pessoa: ['assessmentId','completedAt','scores' => ['BIG5'=>[..],'DISC'=>[..]]] ou null */
    public function latestScores(string $type, int $id): ?array;
    /** lista de seleção; $filters = ['type' => 'c'|'e'|null, 'vacancyId' => ?int, 'subunitId' => ?int, 'name' => ?string]
     *  [['key','type','id','name','subunit','vacancies' => string[],'completedAt','assessmentId']] por nome */
    public function people(array $filters): array;
    /** faixas sugeridas a partir de funcionários (JobFitRuleException se algum não tem teste concluído) */
    public function suggest(array $empNumbers): array;
    /** ['jobTitle' => ['id','name'], 'profile' => ..., 'people' => JobFit::rank([... + 'key','type','name','subunit','assessmentId','ratings'])]
     *  JobFitRuleException se o cargo não tem perfil ou alguém não tem teste concluído (mensagem com o nome) */
    public function compare(JobTitle $jobTitle, array $subjects): array;
    /** grava (1..5) ou apaga (null) a nota; a pessoa precisa existir */
    public function rate(string $type, int $id, JobCompetency $competency, ?int $rating, ?int $by): void;
    public function moveRatingsToEmployee(Candidate $candidate, Employee $employee): void;
    /** o cargo de uma vaga, para preencher a comparação */
    public function jobTitleIdForVacancy(int $vacancyId): ?int;
}
```
Detalhes:
- `people`:
  - Carrega os `Assessment` COMPLETED, com `completedAt` desc.
  - Chave: `e{empNumber}` se `employee` não é nulo, senão `c{candidateId}`. Fica o primeiro de cada chave.
  - Filtros:
    - tipo;
    - vaga: o candidato tem `CandidateVacancy` para a vaga, ou `assessment.vacancy` é a vaga;
    - empresa: `subDivision` com `lft`/`rgt` dentro da sub-unidade, só para funcionários;
    - nome: `mb_stripos` em nome e sobrenome.
  - Funcionário com `purgedAt` fica fora.
  - `subunit` é o nome da sub-unidade do funcionário.
- Notas:
  - lidas por `employee` quando o tipo é `e`, senão por `candidate` (com `employee IS NULL` não é exigido);
  - `moveRatingsToEmployee` preenche `employee` nas notas do candidato, a não ser que o funcionário já tenha nota para aquela competência.

- [ ] **Step 1:** sonda `probe_jobfit_service.php`, em transação com rollback:
  - cria um cargo, um candidato com teste concluído (usar `AssessmentService` como em `probe_assessment_service.php`) e um funcionário com teste concluído;
  - `saveProfile` e depois `profileFor` devolvem o que foi salvo;
  - salvar de novo sem uma competência apaga a competência e as notas dela;
  - `people` com cada filtro;
  - `suggest([emp])`;
  - `compare`:
    - devolve ranking e `partial`;
    - com uma nota, recalcula;
    - cargo sem perfil lança exceção;
  - `rate(null)` apaga;
  - contratação (`linkHiredEmployee`) move a nota e a pessoa passa a ser `e{id}`.
  - Esperado: tudo OK.
- [ ] **Step 2:** implementar até a sonda passar (deploy a cada rodada).
- [ ] **Step 3:** rodar a suíte PHP inteira (sem regressão em `AssessmentService`).
- [ ] **Step 4:** commit `feat(br): aderência ao cargo - serviço`.

### Task 5: APIs, rotas, telas e menu

**Files:**
- Create: `Api/JobProfileAPI.php` (`CrudEndpoint` + `ResourceEndpoint`):
  - `getAll` → `jobTitles()`;
  - `getOne` → `profileFor`;
  - `update` → `saveProfile`, com corpo `behaviorWeight`, `factors` (array) e `competencies` (array);
  - regras com `Rules::ARRAY_TYPE`;
  - `id` = `jobTitleId`;
  - create/delete → not implemented.
- Create: `Api/JobProfileSuggestionAPI.php` (`CollectionEndpoint`): `getAll`, com `empNumbers` = string CSV.
- Create: `Api/ProfilePeopleAPI.php` (`CollectionEndpoint`):
  - `getAll`, filtros `type` (IN c/e), `vacancyId`, `subunitId` e `name`, todos opcionais;
  - `meta.jobTitleId` = `jobTitleIdForVacancy` quando há vaga.
- Create: `Api/ProfileComparisonAPI.php` (`CollectionEndpoint`): `getAll`, com `jobTitleId` e `subjects` obrigatórios.
- Create: `Api/CompetencyRatingAPI.php` (`ResourceEndpoint` + `CrudEndpoint`):
  - `update`, com `id` = competência e corpo `subject` (`c12`/`e5`) e `rating` opcional (1–5);
  - sem `rating`, apaga;
  - devolve `['competencyId', 'subject', 'rating']`.
- Todas as APIs: `JobFitRuleException` → `getBadRequestException($e->getMessage())`.
- Create: `Controller/BrJobProfilesController.php` (componente `br-job-profiles`).
- Create: `Controller/BrJobProfileController.php` (`br-job-profile`, prop `job-title-id` vinda de `id`).
- Create: `Controller/BrProfileCompareController.php`:
  - componente `br-profile-compare`;
  - props `job-title-id`, `subjects` e `vacancy-id`, lidas da query string (`$request->query`);
  - número inválido vira `null`.
- Modify: `config/routes.yaml`:
  - `/api/v2/attendance/br/job-profiles` (GET);
  - `/api/v2/attendance/br/job-profiles/{id}` (GET, PUT);
  - `/api/v2/attendance/br/job-profile-suggestion` (GET);
  - `/api/v2/attendance/br/profile-people` (GET);
  - `/api/v2/attendance/br/profile-comparison` (GET);
  - `/api/v2/attendance/br/competency-ratings/{id}` (PUT);
  - páginas `/recruitment/brJobProfiles`, `/recruitment/brJobProfile/{id}` e `/recruitment/brProfileCompare`.
- Modify: `Menu/AssessmentMenuConfigurator.php`:
  - mapa `['brAssessmentProfile' => 'brAssessments', 'brJobProfile' => 'brJobProfiles']`;
  - só sobrescreve quando a tela está no mapa;
  - as outras telas marcam o próprio item.
- Test: `test/Api/JobFit/JobFitApiValidationTest.php`, no molde de `AssessmentApiValidationTest`:
  - PUT do perfil aceita arrays e recusa `behaviorWeight` como texto;
  - comparação exige `jobTitleId` e `subjects`;
  - pessoas aceita filtros vazios e recusa `type` = x;
  - nota aceita `subject` sem `rating` e recusa `rating` 6.

- [ ] **Step 1:** escrever o teste de validação e ver falhar.
- [ ] **Step 2:** implementar as APIs, rotas, controllers e menu; ver o teste passar; rodar a suíte PHP (inclui `ApiRouteContractTest`).
- [ ] **Step 3:** deploy.
- [ ] **Step 4:** sonda `probe_jobfit_api.php` pela `GenericRestController`, com o helper `rest()` de `probe_assessment_api.php`:
  - PUT do perfil dá 200;
  - GET getOne;
  - lista com `hasProfile`;
  - sugestão;
  - pessoas com filtro de vaga e `meta.jobTitleId`;
  - comparação;
  - PUT da nota e depois a comparação muda;
  - PUT sem nota apaga;
  - 400 para cargo sem perfil;
  - 400 para pessoa sem teste.
- [ ] **Step 5:** conferir com curl (sessão Admin, como antes) que as 3 páginas abrem com 200 e marcam o item certo do menu.
- [ ] **Step 6:** php-cs-fixer nos arquivos novos.
- [ ] **Step 7:** commit `feat(br): aderência ao cargo - APIs e telas`.

### Task 6: Strings (i18n 018)

**Files:**
- Create: `br-customizations/i18n/018_job_fit_i18n.sql`, gerado por `$SP/gen_i18n_jobfit.py`, que é uma cópia de `gen_i18n_assessment.py` com:
  - prefixo `jobfit\_`;
  - tabela lida deste plano;
  - cabeçalho "Migracao i18n 018";
  - sem o bloco de DELETE.

| key | en | pt_BR |
|---|---|---|
| jobfit_title_profiles | Job profiles | Perfis de cargo |
| jobfit_title_compare | Compare profiles | Comparar perfis |
| jobfit_job_title | Job title | Cargo |
| jobfit_has_profile | Profile defined | Perfil definido |
| jobfit_no_profile | No profile | Sem perfil |
| jobfit_competencies | Competencies | Competências |
| jobfit_updated_at | Last change | Última alteração |
| jobfit_edit | Edit | Editar |
| jobfit_new_job_title | Add job title | Cadastrar cargo |
| jobfit_no_job_titles | No job titles yet. Job titles are created in Admin → Job → Job Titles. | Nenhum cargo cadastrado. Os cargos são criados em Admin → Trabalho → Cargos. |
| jobfit_factors | Behavioural factors | Fatores comportamentais |
| jobfit_min | Min | Mín. |
| jobfit_max | Max | Máx. |
| jobfit_weight_0 | Ignore | Ignorar |
| jobfit_weight_1 | Desirable | Desejável |
| jobfit_weight_2 | Essential | Essencial |
| jobfit_suggest | Suggest from employees | Sugerir a partir de funcionários |
| jobfit_suggest_hint | Pick the reference employees: each range becomes the mean ± 1 SD of their profiles. Nothing is saved until you save. | Escolha os funcionários de referência: cada faixa vira a média ± 1 DP dos perfis deles. Nada é salvo até você salvar. |
| jobfit_apply | Apply | Aplicar |
| jobfit_applied | Suggested ranges applied. Review them and save. | Faixas sugeridas aplicadas. Revise e salve. |
| jobfit_no_reference | Choose at least one employee. | Escolha ao menos um funcionário. |
| jobfit_competency_name | Competency | Competência |
| jobfit_min_level | Minimum level | Nível mínimo |
| jobfit_add_competency | Add competency | Adicionar competência |
| jobfit_behavior_weight | Weight of the behavioural profile | Peso do perfil comportamental |
| jobfit_weight_split | {b}% behavioural · {c}% competencies | {b}% comportamental · {c}% competências |
| jobfit_save | Save | Salvar |
| jobfit_saved | Profile saved | Perfil salvo |
| jobfit_company | Company | Empresa |
| jobfit_all_companies | All companies | Todas as empresas |
| jobfit_vacancy | Vacancy | Vaga |
| jobfit_all_vacancies | All vacancies | Todas as vagas |
| jobfit_type_all | Candidates and employees | Candidatos e funcionários |
| jobfit_type_c | Candidate | Candidato |
| jobfit_type_e | Employee | Funcionário |
| jobfit_search_name | Search by name | Buscar por nome |
| jobfit_select_all | Select all | Marcar todos |
| jobfit_clear | Clear | Limpar |
| jobfit_selected | {n} of {max} selected | {n} de {max} selecionados |
| jobfit_pick_job | Choose the job title | Escolha o cargo |
| jobfit_pick_people | Choose who to compare | Escolha quem comparar |
| jobfit_define_first | This job title has no profile yet. Define it before comparing. | Este cargo ainda não tem perfil. Defina o perfil antes de comparar. |
| jobfit_define_profile | Define profile | Definir perfil |
| jobfit_no_people | Nobody with a completed profile test matches the filters. | Ninguém com teste de perfil concluído atende aos filtros. |
| jobfit_tab_radar | Radar | Radar |
| jobfit_tab_table | Ranking and factors | Ranking e fatores |
| jobfit_tab_duel | Duel | Duelo |
| jobfit_overall | Fit | Aderência |
| jobfit_behavior | Behavioural | Comportamental |
| jobfit_partial | partial | parcial |
| jobfit_partial_hint | Some competencies have no rating yet | Há competências sem nota |
| jobfit_alert_factor | Outside the range in an essential factor | Fora da faixa em fator essencial |
| jobfit_alert_competency | Below the minimum in an essential competency | Abaixo do mínimo em competência essencial |
| jobfit_show_in_chart | Show in chart | Mostrar no gráfico |
| jobfit_chart_limit | The chart shows up to 6 people at a time. | O gráfico mostra até 6 pessoas por vez. |
| jobfit_job_range | Job range | Faixa do cargo |
| jobfit_closer | Closer to the job | Mais perto do cargo |
| jobfit_essential_mark | ★ essential | ★ essencial |
| jobfit_color_in | Inside the range | Dentro da faixa |
| jobfit_color_near | Up to 10 points outside | Até 10 pontos fora |
| jobfit_color_far | More than 10 points outside | Mais de 10 pontos fora |
| jobfit_rating | Rating | Nota |
| jobfit_no_rating | No rating | Sem nota |
| jobfit_duel_pick | Choose two people | Escolha duas pessoas |
| jobfit_comparison | Comparison | Comparação |
| jobfit_compare_selected | Compare selected | Comparar selecionados |
| jobfit_factor | Factor | Fator |
| jobfit_person | Person | Pessoa |

Nomes dos fatores: reaproveitar `attendance.assessment_f_big5_*` e `attendance.assessment_f_disc_*`. "Baixar PDF": reaproveitar `attendance.assessment_download_pdf`.

- [ ] **Step 1:** gerar o SQL, aplicar duas vezes e conferir no banco que há 68 chaves `jobfit_*` com pt_BR.
- [ ] **Step 2:** limpar o cache i18n (`src/cache/orangehrm`) e conferir `/core/i18n/messages` com uma chave nova.
- [ ] **Step 3:** commit `feat(br): aderência ao cargo - textos pt-BR`.

### Task 7: RadarChart com várias séries e faixa

**Files:**
- Modify: `src/client/src/orangehrmAttendancePlugin/components/assessment/RadarChart.vue`
- Test: `components/assessment/__tests__/RadarChart.spec.js` (os testes atuais continuam e ganham casos novos)
- Create: `src/client/src/orangehrmAttendancePlugin/utils/jobFit.js` + `utils/__tests__/jobFit.spec.js`

**Interfaces (Produces):**
- RadarChart:
  - Props novas, opcionais:
    - `series: [{key, color, values: number[]}]`;
    - `band: [{min, max} | null]`, alinhado com `axes`; `null` = eixo sem faixa.
  - `axes[i].muted` (eixo tracejado) e `axes[i].essential` (★ no rótulo).
  - Sem `series`, desenha `axes[].value` com a cor atual, como hoje.
  - A faixa é um `<path class="ohrm-radar__band" fill-rule="evenodd">`: o polígono do máximo mais o do mínimo invertido. Eixo sem faixa usa 0 nos dois.
- `jobFit.js`:
  - `SERIES_COLORS = ['#1D9E75', '#D85A30', '#534AB7', '#185FA5', '#D4537E', '#BA7517']`;
  - `subjectKey({subjectType, candidateId, employeeId})` → `e{employeeId}` se houver, senão `c{candidateId}`;
  - `duelWinner(a, b)`: `'A' | 'B' | null`; `null` se algum é `null` ou se são iguais;
  - `parseSubjects(str)` → array;
  - `formatSplit(t, b)`.

- [ ] **Step 1:** testes.
  - Com `series` de 2 pessoas, há 2 `polygon.ohrm-radar__series` com o `stroke` certo.
  - Com `band`, o `path.ohrm-radar__band` existe e o seu `d` contém 2 subcaminhos (`M` duas vezes).
  - Eixo `muted` tem `stroke-dasharray`.
  - O rótulo essencial termina em ★.
  - Sem `series`, continua o `polygon.ohrm-radar__shape`.
  - `duelWinner(80, 75)` dá A; `(75, 75)` dá null; `(null, 50)` dá null.
  - `subjectKey`.
- [ ] **Step 2:** `npx jest components/assessment utils` e ver falhar.
- [ ] **Step 3:** implementar.
- [ ] **Step 4:** ver passar; `npx eslint --ext .js,.vue src/orangehrmAttendancePlugin --fix`.
- [ ] **Step 5:** commit `feat(br): radar com várias séries e faixa do cargo`.

### Task 8: Perfis de cargo (lista e edição)

**Files:**
- Create: `src/client/src/orangehrmAttendancePlugin/pages/jobfit/BrJobProfiles.vue`
- Create: `pages/jobfit/BrJobProfile.vue`
- Create: `components/jobfit/FactorRangeRow.vue`
- Create: `pages/jobfit/jobfit.scss`
- Modify: `src/client/src/orangehrmAttendancePlugin/index.ts` (registrar `br-job-profiles`, `br-job-profile`, `br-profile-compare`)
- Test: `pages/jobfit/__tests__/BrJobProfiles.spec.js`, `pages/jobfit/__tests__/BrJobProfile.spec.js`, `components/jobfit/__tests__/FactorRangeRow.spec.js`

**Telas:**
- **`BrJobProfiles`**
  - `GET /api/v2/attendance/br/job-profiles`.
  - Tabela com cargo, situação (chip), competências, última alteração e **Editar** (`navigate('/recruitment/brJobProfile/{id}')`).
  - **Cadastrar cargo** abre `/admin/viewJobTitleList` com `window.open` em outra aba.
  - Vazio: `jobfit_no_job_titles`.
  - Visual com `ohrm-builder__*` e `ohrm-br-table`, igual ao `BrAssessments`.
- **`BrJobProfile`** (prop `jobTitleId`)
  - GET do perfil e cabeçalho com o nome do cargo.
  - Duas seções (Big Five e DISC), com uma `FactorRangeRow` por fator.
    - `FactorRangeRow` (props `label`, `modelValue: {min, max, weight}`; emite `update:modelValue`) tem:
      - uma trilha de 0 a 100 com a faixa pintada;
      - `input type=number` para mínimo e máximo, com `step` 5, de 0 a 100;
      - três botões de importância.
    - Com peso 0, a linha recebe `is-muted`.
    - Ao sair do campo, `min` > `max` troca os dois.
  - **Sugerir a partir de funcionários**: um painel com
    - o filtro de empresa (`/api/v2/admin/subunits`);
    - a lista vinda de `ProfilePeopleAPI`, com `type=e` e `subunitId`, em caixas de marcar;
    - o botão Aplicar: `GET job-profile-suggestion?empNumbers=`, que substitui só `min` e `max` e mostra `jobfit_applied`. Sem ninguém marcado, mostra `jobfit_no_reference`.
  - Competências:
    - linhas com nome, importância (desejável ou essencial), nível mínimo (select de 1 a 5), ↑ ↓ e remover;
    - Adicionar;
    - o `id` da competência é mantido no payload.
  - Peso comportamental: `input` de 0 a 100 com o texto `jobfit_weight_split`.
  - Salvar faz PUT, mostra o toast `jobfit_saved` (`this.$toast.saveSuccess()`) ou o erro 400 na tela.
  - Voltar leva à lista.

- [ ] **Step 1:** testes (mock do `APIService` como em `BrAssessmentProfile.spec.js`).
  - A lista renderiza os chips e navega ao editar.
  - O editor carrega 9 linhas.
  - Mudar a importância para essencial vai no PUT com `weight: 2`.
  - A sugestão aplicada muda só as faixas e não chama o PUT.
  - Remover a competência a tira do payload.
  - O 400 mostra a mensagem.
  - `FactorRangeRow` pinta a faixa (`left`/`width` em %) e troca `min` por `max` quando estão invertidos.
- [ ] **Step 2:** ver falhar.
- [ ] **Step 3:** implementar.
- [ ] **Step 4:** ver passar; lint.
- [ ] **Step 5:** commit `feat(br): telas de perfil de cargo`.

### Task 9: Comparar perfis (seleção, radar, ranking, duelo) + atalho na lista de perfis

**Files:**
- Create: `pages/jobfit/BrProfileCompare.vue`
- Create: `components/jobfit/PeoplePicker.vue`
- Create: `components/jobfit/CompareTable.vue`
- Create: `components/jobfit/DuelView.vue`
- Modify: `pages/assessment/BrAssessments.vue`
  - caixa de marcar nas linhas COMPLETED;
  - filtro de vaga (`/api/v2/recruitment/vacancies?limit=0`), passando `vacancyId` à listagem;
  - botão **Comparar selecionados** → `navigate('/recruitment/brProfileCompare', {subjects: keys.join(','), vacancyId})`.
- Test: `pages/jobfit/__tests__/BrProfileCompare.spec.js`, `components/jobfit/__tests__/PeoplePicker.spec.js`, `components/jobfit/__tests__/DuelView.spec.js`, `components/jobfit/__tests__/CompareTable.spec.js`, `pages/assessment/__tests__/BrAssessments.spec.js` (caso novo).

**Componentes:**
- **`PeoplePicker`** (props `modelValue: string[]` e `vacancyId`; emite `update:modelValue` e `vacancy-job-title`)
  - Filtros: tipo, vaga, empresa e nome (com debounce de 300 ms).
  - `GET profile-people`; lista com caixas de marcar.
  - **Marcar todos** acrescenta os visíveis até o limite de 20. **Limpar** desmarca.
  - Contador `jobfit_selected`.
  - Quando `meta.jobTitleId` vem preenchido, emite `vacancy-job-title`.
- **`BrProfileCompare`** (props `jobTitleId`, `subjects` (string) e `vacancyId`)
  - Select de cargo (`GET job-profiles`), com PeoplePicker.
  - Guarda o estado no endereço com `history.replaceState` (`?jobTitleId=&subjects=&vacancyId=`).
  - Com cargo e ao menos 1 pessoa: `GET profile-comparison`.
  - Cargo sem perfil (`hasProfile` false): aviso `jobfit_define_first` e botão para o editor.
  - Abas:
    - **Radar**: dois `RadarChart`, com `series` = pessoas com `shown`, `band` a partir do perfil (peso 0 → `null` e eixo `muted`) e `essential` = peso 2. A legenda usa `jobfit_job_range` e as cores. Por padrão, `shown` = as 6 primeiras.
    - **Ranking e fatores**: `CompareTable`.
    - **Duelo**: `DuelView`.
  - **Baixar PDF**: o `onPdf` do perfil individual, com o título `${jobfit_comparison} - ${cargo}`; imprime a aba aberta, com o mesmo bloco `@media print` e `.ohrm-compare__controls` escondido.
- **`CompareTable`** (props `result` e `shown: string[]`; emite `toggle-shown(key)` e `rated`)
  - Ranking: posição, nome, tipo e empresa, aderência geral (número e barra), comportamental, competências, chip `jobfit_partial` e chips de alerta.
  - Caixa **Mostrar no gráfico**, desabilitada quando já há 6 marcadas e aquela não está.
  - Tabela com fatores nas linhas e pessoas nas colunas: score com a classe `is-in`, `is-near` ou `is-far` e ★ nos essenciais.
  - Competências nas linhas, cada uma com um select de 1 a 5 ou "Sem nota".
    - A mudança faz `PUT competency-ratings/{id}` `{subject, rating?}` e emite `rated`; a página recarrega a comparação.
    - A nota atual vem em `people[].ratings`.
- **`DuelView`** (props `result`)
  - Dois selects, X e Y, com padrão nos 2 primeiros.
  - Cabeçalho com a barra de vida = `overall`.
  - Por fator:
    - 10 blocos, com `on` se `score >= (i + 1) * 10 - 5`;
    - X espelhado (blocos em ordem reversa);
    - moldura `.ohrm-duel__band` com `left`/`width` em %, espelhada no lado X;
    - rótulo com os scores;
    - seta `.ohrm-duel__win` no lado de `duelWinner(fitX, fitY)`.
  - Fator com peso 0: sem moldura e sem seta.
  - Competências: 5 estrelas (`bi-star-fill` e `bi-star`), marca do nível mínimo e seta pela maior nota.

- [ ] **Step 1:** testes.
  - `PeoplePicker`: marcar todos respeita o limite de 20; a busca filtra; emite o cargo da vaga.
  - `CompareTable`: ordem do ranking, chips de parcial e alerta, cor por classe, limite de 6 em "mostrar", e a mudança de nota chama o PUT e emite `rated`.
  - `DuelView`: blocos acesos (82 → 8 blocos); seta para quem tem a maior nota do fator (Extroversão 88 fora da faixa perde para 70 dentro); sem seta no empate; estrelas pela nota.
  - `BrProfileCompare`:
    - com props, chama `profile-comparison` com `jobTitleId` e `subjects`;
    - radar com as 6 primeiras;
    - aviso de cargo sem perfil;
    - `replaceState` chamado.
  - `BrAssessments`: marcar duas linhas concluídas e clicar em Comparar navega com `subjects=c1,e2`.
- [ ] **Step 2:** ver falhar.
- [ ] **Step 3:** implementar.
- [ ] **Step 4:** ver passar; rodar toda a suíte Jest e o lint.
- [ ] **Step 5:** commit `feat(br): comparar perfis - radar, ranking e duelo`.

### Task 10: Build, deploy, verificação visual, docs

- [ ] **Step 1:** `yarn build` em `src/client`; `docker cp web/dist/.` para o container; conferir com curl que o JS servido contém `ohrm-duel__band`.
- [ ] **Step 2:** dados de teste reais pela API (sonda sem rollback, marcada com o prefixo "Teste Aderência", e remoção ao final):
  - o cargo "Frentista (teste)" com perfil;
  - 3 candidatos e 2 funcionários com teste concluído (respostas geradas).
- [ ] **Step 3:** fotos com o Playwright e o `chrome-headless-shell`, logado como Admin:
  - lista de perfis de cargo;
  - edição;
  - comparação: radar, tabela e duelo.
  - PDF real com `page.pdf()` das abas radar e duelo.
  - Olhar as imagens e corrigir o que estiver feio.
- [ ] **Step 4:** apagar tudo o que a sonda criou (cargo, pessoas, testes, notas).
- [ ] **Step 5:** atualizar `br-customizations/README.md`:
  - seção "Perfil do cargo e aderência": telas, fórmula, migrações 018 e i18n 018;
  - `attendance-br/TODO.md`: ciclo D/E feito; pendências "Fora deste ciclo".
- [ ] **Step 6:** rodar a suíte PHP inteira, o Jest inteiro e o lint.
- [ ] **Step 7:** commit `docs(br): README - perfil do cargo e aderência` e `git push`.
