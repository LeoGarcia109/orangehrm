# Pendencias - Relatorio de Jornada BR

## Relatorio em branco (2026-07-30) - RESOLVIDO em 2026-08-17

Era a hipotese 2 da lista original. `WorkTimeReportAPI::getAll()` montava
`new EndpointCollectionResult(ArrayModel::class, [[$result]], ...)` com um nivel
de array a mais, serializando como `data: [[{...}]]`. O Vue faz
`this.report = data[0]`, recebia um array em vez do objeto e todos os campos
resolviam para undefined. Como array nao-vazio e truthy, o `v-if="report"`
passava e os cards apareciam vazios. Corrigido para `[$result]`.

Nao tinha nada a ver com permissao nem com o formato do empNumber.

Outros tres bugs da mesma familia (contrato backend/frontend divergente)
foram corrigidos junto:

- Dropdown de empresas do geofence em branco: `oxd-select` renderiza
  `option.label`, e as opcoes eram montadas com `name`.
- PUT do geofence sempre 422: rota injeta `id: 0` e as regras de update nao
  excluiam `CommonParams::PARAMETER_ID`.
- AFD/AFDT com 500: `EndpointResourceResult` recebia um `ParameterBag` na
  posicao do model. Viraram controllers de download (`Controller/File/`).

## Pendente

1. [ ] Traduzir labels do formulario para portugues (ainda em ingles/hardcoded)
2. [ ] Relatorio nao filtra por empresa/unidade (ver Fase 5, item 2)

# Fase 4 - PWA Mobile + Geofence (2026-08-15)

## Entregue

- [x] Pagina mobile `/attendance/mobile` (punch com GPS, historico do dia)
- [x] Backend aceita/persiste latitude/longitude no punch (POST/PUT records)
- [x] GeofenceService (haversine) + endpoint GET/PUT /api/v2/attendance/geofence
- [x] Shell PWA: manifest.webmanifest, sw.js, icones, meta tags iOS/Android
- [x] Migracao 004: permissoes, tela, config defaults, i18n pt_BR

## Pendente (fase 4)

1. [x] Tela admin para gerenciar areas do geofence → feita na Fase 5
      (`/attendance/brGeofence`, por empresa)
2. [ ] Notificacoes push (lembrete de punch out) — exige VAPID keys
3. [x] **Fila offline de punch (2026-08-31, migracao 009).**

      Bater sem sinal guarda a batida no aparelho (`localStorage`) com o
      **horario e o GPS do momento em que o botao foi apertado**, nao os da
      sincronizacao — e essa a batida que de fato aconteceu, e e ela que o
      geofence e a folha precisam ver.

      Descoberta que definiu o desenho: o punch proprio ja recusava qualquer
      horario fora de ±180 segundos do relogio do servidor
      (`MyAttendanceRecordAPI::isCurrantDateTimeValid`), que e justamente o que
      impede o funcionario de escolher o proprio horario. Batida offline nao
      tem como satisfazer isso. Entao aceitar a fila **afrouxa esse controle**,
      e o afrouxamento e explicito:

      - o empregador liga `attendance.br.offline_punch.enabled` (ja ligada);
      - `attendance.br.offline_punch.max_hours` (24h) limita ate onde a batida
        pode ser retroativa; passado isso vira correcao, que pertence ao fluxo
        de retificacao com pedido e aprovacao;
      - batida no futuro alem de 180s de tolerancia e recusada;
      - toda batida que chega por essa via ganha linha propria na trilha
        (`action = 'OFFLINE_SYNC'`).

      A fila esvazia na ordem em que foi criada e para na primeira que nao sai:
      punch-out replicado antes do punch-in seria recusado por nao haver
      registro aberto. Batida que o servidor respondeu recusando sai da fila e
      e reportada na tela, senao travaria todas as seguintes para sempre.

      `OfflinePunchWindow` (7 testes PHP) e `useOfflinePunchQueue`
      (12 testes Jest).

4. [ ] Camera/selfie no punch (prova adicional, opcional)
5. [ ] Testar instalacao PWA em iOS real (Safari → Adicionar à Tela de Início)
6. [ ] Menu lateral: link para a pagina mobile (opcional; funcionarios
      acessam direto pelo link compartilhado)
7. [x] **Central de notificacoes e justificativa de falta (2026-09-01,
      migracoes 010 e 011).**

      Duas conversas entre RH e funcionario na mesma caixa de entrada.

      **Avisos** — RH publica para a rede, para uma empresa/posto ou para uma
      pessoa. O alcance sobe a arvore como o geofence e o CNPJ, entao aviso
      para a Acacia encontra quem esta nos departamentos abaixo e nao vaza
      para a empresa ao lado. Comunicado pode exigir ciencia: o funcionario
      clica "estou ciente" e fica datado. Leitura e ciencia sao recibos
      separados; so a primeira leitura conta.

      **Faltas** — funcionario envia periodo, motivo, observacao e documento
      (o input usa `capture=environment`, entao no celular abre a camera).
      Atestado e declaracao exigem o documento: sem ele nao ha o que pesar.
      RH aprova ou recusa; recusar exige motivo; decidir duas vezes e
      recusado.

      Telas do RH: `/attendance/brAnnouncements` (publicar + quem leu/deu
      ciencia) e `/attendance/brAbsences` (fila, aprovar/recusar, abrir o
      documento). O download do atestado tem acesso mais estreito que o resto
      do modulo — e informacao de saude de pessoa nomeada.

      O AFD nao foi tocado: ele registra marcacoes, nao ausencias.

      `AnnouncementAudience` (10 testes) e `AbsenceJustificationRules` (15).

### Pendente da caixa de entrada

8. [x] **Assinatura mensal da folha (2026-09-01, migracao 012).**

      O funcionario assina a folha do mes fechado na aba de historico do
      mobile, **confirmando a senha**. Escolha do Leo: so assinar, sem
      contestar; o uso e judicial (segundo ele, dispensa testemunha em
      reclamatoria -- **nao confirmado**, ver ressalva da norma).

      A assinatura e um HMAC sobre os hashes dos registros do mes, na ordem do
      NSR, com o mesmo segredo da 007. Guarda tambem IP e user agent, porque
      "deixaram a sessao aberta" e a primeira coisa que se alega contra uma
      assinatura.

      **Furo encontrado pela propria sonda e corrigido:** a primeira versao
      comparava so os `record_hash` guardados, e um UPDATE direto no banco nao
      mexe nessa coluna -- entao a folha continuava "integra" depois de a
      batida ser alterada, e so quebrava se o fraudador reassinasse o
      registro. Exatamente ao contrario do que importa. Agora o `isIntact()`
      faz as duas verificacoes: cada registro tem de continuar gerando o hash
      guardado (pega o UPDATE cru) **e** o conjunto tem de bater com a
      assinatura (pega a reassinatura). Conferido nos dados reais: as duas
      adulteracoes derrubam a folha.

      Regras: mes tem de estar fechado (assinar mes em curso prenderia uma
      folha que a proxima batida muda); batida em aberto impede assinar;
      assinar duas vezes e recusado.

      `TimesheetSignatureRules`, 23 testes.

### Pendente da assinatura da folha

12. [ ] **Ninguem avisa o funcionario que a folha abriu para assinatura.** Ele
      precisa entrar na aba de historico. Resolver junto com push (fase 4,
      item 2) ou publicando um comunicado automatico no dia 1.

13. [ ] **Folha quebrada nao notifica ninguem.** A tela do RH mostra, mas so
      quem abrir vai ver. Vale um alerta quando `broken > 0`.

9. [ ] **Notificacao de aviso novo so aparece ao abrir o app.** Sem push
      (item 2 desta fase), o funcionario precisa entrar para ver. O contador
      de ciencia pendente ja carrega junto com a tela de ponto.

10. [ ] **Comunicado nao pode ser editado nem removido depois de publicado.**
      Falta decidir se apagar deve existir, e o que acontece com os recibos.

11. [ ] **`ESocialEventAPI` tem o bug de formato do `data[[...]]`.** Passa
      `[$events]` para o `EndpointCollectionResult`, entao a lista inteira
      vira um unico item — a mesma familia do bug do relatorio de jornada,
      corrigido na af80ca24d. Nao mexi porque o e-Social nao esta implantado
      e nao teria como conferir; corrigir junto com o P3 item 13.

# Fase 5 - Multi-empresa (2026-08-15)

## Entregue

- [x] CNPJ/CEI por unidade da estrutura organizacional (colunas em ohrm_subunit
      + campos nos dialogos SaveOrgUnit/EditOrgUnit)
- [x] Tabela ohrm_attendance_geofence_location (locais por unidade;
      subunit_id NULL = conjunto padrao/fallback)
- [x] GeofenceService reescrito: getLocationsForScope/replaceLocationsForScope/
      validateForEmployee (locais da unidade do funcionario que bate ponto)
- [x] GeofenceConfigurationAPI v2: GET ?subunitId= / PUT com upsert+delete
      por escopo; JSON antigo migrado para a tabela
- [x] Tela admin /attendance/brGeofence + item de menu (Admin only)
- [x] EmployerResolverService: AFD/AFDT/e-Social/comprovante usam o CNPJ da
      unidade do funcionario (fallback: tax_id da organizacao)
- [x] Migracao 005_multi_company.sql (idempotente)

## Pendente (fase 5 / proximas)

1. [ ] Cadastro em massa de empresas/funcionarios por CNPJ (importacao CSV?)
2. [ ] Relatorio de jornada agrupado por empresa (o brWorkTimeReport atual
      nao filtra por unidade)
3. [ ] AFD multi-empresa: exportar um arquivo por CNPJ em lote (hoje o
      cabecalho por unidade vale apenas para exportacao por funcionario)
4. [ ] Selfie/camera no punch (pendente da fase 4)
5. [ ] Fila offline de punch (pendente da fase 4)

# Roadmap de conformidade (2026-08-17)

Levantado consultando o banco e o codigo, nao a documentacao.

Premissas confirmadas com o Leo:
- A raiz "Grupo HRR" nao tem funcionarios; ela so agrupa as ~30 empresas, e o
  CNPJ vive em cada unidade filha. Logo NAO faz sentido preencher CNPJ na raiz.
- Funcionario unico atual e de teste; PIS vazio nao e problema hoje.
- **Geofence sera obrigatorio para as empresas selecionadas**, bloqueando o
  ponto fora do raio. Esse requisito nao e atendido hoje (bloco P0 abaixo).

## P0 - Geofence por empresa - FEITO (2026-08-17, migracao 006)

1. [x] Flag `ohrm_subunit.geofence_required` por unidade; a chave global
      `attendance.br.geofence.enabled` vira chave-mestra.
2. [x] Fail-closed: empresa que exige geofence sem local cadastrado RECUSA o
      ponto (antes liberava em silencio).
3. [x] Funcionario sem unidade e recusado (`missing_subunit`).
4. [x] Resolucao sobe a arvore (departamento -> empresa -> raiz); o conjunto
      padrao (`subunit_id NULL`) saiu da validacao.
5. [x] Tela: switch "Exigir geofence nesta empresa" + aviso quando marcada sem
      local cadastrado.

## P1 - Inviolabilidade dos registros - FEITO (2026-08-28, migracao 007)

6. [x] **Segredo criado.** `attendance.br.signature_secret` agora existe em
      `hs_hr_config`, gerado com `SHA2(RANDOM_BYTES(64), 256)` pela migracao
      007 (idempotente: nao sobrescreve um segredo ja existente).
      O fallback derivado do hostname foi **removido** — ele deixava o codigo
      assinar com uma chave publica e trocava sozinho a cada deploy. Sem o
      segredo o servico agora recusa assinar
      (`AttendanceServiceException::signatureSecretNotConfigured`).
      O hash passou de `sha256(campos|segredo)` para
      `hash_hmac('sha256', campos, segredo)`.

5. [x] **Assinatura roda no fluxo de punch.** `AttendanceDao::savePunchRecord`
      assina o registro quando ele fica final (`isSignable`: state
      `PUNCHED OUT` **e** `punch_out_utc_time` preenchido). Registro ainda
      aberto nao e assinado — na mesma linha ficam as duas batidas, e assinar
      antes marcaria o proprio punch-out como adulteracao.

      Diferente do audit log, **nao e best-effort**: se o segredo sumir, o
      punch-out falha em vez de gravar um registro sem prova de
      inviolabilidade. Decisao consciente, mesma politica fail-closed do P0.

      Verificado contra o banco real (dentro de transacao revertida): punch-in
      nao assina; punch-out assina; `UPDATE` direto no `punch_out_utc_time`
      derruba a verificacao para `VIOLATED`; sem a chave, recusa.

      Testes: `RecordSignatureTest`, 12 casos sobre o hash e a regra de quando
      assinar, sem banco.

### Pendente do P1

- [ ] **Reassinar os 5 registros existentes** (`backfill_signatures.php`).
      O registro 1 tem hash gerado antes da 007, com a chave derivada do
      hostname e o algoritmo antigo: ele **nao prova nada** e nunca vai
      conferir. Os outros 4 estao com `record_hash` NULL. Precisa descartar o
      hash antigo e reassinar os 5 — sao registros do usuario de teste.

- [ ] **Registro que fica aberto nunca e assinado.** Se o funcionario esquece
      o punch-out, a linha fica sem hash ate ser fechada. O
      `verifyPeriod` reporta isso como `unsigned`, mas ninguem olha.
      -> Avaliar alerta ou fechamento automatico.

## P2 - Antes de cadastrar as empresas reais - PARCIAL (2026-08-28)

7. [x] **CNPJ validado no cadastro e exigido na exportacao.**
      `Rules::CNPJ` (do Respect\Validation, que ja vinha no projeto) passou a
      valer no `SubunitAPI`: CNPJ com digito trocado ou `00.000.000/0000-00` e
      recusado ao salvar a unidade. String vazia continua limpando o campo.

      Achado no caminho: **`EmployerResolverService` nao subia a arvore.** Ele
      so olhava a lotacao imediata do funcionario, entao funcionario em
      departamento abaixo da empresa nao encontrava CNPJ nenhum e caia no
      `tax_id` da raiz -- que e NULL por decisao. Agora sobe a cadeia como o
      geofence, parando na unidade mais proxima que declarou CNPJ. A subida
      virou `SubunitChainTrait`, compartilhado com o `GeofenceService`, para os
      dois nunca discordarem sobre a qual empresa o funcionario pertence.

      CNPJ invalido e tratado como ausente e **nao** sobe para a empresa acima:
      arquivar o funcionario sob a empresa errada e pior do que recusar.

8. [x] **PIS/NIS exigido na exportacao.** `Rules::PIS` no
      `EmployeePersonalDetailAPI` recusa digito verificador errado. O AFD/AFDT
      recusa exportar quando o funcionario nao tem PIS valido, nomeando quem
      esta pendente. O fallback para "Other Id" continua, mas so quando o que
      esta la e mesmo um PIS -- numero de cracha nao entra mais no arquivo.

      **Consequencia imediata:** o AFD nao gera enquanto o funcionario de teste
      estiver sem PIS e sem lotacao. Era esse o ponto: antes gerava um arquivo
      com `000000000000` que so seria recusado pelo auditor.

      Guardas em `BrExportGuard`, 9 testes; validadores em
      `Core\Utility\BrazilianDocument`, 18 testes.

### Pendente do P2

9. [ ] **Cadastro em massa das ~30 empresas** (ver Fase 5, item 1).

10. [ ] **Campo de PIS aceita 12 caracteres, entao PIS pontuado
      (`120.64487.89-3`, 14 caracteres) e recusado pelo tamanho antes de chegar
      na validacao.** So digitos funciona. Avaliar aumentar o limite ou
      normalizar na tela.

11. [ ] **e-Social e comprovante de punch nao tem essas guardas.** Usam o mesmo
      `EmployerResolverService` (entao ja subem a arvore), mas ainda formatam
      zeros quando falta CNPJ/PIS. Fazer quando o e-Social for implantado
      (P3, item 13).

## P3 - Limpeza e documentacao - QUASE FECHADO (2026-08-28, migracao 008)

10. [x] **`NsrService` removido.** Era codigo morto: o NSR real sempre saiu de
      `AttendanceDao::getNextNsr()` com `SELECT ... FOR UPDATE`, e ninguem
      chamava o servico.

11. [x] **Batida por terceiro agora exige justificativa.**
      Achado ao investigar: nao era o geofence do admin sendo validado -- o
      `isGeofenceApplicable()` ja limitava a validacao a batidas proprias.
      O problema era outro e maior: **a batida por terceiro pulava o geofence
      por completo**, e era a unica rota em volta de uma cerca que a empresa
      tornou obrigatoria.

      Nao da para validar a cerca nesse caso: as coordenadas sao de quem opera
      a tela, nao do trabalhador. Entao a batida continua permitida (esquecer
      de bater tem de ser corrigivel) mas, em empresa que exige geofence,
      precisa de motivo com pelo menos 5 caracteres.

      A justificativa vira uma linha propria na trilha
      (`action = 'PROXY_PUNCH'`, migracao 008), para o desvio ser consultavel
      e nao apenas dedutivel de `changed_by <> employee_id`.

      `ProxyPunchGuard`, 7 testes. Conferido contra os dados reais: na Acacia
      recusa sem motivo e aceita com; no Grupo HRR, que nao exige, nada muda.

12. [x] **Norma uniformizada em "Portaria SEPRT 673/2021"** (escolha do Leo).
      Os layouts de arquivo continuam citados como herdados da Portaria
      1.510/2009 (AFD, Anexo I; AFDT, Anexo II), que e de onde o formato vem.

      **Continua pendente de confirmacao com contador/juridico.** A busca web
      falhou no ambiente, entao a citacao nao foi verificada em fonte oficial;
      foi so uniformizada para o codigo parar de dizer tres coisas diferentes.
      Ressalva registrada no README. Nada de comportamento depende disso.

### Corrigido de passagem (2026-08-31)

14. [x] **A trilha de auditoria nunca gravava o autor.**
      `AttendanceDao::getCurrentEmpNumber()` chamava um
      `Services::getContainer()` estatico que nao existe, dentro de um
      `catch (\Throwable)` — entao toda linha CREATE/UPDATE era gravada com
      `changed_by_emp_number` NULL, em silencio. As duas linhas que existiam
      no banco estavam assim.

      Passou a usar o `AuthUserTrait`, que e o idioma que o resto do codigo ja
      usa. Conferido: agora grava `changed_by_emp_number = 1`.

      Isso importa para o P3 item 11: a justificativa da batida por terceiro
      so vale como prova se der para dizer quem bateu.

### Pendente do P3

13. [ ] **e-Social nao implantado** (`ohrm_br_esocial_config` vazio). O gerador
      S-1200/S-1210 existe; falta configuracao e processo.

## Consequencia operacional a decidir

Com geofence exigindo coordenadas, batida sem GPS e recusada
(`missing_coordinates`). A tela desktop padrao (`/attendance/punchIn`) nao envia
coordenadas, entao funcionarios de empresa com geofence terao de usar a pagina
mobile (`/attendance/mobile`). Confirmar que e o fluxo desejado.
