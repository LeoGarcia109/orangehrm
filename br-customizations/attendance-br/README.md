# Ponto Eletronico BR - Portaria SEPRT 673/2021

Customizacao completa do modulo de Attendance do OrangeHRM para atender a
legislacao brasileira de ponto eletronico (Portaria SEPRT 673/2021).

## Fases implementadas

### Fase 1 - Conformidade Basica

| Requisito | Status |
|---|---|
| NSR (Numero Sequencial de Registro) integrado no DAO | Feito |
| PIS/NIS no cadastro (`Employee.pisNumber`) | Feito |
| CNPJ/CEI via `Organization.taxId` / `registrationNumber` | Feito |
| Audit trail completo (`ohrm_attendance_audit_log`) | Feito |
| Retificacoes com workflow de aprovacao | Feito |
| Exportacao AFD (layout Portaria 1.510/2009) | Feito |
| Geolocalizacao (colunas lat/lng) | Feito |

### Fase 2 - Calculos Brasileiros

| Requisito | Status |
|---|---|
| Horas extras (50% / 100%) | Feito |
| Adicional noturno (20% urbano, 25% rural) | Feito |
| Hora reduzida noturna (52min30s = 1h) | Feito |
| Intervalo intrajornada (min 1h para >6h) | Feito |
| Intervalo interjornada (min 11h) | Feito |
| Banco de horas (saldo/fechamento por periodo) | Feito |
| Domingo/feriado (100%) | Feito |

### Fase 3 - Extras

| Requisito | Status |
|---|---|
| Assinatura digital SHA-256 (inviolabilidade) | Feito |
| Comprovante do empregado (HTML imprimivel/PDF) | Feito |
| Exportacao AFDT (Arquivo Fonte de Dados Tratado) | Feito |
| Gerador de eventos e-Social (S-1200, S-1210) | Feito |
| Captura GPS no frontend (composable Vue) | Feito |

## Migracoes SQL

```bash
mysql -u<user> -p<senha> orangehrm < migrations/001_nsr_audit_pis.sql
mysql -u<user> -p<senha> orangehrm < migrations/002_time_bank.sql
mysql -u<user> -p<senha> orangehrm < migrations/003_hash_esocial.sql
```

## Estrutura

```
attendance-br/
├── Api/
│   ├── AFDExportAPI.php              # Download AFD
│   ├── AttendanceAuditLogAPI.php     # Trilha de auditoria
│   └── AttendanceRetificationAPI.php # CRUD retificacoes
├── config/
│   └── routes.yaml                   # Todas as rotas (Fases 1-3)
├── Entity/
│   ├── AttendanceAuditLog.php
│   └── AttendanceRetification.php
├── frontend/
│   ├── useGeolocation.js             # Composable Vue para GPS
│   └── INTEGRATION.md                # Guia de integracao no frontend
├── migrations/
│   ├── 001_nsr_audit_pis.sql
│   ├── 002_time_bank.sql
│   └── 003_hash_esocial.sql
├── Service/
│   ├── AFDExporter.php               # Gerador AFD
│   ├── AFDTExporter.php              # Gerador AFDT
│   ├── AttendanceAuditService.php    # Auditoria
│   ├── BrazilianWorkTimeCalculator.php # HE + Noturno + Intervalos
│   ├── ESocialEventGenerator.php     # XML e-Social (S-1200, S-1210)
│   ├── PunchReceiptService.php       # Comprovante HTML/PDF
│   ├── RecordSignatureService.php    # Hash SHA-256
│   └── TimeBankService.php           # Banco de horas
└── README.md
```

## Endpoints da API

| Metodo | Rota | Fase |
|---|---|---|
| GET | `/api/v2/attendance/br/afd-export` | 1 |
| GET | `/api/v2/attendance/br/audit-log` | 1 |
| GET/POST/DELETE | `/api/v2/attendance/br/retification` | 1 |
| PUT | `/api/v2/attendance/br/retification/{id}` | 1 |
| GET/POST | `/api/v2/attendance/br/time-bank` | 2 |
| GET | `/api/v2/attendance/br/time-bank/balance/{empNumber}` | 2 |
| GET | `/api/v2/attendance/br/work-time-report` | 2 |
| GET | `/api/v2/attendance/br/receipt/{id}` | 3 |
| GET | `/api/v2/attendance/br/receipt/daily` | 3 |
| GET/POST | `/api/v2/attendance/br/signature/verify` | 3 |
| GET | `/api/v2/attendance/br/afdt-export` | 3 |
| POST | `/api/v2/attendance/br/esocial/generate` | 3 |
| GET | `/api/v2/attendance/br/esocial/events` | 3 |

## Regras de negocio

- **Hora extra**: 50% primeiras 2h, 100% excedente (configuravel)
- **Adicional noturno**: 20% urbano (22h-5h), 25% rural (configuravel)
- **Hora reduzida**: 52min30s = 1h noturna (art. 73 CLT)
- **Intervalo intrajornada**: min 1h para >6h (art. 71 CLT)
- **Intervalo interjornada**: min 11h (art. 66 CLT)
- **Domingo/feriado**: 100% (Sumula 146 TST)
- **Banco de horas**: saldo com fechamento mensal (art. 59 CLT)
- **NSR**: sequencial global, atribuido com lock pessimista
- **Hash SHA-256**: sobre NSR + emp + tempos + state + secret
- **Retificacao**: original intocado, workflow PENDING/APPROVED/REJECTED

## Configuracao

Apos aplicar as migracoes, configurar:

1. **CNPJ/CEI**: Admin > Organization > General Information (Tax ID / Registration Number)
2. **PIS/NIS**: Coluna `pis_number` em `hs_hr_employee`
3. **Secret do hash**: Inserir em `hs_hr_config`:
   ```sql
   INSERT INTO hs_hr_config (`name`, `value`) VALUES ('attendance.br.signature_secret', 'sua-chave-secreta-aqui');
   ```
4. **e-Social**: Inserir configuracao em `ohrm_br_esocial_config`
