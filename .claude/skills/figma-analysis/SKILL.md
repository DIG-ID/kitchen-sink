---
name: figma-analysis
description: Analisa um projecto Figma e gera o figma-architecture-analysis.txt com o mapeamento para a arquitectura do tema (elementos, sections, components, modules, templates WP, campos ACF, mapa por página). Usar no arranque de um projecto novo, depois do SETUP.md, ou quando o design é actualizado.
---

# Figma → Arquitectura do Tema

Gera um `figma-architecture-analysis.txt` na raiz do **tema do projecto** (nunca
na raiz do starter) a partir de um ficheiro Figma, mapeando o design para a
arquitectura deste starter (taxonomia Section / Component / Module / Template /
Helpers do `CLAUDE.md`).

Método: **elementos → secções → páginas**. Parte-se dos nós concretos (texto,
imagens, botões), agrupam-se em blocos, e as páginas são a lista ordenada dos
blocos. As secções ganham nome a partir do conteúdo que contêm, não de palpites.

## Inputs (perguntar se não fornecidos)

1. **Link do ficheiro Figma** (URL `figma.com/design/...`)
2. **Nome da página do Figma** a analisar — os ficheiros de cliente têm
   normalmente páginas Lofi/Hifi/Design System; analisar a mais recente/final.
   Se existir uma cópia "DEVS", preferir essa.

## Processo

### 0. Obter a árvore

`get_metadata` sem nodeId → lista as páginas do documento. Depois `get_metadata`
do node da página-alvo. O output é quase sempre grande demais para o contexto e
o MCP grava-o num ficheiro de tool-results — **é esse o caminho a passar ao
script**, não tentar ler o dump todo.

Todos os passos seguintes correm sobre esse ficheiro, sem mais chamadas ao MCP:

```bash
S=.claude/skills/figma-analysis/scripts/figma_tree.py
python3 $S <dump> probe
```

### 1. Sondar antes de confiar

`probe` responde a duas perguntas que decidem o resto do trabalho:

- **Os nomes das camadas de texto trazem o copy real?** Acima de ~60% dá para
  nomear as secções por parsing. Abaixo disso os nomes são genéricos
  (`Text 1`, `Frame 42`) e **não se pode nomear nada automaticamente** — passa-se
  ao caminho por screenshots e avisa-se o utilizador do custo.
- **As páginas estão agrupadas em secções?** As agrupadas têm as fronteiras
  definidas pelo designer e saem de graça. As flat precisam de segmentação
  (passo 5) e de confirmação visual.

Reportar o resultado das sondas ao utilizador antes de continuar.

### 2. Inventário de páginas

`pages` lista os ecrãs reais (filtra artwork solto da canvas). Separar por
breakpoint: normalmente só as **desktop** contam como páginas distintas —
mobile e tablet são o mesmo conteúdo noutro layout e entram como variantes
responsivas das mesmas secções, não como páginas novas. Confirmar com o
utilizador se o padrão de nomes não for claro.

### 3. Secções — catálogo por repetição

`clusters` agrupa os blocos por **geometria**, não por nome: o mesmo bloco
costuma estar duplicado sob ids diferentes, e contar por nome subestima muito a
reutilização. Para cada cluster mostra o copy que contém — é daí que sai o nome
da secção (`intro-panel`, `usp-band`, `cta-form`…).

Classificar cada cluster:
- presente em ~todas as páginas e no topo/fundo → **global** (header/footer)
- presente em 3+ páginas → **section partilhada**
- presente numa página só → **section específica dessa página**

Peças pequenas repetidas dentro dos blocos (cards, botões, títulos de secção)
são **components**: recebem dados via `get_template_part( ..., null, $args )` e
nunca chamam `get_field()`.

### 4. Páginas — mapa ordenado

`map <page-id>` (ou `all`) dá os blocos de cada página por ordem vertical, já com
o copy de cada um. Cruzar com o catálogo do passo 3 para produzir a linha
`página → secções ordenadas + variante`, que é o contrato que as skills de
scaffold vão consumir.

### 5. Páginas flat

`segment <page-id>` propõe fronteiras nas páginas sem agrupamento, por ordem de
fiabilidade: fundo full-bleed (dá início **e** fim), linha divisória, título
grande, overline. **Confirmar sempre com `get_screenshot`** — os metadados não
trazem tamanho de fonte, portanto título e parágrafo alto são indistinguíveis
sem heurística.

### 6. Modules

`signals` procura afordâncias de interacção (filtros, paginação, acordeões,
setas, submits) e páginas duplicadas — que normalmente são estados de interacção
desenhados, não ecrãs distintos.

As keywords por omissão são DE/EN. **Num projecto noutra língua passar um ficheiro
JSON próprio** (`signals <kw.json>`), senão não apanha nada. Keyword matching dá
falsos positivos (texto legal, nomes de produto) — verificar cada um.

Classificar como **Module** o que tem comportamento JS próprio (sliders → Swiper,
overlays/animações → GSAP, filtros → Isotope, lightbox → Fancybox, mapas). Um
bloco cujo JS pertence a um plugin (formulários CF7) é **Section**, não Module.

Confirmar que as libs do bundle são mesmo usadas e **sinalizar as que não são**.

### 7. Tokens

`get_variable_defs` num frame concreto (não funciona ao nível da página) para
cores e tipografia exactas → `_vars.sass` e `tailwind.config.js`. Se o design não
usar variáveis Figma, registar isso e extrair as cores dos próprios nós.

## Output — `figma-architecture-analysis.txt` na raiz do tema do projecto

1. **Cabeçalho** — projecto, ficheiro/página Figma, data, resultado das sondas
   (quantas páginas agrupadas vs. flat, se a nomeação automática é fiável).
2. **Visão geral** — tipo de site, línguas (WPML?), nº de templates, áreas.
3. **Globais** — header, footer, menu overlay → `template-parts/` (Section).
4. **Secções** — com nº de páginas em que aparecem e variantes.
5. **Componentes** — com os `$args` sugeridos para cada um.
6. **Modules** — com a lib associada e a evidência que os classificou assim.
7. **Mapeamento WordPress** — front-page, page-templates, CPTs (+archive/single),
   404, legais. Se muitas páginas partilharem secções, sinalizar que templates
   fixos por página são inviáveis e propor secções partilhadas / flexible content.
8. **Mapa página → secções** — a tabela ordenada do passo 4, para todas as
   páginas. É isto que as skills de scaffold consomem.
9. **Sugestão de custom fields** — por secção e por template/CPT.
10. **Template tags** — helpers funcionais (datas `<time>`, ícones, links ACF).
11. **Decisões em aberto** — o que precisa de resposta do designer/cliente.

## Regras

- **Perguntar antes de assumir comportamento.** Os metadados dão geometria e
  texto, não interacção. Se há dúvida se um bloco é slider ou estático, se um
  filtro é client-side ou AJAX, se há lightbox — perguntar, em bloco e com a
  evidência de cada lado. Nunca classificar por palpite.
- Uma dúvida de **implementação** não bloqueia a **arquitectura**: um filtro é
  Module quer seja Isotope quer seja AJAX. Registar em aberto e seguir.
- Não inventar conteúdo: registar só o que está no design.
- Nada de valores deste ou daquele projecto no código da skill — grelhas, ids de
  grupo e keywords de idioma são sempre do projecto em análise.
- Respeitar o stack standard (`CLAUDE.md`): schema via Yoast, formulários CF7,
  newsletter Mailchimp, multilingue WPML.
- O `.txt` é um documento de trabalho **do projecto**. Quando o design muda,
  actualizar (secções novas → acrescentar, não reescrever de raiz).
