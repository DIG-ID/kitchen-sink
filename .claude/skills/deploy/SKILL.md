---
name: deploy
description: Publica um tema WordPress em produção (deploy por push à branch de produção, via WP Pusher ou CI). Reconhece primeiro as ferramentas do projecto (build, lint, Node, branch, output, onde vive a versão) e adapta-se a projectos antigos e recentes. Faz versionamento SemVer, cria/sincroniza CHANGELOG.md e style.css em todos os deploys, compila assets de produção, prepara um commit curto e só faz push com confirmação explícita. Usar sempre que o utilizador quiser publicar, fazer deploy, ir para produção, "go live", subir o site, lançar uma versão ou fazer push para main/master num projecto de tema WordPress — mesmo que não diga "deploy".
---

# Deploy para produção

> Este skill é para **repos de projecto**. No repo do starter não há produção —
> usar o `/release` (tag → Action → zip).

Nestes repos o deploy é feito por **push à branch de produção** (WP Pusher ou CI).
O `git push` **é** o deploy: assim que acontece, o site actualiza. Por isso o
push nunca acontece sem luz verde explícita do utilizador.

Os projectos variam muito: os recentes usam o starter (Vite, `npm run prod`,
`php:lint`), os de transição o starter antigo (Laravel Mix), e os antigos podem
usar Gulp, Grunt, webpack, sass CLI ou nenhuma ferramenta. **Nunca assumir** — detectar primeiro, e só correr o que existe.

## Processo

### 1. Reconhecimento do projecto

Antes de tocar em qualquer coisa, levantar o perfil do projecto e mostrá-lo ao
utilizador num resumo curto. Isto evita builds que falham a meio, versões no
sítio errado e pushes sem assets.

- **Branch de produção**: `git symbolic-ref refs/remotes/origin/HEAD` (ou
  `git remote show origin`). Pode ser `main` ou `master`. Se houver dúvida (ex.:
  WP Pusher configurado para outra branch), perguntar.
- **Tema**: localizar o `style.css` com o header `Theme Name:`. Pode estar na raiz,
  numa subpasta (repo de `wp-content` inteiro) ou ser um child theme.
- **Origem do header**: se existir um `.scss` com o mesmo header (`/*! Theme Name…`),
  o `style.css` é **gerado** — a versão tem de ser editada no ficheiro-fonte.
- **Gestor de pacotes**: pelo lockfile (`package-lock.json` → npm,
  `yarn.lock` → yarn, `pnpm-lock.yaml` → pnpm). Sem `package.json` = sem build JS.
- **Script de build**: ler `scripts` do `package.json`. Ordem de preferência:
  `prod` → `production` → `build` → `dist`. Se só houver Gulp/Grunt sem script,
  ver o `gulpfile`/`Gruntfile` para a task de produção. Se nada for claro, perguntar.
- **Node**: verificar `.nvmrc` e `engines` no `package.json` contra `node -v`.
  Projectos com `node-sass` costumam falhar em Node recente — avisar e sugerir
  `nvm use` antes do build.
- **Output do build**: `dist/`, `build/`, `assets/`, `css/`… e o manifest, se
  houver (`mix-manifest.json`, `manifest.json` do Vite, nenhum).
- **Output ignorado?** Se a pasta de output estiver no `.gitignore`, o push vai
  para produção **sem** os assets compilados. Parar e discutir com o utilizador
  (o WP Pusher não corre builds).
- **Lint**: `php:lint`/`php:fix` no `package.json`, ou `phpcs`/`phpcbf` via
  `composer.json`. Se não existir nada, o mínimo é `php -l` nos `.php` alterados.
- **Onde vive a versão**: `style.css` (ou o `.scss` de origem) e, se existirem,
  constantes no `functions.php` (`_S_VERSION`, `THEME_VERSION`, `define(...VERSION`),
  `"version"` no `package.json`, `Stable tag` no `readme.txt`. Todos estes vão
  ser sincronizados.
- **Cache-busting**: ver como o tema versiona os assets (helper
  `<prefixo>_asset_version()`, `filemtime()`, hashes do manifest, ou
  `wp_get_theme()->get('Version')`). Se depender da versão do tema, não subir
  a versão significa que os browsers continuam a servir CSS/JS antigos — dizer
  isto ao utilizador no passo 4.

Se o projecto tiver um `.deploy.json` na raiz (ver fim do documento), usá-lo
como ponto de partida e só confirmar que continua válido.

### 2. Pré-check do repositório

- Confirmar que a branch actual é a de produção detectada.
- `git fetch` e comparar com o remoto (`git rev-list --count HEAD..origin/<branch>`).
  Se houver commits novos no remoto (um colega fez push), parar e avisar —
  integrar com `git pull` antes de continuar. Nunca construir sobre uma branch
  desactualizada nem sobrepor trabalho alheio.
- `git status`: se houver alterações **não relacionadas** misturadas, alertar e
  sugerir separá-las.
- Lembrar: **push à branch de produção = site live imediato**.

### 3. Lint

- Correr o lint detectado. Se falhar e houver auto-fix, corrê-lo, mas **mostrar o
  diff** antes de o incluir: em projectos antigos o auto-fix pode reformatar
  dezenas de ficheiros não relacionados e poluir o commit de deploy. Nesse caso,
  propor fazer o fix num commit separado ou aplicá-lo só aos ficheiros alterados.
- Reportar o que não for auto-corrigível e parar até estar limpo — a não ser que
  o utilizador decida avançar conscientemente (projectos antigos têm muitas vezes
  erros de lint históricos; distinguir os novos dos antigos).

### 4. Versionamento (SemVer)

**Sem `CHANGELOG.md`** → **perguntar sempre** que versão usar. Apresentar:
- a versão actual do `style.css`, normalizada para SemVer (`2.3` → `2.3.0`,
  `1` → `1.0.0`) — típico num projecto antigo já live;
- `1.0.0` — típico na primeira ida a produção de um projecto novo;
- indicar qual parece mais adequada e porquê (tags existentes, idade do repo,
  site já em produção?), mas deixar o utilizador decidir.

**Com `CHANGELOG.md`** → propor bump com base no diff desde a última versão:
- só correcções/ajustes → **PATCH**
- funcionalidade nova → **MINOR**
- breaking change (templates removidos, requisitos novos de PHP/WP, mudanças
  que exigem acção no admin) → **MAJOR**

Apresentar sempre também a opção **manter a versão actual** (acrescentando a
entrada à secção já existente). Recomendação por omissão:
- projecto ainda em fase local/staging, sem lançamento público → manter;
- projecto já em produção → **bump** (sobretudo se o cache-busting depender
  da versão do tema).

Confirmar com o utilizador antes de avançar.

### 5. CHANGELOG.md + sincronização da versão (em todos os deploys)

Fazer isto **antes** do build — se o `style.css` for gerado a partir de SASS,
editar só o compilado perde-se no build seguinte.

- Se o `CHANGELOG.md` não existir, criá-lo no formato Keep a Changelog:
  ```markdown
  # Changelog

  All notable changes to this theme are documented in this file.
  The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
  and this project adheres to [Semantic Versioning](https://semver.org/).

  ## [X.Y.Z] — YYYY-MM-DD
  ```
  Num projecto antigo, a primeira secção é a baseline: descrever as alterações
  deste deploy e, opcionalmente, uma linha a indicar que o changelog começou
  aqui (o histórico anterior está no git).
- Adicionar/completar a secção `## [X.Y.Z] — YYYY-MM-DD` com as categorias certas
  (Added/Changed/Fixed/Removed/Deprecated/Security), em inglês, orientada ao que
  mudou para quem usa o site.
- **Sincronizar sempre**, mesmo quando a versão se mantém: verificar que o
  `Version:` do `style.css` (e do `.scss` de origem), as constantes do
  `functions.php`, o `package.json` e o `readme.txt` batem certo com a versão mais
  recente do `CHANGELOG.md`. Se algum estiver dessincronizado (comum em projectos
  antigos), corrigir e mencionar no resumo final.

### 6. Build de produção (passo-chave, não saltar)

- Correr o script de build detectado. Nunca deixar assets de desenvolvimento irem
  para produção.
- Confirmar que o output foi regenerado (datas de modificação, manifest/hashes,
  ficheiros minificados) e que o header do `style.css` compilado tem a versão nova
  (o `/*!` tem de sobreviver à minificação).
- **Starter Vite**: o build apaga os ficheiros com hash antigos — incluir essas
  remoções no commit (`git add -A dist`); confirmar que `dist/.vite/manifest.json`
  foi regenerado e que não existe `dist/hot` (marcador do `npm run dev`). Nunca
  fazer deploy de um `dist/` deixado pelo `npm run watch` (build de desenvolvimento).
- Se o build falhar, parar e reportar — com o diagnóstico mais provável (versão
  de Node, dependências em falta → `npm ci`).
- **Projecto sem build**: saltar este passo e dizê-lo explicitamente no resumo.

### 7. Commit (mensagem curta)

- Formato do assunto: `vX.Y.Z: <resumo curto>` — inglês, uma linha, orientado
  ao utilizador.
- **Nunca** adicionar `Co-Authored-By: Claude …`. Em vez disso, terminar com um
  trailer a indicar a ferramenta e o modelo:
  ```
  v2.4.0: Add booking widget to room pages

  Assisted-by: Claude Code (<id do modelo>)
  ```
  Usar a ferramenta e o modelo que a sessão actual reporta — nunca inventar nem
  copiar de um commit antigo. Se não for possível saber o modelo, escrever só a
  ferramenta.
- Incluir os assets recompilados, o `CHANGELOG.md` e todos os ficheiros de
  versão sincronizados.
- **Nunca fazer push neste passo.**

### 8. Push = deploy (só com confirmação explícita)

Antes de pedir luz verde, mostrar um resumo:
- versão (antes → depois) e ficheiros sincronizados;
- build corrido (ou "projecto sem build") e assets incluídos no commit;
- branch e remoto de destino;
- lembrete para confirmar que conteúdo e funcionalidades (loja, traduções,
  formulários, plugins específicos do projecto…) estão prontos para o público.

Com confirmação explícita: `git push origin <branch>`.

Depois, indicar como **verificar o deploy**:
- abrir o site em produção e fazer hard-refresh (Ctrl/Cmd+Shift+R);
- confirmar que os CSS/JS novos carregam (ver o `?ver=` ou os hashes no source);
- limpar a cache do site se houver plugin de cache (WP Rocket, SG Optimizer…);
- ver o log do plugin de deploy (WP Pusher → Deploy log) ou o estado do CI.

### 9. Tag (opcional)

Como o deploy é por branch, a tag é só um marcador histórico e não dispara
deploy. Se o utilizador quiser: `git tag vX.Y.Z` e, com confirmação,
`git push origin vX.Y.Z`.

## Perfil guardado (opcional)

No fim do primeiro deploy num projecto, oferecer guardar o perfil detectado num
`.deploy.json` na raiz, para os próximos deploys serem mais rápidos:

```json
{
  "branch": "master",
  "theme_dir": "wp-content/themes/cliente",
  "version_source": "src/scss/style.scss",
  "version_files": ["style.css", "functions.php", "package.json"],
  "build": "npm run production",
  "node": "14",
  "lint": "php -l",
  "output": "assets/dist"
}
```

Só criar com o acordo do utilizador, e incluir no commit de deploy.

## Regras

- **Detectar antes de agir**: nunca assumir scripts, branch, pasta de output ou
  localização da versão.
- **Sempre** `git fetch` e verificar o remoto antes de começar.
- **Nunca** `git push` (nem push de tags) sem confirmação explícita.
- **Sempre** compilar em modo produção antes do commit, quando o projecto tem build.
- **Versão sobe antes do build**, no ficheiro-fonte.
- `CHANGELOG.md` existe e está sincronizado com o `style.css` (e restantes ficheiros
  de versão) em **todos** os deploys.
- Sem `CHANGELOG.md`: perguntar sempre a versão inicial.
- Commit curto, sem `Co-Authored-By`; trailer `Assisted-by: <ferramenta> (<modelo>)`.
- Alterações não relacionadas ou auto-fix extenso: alertar e sugerir separar.

## Diferenças face ao `/release`

| | `/release` | `/deploy` |
|---|---|---|
| Compila assets | não | **sim (script detectado)** |
| Deploy | tag → Action → zip | **push à branch de produção** |
| Commit | resumo detalhado | resumo curto + trailer |
| Pós-passo | Action verde + zip | site live + deploy log |
