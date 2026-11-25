# [DoliMeet] [23.2.3] - Signature en ligne des contrats plus sûre - Certificat de réalisation complet

Description : la signature en ligne des contrats de formation s'appuie maintenant sur une clé propre à l'instance, la case « Signature obligatoire » des contacts demande le droit de modifier l'objet, et le certificat de réalisation produit depuis une session est complet. **Mise à jour recommandée pour toutes les instances.**

**Cette version demande Saturne 23.10.0 ou supérieur.**

**À savoir avant de mettre à jour : les liens de signature en ligne des contrats envoyés avant cette version ne fonctionnent plus.** Renvoyez le lien depuis la fiche du contrat ou depuis votre modèle d'e-mail : il est produit avec la nouvelle clé.

## Améliorations & corrections

### Signature en ligne des contrats : contrôles renforcés

* Le lien de signature en ligne d'un contrat porte une clé propre à l'instance, créée à la mise à jour du module dans chaque entité où il est actif.
* La page de signature en ligne de DoliMeet refuse tout lien tant que cette clé n'existe pas.

### Contacts des objets

* La case « Signature obligatoire » d'un contact ne se modifie plus qu'avec le droit de modifier l'objet.

### Certificat de réalisation

* Le certificat produit depuis une session indique l'entreprise du stagiaire, l'intitulé de la formation et la nature de l'action, comme celui produit depuis le contrat.
* L'aperçu du modèle, dans Administration > Documents, s'affiche avec un stagiaire et une formation d'exemple, sans avertissement.

## Testez les nouveautés maintenant

**Allez tester un contrat de formation et son certificat de réalisation sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.2](https://github.com/Evarisk/dolimeet/compare/23.2.2...23.2.3) et 23.2.3

* [Contrat] fix: la page de signature en ligne de DoliMeet refuse toute clé sans jeton [`cde06c4b`](https://github.com/Evarisk/dolimeet/commit/cde06c4b)
* [#1034] [Document] fix: certificat de réalisation PDF complet dans l'aperçu et depuis une session [`86345926`](https://github.com/Evarisk/dolimeet/commit/86345926)
* [Contrat] fix: jeton de la signature en ligne des contrats initialisé à l'activation [`d2fb01f6`](https://github.com/Evarisk/dolimeet/commit/d2fb01f6)
* [Contact] fix: case « Signature obligatoire » modifiable seulement avec le droit d'écriture sur l'objet [`ff8dbe58`](https://github.com/Evarisk/dolimeet/commit/ff8dbe58)
