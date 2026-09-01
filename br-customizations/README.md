# Customizações OrangeHRM — Brasil 🇧🇷

Este diretório contém as customizações brasileiras do OrangeHRM 5.9 Starter.

## Tradução pt_BR

### Status
- **1.262 strings** traduzidas (100% do sistema)
- Idioma: `pt_BR` (Portuguese (Brazil) - Português (Brasil))
- Aplicado diretamente no banco de dados via SQL

### Como aplicar
```bash
# Após instalação do OrangeHRM, executar:
docker exec -i orangehrm-db mysql -uroot -p'<sua_senha>' orangehrm < br-customizations/i18n/pt_br_translations.sql

# Limpar cache:
docker exec orangehrm-web rm -rf /var/www/html/src/cache/*
```

### Como ativar no sistema
1. Acesse como Admin
2. Admin → Configuration → Localization
3. Language → Portuguese (Brazil) - Português (Brasil)
4. Salvar

### Estrutura das traduções

| Grupo | Strings | Descrição |
|---|---|---|
| general | 343 | Textos gerais do sistema |
| admin | 245 | Módulo administrativo |
| pim | 188 | Gestão de funcionários (PIM) |
| leave | 107 | Folgas e licenças |
| performance | 82 | Avaliação de desempenho |
| recruitment | 68 | Recrutamento e seleção |
| time | 59 | Folhas de ponto |
| auth | 47 | Autenticação e login |
| claim | 29 | Reembolsos |
| buzz | 28 | Rede social interna |
| dashboard | 23 | Painel principal |
| attendance | 23 | Ponto eletrônico |
| maintenance | 14 | Manutenção |
| help | 6 | Ajuda |
| **Total** | **1.262** | |

## Ponto Eletrônico BR (attendance-br)

Implementação da Fase 1 de conformidade com a Portaria SEPRT 673/2021:

- NSR (Número Sequencial de Registro) em cada batida
- PIS/NIS no cadastro do funcionário
- CNPJ/CEI mapeados nos campos da Organização
- Audit trail completo de alterações
- Retificações (original permanece inalterado)
- Exportação AFD no layout da Portaria
- Colunas de geolocalização (preparação para Fase 3)

Detalhes em [`attendance-br/README.md`](attendance-br/README.md).

## PWA Mobile + Geofence (Fase 4)

Experiência mobile-first de ponto, instalável como PWA (sem lojas de app):

- **Página mobile dedicada** em `/attendance/mobile` — relógio, botão de punch
  gigante, captura de GPS, status de localização, histórico do dia
- **Geofence (área restrita)** — punch só é aceito dentro dos locais
  configurados (raio em metros, validação haversine no servidor). Desativado
  por padrão; ativa via API ou SQL:
  `attendance.br.geofence.enabled` = `true` em `hs_hr_config`
- **GPS persistido** — latitude/longitude agora são aceitos e salvos nos
  endpoints de punch (`POST/PUT /api/v2/attendance/records[...]`), corrigindo
  a lacuna em que o frontend enviava mas o backend descartava
- **Shell PWA** — manifest.webmanifest, service worker (cache do app shell,
  network-first para navegações), ícones 192/512/maskable, meta tags iOS/Android
- **Novo endpoint** `GET/PUT /api/v2/attendance/geofence` (configuração; PUT só Admin)
- **Permissões**: Admin/ESS/Supervisor leem a config; só Admin atualiza.
  Tela `Mobile Attendance` liberada para Admin, ESS e Supervisor

Como usar (após deploy):

1. Funcionário acessa `https://<servidor>/web/index.php/attendance/mobile`
2. Android: prompt de instalação automático. iOS: Compartilhar →
   "Adicionar à Tela de Início"
3. Admin define áreas permitidas via PUT `/api/v2/attendance/geofence`:
   `{"enabled": true, "locations": [{"name": "Escritório", "latitude": -23.55, "longitude": -46.63, "radius": 300}]}`

Migração: [`attendance-br/migrations/004_geofence_mobile.sql`](attendance-br/migrations/004_geofence_mobile.sql)

## Multi-empresa (Fase 5)

Um único RH atendendo várias empresas (CNPJ distintos) na mesma instalação,
usando a estrutura organizacional nativa (`ohrm_subunit`) — cada unidade pode
ser uma empresa:

- **CNPJ/CEI por unidade** — colunas `cnpj`/`cei` em `ohrm_subunit`, editáveis
  nos diálogos de adicionar/editar unidade (Admin → Organização)
- **Geofence por empresa** — locais de ponto passam a viver na tabela
  `ohrm_attendance_geofence_location` (por unidade); `subunit_id NULL` =
  conjunto padrão (fallback para unidades sem locais próprios). O JSON antigo
  em `hs_hr_config` (`attendance.br.geofence.locations`) é migrado
  automaticamente para o conjunto padrão
- **Tela administrativa** `/attendance/brGeofence` (menu Ponto → Locais de
  Ponto (Geofence)) — escolher empresa, ativar/desativar validação e gerenciar
  os locais (nome, latitude, longitude, raio)
- **Exportadores multi-empresa** — AFD, AFDT, eventos e-Social (S-1200/S-1210)
  e comprovante de punch usam o CNPJ/CEI da unidade do funcionário quando
  preenchido, caindo para o `tax_id` da organização (`EmployerResolverService`)
- **Validação no punch** — o geofence valida contra os locais da unidade do
  funcionário que está batendo o ponto (multi-empresa de verdade)

Endpoints alterados:

- `GET /api/v2/attendance/geofence?subunitId=<id>` — config + locais de uma
  unidade (sem o parâmetro: conjunto padrão)
- `PUT /api/v2/attendance/geofence` — `{enabled, subunitId|null, locations[]}`
  (upsert/delete dos locais da unidade)
- `POST/PUT /api/v2/admin/subunits` — aceitam `cnpj` e `cei` opcionais

Migração: [`attendance-br/migrations/005_multi_company.sql`](attendance-br/migrations/005_multi_company.sql)

## CNPJ e PIS — o que os arquivos fiscais exigem

Todo campo do AFD/AFDT é de largura fixa e preenchido com zeros à esquerda, então
um CNPJ ou PIS ausente produzia um arquivo bem-formado que não identifica
ninguém — aceito na exportação, recusado pelo auditor meses depois.

- **No cadastro** — CNPJ da unidade (Admin → Organização) e PIS/NIS do
  funcionário (PIM → Dados Pessoais) passam por dígito verificador. Campo em
  branco continua limpando o valor; o que é recusado é número inventado.
- **Na exportação** — AFD e AFDT recusam gerar quando falta CNPJ do empregador
  ou PIS válido de algum funcionário do período, e a mensagem nomeia qual
  empresa ou qual pessoa corrigir.
- **Qual empresa assina** — a resolução sobe a árvore da estrutura
  organizacional a partir da lotação do funcionário e para na unidade mais
  próxima que declarou CNPJ (`SubunitChainTrait`, o mesmo caminho que o
  geofence percorre). Funcionário em departamento herda o CNPJ da empresa acima.
- **CNPJ inválido é tratado como ausente** e não sobe para a empresa acima —
  arquivar o funcionário sob a empresa errada é pior do que recusar a exportação.

A aritmética dos dígitos vem do `Respect\Validation`, que já era dependência do
projeto (`Rules::CNPJ`, `Rules::PIS`). `Core\Utility\BrazilianDocument` existe
para que os exportadores, fora da camada de API, deem a mesma resposta.

## Caixa de entrada — avisos e justificativa de falta

Duas conversas entre RH e funcionário, nas mesmas abas do app mobile.

### Avisos do RH

O RH publica em `Ponto → Avisos` escolhendo o alcance: toda a rede, uma
empresa/posto, ou uma pessoa. O alcance **sobe a árvore** da estrutura
organizacional — aviso para a Acácia do Sul encontra quem está nos
departamentos abaixo dela, e não vaza para a empresa ao lado.

- **Ciência registrada** — marcando "Exigir ciência", o funcionário precisa
  clicar em *Estou ciente*, e isso fica gravado com data. É o que transforma
  "avisamos" em algo demonstrável.
- **Leitura e ciência são recibos separados.** Abrir o aviso grava a leitura;
  só a primeira conta, porque a pergunta é quando chegou, não quantas vezes
  abriu.
- A tela do RH mostra, por aviso, quantos leram e quantos deram ciência.

### Justificativa de falta

O funcionário envia em `Faltas`, no mobile: período, motivo, observação e
documento. O campo de arquivo usa `capture="environment"`, então **no celular
abre a câmera direto** — fotografar o atestado é um toque.

- **Atestado médico e declaração de comparecimento exigem o documento.** Sem
  ele não há o que pesar, só uma alegação.
- O RH decide em `Ponto → Justificativas de Falta`. **Recusar exige motivo** —
  sem ele o funcionário não tem como corrigir o pedido. **Decidir duas vezes é
  recusado**, para uma aprovação não virar recusa depois de comunicada.
- O download do atestado tem acesso mais estreito que o resto do módulo: é
  informação de saúde de pessoa nomeada, então só o próprio funcionário ou
  quem já enxerga os registros dele.

**O AFD não é tocado.** Ele registra marcações, não ausências — a justificativa
é um registro paralelo, para o espelho de ponto. Misturar as duas coisas
quebraria o arquivo fiscal.

Migrações: [`010_inbox.sql`](attendance-br/migrations/010_inbox.sql),
[`011_inbox_screens.sql`](attendance-br/migrations/011_inbox_screens.sql)
· strings: [`i18n/011_inbox_i18n.sql`](i18n/011_inbox_i18n.sql)

## Fila offline de ponto

Bater ponto sem sinal guarda a batida no aparelho e a envia quando a conexão
volta. O que fica guardado é o **horário e o GPS do momento em que o botão foi
apertado** — não os da sincronização — porque é essa a batida que aconteceu.

### O que isso custa, e por quê

O punch próprio normalmente é preso ao relógio do servidor com margem de 180
segundos, e é isso que impede o funcionário de escolher o próprio horário. Uma
batida feita sem sinal não tem como satisfazer essa regra: só o aparelho a
presenciou. **Aceitar a fila afrouxa esse controle** — não há como ter as duas
coisas.

O afrouxamento é explícito e limitado:

| Chave | Padrão | O que faz |
|---|---|---|
| `attendance.br.offline_punch.enabled` | `true` | Sem ela ligada, batida sincronizada é recusada |
| `attendance.br.offline_punch.max_hours` | `24` | Até onde a batida pode ser retroativa |

- Passada a janela, deixa de ser sincronização e vira correção — que pertence
  ao fluxo de retificação, com pedido e aprovação.
- Batida no futuro além de 180 segundos de tolerância é recusada (relógio do
  aparelho adiantado).
- Toda batida que chega por essa via ganha linha própria na trilha
  (`action = 'OFFLINE_SYNC'`), para não se confundir com uma batida que o
  servidor presenciou ao vivo.
- O geofence continua valendo: valida as coordenadas capturadas no momento da
  batida, não as da sincronização.

Para desligar: `UPDATE hs_hr_config SET value = 'false' WHERE name =
'attendance.br.offline_punch.enabled';`

### Como a fila se comporta

Esvazia na ordem em que foi criada e para na primeira que não sai — punch-out
enviado antes do seu punch-in seria recusado por não haver registro aberto.
Batida que o servidor respondeu recusando sai da fila e é reportada na tela;
mantê-la travaria todas as seguintes para sempre.

Migração: [`attendance-br/migrations/009_offline_punch.sql`](attendance-br/migrations/009_offline_punch.sql)
· strings: [`i18n/010_offline_punch_i18n.sql`](i18n/010_offline_punch_i18n.sql)

## Norma de referência — pendente de confirmação

Todo o módulo BR cita **Portaria SEPRT 673/2021** como norma de referência. Os
layouts de arquivo (AFD, Anexo I; AFDT, Anexo II) são citados como herdados da
**Portaria 1.510/2009**, que é de onde o formato vem.

**Isso ainda não foi confirmado com contador ou jurídico.** A citação foi
uniformizada para o código parar de dizer três coisas diferentes em lugares
diferentes, não porque a referência tenha sido verificada em fonte oficial. Se
o seu contador apontar outra norma, a troca é mecânica — é só texto de
comentário e documentação, nada de comportamento depende disso.

## Assinatura dos registros (inviolabilidade)

Cada registro de ponto recebe um HMAC-SHA256 quando o dia é fechado, para que
uma alteração posterior no banco não passe despercebida (Portaria 673/2021).

- **Quando assina** — no punch-out, dentro de `AttendanceDao::savePunchRecord`.
  Registro ainda aberto (`PUNCHED IN`, ou `PUNCHED OUT` sem hora de saída) não
  é assinado: as duas batidas moram na mesma linha, e assinar antes marcaria o
  próprio punch-out como adulteração.
- **O que é assinado** — `NSR | emp_number | punch_in_utc | punch_out_utc |
  state`, com a chave `attendance.br.signature_secret` de `hs_hr_config`.
- **Verificação** — `GET /api/v2/attendance/br/signature/verify?fromDate=&toDate=`
  devolve `INTEGRAL` ou `VIOLATED`; `POST` no mesmo caminho assina em lote os
  registros de um período que ainda estejam sem hash.

### O segredo — leia antes de mexer

A migração [`007_signature_secret.sql`](attendance-br/migrations/007_signature_secret.sql)
cria a chave com `SHA2(RANDOM_BYTES(64), 256)` e **não sobrescreve** uma chave
existente.

- **Guarde a chave no backup junto com o banco.** Trocar ou perder o segredo
  invalida o hash de todos os registros já assinados — eles passam a aparecer
  como `VIOLATED`, sem que ninguém tenha adulterado nada.
- **Não existe chave de fallback.** Sem o segredo o serviço recusa assinar, e
  o punch-out falha. É deliberado: um registro gravado sem hash é um registro
  sem prova de inviolabilidade, e antes disso o código caía numa chave
  derivada do hostname — pública, e diferente a cada recriação do container.
- **Limite conhecido:** o segredo mora no mesmo banco que os registros. Isso
  detecta edição direta por quem não conhece o esquema, mas não impede quem
  tem escrita no MySQL de ler a chave e recalcular um hash válido. Mover a
  chave para fora do banco (env var ou arquivo) fecharia essa brecha —
  o valor precisa ser exatamente o mesmo, senão os hashes atuais quebram.
- **Hashes anteriores à 007 não valem nada** — foram gerados com a chave de
  hostname e outro algoritmo. Precisam ser descartados e reassinados.

## Próximas customizações planejadas

- [ ] Integração com e-Social
- [ ] Holerite brasileiro
- [x] Ponto eletrônico compatível com Portaria 673/2021
- [x] Adaptadores de PIS, CNPJ (via attendance-br)
- [x] Multi-empresa: CNPJ por unidade + geofence por empresa (Fase 5)
- [x] Assinatura dos registros no punch-out (migração 007)
- [x] CNPJ e PIS validados no cadastro e exigidos no AFD/AFDT
- [x] Fila offline de ponto no mobile (migração 009)
- [x] Avisos do RH com ciência registrada e justificativa de falta com anexo
- [ ] Integração com WhatsApp para notificações
## Troubleshooting - Docker (IMPORTANTE)

### Tela branca apos operacoes no container

O container usa volume Docker (nao bind mount). O Apache roda como `www-data`,
mas comandos via `docker exec` rodam como `root`. Isso causa dois problemas recorrentes:

**1. Cache com permissao errada (causa tela branca / erro 500 no I18N):**

```bash
docker exec orangehrm-web bash -c "chown -R www-data:www-data /var/www/html/src/cache/ && chmod -R 775 /var/www/html/src/cache/ && rm -rf /var/www/html/src/cache/orangehrm/*"
```

**2. Assets do frontend com 403 Forbidden (causa tela branca sem erros no log):**

Apos qualquer `docker cp` para `/var/www/html/web/dist/`:

```bash
docker exec orangehrm-web bash -c "chown -R www-data:www-data /var/www/html/web/dist/ && chmod -R 755 /var/www/html/web/dist/"
```

**3. Coluna nova numa entidade nao aparece (le sempre o valor default):**

O Doctrine cacheia o mapeamento em `src/cache/doctrine_metadata/`. Limpar so
`src/cache/orangehrm/` NAO basta: a coluna existe no banco, o arquivo da
entidade esta certo, e mesmo assim a propriedade volta o default. Apos alterar
qualquer `entity/*.php`:

```bash
docker exec orangehrm-web bash -c "rm -rf /var/www/html/src/cache/doctrine_metadata/* /var/www/html/src/cache/doctrine_queries/* /var/www/html/src/cache/orangehrm/* && chown -R www-data:www-data /var/www/html/src/cache/"
```

**4. Apos `composer dump-autoload` (regenera cache como root):**

```bash
docker exec orangehrm-web bash -c "cd /var/www/html/src && php composer.phar dump-autoload && chown -R www-data:www-data /var/www/html/src/cache/"
```

### Deploy completo (frontend + backend)

```bash
# Build do frontend (no host)
cd src/client && yarn install --frozen-lockfile && yarn build

# Copiar dist para o container
docker cp web/dist/. orangehrm-web:/var/www/html/web/dist/

# Copiar arquivos PHP modificados
docker cp src/plugins/orangehrmAttendancePlugin/. orangehrm-web:/var/www/html/src/plugins/orangehrmAttendancePlugin/
docker cp src/plugins/orangehrmPimPlugin/. orangehrm-web:/var/www/html/src/plugins/orangehrmPimPlugin/

# Corrigir permissoes + regenerar autoload + limpar cache
docker exec orangehrm-web bash -c "
  chown -R www-data:www-data /var/www/html/web/dist/ &&
  chmod -R 755 /var/www/html/web/dist/ &&
  cd /var/www/html/src && php composer.phar dump-autoload &&
  chown -R www-data:www-data /var/www/html/src/cache/ &&
  rm -rf /var/www/html/src/cache/orangehrm/*
"
```

### Credenciais do banco (container orangehrm-db)

- Host: `orangehrm-db`
- User: `orangehrm`
- Pass: `OhrmDb2026!Leo`
- Database: `orangehrm`

### Manutencao (tela de senha nao aparece no Safari)

A tela de re-verificacao de credencial do Maintenance (`/maintenance/purgeEmployee`)
nao renderiza no Safari (incompatibilidade com o bundle Vue/OXD).
Usar Chrome ou Firefox para acessar funcoes de Maintenance.
