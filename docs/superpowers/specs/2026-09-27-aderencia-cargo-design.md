# Perfil do cargo e comparação de aderência

**Data:** 2026-09-27
**Branch:** `pt-br-customizations`
**Situação:** desenho aprovado em chat, seção por seção.
**Escopo:** partes D (perfil do cargo / engenharia de cargo) e E (aderência pessoa × cargo)
do perfil comportamental. Parte do que já existe: teste de perfil Big Five + DISC (spec
`2026-09-27-perfil-comportamental-design.md`, migração 017).

## Objetivo

O RH define, para cada **cargo**, o perfil ideal: faixa desejada em cada fator do Big Five e
do DISC, a importância de cada fator e as competências do cargo. Depois escolhe várias
pessoas (candidatos e funcionários, de qualquer empresa do grupo: postos, clínicas e outros
ramos) e vê, em gráficos sobrepostos ao perfil do cargo, quem tem mais aderência.

## Decisões tomadas

| Tema | Decisão |
|---|---|
| Onde fica o perfil ideal | **No cargo** nativo (Admin → Cargos). Toda vaga do cargo usa o mesmo perfil |
| Como definir | **Faixa + importância** por fator; botão que **sugere** as faixas a partir de funcionários de referência |
| Competências | Lista no cargo, com importância e nível mínimo; **nota de 1 a 5 dada pelo RH** por pessoa |
| Quem entra na comparação | **Candidatos e funcionários**, de qualquer empresa do grupo |
| Arquitetura | Tabelas próprias ligadas ao cargo nativo; aderência **calculada na hora**, nunca gravada |
| Seleção | Lista com caixas de marcar, filtros e "marcar todos"; até 20 pessoas |
| Visões | Radar sobreposto (até 6 linhas), ranking com tabela fator por fator, duelo X × Y em barras de RPG |
| PDF | Pela impressão do navegador, como no perfil individual |
| Telas nativas | Não são alteradas. Nem a lista de Candidatos nem a de Vagas ganham botões |

As telas falam em **teste de perfil** e não trazem ressalvas psicométricas (decisão do Leo).

## 1. Cálculo da aderência

Todos os fatores estão na escala de 0 a 100 (`InventoryScoring::score`). Os fatores são
identificados por **instrumento + fator**, porque `C` existe nos dois (Conscienciosidade no
Big Five, Conformidade no DISC).

### Fator
- O cargo define `min` e `max` (0 a 100, múltiplos de 5, `min ≤ max`) e o peso: **ignorar = 0,
  desejável = 1, essencial = 2**.
- Distância `d`: 0 se `min ≤ x ≤ max`; senão a distância até a borda mais próxima.
- Nota do fator: `max(0, 100 − 2,5 × d)`. Zera com 40 pontos fora da faixa.
- Cor na tabela: **verde** com `d = 0`, **âmbar** com `0 < d ≤ 10`, **vermelho** com `d > 10`.

### Bloco comportamental
Média ponderada das notas dos fatores com peso > 0, nos dois instrumentos juntos.

### Competências
- Cada competência tem peso **desejável = 1** ou **essencial = 2** e um **nível mínimo** de
  1 a 5.
- Nota do RH `r` de 1 a 5 vira `(r − 1) / 4 × 100`: 0, 25, 50, 75 ou 100.
- Bloco de competências: média ponderada das competências **com nota**.

### Aderência geral
- `geral = p × comportamental + (1 − p) × competências`, com `p` = peso comportamental do
  cargo (0 a 100 %, padrão 50 %).
- Cargo sem competências: `geral = comportamental`, sem marca.
- Alguma competência sem nota: o bloco usa só as que têm nota e a pessoa recebe a marca
  **"parcial"**. Nenhuma com nota: `geral = comportamental` e marca "parcial".
- Exibição arredondada para inteiro; o ranking ordena pelo valor não arredondado e, no
  empate, pelo nome.

### Alertas (não eliminam)
- **"Fora da faixa em fator essencial"**: algum fator essencial com `d > 0`.
- **"Abaixo do mínimo em competência essencial"**: alguma competência essencial com nota
  menor que o nível mínimo. Competência sem nota não alerta.

### Duelo: quem está mais perto
Calculado na tela, a partir das notas por fator que a API já devolve.
- Fator: a seta vai para a maior nota do fator (a mais perto da faixa, **não** o maior
  score). Notas iguais, sem seta.
- Competência: a seta vai para a maior nota do RH. Sem nota de um dos dois, sem seta.
- Fator com peso 0 aparece no duelo sem faixa e sem seta.

### Sugestão por funcionários de referência
- Entrada: 1 a 20 funcionários com teste concluído.
- Para cada fator: `min = piso5(média − DP)`, `max = teto5(média + DP)`, com DP amostral e
  limites 0 e 100. Com **uma** referência, `média ± 10`. Largura mínima da faixa: 10.
- Pesos e competências não são sugeridos. A sugestão só preenche o formulário; o RH revisa e
  salva.

## 2. Qual teste de cada pessoa

- **Pessoa** é identificada por `c{id}` (candidato) ou `e{id}` (funcionário).
- Entra o teste com status `COMPLETED` mais recente (`completed_at`), buscando por
  `employee_id` para funcionários e por `candidate_id` para candidatos.
- Candidato contratado tem o teste ligado também ao funcionário (já feito na contratação),
  então aparece como **funcionário**. Na lista de seleção, candidatos cujo teste já tem
  `employee_id` ficam fora do tipo "Candidato".
- Pessoa sem teste concluído não entra na comparação (a API responde 400 com o nome).

## 3. Dados (migração 018)

### `ohrm_br_job_profile`
- `id`, `job_title_id` (único, FK `ohrm_job_title` ON DELETE CASCADE),
  `behavior_weight` (0 a 100, padrão 50), `updated_by_emp_number`, `updated_at`.

### `ohrm_br_job_profile_factor`
- `id`, `job_profile_id` (FK CASCADE), `instrument` (`BIG5`/`DISC`), `factor`,
  `min_score`, `max_score`, `weight` (0, 1 ou 2).
- Único por (`job_profile_id`, `instrument`, `factor`). O perfil sempre grava os 9 fatores.

### `ohrm_br_job_competency`
- `id`, `job_profile_id` (FK CASCADE), `name` (até 100), `weight` (1 ou 2),
  `min_level` (1 a 5, padrão 3), `sort_order`.

### `ohrm_br_competency_rating`
- `id`, `competency_id` (FK CASCADE), `candidate_id` (NULL, FK CASCADE), `employee_id`
  (NULL, FK CASCADE), `rating` (1 a 5), `rated_by_emp_number`, `rated_at`.
- Únicos: (`competency_id`, `candidate_id`) e (`competency_id`, `employee_id`).
- Busca: pelo funcionário, se a pessoa é `e{id}`; senão pelo candidato.
- **Contratação:** `linkHiredEmployee` também grava o `employee_id` nas notas do candidato,
  para elas seguirem a pessoa.

### Telas, menu e permissões
- Telas no módulo `recruitment`, `menu_configurator` =
  `AssessmentMenuConfigurator`, só Admin:
  `brJobProfiles` (lista), `brJobProfile` (edição, `jobTitleId` no caminho),
  `brProfileCompare` (comparação).
- Menu em Recrutamento: **Perfis de cargo** (ordem 400) e **Comparar perfis** (500).
- APIs (Admin; data groups, `ohrm_api_permission` e `ohrm_user_role_data_group`):
  - `JobProfileAPI`: lista de cargos com situação; GET/PUT do perfil de um cargo.
  - `JobProfileSuggestionAPI`: GET com os funcionários de referência → faixas sugeridas.
  - `ProfilePeopleAPI`: GET da lista de seleção (filtros: vaga, empresa, tipo, nome).
  - `ProfileComparisonAPI`: GET com `jobTitleId` e `subjects` → perfil do cargo, pessoas,
    notas, blocos, geral, marcas e alertas, já ordenado.
  - `CompetencyRatingAPI`: PUT de uma nota (pessoa, competência, 1 a 5); PUT sem nota
    apaga a nota.
- Idempotente, como a 017. i18n num arquivo próprio, prefixo `jobfit_`, grupo 17.

## 4. Regras (classes puras, testadas sem banco)

- `JobFit`: nota do fator, cor, bloco comportamental, competências, geral, marca "parcial",
  alertas e ranking.
- `JobProfileSuggestion`: média, DP amostral, arredondamento de 5 em 5, largura mínima,
  caso de uma referência.
- `JobProfileRules`: valida o perfil (9 fatores, `min ≤ max`, múltiplos de 5, pesos 0/1/2,
  ao menos um fator com peso > 0, peso comportamental 0 a 100, nome da competência não
  vazio e sem repetição, nível mínimo 1 a 5, até 30 competências) e a comparação (1 a 20
  pessoas, sem repetição; uma pessoa já mostra a aderência dela ao cargo).
- Violações lançam `JobFitRuleException`, que a API devolve como 400 com a mensagem.

## 5. Telas

### Perfis de cargo (Recrutamento → Perfis de cargo)
- Tabela com os cargos nativos não excluídos: nome, "Perfil definido" ou "Sem perfil",
  quantidade de competências, última alteração, botão **Editar**.
- Botão **Cadastrar cargo** abre Admin → Cargos em outra aba.
- Cargo sem nenhum cadastro: mensagem explicando que os cargos vêm de Admin → Cargos.

### Editar perfil do cargo
- **Fatores**, agrupados em Big Five e DISC: para cada um, uma barra de 0 a 100 com a
  faixa marcada, dois campos numéricos (mínimo e máximo, passo 5) e a importância
  (ignorar, desejável, essencial). Fator ignorado fica esmaecido.
- **Sugerir a partir de funcionários**: diálogo com filtro por empresa e busca; só aparecem
  funcionários com teste concluído. "Aplicar" preenche as faixas sem salvar.
- **Competências**: linhas com nome, importância e nível mínimo (1 a 5), com adicionar,
  remover e reordenar.
- **Peso comportamental × competências**: campo de 0 a 100 %.
- Perfil novo abre com todas as faixas 0–100 e peso **desejável**.

### Comparar perfis (Recrutamento → Comparar perfis)
- **Cargo** (obrigatório). Se a tela abrir com filtro de vaga, o cargo da vaga vem
  preenchido. Cargo sem perfil definido não compara: a tela pede para definir o perfil e
  leva à edição.
- **Seleção**: lista das pessoas com teste concluído, com filtros de vaga (candidatos),
  empresa (funcionários, incluindo sub-unidades), tipo e nome; **Marcar todos** marca o que
  o filtro mostra; até 20 marcadas.
- Estado no endereço (`?jobTitleId=…&subjects=c12,e5,…&vacancyId=…`): recarregar ou mandar o
  link abre a mesma comparação.
- **Radar sobreposto** (Big Five e DISC): faixa do cargo em anel sombreado entre mínimo e
  máximo; eixos com peso 0 tracejados e sem faixa; essencial com ★; uma linha colorida por
  pessoa. Até 6 linhas (duelo: exatamente 2 pessoas): por padrão as 6 primeiras do ranking; cada pessoa do ranking tem a
  chave **Mostrar no gráfico**.
- **Ranking**: posição, nome, tipo (e empresa, para funcionário), geral, comportamental,
  competências, marca "parcial", alertas.
- **Tabela fator por fator**: um fator por linha, uma pessoa por coluna, score com a cor
  verde/âmbar/vermelho; abaixo, as competências com o campo de nota (1 a 5). Salvar uma nota
  recalcula o ranking.
- **Duelo X × Y**: escolher duas pessoas; "barra de vida" com a aderência geral; fatores em
  barras segmentadas de 10 blocos espelhadas (X para a esquerda, Y para a direita) com a faixa
  do cargo em moldura dourada; competências em 1 a 5 estrelas com o nível mínimo marcado;
  seta ▲ para quem está mais perto do cargo em cada linha.
- **Baixar PDF**: imprime a visão aberta (radar com ranking e tabela, ou o duelo), A4, sem
  menus, título "Comparação – {cargo}".

### Perfis comportamentais (tela existente)
- Linhas com teste concluído ganham caixa de marcar; botão **Comparar selecionados** abre a
  comparação com essas pessoas (e o cargo da vaga filtrada, se houver).

## 6. Testes e verificação

- PHPUnit no harness sem banco: `JobFit`, `JobProfileSuggestion`, `JobProfileRules` e as APIs
  (validação de parâmetros e 400 nas regras), como nos módulos anteriores.
- Jest: radar com várias séries e faixa, ranking, duelo (setas e faixa), seletor (marcar
  todos, limite de 20), editor do perfil (sugestão aplicada sem salvar).
- Sondas pela `GenericRestController` no container: criar perfil, sugerir, comparar, dar
  nota, contratar candidato e ver a nota seguir o funcionário.
- `yarn build`, deploy, conferência do JS servido e PDF real gerado pelo Playwright.

## Fora deste ciclo

- Nota de competência vinda de uma prova dos Formulários.
- Histórico de comparações salvas.
- Perfis de cargo prontos por ramo (posto, clínica).
- Botões nas telas nativas de Candidatos e Vagas.
- Média da equipe de uma empresa como série no radar.
