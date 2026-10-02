# [DoliMeet] [23.1.2] - Compatibilité déclarée et chaîne qualité

Description : Version de maintenance. Elle supprime les avertissements que le module écrivait dans les listes de ses voisins, déclare la compatibilité Dolibarr 23 à 24, retire quatre clés de traduction mortes qui pouvaient faire ressortir un libellé anglais chez un module voisin, et place le module sous analyse statique à chaque modification.

**Cette version demande Saturne 23.2.1 ou supérieur.**

## Améliorations & corrections

### Listes des autres modules

* **Le module écrivait un avertissement par ligne dans les listes des modules voisins.** Son greffon de colonnes comparait le type de l'objet affiché sans vérifier qu'il y en ait un : sur la liste d'un autre module, Dolibarr ne lui en fournit pas. Mesuré sur la liste du temps passé de DoliSIRH : 35 avertissements pour un seul affichage de page, et aucun traitement utile derrière.

### Compatibilité

* Le module déclare **Dolibarr 23 au minimum et 24 au maximum**. Le plancher était déjà la 23 ; le plafond manquait.

### Traductions

* **Quatre clés anglaises mortes sont retirées** (`OpcoSatisfactionSurvey`, `OpcoSatisfactionSurveyDescription`, `AverageDurationBySessionType`, `SetContrat`). Plus aucun code ne les utilisait, et elles n'avaient pas d'équivalent français. Une clé définie uniquement côté anglais reste bloquée en anglais pour toute la requête, dans **tous** les modules chargés : c'est ce genre de résidu qui fait ressortir un libellé anglais là où la traduction existe pourtant.
* Les deux fichiers de langue sont désormais vérifiés à parité : une clé ajoutée d'un côté et oubliée de l'autre arrête la chaîne qualité.

### Documentation du module

* Le changelog reprend le nom attendu par Dolibarr, `ChangeLog.md`. Le cœur le lit pour l'injecter dans la documentation générée du module ; sous l'ancien nom, il ne le trouvait pas sur un serveur Linux.

### Intégration continue

* Les pull requests passent désormais **PHPStan**, un **lint PHP** et un contrôle de **parité des fichiers de langue** français / anglais.
* La baseline PHPStan figeait le numéro de version du trigger dans un message d'erreur : chaque release cassait la chaîne qualité. Le motif est maintenant indépendant du numéro.

## Comparaison des versions [23.1.1](https://github.com/Evarisk/dolimeet/compare/23.1.1...23.1.2) et 23.1.2

* #948 [Hook] fix: un avertissement par ligne sur les listes des autres modules [`fcfead5`](https://github.com/Evarisk/dolimeet/commit/fcfead5)
* #944 [Mod] fix: renommer le changelog en ChangeLog.md [`711b68b`](https://github.com/Evarisk/dolimeet/commit/711b68b)
* #941 [CI] rework: élaguer les entrées mortes de la baseline [`15a684f`](https://github.com/Evarisk/dolimeet/commit/15a684f)
* #938 [CI] rework: scanner le socle par dossier plutôt que l'exclure par morceaux [`effce64`](https://github.com/Evarisk/dolimeet/commit/effce64)
* #934 [CI] fix: exclure les bouchons phan de Saturne de l'analyse [`4169848`](https://github.com/Evarisk/dolimeet/commit/4169848)
* #928 [CI] fix: numéro de version du trigger figé dans la baseline, et exclusion des stubs restaurée [`96ea601`](https://github.com/Evarisk/dolimeet/commit/96ea601)
* #926 [CI] rework: aligner phpstan.neon sur le gabarit commun [`86301d5`](https://github.com/Evarisk/dolimeet/commit/86301d5)
* #924 [CI] fix: PHPStan ne scanne plus les stubs de test de Saturne [`9d2c081`](https://github.com/Evarisk/dolimeet/commit/9d2c081)
* [CI] fix: compléter les dossiers du coeur vus par PHPStan [`fd12d2e`](https://github.com/Evarisk/dolimeet/commit/fd12d2e)
* #922 [Lang] fix: retirer quatre clés mortes de en_US [`3228e37`](https://github.com/Evarisk/dolimeet/commit/3228e37)
* #922 [CI] feat: PHPStan, lint PHP et parité des langues [`4e96ec5`](https://github.com/Evarisk/dolimeet/commit/4e96ec5)
* #920 [Module] rework: bornes de version Dolibarr 23 minimum, 24 maximum [`44290fa`](https://github.com/Evarisk/dolimeet/commit/44290fa)
