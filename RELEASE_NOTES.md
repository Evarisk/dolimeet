# [DoliMeet] [23.1.3] - Signature du formateur retrouvée sur le certificat

Description : Version corrective. Sur une installation multi-entités, la signature du formateur responsable n'apparaissait pas sur le certificat de réalisation si elle avait été tracée depuis une autre entité. Elle est désormais lue partout.

**Cette version demande Saturne 23.3.0 ou supérieur.**

## Améliorations & corrections

### Certificat de réalisation

* **La signature du formateur responsable manquait au certificat** dès qu'elle avait été tracée depuis une autre entité. Avec Multicompany, la signature de la fiche utilisateur est désormais partagée entre les entités (Saturne 23.3.0) : le certificat va la chercher là où elle se trouve, et non plus seulement dans l'entité courante.
* **Un formateur qui n'a jamais signé ne fait plus échouer la génération.** Le document imprime « N/A » à la place de la signature, et conserve le nom du formateur.

## Testez les nouveautés maintenant

**Allez tester la génération du certificat de réalisation sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.1.2](https://github.com/Evarisk/dolimeet/compare/23.1.2...23.1.3) et 23.1.3

* #952 [Signature] fix: trainer signature read in every entity on the completion certificate [`c51c0a3`](https://github.com/Evarisk/dolimeet/commit/c51c0a3)
