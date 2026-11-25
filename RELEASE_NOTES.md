# [DoliMeet] [23.2.2] - Page publique d'ajout de stagiaires plus sûre - Lignes des contrats de formation dans l'ordre

Description : la page publique où le client d'une convention ajoute ses stagiaires n'accepte plus que le lien sécurisé de son contrat, et les lignes d'un contrat de formation créé depuis une proposition commerciale arrivent dans l'ordre. **Mise à jour recommandée pour toutes les instances.**

**Cette version demande Saturne 23.9.1 ou supérieur.**

**À savoir avant de mettre à jour : les liens d'ajout de stagiaires envoyés avant cette version ne fonctionnent plus.** Renvoyez le lien depuis le bouton « Interface Publique » de la fiche du contrat ou depuis votre modèle d'e-mail : ils produisent le nouveau lien d'eux-mêmes. Les stagiaires déjà ajoutés restent en place.

## Améliorations & corrections

### Page publique d'ajout de stagiaires : contrôles renforcés

* Le lien porte une clé propre au contrat : il est produit par le bouton « Interface Publique » de la fiche du contrat et par la variable `__DOLIMEET_PUBLIC_INTERFACE_CONTACT_URL__` des modèles d'e-mail.
* La page vérifie ce lien avant tout affichage ou enregistrement. Un lien incomplet ou modifié affiche « Ce lien n’est pas ou plus valide. Demandez un nouveau lien à votre organisme de formation. »
* Désactiver l'interface publique dans la configuration ferme aussi l'enregistrement, plus seulement l'affichage.
* Prénom, nom et e-mail valide sont obligatoires, et un stagiaire déjà présent est signalé, sans tenir compte des majuscules.
* Le bouton de la fiche ne place plus le jeton de session de l'utilisateur dans le lien copié pour le client.

### Contrats de formation

* Les lignes d'un contrat de formation créé depuis une proposition commerciale sont numérotées de 1 à N : plus de ligne au rang 0 ni de doublon, et les flèches pour monter ou descendre une ligne fonctionnent.

### Cohabitation avec les autres modules

* Un module qui déclenche un événement sur un objet qui n'est pas un objet Dolibarr (ExternalAccess, par exemple) ne laisse plus d'avertissements dans le journal PHP.

## Testez les nouveautés maintenant

**Allez tester un contrat de formation et sa page d'ajout de stagiaires sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.1](https://github.com/Evarisk/dolimeet/compare/23.2.1...23.2.2) et 23.2.2

* [Public] fix: contrôles de la page publique d'ajout de contacts [`4766667f`](https://github.com/Evarisk/dolimeet/commit/4766667f)
* [#1027] [Trigger] fix: plus d'avertissement sur un événement sans « _ » ou dont l'objet n'est pas un CommonObject [`36b415b6`](https://github.com/Evarisk/dolimeet/commit/36b415b6)
* [#1025] [Contrat] fix: lignes d'un contrat de formation cree depuis une propale renumerotees de 1 a N [`2dc68166`](https://github.com/Evarisk/dolimeet/commit/2dc68166)
