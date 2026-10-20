---
name: setup-project
description: Executa o SETUP.md interactivamente para arrancar um projecto novo a partir do starter - pergunta nome/slug/prefixo/URL, faz o find & replace, actualiza style.css e vite.config.mjs, faz reset ao versionamento e prepara o primeiro build. Usar uma vez, logo depois de extrair o zip do tema.
---

# Setup de projecto novo (SETUP.md automatizado)

Automatiza os passos 1–9 do `SETUP.md`. Assume que o zip do starter já foi
extraído para `wp-content/themes/` e que estamos dentro dessa pasta.

## Inputs (perguntar ao utilizador de uma vez, no início)

1. **Nome do projecto** (human-readable, ex.: "Weizenkorn")
2. **Slug da pasta/tema** (ex.: `weizenkorn`) — a pasta deve já ter este nome;
   se ainda se chamar `theme-starter-vite`, avisar para renomear primeiro
   (renomear a pasta com o Claude Code lá dentro pode partir a sessão).
3. **Prefixo/text domain** (curto, lowercase, underscores, ex.: `weizenkorn`)
4. **URL de dev local** (ex.: `https://weizenkorn.digid/`)
5. **Cores primária/secundária e fontes** (opcional — pode ficar para depois)

## Processo (por ordem, validando cada passo)

1. **Find & replace no projecto inteiro** (case-sensitive, ordem importa):
   - `theme-starter-vite` → slug
   - `Theme Starter Vite` → nome do projecto
   - `digid` → prefixo (ATENÇÃO: não substituir `dig.id` nem os hostnames de
     dev do Local terminados em `.digid` — ex.: `theme-starter-vite.digid`,
     `my-site.digid` no `vite.config.mjs`; se forem trocados, o servidor Vite
     deixa de encontrar o site e os certificados do Local. Rever os matches
     antes de aplicar — Author/Author URI mantêm dig.id)
   - Renomear também o output do script `zip` no `package.json`.
2. **style.css**: Theme Name, Description, `Version: 1.0.0`, Text Domain.
3. **vite.config.mjs**: confirmar o fallback de `DEV_HOST` — o find & replace
   do passo 1 já o deixa como `<slug>.digid`. Só ajustar se o hostname do Local
   for diferente do slug (sem protocolo nem `/` final, ex.: `weizenkorn.digid`).
4. **_vars.sass + tailwind.config.js**: cores e fontes, se fornecidas.
5. **functions.php**: rever constantes (font provider, Google Maps key vazia).
6. **inc/theme-setup.php**: rever nav menus com o utilizador.
7. **Reset de versionamento** (passo 9 do SETUP.md): `CHANGELOG.md` limpo com
   entrada única `[1.0.0]` referindo a versão do starter de origem (ler a
   última versão do CHANGELOG antes de o limpar); apagar `TODO.md`;
   `package.json` name/version; limpar `.gitattributes` removendo as regras
   `export-ignore` (`dist/`, `TODO.md`, `.github/`) — só fazem sentido no zip
   de release do starter; num projecto, ferramentas de deploy que usam
   `git archive` (ex.: WP Pusher) respeitam-nas e podem excluir o `dist/`
   compilado sem erro visível. Manter só `* text=auto eol=lf`.
8. **CLAUDE.md do projecto**: limpar exemplos do starter que não se aplicam
   (CPTs/field groups de exemplo) e deixar nota para preencher com os reais.
9. **Instalar e buildar**: antes de instalar, verificar `node -v` contra o
   `engines` do `package.json` (o Vite 8 exige `^20.19.0 || >=22.12.0`); se não
   cumprir, parar e pedir para actualizar o Node (ou `nvm use`). Depois
   `npm install` e `npm run prod` (escreve `dist/`).
   Reportar erros. Lembrar que `npm run dev` é o servidor Vite (HMR) e não
   escreve `dist/`.
10. Sugerir próximos passos: criar o repo git do projecto, correr
    `/figma-analysis` com o design (passo 12 do SETUP.md). Lembrar que o
    `npm run dev` usa o certificado SSL do Local para o hostname de dev: o site
    tem de ter SSL activo e confiável (Local → site → SSL → **Trust**), senão o
    servidor arranca em HTTP e o browser bloqueia os assets (mixed content).

## Regras

- Mostrar um resumo do find & replace (nº de ocorrências por ficheiro) ANTES
  de aplicar — substituições erradas de prefixo são difíceis de reverter.
- Nunca tocar em `dist/` manualmente; é regenerado pelo build.
- No fim, confirmar que `npm run php:lint` continua a passar.
