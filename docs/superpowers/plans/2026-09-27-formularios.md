# Formulários (provas e pesquisas) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** O RH monta provas e pesquisas campo a campo no desktop e as envia para uma pessoa, uma empresa ou posto, ou a rede inteira. O funcionário responde no mobile ou no desktop. O RH corrige e vê os resultados.

**Architecture:** Tudo fica no `orangehrmAttendancePlugin`, ao lado de Avisos, Faltas e Folha, e reaproveita `AnnouncementAudience`, `SubunitChainTrait` e `BrAccessScope`.
- **Regras puras:** a correção, a publicação, o envio, o YouTube, o serializador sem gabarito e a agregação dos resultados trabalham sobre **arrays**, a "definição do formulário". Por isso são testadas sem banco.
- **Serviços:** convertem entidade ↔ definição e gravam.
- **APIs:** finas, só com validação e chamada ao serviço.
- **Frontend:** o Vue compartilha o componente `FormFiller` entre o mobile e o desktop.

**Tech Stack:** PHP 8.3, Symfony, Doctrine (anotações), OrangeHRM API v2, Vue 3 + OXD, Jest, MySQL 8.

**Spec:** `docs/superpowers/specs/2026-09-27-formularios-design.md`

## Global Constraints

- **Repo:** `/home/leo/orangehrm-repo`, branch `pt-br-customizations`. O host **não tem PHP**: tudo roda no Docker.
- **Testes PHP:** a partir da Task 1, com o harness versionado:
  `docker run --rm -v /home/leo/orangehrm-repo:/app -w /app orangehrm/orangehrm:latest php /app/src/vendor/bin/phpunit -c /app/br-customizations/tests/phpunit-nodb.xml`
- **Estilo PHP:** `docker run --rm -v /home/leo/orangehrm-repo:/app -w /app orangehrm/orangehrm:latest php /app/devTools/core/vendor/bin/php-cs-fixer fix --config=/app/.php-cs-fixer.dist.php --dry-run --path-mode=intersection <arquivos>`
- **Frontend:** `cd src/client && npx jest <spec>`, `npx eslint --ext .js,.vue,.ts <arquivos>`. O build é **sempre `yarn build`**, nunca `vue-cli-service build`.
- **Cabeçalho de licença:** todo arquivo PHP/Vue/JS novo começa com o cabeçalho GPL idêntico ao de `Service/AnnouncementAudience.php` (Vue: dentro de `<!-- -->`; JS: `/** */`).
- **Namespaces:** serviço `OrangeHRM\Attendance\Service`; entidade `OrangeHRM\Entity` em `src/plugins/orangehrmAttendancePlugin/entity/`; API `OrangeHRM\Attendance\Api`; teste `OrangeHRM\Tests\Attendance\...` estendendo `OrangeHRM\Tests\Util\TestCase`, com `@group Attendance` e `@group Forms`.
- **Mensagens de erro ao usuário:** em pt-BR **sem acento**, como em `AttendanceServiceException`.
- **i18n:** grupo 17 (`attendance.*`), prefixo `form_`, com a tradução pt_BR em `ohrm_i18n_translate` (modelo: `br-customizations/i18n/013_timesheet_confirm_i18n.sql`).
- **Papéis:** 1 = Admin, 2 = ESS (todo funcionário), 3 = Supervisor. API nova precisa de `ohrm_data_group` + `ohrm_api_permission` + `ohrm_user_role_data_group`; tela nova, de `ohrm_screen` + `ohrm_user_role_screen`. A tela é o segundo trecho da URL (`/attendance/brFormBuilder/5` → `brFormBuilder`).
- **Validação:** booleano opcional no corpo JSON usa `notRequiredParamRule(new ParamRule(X, new Rule(Rules::BOOL_TYPE)))`; na query string, `new ParamRule(X, new Rule(Rules::BOOL_VAL))` sem decorator. Lista passada para `EndpointCollectionResult` direto, nunca embrulhada em outro array.
- **CSS:** cada componente Vue filho tem o próprio `<style scoped>`.
- **Commits:** terminam com `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. **Nunca** incluir `web/.htaccess`.
- **Limites (da spec):**

  | Item | Limite |
  |---|---|
  | Título | 150 |
  | Opção | 255 |
  | Texto curto | 255 |
  | Texto longo | 5000 |
  | Imagem | 2 MB, JPG/PNG/WebP |
  | Escala | 1–5 |
  | Anonimato | resultados a partir de 3 respostas |
  | YouTube | ID `[A-Za-z0-9_-]{11}`, exibido por `https://www.youtube-nocookie.com/embed/{id}` |

---

## A "definição do formulário" (contrato entre todas as tasks)

As classes puras recebem e devolvem este array. O serviço (Task 6) converte a entidade para ele e o frontend envia o mesmo formato.

```php
$definition = [
    'kind' => 'QUIZ',             // 'QUIZ' | 'SURVEY'
    'anonymous' => false,
    'passPercent' => 70,          // ?int
    'scope' => 'NETWORK',         // 'NETWORK' | 'SUBUNIT' | 'EMPLOYEE'
    'subunitId' => null,          // ?int
    'employeeId' => null,         // ?int
    'dueAt' => null,              // ?DateTime  (API: 'Y-m-d' ou null)
    'isTemplate' => false,
    'items' => [
        [
            'id' => 12,           // ?int (null em bloco novo)
            'type' => 'SINGLE',   // CONTENT|SINGLE|MULTIPLE|SHORT_TEXT|LONG_TEXT|SCALE|YES_NO
            'prompt' => 'Qual extintor...',
            'helpText' => null,
            'required' => true,
            'points' => 1.0,
            'imageId' => null,    // ?int
            'youtubeId' => null,  // ?string
            'correctYesNo' => null, // ?bool (só YES_NO em QUIZ)
            'options' => [
                ['id' => 31, 'label' => 'Po quimico', 'isCorrect' => true],
                ['id' => 32, 'label' => 'Agua', 'isCorrect' => false],
            ],
        ],
    ],
];
```

A resposta do funcionário chega indexada pelo id da questão:

```php
$answers = [
    12 => ['optionIds' => [31]],          // SINGLE / MULTIPLE
    13 => ['text' => '...'],              // SHORT_TEXT / LONG_TEXT
    14 => ['scale' => 4],                 // SCALE
    15 => ['yesNo' => true],              // YES_NO
];
```

No JSON, o front envia `answers: [{itemId, optionIds?, text?, scale?, yesNo?}]` e a API reindexa.

---

## File Structure

**Backend novo** (`src/plugins/orangehrmAttendancePlugin/`):

| Arquivo | Responsabilidade |
|---|---|
| `Service/Form/FormTypes.php` | constantes de tipo, status e limites |
| `Service/Form/YoutubeLink.php` | URL → ID do YouTube |
| `Service/Form/FormPublication.php` | pode publicar? |
| `Service/Form/FormGrading.php` | corrige, soma e diz se passou |
| `Service/Form/FormSubmissionRules.php` | o envio é aceitável? |
| `Service/Form/FormFillSerializer.php` | definição → versão para responder (sem gabarito) |
| `Service/Form/FormResultAggregator.php` | estatística por questão |
| `Exception/FormRuleException.php` | exceção com a posição do bloco |
| `entity/Form*.php` (8 entidades) | mapeamento Doctrine |
| `Service/Form/FormService.php` | definição ↔ entidade; rascunho, publicar, encerrar, duplicar; público |
| `Service/Form/FormSubmissionService.php` | enviar, minhas pendências, corrigir, nova tentativa |
| `Service/Form/FormResultService.php` | resultados e CSV |
| `Api/FormAPI.php`, `Api/FormImageAPI.php`, `Api/MyFormAPI.php`, `Api/FormSubmissionAPI.php`, `Api/FormResultAPI.php` | APIs |
| `Controller/BrFormsController.php`, `BrFormBuilderController.php`, `BrFormResultsController.php`, `BrMyFormsController.php`, `Controller/File/FormImageView.php`, `Controller/File/FormResultsCsv.php` | controllers |

**Migrações:**
- `br-customizations/attendance-br/migrations/014_forms.sql`: tabelas, telas, menu e permissões.
- `br-customizations/attendance-br/migrations/015_form_templates.sql`: os três modelos de posto.
- `br-customizations/i18n/014_forms_i18n.sql`: textos da interface.

**Frontend** (`src/client/src/orangehrmAttendancePlugin/`):
- `components/forms/FormFiller.vue` + `form-filler.scss`: responder, compartilhado.
- `components/forms/YoutubeEmbed.vue`: vídeo que só carrega ao tocar.
- `composables/useFormDraft.js`: rascunho no `localStorage`.
- `pages/mobile/MobileForms.vue` + `mobile-forms.scss`: a aba "Provas".
- `pages/forms/BrMyForms.vue`: "Meus formulários" no desktop.
- `pages/forms/BrForms.vue`, `BrFormBuilder.vue`, `FormBuilderItem.vue`, `BrFormResults.vue`, `br-forms.scss`: telas do RH.

---

### Task 1: Harness de testes no repo + tipos + YoutubeLink

Hoje o `phpunit-nodb.xml` vive só no scratchpad da sessão e se perde. Ele passa a ser versionado.

**Files:**
- Create: `br-customizations/tests/phpunit-nodb.xml`
- Create: `br-customizations/tests/bootstrap-nodb.php`
- Create: `src/plugins/orangehrmAttendancePlugin/Service/Form/FormTypes.php`
- Create: `src/plugins/orangehrmAttendancePlugin/Service/Form/YoutubeLink.php`
- Test: `src/plugins/orangehrmAttendancePlugin/test/Service/Form/YoutubeLinkTest.php`

**Interfaces:**
- Produces: `FormTypes::KIND_QUIZ|KIND_SURVEY`, `TYPE_*`, `QUESTION_TYPES` (todos menos CONTENT), `STATUS_DRAFT|PUBLISHED|CLOSED`, `SUBMISSION_GRADED|PENDING_REVIEW|RECORDED`, `SHORT_TEXT_MAX = 255`, `LONG_TEXT_MAX = 5000`, `ANONYMOUS_MIN_RESPONSES = 3`, `IMAGE_MAX_BYTES = 2097152`, `IMAGE_TYPES = ['image/jpeg','image/png','image/webp']`.
- Produces: `YoutubeLink::extractId(string $url): ?string`, `YoutubeLink::embedUrl(string $id): string`.

- [ ] **Step 1: Versionar o harness.** Criar `br-customizations/tests/bootstrap-nodb.php`:

```php
<?php
// Testes BR sem banco: so o autoload. Ver br-customizations/README.md.
define('ENVIRONMENT', 'test');
date_default_timezone_set('UTC');
require '/app/src/vendor/autoload.php';
```

`br-customizations/tests/phpunit-nodb.xml` recebe o mesmo conteúdo de `$SP/phpunit-nodb.xml`, com três mudanças:
- `bootstrap="/app/br-customizations/tests/bootstrap-nodb.php"`;
- a lista de `<file>` atual inteira mais `<directory>/app/src/plugins/orangehrmAttendancePlugin/test/Service/Form</directory>`;
- `<directory>/app/src/plugins/orangehrmAttendancePlugin/test/Api/Form</directory>`.

Criar os dois diretórios de teste com `.gitkeep`.

- [ ] **Step 2: Rodar a suíte atual pelo harness novo.** Esperado: `OK (147 tests...)`.

- [ ] **Step 3: Teste que falha** (`YoutubeLinkTest.php`):

```php
namespace OrangeHRM\Tests\Attendance\Service\Form;

use OrangeHRM\Attendance\Service\Form\YoutubeLink;
use OrangeHRM\Tests\Util\TestCase;

/**
 * A video link becomes an iframe on every employee's phone, so only a real
 * YouTube id may come out of it -- never a URL somebody typed.
 *
 * @group Attendance
 * @group Forms
 */
class YoutubeLinkTest extends TestCase
{
    /**
     * @dataProvider validLinks
     */
    public function testExtractsTheIdFromEveryShapeOfLink(string $url): void
    {
        $this->assertSame('dQw4w9WgXcQ', YoutubeLink::extractId($url));
    }

    public function validLinks(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'watch sem www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ&t=42s'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ'],
            'curto' => ['https://youtu.be/dQw4w9WgXcQ?si=abc'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'http' => ['http://youtu.be/dQw4w9WgXcQ'],
            'espacos' => ['  https://youtu.be/dQw4w9WgXcQ  '],
        ];
    }

    /**
     * @dataProvider invalidLinks
     */
    public function testRefusesAnythingThatIsNotYoutube(string $url): void
    {
        $this->assertNull(YoutubeLink::extractId($url));
    }

    public function invalidLinks(): array
    {
        return [
            'dominio imitando' => ['https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ'],
            'prefixo' => ['https://evilyoutube.com/watch?v=dQw4w9WgXcQ'],
            'outro site' => ['https://vimeo.com/123456'],
            'id curto' => ['https://youtu.be/abc'],
            'id com lixo' => ['https://www.youtube.com/watch?v=dQw4w9WgXc<'],
            'javascript' => ['javascript:alert(1)//youtu.be/dQw4w9WgXcQ'],
            'sem v' => ['https://www.youtube.com/watch?list=PL123'],
            'vazio' => [''],
            'so o id' => ['dQw4w9WgXcQ'],
        ];
    }

    public function testEmbedsThroughTheNoCookieDomain(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            YoutubeLink::embedUrl('dQw4w9WgXcQ')
        );
    }
}
```

- [ ] **Step 4: Rodar.** Esperado: FAIL, `Class "OrangeHRM\Attendance\Service\Form\YoutubeLink" not found`.

- [ ] **Step 5: Implementar** `FormTypes.php`:

```php
namespace OrangeHRM\Attendance\Service\Form;

/**
 * BR: the vocabulary of the forms module, in one place.
 */
final class FormTypes
{
    public const KIND_QUIZ = 'QUIZ';
    public const KIND_SURVEY = 'SURVEY';

    public const TYPE_CONTENT = 'CONTENT';
    public const TYPE_SINGLE = 'SINGLE';
    public const TYPE_MULTIPLE = 'MULTIPLE';
    public const TYPE_SHORT_TEXT = 'SHORT_TEXT';
    public const TYPE_LONG_TEXT = 'LONG_TEXT';
    public const TYPE_SCALE = 'SCALE';
    public const TYPE_YES_NO = 'YES_NO';

    public const ITEM_TYPES = [
        self::TYPE_CONTENT, self::TYPE_SINGLE, self::TYPE_MULTIPLE, self::TYPE_SHORT_TEXT,
        self::TYPE_LONG_TEXT, self::TYPE_SCALE, self::TYPE_YES_NO,
    ];
    public const QUESTION_TYPES = [
        self::TYPE_SINGLE, self::TYPE_MULTIPLE, self::TYPE_SHORT_TEXT,
        self::TYPE_LONG_TEXT, self::TYPE_SCALE, self::TYPE_YES_NO,
    ];
    public const CHOICE_TYPES = [self::TYPE_SINGLE, self::TYPE_MULTIPLE];
    public const TEXT_TYPES = [self::TYPE_SHORT_TEXT, self::TYPE_LONG_TEXT];
    /** Types that count towards a quiz score. */
    public const SCORED_TYPES = [
        self::TYPE_SINGLE, self::TYPE_MULTIPLE, self::TYPE_YES_NO,
        self::TYPE_SHORT_TEXT, self::TYPE_LONG_TEXT,
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_CLOSED = 'CLOSED';

    public const SUBMISSION_GRADED = 'GRADED';
    public const SUBMISSION_PENDING_REVIEW = 'PENDING_REVIEW';
    public const SUBMISSION_RECORDED = 'RECORDED';

    public const TITLE_MAX = 150;
    public const OPTION_MAX = 255;
    public const SHORT_TEXT_MAX = 255;
    public const LONG_TEXT_MAX = 5000;
    public const SCALE_MIN = 1;
    public const SCALE_MAX = 5;
    public const ANONYMOUS_MIN_RESPONSES = 3;
    public const IMAGE_MAX_BYTES = 2097152;
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
}
```

`YoutubeLink.php`:

```php
namespace OrangeHRM\Attendance\Service\Form;

/**
 * BR: a YouTube link reduced to the one thing worth keeping, the video id.
 *
 * The host is compared whole, never with "contains" or "ends with": a check that
 * accepts youtube.com.evil.com or evilyoutube.com puts somebody else's page in
 * an iframe on every employee's phone.
 */
final class YoutubeLink
{
    private const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';
    private const WATCH_HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com'];
    private const SHORT_HOST = 'youtu.be';

    public static function extractId(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if ($parts === false || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        $candidate = null;
        if ($host === self::SHORT_HOST) {
            $candidate = ltrim($path, '/');
        } elseif (in_array($host, self::WATCH_HOSTS, true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $candidate = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('#^/(shorts|embed)/([^/]+)$#', $path, $m) === 1) {
                $candidate = $m[2];
            }
        }

        return $candidate !== null && preg_match(self::ID_PATTERN, $candidate) === 1
            ? $candidate
            : null;
    }

    public static function embedUrl(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/' . $id;
    }
}
```

- [ ] **Step 6: Rodar.** Esperado: PASS, com todos os casos de `YoutubeLinkTest` passando.
- [ ] **Step 7: php-cs-fixer** nos arquivos novos. Esperado: nenhum arquivo listado.
- [ ] **Step 8: Commit** `feat(br): formularios - harness de testes versionado e link do YouTube`.

---

### Task 2: FormRuleException + FormPublication

**Files:**
- Create: `src/plugins/orangehrmAttendancePlugin/Exception/FormRuleException.php`
- Create: `src/plugins/orangehrmAttendancePlugin/Service/Form/FormPublication.php`
- Test: `src/plugins/orangehrmAttendancePlugin/test/Service/Form/FormPublicationTest.php`

**Interfaces:**
- Consumes: `FormTypes`.
- Produces:
  - `FormRuleException extends AttendanceServiceException`, com `getItemPosition(): ?int` (1-based) e as factories abaixo;
  - `FormPublication::assertPublishable(array $definition, DateTime $now): void`.

- [ ] **Step 1: Teste que falha.** Um helper `quiz(array $items, array $overrides = [])` monta a definição. A questão válida de referência:

```php
private function single(bool $withAnswer = true): array
{
    return [
        'id' => null, 'type' => 'SINGLE', 'prompt' => 'P', 'helpText' => null, 'required' => true,
        'points' => 1.0, 'imageId' => null, 'youtubeId' => null, 'correctYesNo' => null,
        'options' => [
            ['id' => null, 'label' => 'A', 'isCorrect' => $withAnswer],
            ['id' => null, 'label' => 'B', 'isCorrect' => false],
        ],
    ];
}
```

Os testes, cada um com `expectException(FormRuleException::class)` quando for recusa, e checando `getItemPosition()` quando a regra é de um bloco:

| Teste | Esperado |
|---|---|
| `testAQuizWithAnAnswerKeyIsPublishable` | passa |
| `testASurveyNeedsNoAnswerKey` | SURVEY sem opção certa passa |
| `testAFormWithOnlyContentIsRefused` | só CONTENT, posição null |
| `testAChoiceNeedsTwoOptions` | posição 1 |
| `testAnEmptyOptionLabelIsRefused` | label `'  '`, posição 1 |
| `testAQuizSingleChoiceNeedsExactlyOneCorrect` | 0 certas: posição 2 (2º bloco); 2 certas: recusa |
| `testAQuizMultipleChoiceNeedsAtLeastOneCorrect` | recusa |
| `testAQuizYesNoNeedsItsAnswer` | `correctYesNo` null: recusa |
| `testAQuizNeedsAPassMark` | `passPercent` null ou 101: recusa |
| `testAQuizCannotBeAnonymous` | recusa |
| `testAnAnonymousSurveyForOnePersonIsRefused` | scope EMPLOYEE + anonymous: recusa |
| `testATargetedFormNeedsItsTarget` | SUBUNIT sem `subunitId`: recusa; EMPLOYEE sem `employeeId`: recusa |
| `testADeadlineInThePastIsRefused` | `dueAt` ontem: recusa; `dueAt` hoje às 23:59 com now 10:00: passa |
| `testATemplateIsNeverPublished` | recusa |
| `testAnEmptyPromptIsRefused` | posição 1 |

- [ ] **Step 2: Rodar.** Esperado: FAIL (classes inexistentes).

- [ ] **Step 3: Implementar** `FormRuleException`:

```php
namespace OrangeHRM\Attendance\Exception;

/**
 * BR: a form rule broken, remembering which block broke it so the builder can
 * point at the card instead of leaving HR to hunt for it.
 */
class FormRuleException extends AttendanceServiceException
{
    private ?int $itemPosition = null;

    public static function at(int $position, string $message): self
    {
        $e = new self("Bloco {$position}: {$message}");
        $e->itemPosition = $position;
        return $e;
    }

    public static function form(string $message): self
    {
        return new self($message);
    }

    public function getItemPosition(): ?int
    {
        return $this->itemPosition;
    }
}
```

(`AttendanceServiceException extends Exception` e tem construtor padrão, então o `new self($message)` funciona.)

`FormPublication::assertPublishable`, conferindo nesta ordem:
1. `isTemplate` → `FormRuleException::form('Um modelo nao e publicado: use "Usar modelo" para criar uma copia.')`.
2. Nenhum item em `QUESTION_TYPES` → `form('Inclua ao menos uma questao.')`.
3. QUIZ + `anonymous` → `form('Prova nao pode ser anonima: a nota precisa ficar ligada a pessoa.')`.
4. SURVEY + `anonymous` + EMPLOYEE → `form('Pesquisa anonima para uma pessoa so nao e anonima: escolha outro publico ou desmarque "anonima".')`.
5. SUBUNIT sem `subunitId`, ou EMPLOYEE sem `employeeId` → `form('Escolha a empresa/posto ou a pessoa que vai receber.')`.
6. QUIZ com `passPercent` null ou fora de 0..100 → `form('Informe a nota minima (0 a 100%).')`.
7. `dueAt !== null && dueAt < now` → `form('O prazo ja passou.')`. `dueAt` é DateTime no fim do dia (a API converte `Y-m-d` para `Y-m-d 23:59:59`).
8. Por item, com `$pos = $i + 1`:
   - `trim(prompt) === ''` → `at($pos, 'escreva o enunciado.')`;
   - em `CHOICE_TYPES`: menos de 2 opções → `at($pos, 'inclua ao menos duas opcoes.')`; label vazio → `at($pos, 'ha uma opcao sem texto.')`;
   - se QUIZ:
     - SINGLE com certas ≠ 1 → `at($pos, 'marque exatamente uma opcao certa.')`;
     - MULTIPLE com certas = 0 → `at($pos, 'marque ao menos uma opcao certa.')`;
     - YES_NO com `correctYesNo === null` → `at($pos, 'marque a resposta certa (Sim ou Nao).')`.

- [ ] **Step 4: Rodar.** Esperado: PASS.
- [ ] **Step 5: php-cs-fixer.** Commit `feat(br): formularios - regras de publicacao`.

---

### Task 3: FormGrading

**Files:**
- Create: `src/plugins/orangehrmAttendancePlugin/Service/Form/FormGrading.php`
- Test: `src/plugins/orangehrmAttendancePlugin/test/Service/Form/FormGradingTest.php`

**Interfaces:**
- Produces:
  - `FormGrading::grade(string $kind, array $items, array $answers): array`, que devolve `['scorePoints' => ?float, 'maxPoints' => ?float, 'status' => string, 'awarded' => array<int, ?float>]`. `awarded` é indexado por item id e só traz as questões que pontuam.
  - `FormGrading::totals(array $awarded, float $maxPoints): array`, que devolve `['scorePoints' => float, 'status' => GRADED|PENDING_REVIEW]` (usado depois da correção manual).
  - `FormGrading::percent(float $score, float $max): float` (0–100, 1 casa decimal).
  - `FormGrading::passed(float $score, float $max, int $passPercent): bool`.
  - `FormGrading::assertReviewPoints(float $given, float $max, int $position): void`.

- [ ] **Step 1: Teste que falha**, com os casos:

| Teste | Situação | Esperado |
|---|---|---|
| `testASurveyIsRecordedWithoutAScore` | SURVEY | `status` RECORDED, `scorePoints` null |
| `testTheRightSingleChoiceScoresItsPoints` | points 2, marcou a certa | 2.0 |
| `testTheWrongSingleChoiceScoresNothing` | marcou a errada | 0.0 |
| `testMultipleChoiceScoresOnlyTheExactSet` | certas {1,3} | {1,3} → pontos; {1} → 0; {1,2,3} → 0; ordem [3,1] → pontos |
| `testYesNoScoresTheRightAnswer` | Sim/Não | certo → pontos, errado → 0 |
| `testAnUnansweredOptionalQuestionScoresZero` | sem resposta | 0 e conta no máximo |
| `testScaleNeverCounts` | SCALE | fora de `awarded` e do máximo |
| `testATextAnswerLeavesTheQuizPendingReview` | SHORT_TEXT com 3 pontos | `awarded[id]` null, status PENDING_REVIEW, max inclui os 3 e score só o automático |
| `testAnUnansweredTextIsZeroNotPending` | texto não obrigatório em branco | 0, sem pendência (não há o que corrigir) |
| `testAQuizWithNothingScoredIsRecorded` | QUIZ só com SCALE | RECORDED |
| `testTotalsAfterReview` | `totals([1=>2.0, 2=>1.5], 5.0)` | score 3.5, GRADED; com um null, PENDING_REVIEW |
| `testPassMarkIsInclusive` | `passed(7, 10, 70)` / `passed(6.9, 10, 70)` | true / false |
| `testPercentRoundsToOneDecimal` | `percent(2, 3)` | 66.7 |
| `testReviewPointsMustFitTheQuestion` | `assertReviewPoints(4, 3, 2)` / `(-1, 3, 2)` | ambos lançam `FormRuleException` com posição 2 |

- [ ] **Step 2: Rodar.** Esperado: FAIL.
- [ ] **Step 3: Implementar:**

```php
public static function grade(string $kind, array $items, array $answers): array
{
    if ($kind !== FormTypes::KIND_QUIZ) {
        return ['scorePoints' => null, 'maxPoints' => null,
            'status' => FormTypes::SUBMISSION_RECORDED, 'awarded' => []];
    }
    $awarded = [];
    $max = 0.0;
    foreach ($items as $item) {
        if (!in_array($item['type'], FormTypes::SCORED_TYPES, true)) {
            continue;
        }
        $points = (float)$item['points'];
        $max += $points;
        $awarded[$item['id']] = self::scoreItem($item, $answers[$item['id']] ?? null, $points);
    }
    if ($max <= 0.0) {
        return ['scorePoints' => null, 'maxPoints' => null,
            'status' => FormTypes::SUBMISSION_RECORDED, 'awarded' => []];
    }
    return ['maxPoints' => $max, 'awarded' => $awarded] + self::totals($awarded, $max);
}

private static function scoreItem(array $item, ?array $answer, float $points): ?float
{
    switch ($item['type']) {
        case FormTypes::TYPE_SINGLE:
        case FormTypes::TYPE_MULTIPLE:
            $correct = array_map('intval', array_column(
                array_filter($item['options'], fn ($o) => $o['isCorrect']), 'id'
            ));
            $given = array_map('intval', $answer['optionIds'] ?? []);
            sort($correct);
            sort($given);
            return $given !== [] && $given === $correct ? $points : 0.0;
        case FormTypes::TYPE_YES_NO:
            return isset($answer['yesNo']) && $answer['yesNo'] === $item['correctYesNo'] ? $points : 0.0;
        default: // texts: somebody has to read it, unless nothing was written
            return trim((string)($answer['text'] ?? '')) === '' ? 0.0 : null;
    }
}

public static function totals(array $awarded, float $maxPoints): array
{
    $pending = in_array(null, $awarded, true);
    return [
        'scorePoints' => (float)array_sum(array_filter($awarded, fn ($p) => $p !== null)),
        'status' => $pending ? FormTypes::SUBMISSION_PENDING_REVIEW : FormTypes::SUBMISSION_GRADED,
    ];
}

public static function percent(float $score, float $max): float
{
    return $max <= 0.0 ? 0.0 : round($score / $max * 100, 1);
}

public static function passed(float $score, float $max, int $passPercent): bool
{
    // compared in hundredths so 7/10 against 70% is not lost to float error
    return $max > 0.0 && (int)round($score / $max * 10000) >= $passPercent * 100;
}

public static function assertReviewPoints(float $given, float $max, int $position): void
{
    if ($given < 0 || $given > $max) {
        throw FormRuleException::at($position, "de 0 a {$max} pontos.");
    }
}
```

- [ ] **Step 4: Rodar.** Esperado: PASS. **Step 5:** php-cs-fixer e commit `feat(br): formularios - correcao automatica`.

---

### Task 4: FormSubmissionRules + FormFillSerializer

**Files:**
- Create: `Service/Form/FormSubmissionRules.php`, `Service/Form/FormFillSerializer.php`
- Test: `test/Service/Form/FormSubmissionRulesTest.php`, `test/Service/Form/FormFillSerializerTest.php`

**Interfaces:**
- Produces:
  - `FormSubmissionRules::assertAccepting(string $status, ?DateTime $dueAt, DateTime $now): void`;
  - `FormSubmissionRules::assertAttemptLeft(int $used, int $retakesGranted): void`;
  - `FormSubmissionRules::assertAnswers(array $items, array $answers): void`;
  - `FormSubmissionRules::allowedAttempts(int $retakesGranted): int` (= 1 + retakes);
  - `FormFillSerializer::forFilling(array $definition): array`.

- [ ] **Step 1: Testes que falham** (`FormSubmissionRulesTest`):

| Teste | Esperado |
|---|---|
| `testAPublishedFormWithinItsDeadlineAcceptsAnswers` | passa |
| `testADraftOrClosedFormRefuses` | DRAFT e CLOSED recusam: `'Este formulario nao esta aberto para respostas.'` |
| `testAfterTheDeadlineNothingIsAccepted` | `dueAt` 2026-09-27 23:59:59, now 2026-09-28 00:00:01: `'O prazo deste formulario terminou em 27/09/2026.'` |
| `testOneAttemptByDefault` | `assertAttemptLeft(1, 0)`: `'Voce ja respondeu este formulario.'`; `(1, 1)` passa; `allowedAttempts(2) === 3` |
| `testARequiredQuestionLeftBlankIsRefused` | posição apontada |
| `testAnOptionalQuestionMayBeBlank` | passa |
| `testContentBlocksNeedNoAnswer` | passa |
| `testAnOptionFromAnotherQuestionIsRefused` | `'opcao invalida.'` |
| `testSingleChoiceTakesOneOption` | recusa com duas |
| `testScaleMustBeOneToFive` | 0 e 6 recusam |
| `testTextLimits` | curto com 256 e longo com 5001 recusam |
| `testYesNoMustBeABoolean` | `'sim'` recusa |
| `testAnswerForAnUnknownQuestionIsRefused` | `'Resposta para uma questao que nao esta no formulario.'` |

`FormFillSerializerTest`:
- `testTheAnswerKeyNeverLeavesTheServer`: serializa um QUIZ e faz `json_encode`. Garante que a string **não contém** `isCorrect` nem `correctYesNo`, e que as opções mantêm `id` e `label`.
- `testPointsAreShownOnQuizzes`: `points` fica, porque o funcionário pode saber quanto vale cada questão.
- `testSurveysCarryNoPoints`: SURVEY sem `points`.

- [ ] **Step 2: Rodar.** Esperado: FAIL.
- [ ] **Step 3: Implementar.** `assertAnswers` percorre `$answers` primeiro, para achar id desconhecido, e depois os itens. O "em branco" de cada tipo:
  - escolha: `optionIds` vazio ou ausente;
  - texto: `trim(text) === ''`;
  - escala: `scale` null;
  - Sim/Não: `yesNo` null.

  Para escolha, as opções válidas vêm de `array_column($item['options'], 'id')`. Cada erro de bloco usa `FormRuleException::at($pos, ...)`.

`FormFillSerializer::forFilling`:

```php
public static function forFilling(array $definition): array
{
    $quiz = $definition['kind'] === FormTypes::KIND_QUIZ;
    return [
        'kind' => $definition['kind'],
        'anonymous' => (bool)$definition['anonymous'],
        'items' => array_map(static function (array $item) use ($quiz) {
            $out = [
                'id' => $item['id'],
                'type' => $item['type'],
                'prompt' => $item['prompt'],
                'helpText' => $item['helpText'],
                'required' => (bool)$item['required'],
                'imageId' => $item['imageId'],
                'youtubeId' => $item['youtubeId'],
                'options' => array_map(
                    static fn (array $o) => ['id' => $o['id'], 'label' => $o['label']],
                    $item['options']
                ),
            ];
            if ($quiz && in_array($item['type'], FormTypes::SCORED_TYPES, true)) {
                $out['points'] = (float)$item['points'];
            }
            return $out;
        }, $definition['items']),
    ];
}
```

- [ ] **Step 4: Rodar.** Esperado: PASS. **Step 5:** php-cs-fixer e commit `feat(br): formularios - regras do envio e formulario sem gabarito`.

---

### Task 5: FormResultAggregator

**Files:**
- Create: `Service/Form/FormResultAggregator.php`
- Test: `test/Service/Form/FormResultAggregatorTest.php`

**Interfaces:**
- Produces:
  - `FormResultAggregator::perItem(string $kind, array $items, array $answerRows, int $submissionCount): array`. As linhas são `['submissionId'=>string,'itemId'=>int,'optionId'=>?int,'text'=>?string,'scale'=>?int,'yesNo'=>?bool,'points'=>?float]`. Devolve uma lista, uma entrada por item de `QUESTION_TYPES`:
    - `['itemId','type','prompt','answered'=>int]`, mais:
    - escolha: `'options'=>[['id','label','count','percent','isCorrect']]`, e em QUIZ também `'correctPercent'` (float, ou null em SURVEY). Na MULTIPLE, correto = `points` > 0 na linha da questão;
    - Sim/Não: `'yes','no'`, e `'correctPercent'` em QUIZ;
    - escala: `'average'` (1 casa), `'distribution'=>[1=>n,...,5=>n]`;
    - texto: `'texts'=>string[]`, em ordem aleatória (`shuffle`) para não revelar quem escreveu pela ordem.
  - `FormResultAggregator::mayShow(bool $anonymous, int $submissionCount): bool`.

- [ ] **Step 1: Testes que falham:**
  - `testOptionCountsAndPercentages`: 4 envios, 3 marcaram A e 1 marcou B → `count` 3/1 e `percent` 75.0/25.0.
  - `testMultipleChoiceCountsEveryTickedOption`: a soma dos `count` pode passar do número de envios.
  - `testCorrectPercentOnQuizzes`: acerto calculado pelos pontos das linhas.
  - `testScaleAverageAndDistribution`: notas [5,4,4,2] → média 3.8 e distribuição {1:0,2:1,3:0,4:2,5:1}.
  - `testTextsAreListedWithoutOrder`: os mesmos textos, em qualquer ordem (`assertEqualsCanonicalizing`).
  - `testAnAnonymousSurveyIsHiddenBelowThreeResponses`: `mayShow(true, 2)` é false; `mayShow(true, 3)` é true; `mayShow(false, 1)` é true.
  - `testContentBlocksAreSkipped`.
- [ ] **Step 2: Rodar.** Esperado: FAIL. **Step 3:** implementar como descrito. **Step 4:** PASS. **Step 5:** commit `feat(br): formularios - agregacao dos resultados`.

---

### Task 6: Migração 014 (tabelas, telas, menu, permissões) + entidades

**Files:**
- Create: `br-customizations/attendance-br/migrations/014_forms.sql`
- Create: `src/plugins/orangehrmAttendancePlugin/entity/Form.php`, `FormItem.php`, `FormOption.php`, `FormImage.php`, `FormCompletion.php`, `FormSubmission.php`, `FormAnswer.php`, `FormRetake.php`
- Modify: `src/plugins/orangehrmAttendancePlugin/entity/Announcement.php` (campo `form`)
- Probe (scratchpad, não vai ao repo): `probe_forms_entities.php`

**Interfaces:**
- Produces: entidades com getters/setters no padrão de `Announcement.php`:
  - `Form`: `id, title, description, kind, anonymous(bool), passPercent(?int), scope, subunit(?Subunit), employee(?Employee), dueAt(?DateTime), status, isTemplate(bool), publishedAt, closedAt, createdByEmpNumber, createdAt`. Relação `@ORM\OneToMany(targetEntity=FormItem, mappedBy="form", cascade={"persist","remove"}, orphanRemoval=true) @ORM\OrderBy({"position"="ASC"})` → `getItems(): Collection`.
  - `FormItem`: `id, form, position, type, prompt, helpText, required, points(float), image(?FormImage), youtubeId, correctYesNo(?bool)`. Relação `options` OneToMany com cascade e orphanRemoval, ordenada por position.
  - `FormOption`: `id, item, position, label, isCorrect`.
  - `FormImage`: `id, form, filename, fileType, fileSize, content(string; getter que faz stream_get_contents quando o valor é resource, como em AbsenceAttachment), uploadedAt`.
  - `FormCompletion`: `id, form, employee, attempt, completedAt`.
  - `FormSubmission`: `id(string, @ORM\Id sem GeneratedValue, gerado por bin2hex(random_bytes(16)) no construtor), form, employee(?Employee), attempt(?int), submittedAt(?DateTime), scorePoints(?float), maxPoints(?float), status, reviewedByEmpNumber, reviewedAt`.
  - `FormAnswer`: `id, submission, item, option(?FormOption), textValue, scaleValue, yesNoValue(?bool), pointsAwarded(?float)`.
  - `FormRetake`: `id, form, employee, grantedByEmpNumber, grantedAt`.
  - `Announcement::getForm(): ?Form / setForm(?Form)`.

- [ ] **Step 1: Escrever a migração.** Mesmo estilo de `010_inbox.sql`: comentário de cabeçalho, `SET NAMES utf8mb4`, `CREATE TABLE IF NOT EXISTS`, ENGINE=InnoDB e utf8mb4. As tabelas e colunas são exatamente as da seção 1 da spec, com os tipos de lá.
  - **FKs:**
    - `form_item.form_id`, `form_option.item_id`, `form_image.form_id`, `form_answer.submission_id` e `form_submission.form_id`: CASCADE;
    - `form_item.image_id`: SET NULL;
    - `form_answer.item_id`: CASCADE;
    - `form_answer.option_id`: SET NULL;
    - `form_completion`/`form_retake`: `form_id` CASCADE, `employee_id` → `hs_hr_employee(emp_number)` CASCADE;
    - `form_submission.employee_id`: SET NULL;
    - `form.subunit_id` → `ohrm_subunit(id)` SET NULL;
    - `form.employee_id` → `hs_hr_employee` SET NULL.
  - **Índices:**
    - `form(status, is_template)`;
    - `form_completion` UNIQUE(`form_id`,`employee_id`,`attempt`);
    - `form_answer(submission_id)` e `(item_id)`.
  - **Coluna nos avisos:** `ALTER TABLE ohrm_br_announcement ADD COLUMN form_id INT UNSIGNED NULL` com FK SET NULL, protegido contra reexecução com `information_schema` + `PREPARE`, como já é feito na migração 005 (conferir lá o padrão exato e copiar).
  - **Telas** (`ohrm_screen`, `module_id` do attendance): `brForms`, `brFormBuilder`, `brFormResults`, `brMyForms`.
    - `ohrm_user_role_screen`: Admin (1) em todas; ESS (2) e Supervisor (3) só em `brMyForms`, com leitura.
  - **Menu** (parent 56, level 3):
    - `'Forms'` → brForms, order 1000;
    - `'My Forms'` → brMyForms, order 150, logo depois de "My Records".
    - Inserir as `ohrm_i18n_lang_string` do grupo 1 para `Forms` e `My Forms`, e a tradução pt_BR `Formulários` / `Meus formulários`, como em `011_inbox_screens.sql`.
  - **Permissões de API** (bloco igual ao da 013):

    | data group | API | Admin (r,c,u) | ESS (r,c,u) | Supervisor (r,c,u) |
    |---|---|---|---|---|
    | `apiv2_attendance_br_form` | `FormAPI` | 1,1,1 | – | – |
    | `apiv2_attendance_br_form_image` | `FormImageAPI` | 1,1,0 | – | – |
    | `apiv2_attendance_br_my_form` | `MyFormAPI` | 1,0,0 | 1,0,0 | 1,0,0 |
    | `apiv2_attendance_br_form_submission` | `FormSubmissionAPI` | 0,1,0 | 0,1,0 | 0,1,0 |
    | `apiv2_attendance_br_form_result` | `FormResultAPI` | 1,1,1 | – | – |

  A imagem que o funcionário vê **não** passa por API: é servida pelo controller de arquivo da Task 8, com a própria checagem de acesso.

- [ ] **Step 2: Escrever as 8 entidades + o campo em Announcement.** Tabela `ohrm_br_form*` e o `@ORM\Table` com os índices. O tipo `decimal` do Doctrine volta como string: os getters de pontos fazem o cast para `?float`, e os setters recebem float e gravam `(string)`.

- [ ] **Step 3: Aplicar a migração no banco real:**
  - `docker exec -i orangehrm-db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" orangehrm' < br-customizations/attendance-br/migrations/014_forms.sql`
  - Rodar **duas vezes**. Esperado: a segunda sem erro e sem linha duplicada (`SELECT COUNT(*) FROM ohrm_menu_item WHERE menu_title IN ('Forms','My Forms')` = 2).

- [ ] **Step 4: Probe das entidades.** Deploy do plugin (`docker cp` + `chown` + limpar `doctrine_metadata`, `doctrine_queries` e `orangehrm`), e um script PHP dentro do container com `beginTransaction()`/`rollBack()`:
  - cria um Form com 2 itens (um com 2 opções) e uma imagem;
  - cria uma submission anônima e uma identificada, com answers, completion e retake;
  - faz `flush`, `clear` e relê;
  - imprime `OK` se: os itens vêm em ordem, as opções vêm em ordem, `getId()` da submission tem 32 caracteres hex, `getEmployee()` é null na anônima e o conteúdo da imagem é igual ao original.

  Rodar também `vendor/bin/doctrine orm:validate-schema --skip-sync` se estiver disponível; senão, o probe basta. Esperado: `OK`.

- [ ] **Step 5: php-cs-fixer + suíte PHP.** Commit `feat(br): formularios - tabelas, telas, menu e permissoes`.

---

### Task 7: FormService (definição ↔ entidade, rascunho, publicar, encerrar, duplicar, público)

**Files:**
- Create: `Service/Form/FormService.php`
- Probe: `probe_form_service.php` (scratchpad)

**Interfaces:**
- Consumes: entidades da Task 6, `FormPublication`, `AnnouncementAudience`, `SubunitChainTrait` (via `AnnouncementService::getChainIds`), `YoutubeLink`.
- Produces:
  - `toDefinition(Form $form): array`: o contrato do topo, com `dueAt` como DateTime.
  - `createDraft(array $definition, string $title, ?string $description, int $createdBy): Form`.
  - `saveDraft(Form $form, array $definition, string $title, ?string $description): void`:
    - recusa se não for DRAFT (`FormRuleException::form('Formulario publicado nao pode ser editado: use Duplicar.')`);
    - apaga os itens e os recria na ordem, dentro de uma transação;
    - imagens: aceita só `imageId` de uma `FormImage` do **mesmo** formulário, senão `at($pos,'imagem invalida.')`;
    - `youtubeId`: aceita só o que casa com `[A-Za-z0-9_-]{11}`.
  - `publish(Form $form, DateTime $now): Announcement`:
    - faz `FormPublication::assertPublishable`;
    - põe status PUBLISHED e `publishedAt`;
    - cria o `Announcement` com o mesmo público, `form` = este, título `'Nova prova: {titulo}'` ou `'Nova pesquisa: {titulo}'`, corpo com a descrição e `"Prazo: dd/mm/aaaa"` quando houver, `requiresAck=false`, `expiresAt` = dueAt;
    - retorna o aviso.
  - `close(Form $form): void`: só a partir de PUBLISHED.
  - `duplicate(Form $source, int $createdBy): Form`: novo DRAFT com título `"{titulo} (copia)"` quando não é modelo, ou o mesmo título quando é modelo; `isTemplate=false`; escopo NETWORK sem alvo; `dueAt` null; copia itens, opções e imagens (novas `FormImage` com o conteúdo copiado, remapeando `imageId`).
  - `reaches(Form $form, Employee $employee): bool`: `AnnouncementAudience::reaches(...)` com a cadeia do funcionário.
  - `audience(Form $form): Employee[]`: funcionários ativos (`employeeTerminationRecord IS NULL`, `purgedAt IS NULL`) alcançados.
  - `listForHr(): array`: formulários (menos modelos), mais recentes primeiro, e `listTemplates(): Form[]`.

- [ ] **Step 1: Probe que falha**, com `beginTransaction`/`rollBack`, exercitando:
  - criar rascunho de QUIZ com SINGLE, MULTIPLE, YES_NO e texto → `toDefinition` devolve a mesma estrutura;
  - `saveDraft` reordenando → a ordem persiste;
  - `publish` sem gabarito lança `FormRuleException` com a posição certa;
  - com gabarito, publica e cria o aviso com `form` ligado;
  - `saveDraft` depois de publicado lança;
  - `duplicate` gera DRAFT com as imagens copiadas e ids novos;
  - `reaches`/`audience` com SUBUNIT = Acácia do Sul contra o banco real.

  Cada checagem imprime `PASS nome`/`FAIL nome`. Esperado antes de implementar: erro de classe inexistente.
- [ ] **Step 2: Implementar `FormService`**, com as mesmas traits de `AnnouncementService` mais `EntityManagerHelperTrait`.
- [ ] **Step 3: Deploy do plugin e rodar o probe.** Esperado: tudo PASS.
- [ ] **Step 4:** php-cs-fixer e commit `feat(br): formularios - servico do formulario (rascunho, publicar, duplicar)`.

---

### Task 8: FormAPI + FormImageAPI + imagem servida + telas do RH (controllers)

**Files:**
- Create: `Api/FormAPI.php`, `Api/FormImageAPI.php`, `Controller/File/FormImageView.php`, `Controller/BrFormsController.php`, `Controller/BrFormBuilderController.php`, `Controller/BrFormResultsController.php`
- Modify: `config/routes.yaml`
- Test: `test/Api/Form/FormApiValidationTest.php`

**Interfaces:**
- **Rotas:**
  - `GET|POST /api/v2/attendance/br/forms` e `GET|PUT /api/v2/attendance/br/forms/{id}` → `FormAPI` (`implements CollectionEndpoint, ResourceEndpoint`);
  - `POST /api/v2/attendance/br/forms/images` → `FormImageAPI`;
  - `GET /attendance/brFormImage/{id}` → `FormImageView`;
  - páginas `/attendance/brForms`, `/attendance/brFormBuilder` (novo), `/attendance/brFormBuilder/{id}` e `/attendance/brFormResults/{id}`.
- **`FormAPI::getAll`:**
  - devolve `[{id,title,kind,anonymous,status,scope,subunitName,employeeId,dueAt('Y-m-d'|null),publishedAt,respondedCount,audienceCount}]`;
  - meta `templates: [{id,title,kind,anonymous,description}]`.
  - `respondedCount` = número de pessoas distintas em `completion`; `audienceCount` = `count(audience())`, calculado só para PUBLISHED/CLOSED (rascunho mostra `—`).
- **`FormAPI::getOne`:** `{id,title,description,status,isTemplate, ...definition}` com `dueAt` em `Y-m-d`.
- **`FormAPI::create`:**
  - corpo `{sourceId}` → duplicate;
  - ou `{title, description, definition}` → createDraft;
  - retorna `{id}`.
- **`FormAPI::update`:**
  - `{action:'save', title, description, definition}`;
  - `{action:'publish'}`;
  - `{action:'close'}`.
  - `FormRuleException` vira 422 com `{error:{message, itemPosition}}`: capturar e lançar `$this->getBadRequestException()` com a mensagem. Antes, conferir como o `TimesheetSignatureAPI` devolve a mensagem da `AttendanceServiceException` e usar o mesmo mecanismo. A posição vai no texto ("Bloco N: ..."), e o front extrai com `/^Bloco (\d+):/`.
- **Validação de `definition`:** `new ParamRule('definition', new Rule(Rules::ARRAY_TYPE))`, com a checagem profunda feita pelas regras puras (Task 2) e pelo `saveDraft`. `title` com LENGTH 1..150; `description` opcional até 5000.
- **`FormImageAPI::create`:**
  - `{formId, filename, content}` (base64 ou data URI);
  - reaproveita `decodeAttachment`/`detectFileType` do `AbsenceJustificationAPI`, copiando os dois métodos privados. Ver a seção "DRY" abaixo.
  - recusa um tipo fora de `IMAGE_TYPES`, detectado pelos **bytes** com `finfo`, nunca pelo nome;
  - recusa mais de 2 MB;
  - recusa formulário que não seja DRAFT;
  - retorna `{id, url: '/attendance/brFormImage/{id}'}`.
- **`FormImageView`** (modelo: `AbsenceDocumentDownload`):
  - pode ver quem é Admin (`getApiPermissions(FormAPI::class)->canRead()`), ou quem é alcançado pelo formulário quando ele está PUBLISHED/CLOSED;
  - `Content-Disposition: inline`;
  - `X-Content-Type-Options: nosniff`;
  - `Content-Type` = `fileType` gravado (sempre um dos 3 tipos de imagem).

**Feito diferente na execucao:** o `FormImageAPI` usa o formato de anexo padrao do OrangeHRM (`Rules::BASE_64_ATTACHMENT`, `{name,type,size,base64}`) e confere o tipo real pelos bytes com `finfo`, entao nao houve trait nem mudanca no `AbsenceJustificationAPI`. Erros de regra saem como 400 (padrao da casa), nao 422. Plano original: copiar `decodeAttachment`/`detectFileType` seria a terceira cópia. Extraí-los para `Api/Traits/Base64UploadTrait.php` e usar a trait no `AbsenceJustificationAPI` e no `FormImageAPI`. O `AbsenceJustificationAPI` muda só no `use`: rodar a suíte e fazer o teste manual de envio de atestado depois do deploy.

- [ ] **Step 1: Teste que falha** (`FormApiValidationTest`, modelo `BrOptionalFlagValidationTest`):
  - salvar com `{action:'save', title:'Prova', definition:[...]}` é aceito;
  - `{action:'publish'}` é aceito sem título;
  - `action` desconhecida é recusada;
  - título com 151 caracteres é recusado;
  - `FormImageAPI` com `formId` + `filename` + `content` é aceito, e sem `content` é recusado.
- [ ] **Step 2: Rodar.** Esperado: FAIL. **Step 3:** implementar as APIs, a trait, as rotas e os controllers. Os controllers seguem `EditAttendanceController`: passam a prop `form-id` (`Prop::TYPE_NUMBER`) quando há `{id}` e usam os componentes `br-forms`, `br-form-builder` e `br-form-results`. **Step 4:** PASS e a suíte inteira verde.
- [ ] **Step 5: Probe HTTP real** depois do deploy. Obter uma sessão de Admin é difícil via curl, então:
  - verificar que `GET /api/v2/attendance/br/forms` responde **401** sem login e não 404 (a rota existe);
  - um probe PHP chama o `FormService` pelo mesmo caminho da API.

  O teste com login fica para a Task 14, pelo navegador.
- [ ] **Step 6:** php-cs-fixer e commit `feat(br): formularios - API do RH e imagens`.

---

### Task 9: FormSubmissionService + MyFormAPI + FormSubmissionAPI + BrMyFormsController

**Files:**
- Create: `Service/Form/FormSubmissionService.php`, `Api/MyFormAPI.php`, `Api/FormSubmissionAPI.php`, `Controller/BrMyFormsController.php`
- Modify: `config/routes.yaml`; `Api/AnnouncementAPI.php` (`listInbox` passa a incluir `formId`)
- Test: `test/Api/Form/MyFormApiValidationTest.php`
- Probe: `probe_form_submission.php`

**Interfaces:**
- **`FormSubmissionService::myForms(Employee $e, DateTime $now): array`:**
  - `pending`: PUBLISHED, alcança a pessoa, prazo aberto e tentativas usadas < permitidas;
  - `answered`: com completion.
  - Cada item traz `{id,title,kind,anonymous,dueAt,attemptsUsed,attemptsAllowed,result}`, com `result` = `{status, percent, passed}` da **última** submission identificada, ou `{status:'RECORDED'}` numa anônima. Numa prova PENDING_REVIEW, `percent` e `passed` são null.
- **`FormSubmissionService::fillView(Form, Employee, DateTime): array`:** `{id,title,description,dueAt}` + `FormFillSerializer::forFilling(...)`. Lança exceção se a pessoa não é alcançada ou se o formulário não está PUBLISHED.
- **`FormSubmissionService::submit(Form $form, Employee $e, array $answers, DateTime $now): array`**, em transação:
  1. `reaches` → senão `FormRuleException::form('Este formulario nao e para voce.')`;
  2. `assertAccepting`;
  3. `used` = completions da pessoa; `granted` = retakes; `assertAttemptLeft`;
  4. `assertAnswers`;
  5. `grade`;
  6. grava a `FormCompletion(attempt = used+1)`;
  7. grava a `FormSubmission`: anônima → sem employee, attempt ou submittedAt; senão, com os três;
  8. grava os `FormAnswer`: uma linha por opção, com os pontos só na primeira linha da questão; texto, escala e Sim/Não numa linha.

  Retorna `{status, percent, passed}`, ou `{status:'RECORDED'}`.
- **`MyFormAPI`:**
  - `GET /api/v2/attendance/br/my-forms` → a lista `pending` + `answered` com o campo `section: 'pending'|'answered'`, e meta `pendingCount`;
  - `GET /api/v2/attendance/br/my-forms/{id}` → `fillView`.
- **`FormSubmissionAPI`:**
  - `POST /api/v2/attendance/br/my-forms/submissions` com `{formId, answers:[{itemId, optionIds?, text?, scale?, yesNo?}]}`;
  - reindexa por `itemId` e chama `submit`;
  - `FormRuleException` vira 422 com a mensagem, pelo mesmo mecanismo da Task 8.
- **`AnnouncementAPI::listInbox`:** passa a incluir `'formId' => $announcement->getForm()?->getId()`.

- [ ] **Step 1: Testes que falham:**
  - validação do `FormSubmissionAPI`: `{formId:1, answers:[]}` aceito; sem `formId` recusado; `answers` como string recusado;
  - probe (rollback) com o funcionário 1:
    - envio válido de QUIZ → GRADED com o percentual certo;
    - segundo envio → "Voce ja respondeu";
    - `retake` concedido → aceito, attempt 2;
    - QUIZ com texto → PENDING_REVIEW;
    - SURVEY anônima → a linha em `ohrm_br_form_submission` fica com `employee_id IS NULL AND submitted_at IS NULL AND attempt IS NULL` e existe a completion;
    - prazo vencido → recusa;
    - funcionário fora do público → recusa;
    - `fillView` → o JSON não contém `isCorrect`.
- [ ] **Step 2: Rodar.** Esperado: FAIL. **Step 3:** implementar. **Step 4:** deploy e rodar a suíte e o probe. Esperado: tudo PASS.
- [ ] **Step 5:** php-cs-fixer e commit `feat(br): formularios - responder (mobile e desktop) e envio`.

---

### Task 10: FormResultService + FormResultAPI + CSV

**Files:**
- Create: `Service/Form/FormResultService.php`, `Api/FormResultAPI.php`, `Controller/File/FormResultsCsv.php`
- Modify: `config/routes.yaml`
- Test: `test/Api/Form/FormResultApiValidationTest.php`
- Probe: `probe_form_results.php`

**Interfaces:**
- **`GET /api/v2/attendance/br/form-results?formId=N`** → `FormResultAPI::getAll`, que devolve o recurso (lista com um único objeto, porque o endpoint é coleção):

  ```json
  {"summary": {"audienceCount", "respondedCount", "pendingPeople": [{"employeeId", "name", "unit"}],
     "average", "passedPercent", "pendingReviewCount"},
   "hidden": false,
   "perItem": [ /* FormResultAggregator::perItem */ ],
   "people": [{"employeeId", "name", "unit", "submittedAt", "percent", "passed", "status", "submissionId", "attempt"}]}
  ```

  - Usa só a **última** submission de cada pessoa.
  - `people` passa por `BrAccessScope::restrictToEmployees`.
  - Na anônima: `people = []`, e `hidden = !mayShow(...)`. Se `hidden`, `perItem = []`.
- **`GET ...?formId=N&submissionId=X`** → detalhe de uma pessoa (não existe na anônima): as questões com a resposta dada, os pontos e a certa, porque o RH pode ver o gabarito.
- **`PUT`** `{submissionId, points: {itemId: float}}`:
  - só itens de texto;
  - `assertReviewPoints`;
  - `totals`, grava `reviewedBy`/`reviewedAt`;
  - retorna `{status, percent, passed}`.
- **`POST`** `{formId, employeeId}` → cria `FormRetake`. Só vale para quem já tem completion; senão `'Esta pessoa ainda nao respondeu.'`.
- **`GET /attendance/brFormResultsCsv/{id}`:**
  - só Admin; CSV UTF-8 com BOM, separador `;` (Excel pt-BR);
  - colunas: `Nome;Unidade;Data;Tentativa;Nota %;Situacao;` + um enunciado por questão;
  - célula de escolha = labels unidos por `, `.
  - **Anônima:** sem Nome/Unidade/Data, e as linhas embaralhadas.
  - **Injeção de fórmula:** célula que começa com `= + - @` recebe `'` na frente.

- [ ] **Step 1: Testes que falham:**
  - validação: GET sem `formId` recusado; PUT com `points` não-array recusado; POST sem `employeeId` recusado;
  - teste puro de `FormResultsCsv::escapeCell` (método estático público): `'=SOMA(A1)'` → `"'=SOMA(A1)"`; `a;b` → `"a;b"` com aspas; aspas internas duplicadas;
  - probe (rollback): 3 envios → médias certas; corrigir o texto → GRADED e percentual recalculado; retake para quem não respondeu → recusa; anônima com 2 envios → `hidden`.
- [ ] **Step 2: Rodar.** Esperado: FAIL. **Step 3:** implementar. **Step 4:** PASS. **Step 5:** commit `feat(br): formularios - resultados, correcao e CSV`.

---

### Task 11: i18n

**Files:**
- Create: `br-customizations/i18n/014_forms_i18n.sql`

- [ ] **Step 1:** Escrever a migração no formato da `013_timesheet_confirm_i18n.sql`: `lang_string` no grupo 17 com o valor em inglês, e um único `INSERT ... CASE` com o pt_BR. As chaves ficam **nesta lista** e o frontend usa exatamente estes nomes:

| chave | en | pt_BR |
|---|---|---|
| form_forms | Forms | Formulários |
| form_my_forms | My forms | Meus formulários |
| form_tab | Tests | Provas |
| form_new | New form | Novo formulário |
| form_templates | Templates | Modelos |
| form_use_template | Use template | Usar modelo |
| form_kind_quiz | Test | Prova |
| form_kind_survey | Survey | Pesquisa |
| form_anonymous | Anonymous | Anônima |
| form_pass_percent | Pass mark (%) | Nota mínima (%) |
| form_due_at | Deadline | Prazo |
| form_status_draft | Draft | Rascunho |
| form_status_published | Published | Publicado |
| form_status_closed | Closed | Encerrado |
| form_add | Add | Adicionar |
| form_type_content | Content | Conteúdo |
| form_type_single | Single choice | Escolha única |
| form_type_multiple | Multiple choice | Múltipla escolha |
| form_type_short_text | Short text | Texto curto |
| form_type_long_text | Long text | Texto longo |
| form_type_scale | Scale 1–5 | Escala 1–5 |
| form_type_yes_no | Yes/No | Sim/Não |
| form_prompt | Question | Enunciado |
| form_help_text | Help text | Texto de ajuda |
| form_required | Required | Obrigatória |
| form_points | Points | Pontos |
| form_point | point | ponto |
| form_option | Option | Opção |
| form_add_option | Add option | Adicionar opção |
| form_correct | Correct | Certa |
| form_image | Image | Imagem |
| form_youtube | YouTube link | Link do YouTube |
| form_youtube_invalid | Not a YouTube link | Link do YouTube inválido |
| form_preview | Preview | Pré-visualizar |
| form_save_draft | Save draft | Salvar rascunho |
| form_publish | Publish | Publicar |
| form_close | Close | Encerrar |
| form_duplicate | Duplicate | Duplicar |
| form_remove | Remove | Excluir |
| form_results | Results | Resultados |
| form_responded | Responded | Responderam |
| form_audience | Audience | Público |
| form_not_responded | Not responded yet | Ainda não responderam |
| form_average | Average | Média |
| form_passed_percent | Passed | Aprovados |
| form_pending_review | Awaiting review | Aguardando correção |
| form_review | Review | Corrigir |
| form_grant_retake | Allow another attempt | Liberar nova tentativa |
| form_export_csv | Export CSV | Exportar CSV |
| form_close_confirm | Close this form? Nobody will be able to answer it anymore. | Encerrar este formulário? Ninguém mais poderá responder. |
| form_view_answers | View answers | Ver respostas |
| form_attempt | Attempt | Tentativa |
| form_score | Score | Nota |
| form_submitted_at | Submitted | Enviado em |
| form_by_question | By question | Por questão |
| form_by_person | By person | Por pessoa |
| form_correct_rate | Correct | Acerto |
| form_retake_confirm | Allow this person another attempt? | Liberar nova tentativa para esta pessoa? |
| form_hidden_anonymous | Results appear after 3 responses, to protect anonymity. | Os resultados aparecem a partir de 3 respostas, para proteger o anonimato. |
| form_pending | To answer | Para responder |
| form_answered | Answered | Respondidos |
| form_no_pending | Nothing to answer | Nada para responder |
| form_due_in | Due | Vence |
| form_answer | Answer | Responder |
| form_submit | Submit answers | Enviar respostas |
| form_submit_confirm | Once submitted, answers cannot be changed. | Depois de enviado não é possível alterar. |
| form_anonymous_banner | This survey is anonymous: your answers are not linked to your name. | Esta pesquisa é anônima: suas respostas não ficam ligadas ao seu nome. |
| form_result_passed | Passed | Aprovado |
| form_result_failed | Not passed | Não aprovado |
| form_thanks | Thank you for answering | Obrigado por responder |
| form_done | Answered | Respondido |
| form_required_missing | Answer the required questions | Responda as questões obrigatórias |
| form_draft_restored | Your previous answers were restored | Suas respostas anteriores foram recuperadas |
| form_watch_video | Watch video | Assistir ao vídeo |
| form_yes | Yes | Sim |
| form_no | No | Não |

- [ ] **Step 2:** Aplicar duas vezes no banco. Esperado: sem erro, e `SELECT COUNT(*) FROM ohrm_i18n_lang_string WHERE group_id=17 AND unit_id LIKE 'form\_%'` = 76.
- [ ] **Step 3:** Commit `feat(br): formularios - textos pt-BR`.

---

### Task 12: FormFiller + YoutubeEmbed + useFormDraft

**Files:**
- Create: `src/client/src/orangehrmAttendancePlugin/components/forms/FormFiller.vue`, `form-filler.scss`, `YoutubeEmbed.vue`
- Create: `src/client/src/orangehrmAttendancePlugin/composables/useFormDraft.js`
- Test: `src/client/src/orangehrmAttendancePlugin/components/forms/__tests__/FormFiller.spec.js`, `composables/__tests__/useFormDraft.spec.js`

**Interfaces:**
- **`useFormDraft(formId)`** → `{load(): object|null, save(answers): void, clear(): void}`. A chave é `ohrm.formDraft.{formId}`, e todo acesso fica em try/catch (retorna null ou ignora).
- **`<form-filler :form="fillView" :submitting="bool" @submit="answersArray" />`:**
  - `form` é o retorno de `GET my-forms/{id}`;
  - o evento emite `[{itemId, optionIds?, text?, scale?, yesNo?}]` só depois da confirmação;
  - faz `load()` ao montar; se voltou algo, mostra `form_draft_restored`;
  - faz `save()` a cada mudança (watch deep).
- **`<youtube-embed :video-id="string" />`:** mostra um botão com a miniatura `https://i.ytimg.com/vi/{id}/hqdefault.jpg` e o texto `form_watch_video`. Ao tocar, troca pelo `iframe` `https://www.youtube-nocookie.com/embed/{id}?autoplay=1` com `allow="autoplay; encrypted-media; picture-in-picture"`, `allowfullscreen`, `referrerpolicy="strict-origin-when-cross-origin"` e proporção 16:9.
- **A imagem da questão:** `<img :src="\`${baseUrl}/attendance/brFormImage/${item.imageId}\`" loading="lazy" alt="">`.

- [ ] **Step 1: Testes que falham** (`FormFiller.spec.js`, mock de `$t` → chave):
  - `renders one control per item type`: SINGLE → radios; MULTIPLE → checkboxes; SHORT_TEXT → `input[type=text]` com maxlength 255; LONG_TEXT → `textarea` com maxlength 5000; SCALE → 5 botões; YES_NO → 2 botões; CONTENT → nenhum controle.
  - `shows the anonymous banner only on anonymous surveys`.
  - `marks required questions`: um `*` em cada `required`.
  - `refuses to submit with a required question blank`: não emite, mostra `form_required_missing` e dá `scrollIntoView` no bloco (mockar `Element.prototype.scrollIntoView`).
  - `asks for confirmation before emitting`: o primeiro clique abre a confirmação; o botão de confirmar emite `submit` com o array no formato do contrato.
  - `restores a saved draft`: com `localStorage` pré-preenchido, a opção marcada volta.
  - `does not load the video until tapped`: não há iframe; depois do clique, o iframe tem src `youtube-nocookie`.

  `useFormDraft.spec.js`: save/load/clear, e `localStorage` que lança → `load()` null sem quebrar.
- [ ] **Step 2: Rodar** `npx jest components/forms composables/__tests__/useFormDraft.spec.js`. Esperado: FAIL.
- [ ] **Step 3: Implementar.** Visual no padrão do mobile (`_mobile-tokens.scss`: cards brancos, raio 1rem, laranja da marca):
  - cada bloco é um card;
  - as opções são linhas tocáveis de 48px de altura com o input nativo à esquerda;
  - a escala e o Sim/Não são botões segmentados;
  - os inputs usam `box-sizing: border-box` e 16px.
  - O componente é usado no mobile (390px) e no desktop, com `max-width: 720px; margin: 0 auto`.
- [ ] **Step 4: Rodar.** Esperado: PASS. `npx eslint` nos arquivos. **Step 5:** commit `feat(br): formularios - componente de resposta`.

---

### Task 13: Aba "Provas" no mobile + "Meus formulários" no desktop + botão Responder no aviso

**Files:**
- Create: `pages/mobile/MobileForms.vue`, `pages/mobile/mobile-forms.scss`, `pages/forms/BrMyForms.vue`
- Modify: `pages/mobile/MobileAttendance.vue` (5ª aba + badge + `loadPendingForms`), `pages/mobile/MobileAnnouncements.vue` (botão Responder → `$emit('open-form', formId)`), `pages/mobile/mobile-attendance.scss` (dock com 5 itens), `index.ts` (`'br-my-forms'`)
- Test: `pages/mobile/__tests__/MobileForms.spec.js`

**Interfaces:**
- **`MobileForms`:**
  - props `openFormId` (Number|null); emite `pending-changed(n)`;
  - telas internas: lista (pendentes e respondidos) → `FormFiller` → resultado;
  - depois do envio: prova GRADED → "{percent}%" e `form_result_passed`/`form_result_failed`; PENDING_REVIEW → `form_pending_review`; RECORDED → `form_thanks`.
  - O erro 422 do servidor aparece acima do botão enviar, e o rascunho continua.
- **`MobileAttendance`:**
  - `tab === 'forms'` e ícone `bi-clipboard-check`;
  - badge `pendingFormsCount`, vindo de `GET /api/v2/attendance/br/my-forms` (`meta.pendingCount`);
  - `@open-form` dos Avisos faz `tab='forms'` e `openFormId=id`.
- **`BrMyForms` (desktop):** a mesma lista e o mesmo `FormFiller`, dentro de `orangehrm-card-container`. Abre direto um formulário quando a URL tem `?form=ID`, que é o link do aviso no desktop.

- [ ] **Step 1: Testes que falham** (`MobileForms.spec.js`):
  - lista pendentes e respondidos com o prazo;
  - emite `pending-changed` com `meta.pendingCount`;
  - `openFormId` abre o formulário direto;
  - depois de um envio GRADED, mostra o percentual e "Aprovado";
  - erro 422 mostra a mensagem do servidor.

  Complementar o `MobileTimesheet.spec` não é necessário.
- [ ] **Step 2: Rodar.** Esperado: FAIL. **Step 3: Implementar.** O dock passa de 4 para 5 itens: conferir no `mobile-attendance.scss` que os itens usam `flex: 1` e que os rótulos cabem em 390px (fonte 0.65rem). **Step 4:** PASS e a suíte Jest inteira verde.
- [ ] **Step 5:** commit `feat(br): formularios - aba Provas no mobile e Meus formularios no desktop`.

---

### Task 14: Telas do RH, lista, construtor e resultados

**Files:**
- Create: `pages/forms/BrForms.vue`, `pages/forms/BrFormBuilder.vue`, `pages/forms/FormBuilderItem.vue`, `pages/forms/BrFormResults.vue`, `pages/forms/br-forms.scss`
- Modify: `index.ts` (`'br-forms'`, `'br-form-builder'`, `'br-form-results'`)
- Test: `pages/forms/__tests__/BrFormBuilder.spec.js`

**Interfaces:**
- **`BrForms`:**
  - tabela no estilo `BrAnnouncements` (`ohrm-br-table`) com as ações:
    - Editar (só DRAFT) → `/attendance/brFormBuilder/{id}`;
    - Duplicar → `POST {sourceId}` e depois abre o builder;
    - Encerrar → `PUT {action:'close'}`, com confirmação;
    - Resultados → `/attendance/brFormResults/{id}`.
  - Seção Modelos com "Usar modelo" (`POST {sourceId}`), e o botão "Novo formulário" → `/attendance/brFormBuilder`.
  - A navegação usa `navigate()` de `@ohrm/core/util/helper/navigation`.
- **`BrFormBuilder`:**
  - prop `formId` (Number|null); com null, cria o rascunho no primeiro salvar e troca a URL;
  - o estado local é o `definition` do contrato, com um `key` local (`crypto.randomUUID` ou contador) por bloco para o `v-for`;
  - a barra "+ Adicionar" tem 7 botões; cada bloco novo nasce com 2 opções vazias quando é escolha, e com `points: 1` em Prova.
  - **Travamento:** status diferente de DRAFT deixa tudo somente leitura e mostra só Duplicar e Resultados.
  - **Publicar:** salva e depois publica. No erro, extrai a posição com `/^Bloco (\d+):/`, marca o card (`is-error`) e rola até ele.
  - **Pré-visualizar:** renderiza `<form-filler>` com a definição passada por uma função local equivalente a `FormFillSerializer` (sem gabarito), em modo só leitura (`preview` prop: não salva rascunho nem emite).
- **`FormBuilderItem`** (props `item`, `kind`, `formId`, `index`, `total`; emite `update:item`, `move-up`, `move-down`, `duplicate`, `remove`):
  - campos: enunciado, ajuda, obrigatório e pontos (em Prova);
  - opções com a marcação "certa" (radio na SINGLE, checkbox na MULTIPLE); Sim/Não com o gabarito em Prova;
  - imagem: `<input type=file accept="image/jpeg,image/png,image/webp">`. Antes de enviar, confere se passa de 2 MB (mostra erro sem enviar). Envia via `FormImageAPI` e mostra a miniatura por `/attendance/brFormImage/{id}`.
  - YouTube: extrai o ID no cliente com uma função espelho de `YoutubeLink` (`utils/youtubeLink.js`, com teste) e mostra a prévia ou `form_youtube_invalid`. O servidor revalida.
  - **Ajuste no FormFiller:** além da prop `preview` usada aqui, o `FormFiller` da Task 12 ganha `preview` com `default: false`. No preview, o botão enviar fica desabilitado e nada é salvo.
- **`BrFormResults`:** resumo (cards de números + lista de quem não respondeu), por questão (barras em CSS puro `width: percent%`, sem biblioteca de gráfico), por pessoa (tabela + detalhe expansível com o formulário de correção + botão de nova tentativa), e o link do CSV `/attendance/brFormResultsCsv/{id}`.

- [ ] **Step 1: Testes que falham** (`BrFormBuilder.spec.js`, com a API mockada como em `MobileTimesheet.spec.js`):
  - "Adicionar → Escolha única" cria um bloco com 2 opções;
  - mover para cima troca a ordem;
  - na SINGLE, marcar a certa desmarca a outra;
  - "Salvar rascunho" envia `definition` com os itens na ordem e sem os `key` locais;
  - um erro "Bloco 2: ..." no publicar marca o 2º card com `is-error`;
  - formulário PUBLISHED não mostra "Adicionar".

  `utils/__tests__/youtubeLink.spec.js`: os mesmos casos do `YoutubeLinkTest` PHP.
- [ ] **Step 2: Rodar.** Esperado: FAIL. **Step 3:** implementar. **Step 4:** PASS, a suíte inteira e o eslint.
- [ ] **Step 5:** commit `feat(br): formularios - construtor, lista e resultados do RH`.

---

### Task 15: Modelos prontos de posto (migração 015)

**Files:**
- Create: `br-customizations/attendance-br/migrations/015_form_templates.sql`

- [ ] **Step 1: Conferir os itens ⚠ da spec** antes de escrever:
  - (a) obrigação de o posto fazer o teste de qualidade da gasolina a pedido do consumidor: procurar a resolução da ANP vigente;
  - (b) conteúdo de NR-20 aplicável a frentista;
  - (c) um vídeo público no YouTube sobre segurança na pista ou NR-20, de canal institucional.

  Registrar as fontes em comentários no SQL. **Se a fonte não confirmar**, a questão (a) sai do modelo, e (c) fica sem vídeo, com a ajuda "Cole aqui o link do vídeo de treinamento usado pela empresa".

- [ ] **Step 2: Escrever a migração.** Um bloco por modelo, protegido por `WHERE NOT EXISTS (SELECT 1 FROM ohrm_br_form WHERE is_template = 1 AND title = '...')`. Os ids são capturados com `LAST_INSERT_ID()` em variáveis, e os itens e opções inseridos com `position` explícito, com `status='DRAFT'`, `is_template=1` e `scope='NETWORK'`. Conteúdo:

  1. **"Autoavaliação de desempenho — Frentista/Pista"** (SURVEY, não anônima). Descrição: "Avalie seu próprio trabalho no último período. Seja sincero: esta avaliação serve para conversar com sua liderança sobre o que está bom e o que pode melhorar."
     - CONTENT: "Como responder", com ajuda "1 = precisa melhorar muito · 3 = dentro do esperado · 5 = excelente".
     - 10 SCALE obrigatórias:
       - Atendimento ao cliente (cordialidade, atenção, resolver dúvidas);
       - Agilidade no abastecimento sem perder a atenção;
       - Conferência de caixa e troco (fechamento sem diferenças);
       - Uso correto de EPI e uniforme;
       - Procedimentos de segurança no abastecimento (motor desligado, sem celular, sem cigarro);
       - Oferta de produtos e serviços (aditivos, calibragem, troca de óleo, conveniência);
       - Pontualidade e assiduidade;
       - Trabalho em equipe e colaboração com os colegas;
       - Comunicação com a liderança;
       - Iniciativa (organização da pista, limpeza, antecipar problemas).
     - YES_NO: "Recebi treinamento suficiente para exercer minha função?"
     - 3 LONG_TEXT: "Meus pontos fortes" (obrigatória), "O que preciso melhorar" (obrigatória) e "O que a empresa pode fazer para me ajudar" (opcional).
  2. **"Prova: Segurança e procedimentos na pista"** (QUIZ, `pass_percent` 70). Descrição: "Prova de conhecimentos sobre segurança e rotina da pista. Nota mínima: 70%."
     - CONTENT: "Antes de começar", com o texto de NR-20 conferido no Step 1 e o vídeo, se confirmado.
     - SINGLE "Qual extintor é indicado para fogo em combustível líquido (gasolina, diesel, etanol)?":
       - certa: "Pó químico (classe B)";
       - erradas: "Água pressurizada" e "Qualquer extintor serve".
     - SINGLE "O cliente chega fumando ou com o motor ligado. O que você faz?":
       - certa: "Peço, com educação, para apagar o cigarro e desligar o motor antes de abastecer";
       - erradas: "Abasteço rápido para não criar atrito" e "Abasteço e aviso depois".
     - SINGLE "O bico automático desarmou (tanque cheio). O que fazer?":
       - certa: "Parar o abastecimento; não completar";
       - erradas: "Completar até a boca do tanque" e "Arredondar o valor apertando mais algumas vezes".

       Conferir no Step 1 se há norma. Se não houver, manter como boa prática de segurança, com a ajuda "Completar faz o combustível transbordar e acumular vapor".
     - SINGLE "Uso de celular junto à bomba durante o abastecimento:":
       - certa: "Deve ser evitado: atenção total ao abastecimento e ao cliente";
       - erradas: "É liberado se for rápido" e "Só é proibido para o cliente".
     - MULTIPLE "Na descarga do caminhão-tanque, quais cuidados são obrigatórios?":
       - certas: "Aterrar o caminhão antes de conectar as mangueiras", "Isolar e sinalizar a área" e "Manter extintor próximo";
       - errada: "Manter as bombas próximas abastecendo normalmente".
     - MULTIPLE "Quais EPIs o frentista deve usar ao manusear combustível?":
       - certas: "Luvas de proteção" e "Calçado de segurança", mais "Óculos de proteção" (na descarga e na troca de óleo). Os três conferidos no Step 1 (b);
       - errada: "Nenhum, se for rápido".
     - YES_NO (a), se confirmado.
     - SHORT_TEXT, 2 pontos, corrigida pelo RH: "Descreva em poucas palavras o que fazer em caso de derramamento de combustível na pista."
  3. **"Pesquisa de clima — Posto"** (SURVEY, anônima). Descrição: "Pesquisa anônima: ninguém, nem o RH, consegue ver quem respondeu o quê. Os resultados só aparecem a partir de 3 respostas."
     - 7 SCALE:
       - Me sinto respeitado pela minha liderança;
       - Tenho um bom relacionamento com os colegas;
       - Minha escala e minhas folgas são justas;
       - Tenho boas condições de trabalho na pista (equipamentos, uniforme, estrutura);
       - Me sinto seguro no meu trabalho;
       - Meu trabalho é reconhecido;
       - Eu recomendaria este posto para um amigo trabalhar.
     - 2 LONG_TEXT opcionais: "O que mais te incomoda hoje no trabalho?" e "Uma sugestão para melhorar o posto".

- [ ] **Step 3:** Aplicar duas vezes. Esperado: exatamente 3 linhas com `is_template=1`. Rodar `FormPublication` contra os 3 modelos (probe: `toDefinition` + `assertPublishable` com `isTemplate=false`). Esperado: os três passam, o que prova que o modelo vira formulário publicável sem ajuste.
- [ ] **Step 4:** Commit `feat(br): formularios - modelos prontos para postos`.

---

### Task 16: Deploy, verificação ponta a ponta, fotos, formulários de teste para o Leo, docs

**Files:**
- Modify: `br-customizations/README.md`, `br-customizations/attendance-br/TODO.md`
- Memory: atualizar `orangehrm-br-como-rodar-e-deployar.md` (o harness agora está versionado em `br-customizations/tests/`)

- [ ] **Step 1: Deploy.**
  - `cd src/client && yarn build`;
  - `docker cp web/dist/. orangehrm-web:/var/www/html/web/dist/`;
  - `docker cp src/plugins/orangehrmAttendancePlugin/. orangehrm-web:/var/www/html/src/plugins/orangehrmAttendancePlugin/`;
  - `chown -R www-data:www-data`;
  - limpar `src/cache/doctrine_metadata/*`, `doctrine_queries/*` e `orangehrm/*`.
- [ ] **Step 2: Conferir o JS servido.** `curl -s https://rh.leogarcia.com.br/web/dist/js/app.js` (ou o chunk da página, lido do `index`) com grep por `form_anonymous_banner` e por `brFormBuilder`. Esperado: encontrado.
- [ ] **Step 3: Rotas.** `/attendance/brForms`, `/attendance/brMyForms`, `/attendance/brFormBuilder` e `/attendance/brFormImage/1` sem login → redirecionam para o login (302), não 404/500. `GET /api/v2/attendance/br/my-forms` sem login → 401.
- [ ] **Step 4: Ponta a ponta no banco real (rollback):** usar o modelo (duplicate) → ajustar para `EMPLOYEE` 1 → publicar (confere o aviso criado com `form_id`) → responder a prova com 1 erro → percentual certo → nova tentativa → corrigir o texto → resultados → CSV → encerrar → envio recusado.
- [ ] **Step 5: Fotos (Playwright em `/tmp/uicheck`):**
  - `FormFiller` com a prova de posto, em 390px, com a pesquisa anônima (faixa) e o resultado;
  - dock com 5 abas em 390px e 360px;
  - construtor em 1280px com 3 blocos (escolha com certa marcada, imagem, YouTube) e um card com `is-error`.

  Seguir a técnica da memória (spec Jest temporário grava o `wrapper.html()`, sass e Chromium). Corrigir o que estiver feio **antes** de seguir.
- [ ] **Step 6: Formulários de teste para o Leo** (sem rollback, com aviso a ele). Usar os modelos 1 e 2 → `scope EMPLOYEE`, `employee_id` 1, `due_at` = hoje + 30 dias → publicar pelo `FormService`, para que os avisos também sejam criados.
  - Lembrar ao Leo: o funcionário 1 precisa de **usuário ESS** vinculado para entrar no mobile.
  - A unidade e o PIS só importam para bater ponto, não para responder: com scope EMPLOYEE, a cadeia de unidade não é necessária.
- [ ] **Step 7: Docs.** Seção "Formulários" no `br-customizations/README.md`:
  - migrações 014 e 015 e i18n 014, com a ordem de aplicação;
  - o harness em `br-customizations/tests`;
  - as regras de anonimato e o limite de 3.

  No `TODO.md`, marcar o que foi entregue e listar o "Fora da primeira versão" da spec.
- [ ] **Step 8:** Suíte PHP inteira, Jest inteira, eslint e php-cs-fixer. Commit `docs(br): formularios - deploy, README e TODO` e `git push`.

---

## Self-review

- **Cobertura da spec:**

  | Seção da spec | Tasks |
  |---|---|
  | §1 dados | 6 |
  | §2 regras | 2, 3, 4, 5, 1 |
  | Anonimato | 4, 5, 9, 10 |
  | Travamento e duplicar | 7 |
  | §3 APIs | 8, 9, 10 |
  | Permissões | 6 |
  | Serializador sem gabarito | 4, 9 |
  | §4 RH | 14 |
  | Funcionário | 12, 13 |
  | Aviso com Responder | 7, 9, 13 |
  | §5 modelos | 15 |
  | Publicar para o Leo | 16 |
  | §6 testes | 1–16 |

  A "lista de quem não respondeu" está na 10 (`pendingPeople`). O "respondidos / público" está na 8.
- **Ajuste consciente em relação à spec:** a spec lista ações `publish/close/duplicate` na FormAPI; o plano usa `PUT {action}` e `POST {sourceId}`, que dão o mesmo resultado com as rotas genéricas do OrangeHRM. A imagem é servida por controller de arquivo em vez de `GET` na API, porque `<img src>` precisa de uma URL binária, não de JSON.
- **Consistência de nomes:** `FormRuleException::at/form/getItemPosition`, `FormGrading::grade/totals/percent/passed/assertReviewPoints`, `FormSubmissionRules::assertAccepting/assertAttemptLeft/assertAnswers/allowedAttempts`, `FormFillSerializer::forFilling`, `FormResultAggregator::perItem/mayShow` e `YoutubeLink::extractId/embedUrl` são usados com esses nomes em todas as tasks.
