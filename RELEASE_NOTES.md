# [DoliMeet] [23.2.1] - Événements à la bonne référence - E-mails des contrats de formation fiabilisés

Description : version de correctifs. L'agenda nomme une session validée par sa référence définitive, l'e-mail d'un contrat de formation sans session ne montre plus de variable brute, et DoliMeet fonctionne avec DigiQuali installé dans un autre dossier de modules.

**Cette version demande Saturne 23.9.1 ou supérieur.**

## Améliorations & corrections

### Sessions et agenda

* L'événement de validation d'une réunion, d'une session de formation ou d'un audit porte la référence définitive : « Réunion RE2611-0049 validé(e) » au lieu de « Réunion (PROV1545) validé(e) ».
* Créer une session depuis un tiers (tiers déjà choisi dans l'adresse) ne produit plus d'avertissement PHP « Undefined array key "fk_soc" » ; le tiers reste présélectionné.
* À l'activation du module, les documents de DoliMeet restés dans un dossier « (PROVn) » rejoignent celui de leur référence définitive (avec Saturne 23.9.1).

### Contrats de formation

* Dans l'e-mail d'un contrat de formation, la variable des sessions du contrat (`__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__`) est remplacée par rien quand le contrat n'a aucune session, au lieu de rester en clair dans le texte. Le formulaire d'e-mail n'écrit plus d'avertissement PHP à chaque ouverture.

### Stabilité et compatibilité

* Installé dans une racine alternative à `custom/`, DigiQuali est retrouvé par DoliMeet : la configuration des questionnaires, les enquêtes de satisfaction et leurs relances ne tombent plus en erreur fatale quand les deux modules ne sont pas dans le même dossier.
* Plus d'erreur fatale sur les pages qui annulent un formulaire ou génèrent un document quand DoliMeet est actif (par exemple la fiche d'une enveloppe DoliLetter).
* Le pictogramme de DoliMeet s'affiche dans Configuration › Modules › Déclencheurs : c'était une image cassée.

## Testez les nouveautés maintenant

**Allez tester la validation d'une session de formation et son contrat sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.0](https://github.com/Evarisk/dolimeet/compare/23.2.0...23.2.1) et 23.2.1

* [#1016] [Mod] fix: fichiers des objets validés restés sous (PROVn) repris à l'activation depuis Accueil > Modules [`6c2eba12`](https://github.com/Evarisk/dolimeet/commit/6c2eba12)
* [#1017] [Trigger] fix: picto dolimeet_color@dolimeet, l'image dolimeet@dolimeet n'existe pas [`985d5f60`](https://github.com/Evarisk/dolimeet/commit/985d5f60)
* [#1014] [Hook] fix: doActions et saturneBuildDoc acceptent $action nul [`0c0f2ef4`](https://github.com/Evarisk/dolimeet/commit/0c0f2ef4)
* [#1012] [Substitutions] fix: « Undefined array key "__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__" » sur le formulaire d'e-mail d'un contrat de formation [`bc41475c`](https://github.com/Evarisk/dolimeet/commit/bc41475c)
* [#1010] [Agenda] fix: événement de validation libellé avec la référence définitive [`4b513c44`](https://github.com/Evarisk/dolimeet/commit/4b513c44)
* [#1008] [Module] fix: inclusions de DigiQuali par dol_include_once, plus de fatale en racine alternative [`0c84239d`](https://github.com/Evarisk/dolimeet/commit/0c84239d)
* [#1006] [Session] fix: « Undefined array key "fk_soc" » à la création d'une session avec un tiers dans l'URL [`88860a9d`](https://github.com/Evarisk/dolimeet/commit/88860a9d)
