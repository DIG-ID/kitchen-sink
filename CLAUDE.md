# CLAUDE.md — Kitchen Sink

## Language
- Responder sempre em **português de Portugal** no chat.
- Comentários no código devem ser em **inglês (en-EN)**.

---

## Stack

- **WordPress** custom theme (`text-domain: kitchensink`)
- **WooCommerce**
- **Tailwind CSS v3** + **Vite** (via PostCSS)
- **PHP 8.0+**
- **SASS** (estrutura modular em `assets/sass/`)
- **JS** em `assets/js/`
- **Dist** compilado em `dist/`

### Dependências JS notáveis
- GSAP, Lenis (smooth scroll), Swiper, Isotope, Fancyapps UI, imagesloaded

---

## Estrutura de Ficheiros

```
kitchen-sink/
├── assets/
│   ├── js/
│   └── sass/
│       ├── _vars.sass
│       ├── _base-styles.sass
│       ├── _components/
│       ├── _modules/
│       ├── _pages/
│       ├── _posts/
│       └── main.sass
├── dist/                   # compiled assets (não editar directamente)
├── inc/                    # PHP includes do tema (vite.php = loader de assets)
├── page-templates/         # Page templates WordPress
├── template-parts/
│   ├── pages/
│   │   └── home/
│   ├── footer-main.php
│   ├── header-main.php
│   └── design-system.php
├── functions.php
├── vite.config.mjs
├── tailwind.config.js
├── phpcs.xml.dist
└── style.css
```

---

## Comandos de Desenvolvimento

```bash
# Servidor de desenvolvimento Vite (HMR; NÃO escreve dist/)
npm run dev

# Watch: build de desenvolvimento para dist/ a cada alteração (sem HMR/reload)
npm run watch

# Build de produção (escreve dist/ — correr antes de commit/deploy)
npm run prod

# Lint PHP (PHPCS)
npm run php:lint

# Auto-fix PHP (PHPCBF)
npm run php:fix
```

---

## Build (Vite)

- **Config**: `vite.config.mjs`. Os entry points estão na lista `entries` (JS e Sass); um bundle novo (ex.: CSS de uma página específica) = acrescentar aí.
- **Enqueue**: sempre via `inc/vite.php`, referindo o entry pelo **caminho fonte** — nunca caminhos de `dist/` hardcoded (os ficheiros têm hash no nome):
  ```php
  kitchensink_vite_enqueue_style( 'theme-styles', 'assets/sass/main.sass' );
  kitchensink_vite_enqueue_script( 'theme-scripts', 'assets/js/main.js', array( 'jquery' ) );
  ```
  Em produção lê `dist/.vite/manifest.json`; em dev (`npm run dev`, ambiente `local`/`development`) carrega do servidor Vite com HMR. Os scripts são ES modules (`type="module"`).
- **Dev server**: HTTPS com os certificados do Local, no hostname `DEV_HOST` (fallback em `vite.config.mjs`, override por máquina em `.env.local`, gitignored). Alterações a `.php` fazem full reload. O servidor escreve `dist/hot` enquanto corre; se ficar órfão, o PHP ignora-o (testa a porta).
- **jQuery**: usar o jQuery do WordPress (`window.jQuery`, dependência `'jquery'` no enqueue) — não há `jquery` no npm nem autoload.
- **`url()` no Sass**: relativo ao ficheiro `.sass` fonte (ex.: `assets/fonts/...`); o Vite copia para `dist/assets/` com hash.
- **Sass**: os partials ainda usam `@import` (aviso de deprecação silenciado em `vite.config.mjs`). Código novo pode usar módulos `sass:*` (`@use "sass:color"`) em vez de funções globais (`darken()`, etc.).
- **Antes de commit/deploy**: `npm run prod` — o `npm run dev` não escreve `dist/` e o `npm run watch` deixa lá uma build de desenvolvimento (não minificada, com source maps).
- **Deploy em produção**: usar o skill `/deploy` (`.claude/skills/deploy/`). Verificações específicas deste build:
  - o build apaga os ficheiros com hash antigos — incluir também essas remoções no commit (`git add -A dist`);
  - confirmar que `dist/.vite/manifest.json` foi regenerado e que não existe `dist/hot` (marcador do servidor de dev, gitignored);
  - verificação pós-deploy: os assets carregam com nomes com hash novos (cache-busting via `inc/vite.php` + manifest).

---

## Coding Standards

Todo o código PHP **deve** seguir:
- [PHP Coding Standards (PHPCS)](https://github.com/PHPCSStandards/PHP_CodeSniffer/)
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)

Regras activas (ver `phpcs.xml.dist`): `WordPress-Core`, `WordPress-Docs`, `WordPress-Extra`.

Antes de entregar código PHP, verificar com `npm run php:lint`. Usar `npm run php:fix` para correcções automáticas.

---

## Custom Post Types

> **A preencher**: documentar aqui os CPTs reais do projecto (slug + descrição) à medida que forem criados.

| Slug | Descrição |
|------|-----------|
| —    | —         |

---

## ACF Field Groups

> **A preencher**: documentar aqui os field groups reais (CPTs e page templates), com nome do campo, tipo e formato de retorno (ex.: image → ID, link → array).

---

## SEO / Schema

- Plugin: **Yoast SEO** (`wordpress-seo`)
- Schema customizado via **Yoast Schema API** — usar filtros `wpseo_schema_graph_pieces` e `wpseo_schema_*`
- Não criar schema manual com `wp_head` se o Yoast já o suportar.

> **A preencher**: mapear contextos → tipo de schema → campos principais (ex.: CPT de eventos → `schema.org/Event`; página FAQ → `schema.org/FAQPage`).

---

## Plugins

> Atenção: alguns plugins são **apenas locais** (debug, migração, deploy) e não estão em produção.

### Stack standard (presente em ~100% dos sites live — assumir ao desenvolver)
- `advanced-custom-fields-pro` + `acfml` — campos ACF (multilingual)
- WPML: `sitepress-multilingual-cms` + `wpml-string-translation` + `wp-seo-multilingual` + `wpml-mailchimp-for-wp` — multilíngue
- `wordpress-seo` (Yoast) + `acf-content-analysis-for-yoast-seo` — SEO/Schema
- `woocommerce` + `woocommerce-multilingual` — loja
- `contact-form-7` + `contact-form-7-multilingual` — formulários
- `mailchimp-for-wp` — newsletter
- `wp-rocket` — cache, minify, lazyload (**normalmente só activo em live**)
- `sg-security` — hardening (o `inc/security.php` do tema cobre apenas o que este não cobre)
- `safe-svg` — sanitização de uploads SVG
- `simple-maintenance-mode` — plugin interno dig.id ([repo](https://github.com/DIG-ID/simple-maintenance-mode)): modo de manutenção com toggle no admin bar, HTTP 503 + `Retry-After`, bypass para admins. O template é personalizável por tema via override em `themes/<tema>/simple-maintenance-mode/` — a lógica fica sempre no plugin, nunca no tema

Ferramentas dev/deploy (sem impacto no código do tema): `wp-migrate-db`, `wppusher`, `regenerate-thumbnails`.

### Regras
- Não usar plugins adicionais — preferir código no tema ou plugins já instalados.
- **Não duplicar funcionalidade do stack**: lazyload/minify/cache é do WP Rocket; hardening base é do SG Security; schema é via Yoast API; sanitização de SVG é do safe-svg.
- Código sempre compatível com WPML: strings traduzíveis, `wpml_object_id` para IDs hardcoded, campos ACF traduzíveis via acfml.
- Plugins fora do stack standard (ex.: `age-gate`, `woocommerce-gateway-stripe`, `flexible-coupons-pro`, `woo-variation-swatches`) são **específicos de cada projecto** — documentar no CLAUDE.md do projecto.

---

## Documentação de Referência

Ao desenvolver, seguir sempre a documentação oficial como fonte de autoridade para boas práticas, exemplos e dúvidas. Em caso de conflito entre abordagens, a documentação oficial prevalece. Para APIs recentes ou casos duvidosos, **consultar a documentação online em vez de assumir**.

- **Theme Handbook** — https://developer.wordpress.org/themes/ (estrutura, template hierarchy, boas práticas de temas)
- **WordPress Developer Resources** — https://developer.wordpress.org/ (code reference de funções/hooks/classes, APIs)
- **Coding Standards** — https://developer.wordpress.org/coding-standards/ (PHP, HTML, CSS, JS, acessibilidade)
- **ACF** — https://www.advancedcustomfields.com/resources/
- **WooCommerce** — https://developer.woocommerce.com/docs/
- **WPML** — https://wpml.org/documentation/ (hooks e compatibilidade multilíngue)
- **Yoast Schema API** — https://developer.yoast.com/features/schema/

### Front-end (atenção às versões instaladas — ver `package.json`)
- **Tailwind CSS v3** — https://v3.tailwindcss.com/docs (o starter usa `^3.4` via PostCSS no Vite; **não** usar docs/sintaxe da v4 nem o plugin `@tailwindcss/vite`)
- **GSAP v3** — https://gsap.com/docs/v3/
- **Lenis** — https://github.com/darkroomengineering/lenis/blob/main/README.md
- **Swiper** — https://swiperjs.com/swiper-api (o starter usa `^12`; não usar docs de versões antigas)
- **Fancyapps UI (Fancybox v5)** — https://fancyapps.com/fancybox/
- **Vite** — https://vite.dev/guide/ (o starter usa `^8`, com Rolldown; não usar docs de versões antigas — ex.: `build.rolldownOptions` em vez de `build.rollupOptions`)

> Regra: antes de usar exemplos da documentação front-end, confirmar a versão instalada no `package.json` — APIs de Tailwind/Swiper mudam entre major versions.

---

## PHPDoc — Documentação de Código (obrigatório)

Todos os ficheiros PHP **devem** começar com um file docblock com `@package`, `@subpackage` e `@since`:

```php
<?php
/**
 * Short description of what this file does.
 *
 * @package kitchensink
 * @subpackage Template
 * @since 1.0.0
 */
```

### Taxonomia de `@subpackage`
| `@subpackage` | Usar em |
|---|---|
| `Core` | `functions.php`, `header.php`, `footer.php`, `index.php` |
| `Template` | Root templates (`single.php`, `page.php`, `archive.php`, `404.php`, `search.php`) e `page-templates/*` |
| `Section` | `template-parts/` — secções de página (hero, header-main, footer-main, etc.) |
| `Component` | `template-parts/` — partes pequenas e reutilizáveis (cards, botões, breadcrumbs) |
| `Module` | Funcionalidades front-end compostas (sliders, accordions, galerias) |
| `Functionality` | `inc/` — features do tema (security, performance, enqueue, admin, setup) |
| `Helpers` | `inc/helpers.php`, `inc/theme-template-tags.php` |

### Regras
- `@since` = versão do tema em que o ficheiro/função foi introduzido (sincronizado com o `CHANGELOG.md`).
- Todas as funções têm docblock com descrição, `@param` e `@return` (standard `WordPress-Docs` do PHPCS).
- Docblocks e comentários sempre em **inglês (en-EN)**.
- Ao criar ficheiros novos, nunca usar comentários de linha (`// ...`) como cabeçalho — sempre docblock completo.

---

## Semântica HTML5 & Boas Práticas (obrigatório)

- Usar sempre os elementos semânticos correctos: `<header>`, `<nav>`, `<main>` (um único por página), `<section>`, `<article>`, `<aside>`, `<footer>`, `<figure>`/`<figcaption>`, `<time datetime="">`.
- **Um único `<h1>` por página**; hierarquia de headings sem saltos (`h1` → `h2` → `h3`).
- `<section>` só quando tem heading próprio; caso contrário usar `<div>`.
- Acessibilidade: skip-link para `#main-content`, `aria-label` em `<nav>` e botões icon-only, `alt` sempre definido nas imagens (vazio se decorativa), estados de foco visíveis.
- Imagens: usar `wp_get_attachment_image()` (gera `srcset`/`sizes`); `loading="lazy"` por omissão, `loading="eager"` + `fetchpriority="high"` apenas no hero/LCP.
- Schema/structured data: **sempre via Yoast Schema API (JSON-LD)** — nunca microdata (`itemscope`/`itemtype`) inline no HTML.
- Escaping sempre no output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`. Inputs com sanitização e nonces quando aplicável.
- Strings sempre traduzíveis (`__()`, `esc_html__()`, etc.) com text domain `kitchensink`, e em **inglês** por omissão no starter.
- Estas regras aplicam-se a todo o código novo e a qualquer ficheiro tocado (boy-scout rule: ao editar, corrigir a semântica/documentação desse ficheiro).

---

## i18n / Traduções

- Todas as strings do tema são traduzíveis (`__()`, `esc_html__()`, etc.) com text domain `kitchensink`, em **inglês** por omissão.
- **Gerar o `.pot` com WP-CLI** (disponível no shell do Local by WPEngine), idealmente antes do lançamento:
  ```bash
  wp i18n make-pot . languages/kitchensink.pot --domain=kitchensink
  ```
- Com WPML no stack, o `.pot` não é obrigatório (o String Translation faz scan do tema), mas é boa prática: o WPML importa-o e o tema funciona com `.po`/`.mo` nativos em sites sem WPML.
- Não usar Loco Translate ou plugins equivalentes para isto (regra: sem plugins extra).
- `wpml-config.xml` na raiz do tema (quando existir): define defaults de tradução para CPTs, taxonomias e custom fields (traduzir vs. copiar) — ajustar por projecto conforme os campos ACF.

---

## Versionamento & Changelog

O tema segue **Semantic Versioning** (`MAJOR.MINOR.PATCH`) e mantém um `CHANGELOG.md` no formato [Keep a Changelog](https://keepachangelog.com/).

> **Starter vs. projecto:** o versioning e o changelog deste repositório pertencem ao **starter theme**. Quando o starter é usado para arrancar um projecto novo (via `SETUP.md`), o projecto **recomeça em `1.0.0`** com changelog limpo — a primeira entrada regista a versão do starter de origem (ver passo 9 do `SETUP.md`). A partir daí, o versioning do projecto evolui de forma independente com estas mesmas regras.

### Quando incrementar
| Tipo de alteração | Exemplo | Incremento |
|---|---|---|
| Correcção de bug / ajuste pequeno | fix num template, correcção CSS | **PATCH** — `1.0.0` → `1.0.1` |
| Nova funcionalidade / implementação | novo template, novo CPT, nova secção | **MINOR** — `1.0.1` → `1.1.0` |
| Mudança estrutural / incompatível | redesign, remoção de funcionalidade | **MAJOR** — `1.1.0` → `2.0.0` |

### Regras (obrigatório em cada alteração de código)
1. **No fim de cada tarefa concluída**, sugerir sempre a actualização do `CHANGELOG.md` e do `Version:` no `style.css` para reflectir a alteração (propor o novo número segundo a tabela acima).
2. Adicionar a entrada no `CHANGELOG.md` numa secção `## [X.Y.Z] — YYYY-MM-DD`, sob a categoria adequada: `Added`, `Changed`, `Fixed`, `Removed`, `Deprecated` ou `Security`.
3. O número de versão em `style.css` e a última versão do `CHANGELOG.md` devem estar sempre sincronizados.
4. Entradas do changelog **sempre em inglês (en-EN)**, curtas e orientadas ao que mudou para quem usa o tema (não detalhes internos de implementação).
5. Sempre que a versão muda (changelog + `style.css` actualizados), sugerir também uma **mensagem de commit para o push no GitHub** — em inglês, curta, no formato `vX.Y.Z: summary of changes` — para que changelog, versão do tema e histórico git fiquem em sintonia.
6. Para publicar uma **release no GitHub** (com o zip do tema anexado automaticamente via `.github/workflows/release.yml`), criar e enviar a tag da versão: `git tag vX.Y.Z && git push origin vX.Y.Z`. Fazer isto pelo menos nas versões que a equipa vai usar para arrancar projectos novos.

---

## Convenções Gerais

- Não usar plugins desnecessários — preferir código no tema.
- Não instalar dependências npm ou composer sem confirmação explícita.
- Ao adicionar template parts, seguir a estrutura existente em `template-parts/`.
- Assets CSS/JS são compilados via `npm run prod` (ver secção Build) — nunca editar `dist/` directamente.
- Text domain do tema: `kitchensink`.
