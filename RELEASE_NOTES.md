# [DoliMeet] [23.1.1] - Génération de documents rétablie sur Dolibarr 24

Description : Version corrective. Elle rétablit la génération de documents sur Dolibarr 24, où elle était complètement bloquée, aligne le plancher de compatibilité Dolibarr sur celui du socle et corrige le quadrillage de débogage du Bilan Pédagogique et Financier.

**Cette version demande Saturne 23.2.0 ou supérieur.**

## Améliorations & corrections

### Génération de documents

* **Dolibarr 24 : la génération de documents est réparée.** Le cœur de Dolibarr 24 refuse tout modèle livré avec le module et ne transmet plus ses paramètres au générateur : aucune génération n'aboutissait. Corrigé ici et dans Saturne 23.2.0.

### Bilan Pédagogique et Financier

* Le quadrillage de débogage ne dépasse plus la fin de sa boucle.

### Compatibilité

* Le plancher de compatibilité Dolibarr est aligné sur celui du socle.

## Comparaison des versions [23.1.0](https://github.com/Evarisk/dolimeet/compare/23.1.0...23.1.1) et 23.1.1
