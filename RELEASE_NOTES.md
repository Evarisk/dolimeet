# [DoliMeet] [23.1.0] - Bilan Pédagogique et Financier - Suivi des questionnaires

Description : Cette version apporte le Bilan Pédagogique et Financier (tableau de bord et PDF Cerfa 10443*17), le suivi des questionnaires de satisfaction avec relances automatiques, la liste des contrats de formation et ses indicateurs, ainsi qu'une longue série de corrections sur les sessions, les notes de formation et la compatibilité PHP 8.

## Nouvelles fonctionnalités et innovations

### Bilan Pédagogique et Financier

* Nouveau tableau de bord « rapport financier et pédagogique » et sa page de configuration.
* Génération du PDF Cerfa 10443*17 avec les cadres D, E, F, G et H.
* Dictionnaire dédié pour la partie F2 et pourcentage de chiffre d'affaires sur la partie C.
* Le rapport garde une structure complète même quand l'exercice ne porte aucune donnée.

<!-- 📸 Ajouter une screenshot ici -->

### Suivi des questionnaires de satisfaction

* Onglet de configuration des questionnaires et de leurs réglages de relance.
* Modèles de mail d'envoi et de relance définissables par questionnaire.
* Tâche planifiée de relance des questionnaires restés sans réponse.

<!-- 📸 Ajouter une screenshot ici -->

### Liste des contrats de formation

* Nouvelle entrée dans le menu DoliMeet.
* Indicateurs de formation, contacts et facturation directement sur la liste.

<!-- 📸 Ajouter une screenshot ici -->

### Sessions et contacts

* Action de signature de masse depuis la liste des sessions.
* Nom du document de signature affiché par type de session.
* Nombre de formations affiché sur la liste des contacts.
* Nouveau trigger `CONTRAT_DELETE_CONTACT`, miroir de l'ajout de contact.
* L'utilisateur par défaut de l'interface publique reçoit le droit `societe.contact.creer`.

<!-- 📸 Ajouter une screenshot ici -->

---

## Améliorations & corrections

### Propositions commerciales de formation

* La description du produit se remplit de nouveau à l'ajout d'une ligne : le hook ne remplaçait plus le `<select>` produit, ce qui détruisait le `change()` posé par le cœur et empêchait l'appel AJAX qui alimente description, prix et TVA.
* La note publique de formation se rafraîchit sur `LINEPROPAL_MODIFY` et non `LINEPROPAL_UPDATE`.
* Durées gonflées et sessions dupliquées dans la note publique corrigées (les sessions du contrat ne sont plus relues à chaque ligne).
* Le libellé du produit prend le relais quand la description de la ligne de service est vide.
* L'objet de la formation n'est plus vide sur la note de contrat.
* La note de formation est construite à partir des lignes réellement utilisées.

### Sessions

* Plus de page blanche quand `object_type` est absent ou inconnu, ni de fatal sur un `object_type` corrompu.
* Le compteur de participants est initialisé avant d'être incrémenté.
* Le statut « Signé » apparaît dès que tous les participants ont signé.
* La session de formation redevient un objet liable.
* Lecture de `$_POST['fk_soc']` sécurisée sur le formulaire de création.
* Création des tables `llx_categorie_*` manquantes pour les sous-types de session, et `setCategories` routé sur `session`.
* Le comptage de sessions est ignoré pour les objets sans clé étrangère de session.

### Tableau de bord

* Les widgets de session se chargent sur la page d'accueil DoliMeet.
* Les compteurs de signataires sont agrégés en SQL.
* Plus de `TypeError` ni de `foreach` sur données vides quand aucune session n'existe.

### Contrats et triggers

* `CONTRACT_CREATE` : le `fetch()` de contact inutile sur `mandatory_signature` est supprimé et `dolibarr_lib.php` est chargé sur tous les chemins.
* Le nettoyage des signataires sur `CONTRAT_DELETE_CONTACT` est conditionné aux sessions en brouillon, et non au type de formation.

### Hooks et bibliothèques

* Le questionnaire de satisfaction est filtré sur la fiche configurée pour le rôle du contact.
* Garde sur la décoration du libellé de l'extrafield propale, et retrait du `trainingsession_service` déprécié.
* Plus d'erreur SQL dans `completeTabsHead` quand l'objet est un ticket.
* Les sessions supprimées sont exclues du calcul de durée des services de formation.

### Traductions, PHP 8 et configuration

* Traduction fr_FR manquante de la clé `Contract` ajoutée, elle restait en anglais dans tous les modules.
* Résultats de `trainingsession_function_lib1/lib2` ramenés à un tableau pour l'union PHP 8.
* Type nullable explicite sur `set_public_note` (dépréciation PHP 8.4) et warning `$out` corrigé.
* Les entités ne s'affichent plus brutes dans la notice de tâche planifiée.

### Intégration continue

* Les assets sont compilés par le socle Saturne, le gulpfile local est supprimé.
* Vérification des assets compilés à chaque push, en mode `verify` depuis que le robot ne peut plus pousser sur `develop`.

---

## Comparaison des versions [23.0.0](https://github.com/Evarisk/dolimeet/compare/23.0.0...23.1.0) et 23.1.0

* [#905] [CI] fix: basculer les assets en mode verify, le robot ne peut plus pousser [`5ff84c4`](https://github.com/Evarisk/dolimeet/commit/5ff84c4)
* [#903] [CI] feat: verifier les assets compiles a chaque push [`c8afc19`](https://github.com/Evarisk/dolimeet/commit/c8afc19)
* [#901] [CI] rework: compiler les assets via le socle, supprimer le gulpfile local [`5001d4e`](https://github.com/Evarisk/dolimeet/commit/5001d4e)
* [#898] [Session] fix: page blanche quand object_type est absent ou inconnu [`aafc128`](https://github.com/Evarisk/dolimeet/commit/aafc128)
* [#896] [TrainingSession] fix: la session de formation redevient un objet liable [`90cd55b`](https://github.com/Evarisk/dolimeet/commit/90cd55b)
* [#894] [Formation] fix: build the formation note from the lines actually used [`c4b3c2a`](https://github.com/Evarisk/dolimeet/commit/c4b3c2a)
* [#880] [Setup] fix: entities shown raw in the scheduled job notice [`e3f072f`](https://github.com/Evarisk/dolimeet/commit/e3f072f)
* [#880] [Contract] feat: contacts and invoicing on the training contract list [`c9aabc6`](https://github.com/Evarisk/dolimeet/commit/c9aabc6)
* [#880] [Contract] feat: training indicators on the training contract list [`fad6f80`](https://github.com/Evarisk/dolimeet/commit/fad6f80)
* [#880] [Setup] feat: questionnaire configuration tab and its reminder settings [`1f64552`](https://github.com/Evarisk/dolimeet/commit/1f64552)
* [#880] [Cron] feat: scheduled reminder of the unanswered satisfaction surveys [`043ed28`](https://github.com/Evarisk/dolimeet/commit/043ed28)
* [#880] [Setup] feat: send and reminder mail models per satisfaction survey [`57941b8`](https://github.com/Evarisk/dolimeet/commit/57941b8)
* [#880] [Menu] feat: training contract list in the DoliMeet menu [`b2476b8`](https://github.com/Evarisk/dolimeet/commit/b2476b8)
* [#885] [BPF] fix: give the report a full shape when the fiscal year carries no data [`0aff4f6`](https://github.com/Evarisk/dolimeet/commit/0aff4f6)
* [#883] [Session] fix: initialise the attendant counter before incrementing it [`1ef8d7f`](https://github.com/Evarisk/dolimeet/commit/1ef8d7f)
* [#778] [Session] feat: name the signature document of each session type on the list [`5718815`](https://github.com/Evarisk/dolimeet/commit/5718815)
* [#880] [Session] feat: enable the mass sign action on the session list [`9805fa8`](https://github.com/Evarisk/dolimeet/commit/9805fa8)
* fix: resolve undefined variable $out warning in actions_dolimeet [`8cb3a79`](https://github.com/Evarisk/dolimeet/commit/8cb3a79)
* [#873] [Hook] fix: filter the satisfaction survey on the sheet configured for the contact role [`124bae6`](https://github.com/Evarisk/dolimeet/commit/124bae6)
* [#875] [Lang] fix: add the missing fr_FR translation of the Contract key [`2d916de`](https://github.com/Evarisk/dolimeet/commit/2d916de)
* Fix Invalid argument for foreach in dashboard for signatories when no data [`2ee00a5`](https://github.com/Evarisk/dolimeet/commit/2ee00a5)
* Fix TypeError in session dashboard when no sessions exist [`67c6047`](https://github.com/Evarisk/dolimeet/commit/67c6047)
* [#868] [JS] fix: keep core change() handler on propal product combo [`f820386`](https://github.com/Evarisk/dolimeet/commit/f820386)
* [#866] [Trigger] fix: drop useless Contact fetch on CONTRACT_CREATE mandatory_signature [`126b230`](https://github.com/Evarisk/dolimeet/commit/126b230)
* [#864] [Trigger] fix: load dolibarr_lib.php on every CONTRACT_CREATE path [`4495b3e`](https://github.com/Evarisk/dolimeet/commit/4495b3e)
* [#862] [Session] fix: skip session count for objects without a session FK [`2642dbc`](https://github.com/Evarisk/dolimeet/commit/2642dbc)
* [#858] [Session] fix: aggregate signatory dashboard counts in SQL [`3e971b2`](https://github.com/Evarisk/dolimeet/commit/3e971b2)
* [#858] [Dashboard] fix: load session widgets on DoliMeet home page [`ea305ce`](https://github.com/Evarisk/dolimeet/commit/ea305ce)
* [#856] [Lib] fix: exclude deleted sessions from training service duration match [`972d3f4`](https://github.com/Evarisk/dolimeet/commit/972d3f4)
* [#854] [Session] fix: guard $_POST['fk_soc'] read on session create form [`e236660`](https://github.com/Evarisk/dolimeet/commit/e236660)
* [#852] [Session] fix: create missing llx_categorie_* tables for session subtypes [`fdfcdaa`](https://github.com/Evarisk/dolimeet/commit/fdfcdaa)
* fix: prevent sql error in completeTabsHead when object is a ticket [`f13967c`](https://github.com/Evarisk/dolimeet/commit/f13967c)
* [#848] [Hook] fix: guard propal extrafield label decoration, drop deprecated trainingsession_service [`44be897`](https://github.com/Evarisk/dolimeet/commit/44be897)
* [#845] [Trigger] fix: gate CONTRAT_DELETE_CONTACT signatory cleanup on draft sessions, not trainingsession_type [`cf3643e`](https://github.com/Evarisk/dolimeet/commit/cf3643e)
* [#793] [Session] fix: show "Signé" status once all attendants have signed [`57c6262`](https://github.com/Evarisk/dolimeet/commit/57c6262)
* [#741] [Lib] fix: empty 'objet de la formation' on contract note (use product_label) [`68e5d30`](https://github.com/Evarisk/dolimeet/commit/68e5d30)
* [#816] [Trigger] add: CONTRAT_DELETE_CONTACT to mirror contact add [`79b3a34`](https://github.com/Evarisk/dolimeet/commit/79b3a34)
* [Lib] fix: explicit nullable type for set_public_note $propal param (PHP 8.4 deprecation) [`dfa3994`](https://github.com/Evarisk/dolimeet/commit/dfa3994)
* [#828] [Trigger] fix: listen to LINEPROPAL_MODIFY (not _UPDATE) for note refresh [`a77d9ab`](https://github.com/Evarisk/dolimeet/commit/a77d9ab)
* [#828] [Trigger] fix: refresh formation public note on draft proposal line changes [`2283f4f`](https://github.com/Evarisk/dolimeet/commit/2283f4f)
* [#705] [Trigger] fix: fallback to product label when description is empty on formation service lines [`dab3ba1`](https://github.com/Evarisk/dolimeet/commit/dab3ba1)
* [#831] [Lib] fix: fetch contract sessions once to stop inflated durations and duplicated sessions in public note [`82f2ee3`](https://github.com/Evarisk/dolimeet/commit/82f2ee3)
* [#813] [PublicInterface] add: grant societe.contact.creer to default public interface user [`4a403e7`](https://github.com/Evarisk/dolimeet/commit/4a403e7)
* [Session] fix: route setCategories to 'session' to avoid missing llx_categorie_{element} table [`1033602`](https://github.com/Evarisk/dolimeet/commit/1033602)
* [#826] [Session] fix: trainingsession card fatal on '?'-corrupted object_type [`023f50a`](https://github.com/Evarisk/dolimeet/commit/023f50a)
* [#829] [BPF] add: génération du PDF Cerfa 10443*17 + cadres D/E/F/G/H [`416b872`](https://github.com/Evarisk/dolimeet/commit/416b872)
* [ActionsDolimeet] fix: coerce trainingsession lib1/lib2 results to array for PHP 8 union [`bf5176b`](https://github.com/Evarisk/dolimeet/commit/bf5176b)
* [#574] [Dashboard] add: C part missing CA percent [`957f190`](https://github.com/Evarisk/dolimeet/commit/957f190)
* [#574] [Dashboard] add: dictonary for part F2 [`1c1a726`](https://github.com/Evarisk/dolimeet/commit/1c1a726)
* [#574] [Dashboard] add: other BPF parts [`dd162d9`](https://github.com/Evarisk/dolimeet/commit/dd162d9)
* [#574] [Dashboard] fix: date [`d6c2cdf`](https://github.com/Evarisk/dolimeet/commit/d6c2cdf)
* [#574] [Dashboard] fix: missing include [`323baef`](https://github.com/Evarisk/dolimeet/commit/323baef)
* [#574] [Dashboard] add: C part (WIP) [`608c405`](https://github.com/Evarisk/dolimeet/commit/608c405)
* [#574] [Hook] fix: php8 [`8ab14c3`](https://github.com/Evarisk/dolimeet/commit/8ab14c3)
* [#574] [Class] add: dashboard financial_and_pedagogical_report [`f2ea3cb`](https://github.com/Evarisk/dolimeet/commit/f2ea3cb)
* [#574] [admin] add: financial_and_pedagogical_report [`4f37890`](https://github.com/Evarisk/dolimeet/commit/4f37890)
* [#802] [Js] fix: moove js and remove console log [`40bdaf3`](https://github.com/Evarisk/dolimeet/commit/40bdaf3)
* [#802] [ActionsDolimeet] add: number of formations on contact list [`6eccfa2`](https://github.com/Evarisk/dolimeet/commit/6eccfa2)
* [#802] [ActionsDolimeet] add: number of formations on contact list [`8cd6c39`](https://github.com/Evarisk/dolimeet/commit/8cd6c39)
