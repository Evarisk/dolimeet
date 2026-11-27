# [DoliMeet] [23.2.4] - Un seul contrat de formation par proposition - Conversion PDF soumise au droit de générer le document

Description : un contrat de formation ne se crée plus en double depuis la même proposition commerciale, la création d'un contrat hors de sa fiche ne s'arrête plus sur une erreur, et la conversion en PDF depuis la fiche contrat demande le droit de générer le document.

**Cette version demande Saturne 23.10.1 ou supérieur.**

**À installer avant Saturne 23.11.0, ou en même temps.** Avec une version antérieure de DoliMeet, Saturne 23.11.0 refuserait la conversion en PDF sur la fiche contrat.

## Améliorations & corrections

### Contrats de formation

* Un second contrat de formation demandé depuis la même proposition commerciale est refusé. Auparavant, il était créé et validé d'office à chaque fois, ce qui pouvait produire des dizaines de contrats identiques.
* Créer un contrat hors de sa fiche (script, API, autre module) ne s'arrête plus sur une erreur fatale.

### Documents

* La conversion en PDF d'un document depuis la fiche contrat demande le droit de générer ce document, comme le bouton « Générer ».

### Affichage

* Les libellés DoliMeet des fiches proposition commerciale et produit n'affichent plus leur pictogramme en double, et ne laissent plus d'avertissement dans le journal PHP.
* Après une mise à jour de DoliMeet ou de Saturne, les pages chargent les nouveaux styles et scripts : plus besoin de vider le cache du navigateur (Ctrl+F5).
* Si Saturne est absent, DoliMeet affiche un message clair au lieu d'une erreur.

## Testez les nouveautés maintenant

**Allez tester un contrat de formation créé depuis une proposition commerciale sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.3](https://github.com/Evarisk/dolimeet/compare/23.2.3...23.2.4) et 23.2.4

* [#1048] [Hooks] fix: picto en double sur les libellés DoliMeet de la propale [`561db296`](https://github.com/Evarisk/dolimeet/commit/561db296)
* [#1048] [Hooks] fix: $picto défini avant les blocs propale et produit de formObjectOptions [`56a079ef`](https://github.com/Evarisk/dolimeet/commit/56a079ef)
* [#1047] [Assets] fix: version the assets printed by hand through saturne_asset_full_url [`a1f28fea`](https://github.com/Evarisk/dolimeet/commit/a1f28fea)
* [Documents] fix: génération PDF manuelle soumise au droit de générer le document [`3c373249`](https://github.com/Evarisk/dolimeet/commit/3c373249)
* [#1044] [Contrat] fix: remove the unused new Project in the CONTRACT_CREATE trigger (fatal error outside the contract card) [`d4b434cb`](https://github.com/Evarisk/dolimeet/commit/d4b434cb)
* [#1042] [Contrat] fix: un second contrat de formation depuis la même propale est refusé, plus validé d'office à chaque fois [`b36c0774`](https://github.com/Evarisk/dolimeet/commit/b36c0774)
* [#1040] [Saturne] fix: message explicite quand l'amorçage ne trouve pas Saturne [`7ba90bf2`](https://github.com/Evarisk/dolimeet/commit/7ba90bf2)
