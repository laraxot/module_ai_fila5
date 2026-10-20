---
title: "AI — coordinamento wave PHPStan"
type: story
module: AI
status: in-progress
created: 2026-10-20
updated: 2026-10-20
qmd: "ai phpstan wave coord cursor-scratch tsv lane non analyse modules"
---

# AI — wave PHPStan (coordinamento)

## Scopo

Allineare la lane AI alla ground truth coordinator-owned senza duplicare run costosi.

## Contratto

- Errori: `.cursor-scratch/phpstan-wave/AI.tsv` (+ `AI.files` se presente).
- Solo il coordinatore rilancia `cd laravel && ./vendor/bin/phpstan analyse Modules`.
- Prompt: [11-phpstan.md](../../../../../bashscripts/docs/prompts/11-phpstan.md), [46-multi-agent-module-swarm.md](../../../../../bashscripts/docs/prompts/46-multi-agent-module-swarm.md).
- Scopo modulo: [../scopo.md](../scopo.md).

## Nota naming

Le lane implementative possono creare `phpstan-*-swarm-wave.story.md`: **non** usare quel nome qui (evita collisioni).

## Claim

Lane docs: indici e scopo aggiornati; nessun edit PHP applicativo.
