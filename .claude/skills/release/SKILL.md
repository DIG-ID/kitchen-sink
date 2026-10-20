---
name: release
description: Fecha uma versão do tema - corre o lint, valida/actualiza CHANGELOG.md e style.css, propõe o número de versão segundo o SemVer, prepara a mensagem de commit e as instruções de tag/release. Usar no fim de cada tarefa ou entrega.
---

# Fechar uma versão (starter ou projecto)

Automatiza o Flow A do `WORKFLOW.md` (passos 2–6). Funciona tanto no repo do
starter como num repo de projecto criado a partir dele.

## Processo

1. **Lint**: correr `npm run php:lint`. Se falhar, tentar `npm run php:fix` e
   voltar a correr; reportar o que não for auto-corrigível e parar até estar limpo.
2. **Analisar o diff**: `git status` + `git diff` (e commits desde a última tag,
   se existirem) para perceber o que mudou.
3. **Propor a versão** segundo a tabela do `CLAUDE.md`:
   - só correcções/ajustes → PATCH; funcionalidade nova → MINOR; breaking → MAJOR.
   - Confirmar com o utilizador antes de aplicar.
4. **CHANGELOG.md**: criar/completar a secção `## [X.Y.Z] — YYYY-MM-DD` com as
   categorias correctas (Added/Changed/Fixed/Removed/Deprecated/Security),
   em inglês, orientado ao que mudou (não a detalhes internos).
5. **style.css**: sincronizar o `Version:` com o changelog.
6. **Commit**: propor `vX.Y.Z: short summary` (inglês). Só commitar/push se o
   utilizador confirmar.
7. **Tag/Release**: perguntar se é para publicar release. Se sim:
   `git tag vX.Y.Z && git push origin vX.Y.Z` (no starter isto publica o zip
   na Release do GitHub via Action). Indicar como verificar: Actions tab →
   workflow "Release" verde → Releases com o zip anexado.

## Regras

- Nunca fazer push ou criar tags sem confirmação explícita do utilizador.
- Changelog e `style.css` têm de ficar sempre com o mesmo número.
- Se houver alterações não relacionadas misturadas na working tree, alertar e
  sugerir separá-las antes de fechar a versão.
