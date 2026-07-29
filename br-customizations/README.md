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

## Próximas customizações planejadas

- [ ] Integração com e-Social
- [ ] Holerite brasileiro
- [x] Ponto eletrônico compatível com Portaria 673/2021
- [x] Adaptadores de PIS, CNPJ (via attendance-br)
- [ ] Integração com WhatsApp para notificações