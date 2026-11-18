# [DoliMeet] [23.2.0] - Validation des sessions en masse - PDF des formations en un seul zip - Modèles d'e-mail nommés dans Saturne

Description : Depuis la liste des sessions, validez d'un coup toutes les sessions prêtes et téléchargez en un seul zip les PDF des formations cochées. Les réglages d'e-mail de DoliMeet apparaissent enfin par leur nom dans la vue d'ensemble des modèles d'e-mail, et DoliMeet refuse de s'activer sur un Saturne trop ancien au lieu de tomber en erreur.

**Cette version demande Saturne 23.8.0 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Valider plusieurs sessions d'un coup

* Nouvelle action de masse **Valider** sur les listes des réunions, des sessions de formation et des audits, à côté de *Signer* et *Signer pour un participant* : cochez les sessions, choisissez *Valider*, confirmez.
* Seules les sessions en brouillon sont validées. Celles qui le sont déjà sont ignorées et comptées dans un avertissement.
* Comme depuis la fiche, une session à qui il manque un participant pour l'un de ses rôles (un formateur ou un stagiaire, par exemple) n'est pas validée : elle est citée dans un avertissement, avec un lien vers sa fiche pour la compléter.
* Une session validée en masse l'est exactement comme depuis sa fiche : elle reçoit sa référence définitive et l'événement de validation est inscrit dans l'agenda.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolimeet/refs/heads/assets/release-23.2.0/validation-en-masse.png" alt="Liste des sessions de formation après l'action de masse Valider : deux sessions validées, en attente de signature, et un avertissement pour la troisième, sans formateur, restée en brouillon">

### Les PDF des sessions de formation en un seul zip

* Nouvelle action de masse **Télécharger les PDF** sur la liste des sessions de formation, y compris depuis l'onglet DoliMeet d'un contrat : un seul fichier zip, avec un dossier par session, nommé d'après sa référence.
* Chaque dossier rassemble les PDF de la fiche session : feuilles de présence, certificats de réalisation et documents de la session. Les spécimens (aperçus avant signature) sont laissés de côté.
* Les documents générés en ODT dont le PDF n'existe pas encore sont convertis au passage, si la conversion ODT vers PDF est configurée. Le PDF reste enregistré : un document n'est converti qu'une fois. Si une conversion échoue, un avertissement indique combien de documents manquent dans le zip.
* Si aucune des sessions cochées n'a de PDF, un message le dit au lieu d'un zip vide.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolimeet/refs/heads/assets/release-23.2.0/actions-de-masse.png" alt="Liste des sessions de formation, trois sessions cochées : le menu des actions de masse ouvert propose Valider et Télécharger les PDF">

### Les réglages d'e-mail de DoliMeet nommés dans Saturne

* Dans la **vue d'ensemble des modèles d'e-mail** (*Configuration des mails*, onglet *Vue d'ensemble des modèles*), la colonne *Utilisé par* affiche désormais les réglages de DoliMeet par leur nom, par exemple « Enquête de satisfaction stagiaire · Modèle d'email de relance », au lieu d'un nom technique. Chacun mène à la page de configuration des questionnaires.
* Sont concernés : les modèles d'envoi et de relance de l'enquête de satisfaction pour chaque rôle (formateur, stagiaire, client, commanditaire/OPCO), les modèles d'envoi par défaut et l'envoi de l'enquête à un contact depuis le contrat.
* Avant de désactiver ou de supprimer un modèle d'e-mail, vous voyez ainsi d'un coup d'œil s'il sert encore à DoliMeet.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolimeet/refs/heads/assets/release-23.2.0/modeles-email-saturne.png" alt="Vue d'ensemble des modèles d'e-mail filtrée sur « satisfaction » : la colonne Utilisé par nomme chaque réglage DoliMeet (enquête de satisfaction par rôle, modèles de relance, envoi à un contact depuis le contrat)">

### Une activation plus sûre

* DoliMeet vérifie la version de Saturne avant de s'activer. Sur un Saturne absent ou plus ancien que 23.8.0, l'activation est refusée avec le message habituel de Dolibarr, qui indique la version attendue, au lieu de finir en erreur fatale.

## Améliorations & corrections

### Sessions

* Le bouton **Supprimer** de la fiche session et l'envoi de l'enquête de satisfaction depuis les contacts d'un contrat renvoyaient une erreur 403 quand la protection CSRF renforcée de Dolibarr est active : ils fonctionnent de nouveau.
* Choisir le modèle ODT dans la génération de documents de la fiche session rechargeait la fiche et remettait le choix sur le modèle PDF : le choix est conservé.
* Cloner une session, avec ses participants, pouvait finir en erreur fatale quand un autre module installé vérifie qu'il s'agit d'un clonage : le clonage passe.

### Feuille de présence PDF

* Le logo de la société était écrasé dans un cadre fixe, et un logo JPG n'apparaissait pas : il garde maintenant ses proportions, en PNG comme en JPG.
* En multi-entités, la feuille prend le logo de l'entité courante, et non plus toujours celui de la première. Sans logo, elle sort simplement sans.
* La génération n'écrit plus d'avertissements PHP dans le journal du serveur.

### Contacts d'un tiers

* La colonne **Formations** de l'onglet *Contacts/Adresses* d'un tiers ne s'affichait plus, à cause d'une erreur JavaScript : elle est de retour.

### Enquêtes de satisfaction (avec DigiQuali)

* Activer DigiQuali après DoliMeet provoquait une erreur fatale : c'est corrigé, et les modèles d'e-mail des enquêtes de satisfaction sont créés à la réactivation suivante de DoliMeet.
* DoliMeet n'enregistre plus de réglage pour le modèle d'e-mail du certificat de réalisation, que rien n'utilisait : la vue d'ensemble des modèles d'e-mail le croyait envoyé automatiquement. Le modèle lui-même est toujours créé.

### Installation et activation

* À l'activation, DoliMeet active d'abord les modules dont il a besoin (projets, tiers, propositions commerciales…) : le projet modèle des sessions de formation est désormais créé, même quand ces modules étaient coupés.
* Les styles, scripts, onglets, liens publics (signature, questionnaire, ajout de contact), le logo du ministère du Travail sur le certificat de réalisation et la tâche planifiée de relance ne sont plus figés sur le dossier `htdocs/custom` : DoliMeet fonctionne aussi installé dans une autre racine déclarée dans `conf.php`, avec Saturne où qu'il soit. Avec DigiQuali, les deux modules doivent rester dans le même dossier.

## Testez les nouveautés maintenant

**Allez tester la validation en masse et le zip des PDF des sessions de formation sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.1.3](https://github.com/Evarisk/dolimeet/compare/23.1.3...23.2.0) et 23.2.0

* [#986] [CI] ci: retrait du workflow Generate Auto Changelog sur main [`484b1bb5`](https://github.com/Evarisk/dolimeet/commit/484b1bb5)
* [#997] [JS] fix: colonne « Formations » des contacts d'un tiers en mode strict [`cb133fe8`](https://github.com/Evarisk/dolimeet/commit/cb133fe8)
* [#996] [Module] fix: chemins relatifs confiés au cœur sans custom/ [`17ab3e9a`](https://github.com/Evarisk/dolimeet/commit/17ab3e9a)
* [#996] [Module] fix: chemin disque construit avec DOL_DOCUMENT_ROOT . '/custom/…' [`826baea7`](https://github.com/Evarisk/dolimeet/commit/826baea7)
* [#996] [Module] fix: dol_buildpath('/custom/…') sans custom/ [`1ef977db`](https://github.com/Evarisk/dolimeet/commit/1ef977db)
* [#996] [Module] fix: URL construites avec DOL_URL_ROOT . '/custom/…' [`bff74344`](https://github.com/Evarisk/dolimeet/commit/bff74344)
* [#994] [Session] fix: après le clonage, ne retirer que la clé createfromclone de context [`262d7a2f`](https://github.com/Evarisk/dolimeet/commit/262d7a2f)
* [#994] [Session] fix: context de clonage en tableau, comme l'attend Dolibarr [`a94814d2`](https://github.com/Evarisk/dolimeet/commit/a94814d2)
* [#992] [CI] chore: baseline the confirm and toselect false positives of the validate mass action [`f06e9e95`](https://github.com/Evarisk/dolimeet/commit/f06e9e95)
* [#992] [Session] feat: validate mass action on the session list [`4f7ed245`](https://github.com/Evarisk/dolimeet/commit/4f7ed245)
* [#990] [CI] ci: actions GitHub en Node 24 (checkout@v7, cache@v6) [`1cd49ec6`](https://github.com/Evarisk/dolimeet/commit/1cd49ec6)
* [#988] [CI] ci: contrôle « API Saturne publiée » dans la CI qualité [`d1d2de93`](https://github.com/Evarisk/dolimeet/commit/d1d2de93)
* [#986] [CI] ci: retrait du workflow Generate Auto Changelog [`d2a442ce`](https://github.com/Evarisk/dolimeet/commit/d2a442ce)
* [#984] [Mod] feat: version minimale de Saturne, activation refusee sur un Saturne trop ancien [`0ae5bad2`](https://github.com/Evarisk/dolimeet/commit/0ae5bad2)
* [#982] [Registre] fix: chemins du registre publies tels qu'installes, lisibles par Saturne < 23.3.0 [`dc70763b`](https://github.com/Evarisk/dolimeet/commit/dc70763b)
* [#980] [Mod] fix: stop writing DOLIMEET_EMAIL_TEMPLATE_COMPLETION_CERTIFICATE, read nowhere [`52b0d4fa`](https://github.com/Evarisk/dolimeet/commit/52b0d4fa)
* [#978] [Email] feat: declare the DoliMeet email template settings to the Saturne overview [`faaaf01e`](https://github.com/Evarisk/dolimeet/commit/faaaf01e)
* [#976] [Documents] fix: plus de warnings PHP à la génération de la feuille de présence PDF [`95d929d4`](https://github.com/Evarisk/dolimeet/commit/95d929d4)
* [#803] [Documents] fix: logo de la feuille de présence PDF à ses proportions [`5bb93999`](https://github.com/Evarisk/dolimeet/commit/5bb93999)
* [#973] [CI] ci: refuser les inclusions relatives de Saturne [`8b2663c2`](https://github.com/Evarisk/dolimeet/commit/8b2663c2)
* [#973] [Saturne] fix: inclure Saturne par dol_include_once / dol_buildpath [`0aa6bb01`](https://github.com/Evarisk/dolimeet/commit/0aa6bb01)
* [#971] [Module] fix: refuse the activation instead of a fatal error when Saturne is missing [`b1dad48f`](https://github.com/Evarisk/dolimeet/commit/b1dad48f)
* [#968] [Substitution] fix: no fatal when DigiQuali is enabled after DoliMeet [`bd0f3ba9`](https://github.com/Evarisk/dolimeet/commit/bd0f3ba9)
* [#967] [Module] fix: activate dependencies before init so the training session template project is created [`baef8a45`](https://github.com/Evarisk/dolimeet/commit/baef8a45)
* [#965] [Object] rework: retirer le prefixe custom/ des entrees du registre d'objets [`9e59d5ca`](https://github.com/Evarisk/dolimeet/commit/9e59d5ca)
* [#962] [Session] fix: convert the ODT documents without PDF before zipping them in the download PDF mass action [`f07dc69c`](https://github.com/Evarisk/dolimeet/commit/f07dc69c)
* [#962] [Session] fix: rely on the core check for an empty selection in the download PDF mass action [`bad59283`](https://github.com/Evarisk/dolimeet/commit/bad59283)
* [#962] [Session] feat: mass action to download the PDF of selected training sessions as a zip [`22465102`](https://github.com/Evarisk/dolimeet/commit/22465102)
* [#960] [JS] fix: scope model checkbox handler to session form [`5c84589c`](https://github.com/Evarisk/dolimeet/commit/5c84589c)
* [#959] [CSRF] fix: ajouter le token CSRF aux liens et appels AJAX [`be8aa997`](https://github.com/Evarisk/dolimeet/commit/be8aa997)
