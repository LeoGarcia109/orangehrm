# Pendencias - Relatorio de Jornada BR

## Problema atual (2026-07-30)

A pagina `/attendance/brWorkTimeReport` carrega os cabecalhos mas NAO retorna dados
ao clicar em "Gerar Relatorio". O servico backend funciona (testado via CLI),
as permissoes estao corretas, mas a chamada HTTP do Vue retorna vazio.

## Causa provavel

O Vue faz a chamada via `APIService` com `params` (query string), mas pode haver:
1. Cache de permissao na sessao do usuario (precisa relogar apos inserir permissoes)
2. O formato de resposta do `EndpointCollectionResult` com `ArrayModel` pode nao
   estar sendo parseado corretamente pelo Vue (espera `response.data.data[0]`)
3. O `employee-autocomplete` pode estar enviando `empNumber` como objeto em vez de int

## Como debuggar

1. Abrir DevTools > Network no navegador
2. Acessar Time > Attendance > Relatorio de Jornada
3. Selecionar funcionario + data e clicar "Gerar Relatorio"
4. Verificar a request para `/api/v2/attendance/br/work-time-report`
   - Status code (deve ser 200)
   - Query params enviados (empNumber deve ser int, fromDate/toDate em Y-m-d)
   - Response body (deve ter `data[0].workedFormatted` etc.)

## O que ja foi verificado (funciona via CLI)

- TimeBankService.calculatePeriod(1, '2026-07-30', '2026-07-30', 1) retorna dados corretos
- ohrm_api_permission tem entrada para WorkTimeReportAPI com can_read=1
- ohrm_user_role_data_group tem permissao para role Admin (id=1)
- Rota sem `id: 0`, API implementa CollectionEndpoint com getAll()
- AFDExporter gera arquivo corretamente

## Proximos passos

1. [ ] Debuggar a chamada HTTP no navegador (DevTools > Network)
2. [ ] Verificar se o Vue envia empNumber como int ou objeto
3. [ ] Verificar se a resposta da API tem o formato esperado pelo Vue
4. [ ] Testar com curl autenticado (pegar cookie da sessao)
5. [ ] Traduzir labels do formulario para portugues

# Fase 4 - PWA Mobile + Geofence (2026-08-15)

## Entregue

- [x] Pagina mobile `/attendance/mobile` (punch com GPS, historico do dia)
- [x] Backend aceita/persiste latitude/longitude no punch (POST/PUT records)
- [x] GeofenceService (haversine) + endpoint GET/PUT /api/v2/attendance/geofence
- [x] Shell PWA: manifest.webmanifest, sw.js, icones, meta tags iOS/Android
- [x] Migracao 004: permissoes, tela, config defaults, i18n pt_BR

## Pendente (fase 4)

1. [ ] Tela admin para gerenciar areas do geofence (hoje so via API/SQL)
2. [ ] Notificacoes push (lembrete de punch out) — exige VAPID keys
3. [ ] Fila offline de punch (bater ponto sem sinal e sincronizar)
4. [ ] Camera/selfie no punch (prova adicional, opcional)
5. [ ] Testar instalacao PWA em iOS real (Safari → Adicionar à Tela de Início)
6. [ ] Menu lateral: link para a pagina mobile (opcional; funcionarios
      acessam direto pelo link compartilhado)
