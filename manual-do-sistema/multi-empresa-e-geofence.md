# Manual do Sistema — Multi-empresa e Locais de Ponto (Geofence)

> Sistema: OrangeHRM BR (`https://rh.leogarcia.com.br`)
> Perfil necessário: **Admin** para todos os passos deste manual.

Este manual explica como trabalhar com **várias empresas (CNPJ diferentes) em
uma única instalação**, com um RH unificado, e como configurar os **locais
permitidos para bater ponto** (geofence) de cada empresa.

## Como funciona

Cada empresa vira uma **unidade da estrutura organizacional**. Assim:

- Cada unidade pode ter o seu próprio **CNPJ** e **CEI/CNO**
- Cada funcionário é vinculado à unidade da empresa dele
- O **geofence** valida o ponto contra os locais da empresa do funcionário
- Os arquivos fiscais (AFD, AFDT, e-Social, comprovante de ponto) usam o
  **CNPJ da empresa do funcionário** automaticamente

---

## 1. Criar uma empresa

1. Acesse o sistema como Admin
2. Menu **Admin → Organization → Structure** (Estrutura Organizacional)
3. Na árvore, clique em **+** na unidade sob a qual a nova empresa ficará
   (para empresas independentes, use a raiz)
4. No diálogo **Adicionar unidade**, preencha:

| Campo | Descrição |
|---|---|
| **Nome** (obrigatório) | Razão social ou nome fantasia da empresa — deve ser único |
| **Unit Id** | Código interno opcional (ex.: `EMP-001`) |
| **CNPJ** | CNPJ da empresa, com ou sem pontuação (ex.: `12.345.678/0001-90`). Usado no AFD/e-Social |
| **CEI/CNO** | Opcional — para empregador pessoa física ou obra (CNO) |
| **Descrição** | Opcional |

5. Clique em **Salvar**

> 💡 **Dica:** você pode criar uma hierarquia com quantas empresas precisar
> (30, 50, 100...). Cada empresa pode ter filiais como subunidades abaixo
> dela — o CNPJ/CEI vale para a unidade onde foi preenchido.

### Editar o CNPJ de uma empresa já criada

1. **Admin → Organization → Structure**
2. Clique no ícone de **editar** (lápis) da unidade
3. Ajuste os campos **CNPJ** e **CEI/CNO** e salve

---

## 2. Vincular funcionários à empresa

O ponto, o geofence e os arquivos fiscais usam a empresa **vinculada ao
funcionário**. Para vincular:

1. Menu **PIM → Employee List** → clique no funcionário
2. Aba **Job** (Cargo)
3. No campo **Sub-Unit / Sub-unidade**, selecione a empresa
4. **Salvar**

> Para vincular em massa ao importar funcionários, repita o passo acima por
> funcionário — a unidade é salva no cadastro individual.

---

## 3. Adicionar locais de ponto (Geofence)

Aqui você define **onde** os funcionários de cada empresa podem bater ponto
pelo celular (PWA). O registro feito fora dos locais cadastrados é
**recusado pelo servidor**.

1. Menu **Time → Attendance → Locais de Ponto (Geofence)**
   (ou acesse direto: `https://rh.leogarcia.com.br/web/index.php/attendance/brGeofence`)
2. No campo **Empresa (unidade da estrutura)**, escolha:
   - **Padrão (todas as empresas sem locais próprios)** — locais válidos
     como fallback para qualquer empresa que não tenha cadastro próprio
   - ou uma **empresa específica** — locais exclusivos dela
3. Clique em **Adicionar Local** e preencha:

| Campo | Descrição |
|---|---|
| **Nome do local** | Ex.: "Escritório São Paulo", "Obra Centro" |
| **Latitude** | Coordenada decimal (ex.: `-23.550520`) |
| **Longitude** | Coordenada decimal (ex.: `-46.633308`) |
| **Raio (metros)** | Tolerância ao redor do ponto — ex.: `300` |

4. Adicione quantos locais precisar (até 20 por empresa)
5. Marque/desmarque **Ativar validação de localização (geofence)** — o
   interruptor é global (vale para todas as empresas)
6. Clique em **Salvar**

### Como obter latitude e longitude

Opção mais simples: abra o endereço no **Google Maps**, clique com o botão
direito no local → as coordenadas aparecem no topo do menu (clique para
copiar). Use sempre o formato decimal com ponto (ex.: `-23.550520`).

### Como escolher o raio

- GPS de celular tem precisão típica de **10 a 30 m** (pior em áreas com
  prédios altos ou dentro de galpões)
- **Recomendado: 200 a 500 m** para escritórios e obras pequenas
- Para locais com muito sinal refletido (centros urbanos), até **1000 m**
- Raio pequeno demais gera frustração com recusas indevidas; raio grande
  demais permite ponto de longe

---

## 4. Testar

1. No celular, abra o PWA de ponto (`https://rh.leogarcia.com.br/web/index.php/attendance/mobile`)
   logado como um funcionário vinculado a uma empresa com geofence
2. **Dentro do local** → o ponto registra normalmente e mostra
   "Localização capturada"
3. **Fora do local** → o sistema recusa com a mensagem
   *"Registro fora da área permitida — você não está em um local autorizado"*
4. Se o funcionário negar a permissão de localização do navegador, o ponto
   também é recusado quando o geofence está ativo (as coordenadas são
   obrigatórias)

---

## 5. Impacto nos arquivos fiscais (BR)

| Arquivo | Comportamento multi-empresa |
|---|---|
| **AFD / AFDT** (Portaria 673/2021) | Ao exportar **por funcionário**, o cabeçalho usa o CNPJ/CEI da empresa dele. Exportações gerais usam o CNPJ da organização (raiz) |
| **e-Social S-1200 / S-1210** | O evento usa o CNPJ da empresa do funcionário |
| **Comprovante de ponto** | O recibo impresso mostra o CNPJ/nome da empresa do funcionário |

Se a unidade **não tiver CNPJ preenchido**, o sistema usa automaticamente o
CNPJ cadastrado em **Admin → Organization → General Information**.

---

## Perguntas frequentes

**Preciso cadastrar locais para todas as empresas?**
Não. Cadastre locais apenas para as empresas que precisam de restrição.
Empresas sem locais próprios usam o conjunto **Padrão** — e se nem o padrão
tiver locais, o ponto é liberado (mesmo com o geofence ativo).

**Posso trocar a empresa de um funcionário?**
Sim — basta editar a **Sub-unidade** na aba Job. O histórico de ponto é
mantido; a partir da troca, passam a valer os locais e o CNPJ da nova empresa.

**O geofence bloqueia ponto registrado pelo RH (proxy)?**
Não. A validação só se aplica quando o **próprio funcionário** bate o ponto
pelo celular/navegador. Lançamentos feitos pelo RH ou supervisor nunca são
bloqueados pelo geofence.

**Posso remover um local?**
Sim — na tela de locais, clique no ícone de **lixeira** da linha e salve.

---

## Resumo rápido

1. **Admin → Organization → Structure** → criar unidade com **CNPJ**
2. **PIM → funcionário → aba Job** → vincular à empresa
3. **Time → Attendance → Locais de Ponto (Geofence)** → escolher empresa,
   adicionar locais, ativar validação, salvar
4. Testar pelo PWA no celular
