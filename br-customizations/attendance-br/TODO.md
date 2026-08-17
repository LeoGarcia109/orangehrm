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
3. [ ] Fila offline de punch (bater ponto sem sinal e sincronizar)
4. [ ] Camera/selfie no punch (prova adicional, opcional)
5. [ ] Testar instalacao PWA em iOS real (Safari → Adicionar à Tela de Início)
6. [ ] Menu lateral: link para a pagina mobile (opcional; funcionarios
      acessam direto pelo link compartilhado)

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

## P1 - Inviolabilidade dos registros (marcado "Feito", nao opera)

5. [ ] **Assinatura nao roda no fluxo de punch.**
      `RecordSignatureService` so e referenciado por `SignatureVerifyAPI`, ou
      seja, so assina quando alguem chama o endpoint de verificacao. Dos 5
      registros existentes, 4 estao com `record_hash` NULL.
      -> Chamar no punch-out (AttendanceDao/AttendanceService).

6. [ ] **`attendance.br.signature_secret` nao existe em `hs_hr_config`.**
      `computeHash()` faz `$this->secretKey ?? ''`; sem o segredo o hash e um
      SHA-256 de campos publicos (NSR, id, horarios, state). Quem tiver escrita
      no banco adultera o registro e recalcula um hash valido.
      -> Definir o segredo ANTES de assinar em massa, senao os hashes precisam
      ser refeitos. Fazer 6 antes de 5.

## P2 - Antes de cadastrar as empresas reais

7. [ ] **CNPJ por empresa sem validacao.** `EmployerResolverService` cai no
      `tax_id` da organizacao quando a unidade nao tem CNPJ. Como a raiz nao tem
      (e nem deve ter), uma empresa cadastrada sem CNPJ gera AFD com
      `000000000000` no cabecalho, silenciosamente.
      -> Validar formato do CNPJ no cadastro da unidade e avisar quando faltar.

8. [ ] **PIS/NIS obrigatorio para funcionario real.** Hoje vazio (so o usuario
      de teste). O AFD identifica o trabalhador pelo PIS.

9. [ ] **Cadastro em massa das ~30 empresas** (ver Fase 5, item 1).

## P3 - Limpeza e documentacao

10. [ ] **`NsrService` e codigo morto.** O NSR real e atribuido em
      `AttendanceDao::getNextNsr()` com `SELECT ... FOR UPDATE` (funciona); o
      servico duplica a logica e ninguem chama. Remover.

11. [ ] **Admin batendo por terceiro valida o geofence do admin.**
      `validateGeofence` usa `getAuthUser()->getEmpNumber()` mesmo na rota
      `/employees/{empNumber}/records`. Avaliar se e o desejado.

12. [ ] **Norma de referencia inconsistente.** Os docs citam "Portaria SEPRT
      673/2021", "Portaria 1.510/2009" e "Portaria 671/673" em lugares
      diferentes. Uniformizar -- confirmando com contador/juridico, nao pelo
      codigo.

13. [ ] **e-Social nao implantado** (`ohrm_br_esocial_config` vazio). O gerador
      S-1200/S-1210 existe; falta configuracao e processo.

## Consequencia operacional a decidir

Com geofence exigindo coordenadas, batida sem GPS e recusada
(`missing_coordinates`). A tela desktop padrao (`/attendance/punchIn`) nao envia
coordenadas, entao funcionarios de empresa com geofence terao de usar a pagina
mobile (`/attendance/mobile`). Confirmar que e o fluxo desejado.
