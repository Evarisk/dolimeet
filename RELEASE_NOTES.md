# [Doliopi] [23.0.0] - Certificat de complétion - Workflow formation enrichi

Description : Cette version introduit la génération PDF du certificat de complétion, refond le tableau de bord pour des graphes plus performants, ajoute le déclenchement automatique de mails sur action, et corrige de nombreux points sur les contrats, les sessions et les templates.

## Nouvelles fonctionnalités et innovations

### Certificat de complétion

* Nouveau modèle PDF de certificat de complétion (`completioncertificatedocument`).
* Génération automatique : `write_file` opérationnel, traductions des clés PDF en place.
* Plusieurs itérations de correctifs pour finaliser le rendu du certificat.

<!-- 📸 Ajouter une screenshot ici -->

### Triggers et templates de mail

* Nouveau trigger Doliopi et modèle de mail associé : possibilité de configurer un envoi automatique sur événement métier.
* Bouton « Envoyer un mail » directement sur l'action, ouvrant le sondage de satisfaction dans un nouvel onglet.
* Statuts personnalisés (custom status) ajoutés sur les actions Doliopi.

<!-- 📸 Ajouter une screenshot ici -->

### Tableau de bord

* Refonte du dashboard pour de meilleures performances et préparation de l'ajout de nouveaux graphes.

<!-- 📸 Ajouter une screenshot ici -->

---

## Améliorations & corrections

### Sessions et formations

* Filtre de durée sur les sessions ignore désormais les valeurs `0`.
* Correction d'un fatal `getNomUrl` sur la liste de sessions.
* Gestion des sessions sur template améliorée.
* `update_formation_datas` : action admin améliorée.

### Contrats

* Bannière de contrat corrigée et compatibilité PHP 8 assurée.

### Bibliothèques et hooks

* Vérification du type de source de contact ajoutée avant traitement (évite des fatals quand la source n'est pas définie).
* Override de `listeContact` corrigé (override correct au lieu d'un appel direct).
* Séparation entre service de formation et autres services dans une fonction de bibliothèque dédiée.
* Logique de création du sondage de satisfaction corrigée (ne se déclenchait pas dans certains cas).

### PDF Attendance

* Document de feuille de présence corrigé.

### Admin

* Token manquant ajouté sur une action admin.
* Erreur sur `satisfactionSurveys` corrigée.

### Module / configuration

* Inclusions manquantes pour Dolistore ajoutées.
* `completioncertificatedocument` : document manquant ajouté à l'install.
* Paramètres manquants dans `conf` ajoutés.
* Menu et droits améliorés.
* Fatal sur substitution corrigé (vérification d'array manquante).
* Fatal `require_once` manquant corrigé.

### Actions Doliopi

* Warning sur la clé `opco_financing` corrigé.

### Traductions

* Trad ajoutée pour `MandatorySignature`.

### Code

* Plusieurs passes de nettoyage des classes (`[Class] core: clean code`).

## Comparaison des versions [21.0.0](https://github.com/Evarisk/doliopi/compare/21.0.0...23.0.0) et 23.0.0

* [#823] [Lang] add: trad for MandatorySignature [`7c48730`](https://github.com/Evarisk/doliopi/commit/7c48730)
* [#815] [Contract] fix: banner and php8 [`66d8b77`](https://github.com/Evarisk/doliopi/commit/66d8b77)
* [#814] [Hook] fix: logic issue on check for create survey satisfaction [`498339c`](https://github.com/Evarisk/doliopi/commit/498339c)
* [#812] [Dashboard] fix: rework for more performance and prepare other graphs [`5fcee3a`](https://github.com/Evarisk/doliopi/commit/5fcee3a)
* [#811] [Lib] fix: need to override listeContact functions [`ce8ce88`](https://github.com/Evarisk/doliopi/commit/ce8ce88)
* [#810] [ActionDoliopi] fix: warning opco_financing key [`b1dfff6`](https://github.com/Evarisk/doliopi/commit/b1dfff6)
* [#808] [Lib] fix: change function for separate formation service and others [`bfc60c4`](https://github.com/Evarisk/doliopi/commit/bfc60c4)
* [#807] [Admin] fix: error on satisfactionSurveys [`74059c7`](https://github.com/Evarisk/doliopi/commit/74059c7)
* [#806] [Session] fix: need to ignore 0 on duration filter [`2098e9e`](https://github.com/Evarisk/doliopi/commit/2098e9e)
* [#804] [ActionsDoliopi] add: trigger and mail model [`9570ad7`](https://github.com/Evarisk/doliopi/commit/9570ad7)
* [#795] [PDF] fix: attendance sheet document [`2ad6eda`](https://github.com/Evarisk/doliopi/commit/2ad6eda)
* [#794] [ActionsDoliopi] add: custom status, send mail button [`b180b49`](https://github.com/Evarisk/doliopi/commit/b180b49) [`ec5d507`](https://github.com/Evarisk/doliopi/commit/ec5d507)
* [#792] [PDF] add: completion certificate pdf model [`60bbc8a`](https://github.com/Evarisk/doliopi/commit/60bbc8a) [`8c2ac4f`](https://github.com/Evarisk/doliopi/commit/8c2ac4f) [`2e1744b`](https://github.com/Evarisk/doliopi/commit/2e1744b) [`3c5b3a4`](https://github.com/Evarisk/doliopi/commit/3c5b3a4) [`688d3fd`](https://github.com/Evarisk/doliopi/commit/688d3fd) [`66a615e`](https://github.com/Evarisk/doliopi/commit/66a615e)
* [#756] [Lib] fix: need to check contact source type [`c59d568`](https://github.com/Evarisk/doliopi/commit/c59d568)
* [#709] [Admin] fix: improve action update_formation_datas [`2440dc0`](https://github.com/Evarisk/doliopi/commit/2440dc0)
* [#2270] [SessionClass] fix: fatal getnomurl session list [`a0ccb29`](https://github.com/Evarisk/doliopi/commit/a0ccb29)
* [Mod] fix: missing parameters, menu/rights, dolistore include [`862a608`](https://github.com/Evarisk/doliopi/commit/862a608) [`d67f37c`](https://github.com/Evarisk/doliopi/commit/d67f37c) [`4cf78f6`](https://github.com/Evarisk/doliopi/commit/4cf78f6) [`043397d`](https://github.com/Evarisk/doliopi/commit/043397d)
* [Substitution/Lib] fix: array check + missing require_once [`d58751a`](https://github.com/Evarisk/doliopi/commit/d58751a) [`13971c5`](https://github.com/Evarisk/doliopi/commit/13971c5)
* [Session] fix: management on session template [`036376f`](https://github.com/Evarisk/doliopi/commit/036376f)
* [Admin] fix: missing token in action [`e07677b`](https://github.com/Evarisk/doliopi/commit/e07677b)
* [Class] core: clean code [`4b4962a`](https://github.com/Evarisk/doliopi/commit/4b4962a) (et 4 commits associés)
