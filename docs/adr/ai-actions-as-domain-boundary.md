---
status: accepted
module: AI
---

# Actions come confine delle operazioni AI

## Contesto

Le operazioni AI devono validare input, scegliere il modello e restituire dati strutturati senza legare il front office a un provider.

## Decisione

Le operazioni applicative AI sono esposte tramite Actions e contratti del modulo; provider e richieste compatibili restano dettagli interni.

## Driver della decisione

- Separare dominio, provider e presentazione.

## Alternative scartate

- Chiamare direttamente il provider dalle view.
- Duplicare la logica nelle API e nel pannello.

## Conseguenze

Il comportamento è testabile e sostituibile; ogni nuovo provider deve rispettare il contratto dell'Action owner.

## Conferma

Verificare Actions, contratti e test del modulo prima di aggiungere un provider.
