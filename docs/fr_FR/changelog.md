# Changelog

>**IMPORTANT**
>
>Pour rappel s'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte.

# 02/04/2022
- Meilleure gestion des valeurs de comparaison

# 04/03/2022
- Nouvelle option de configuration pour calculer les seuils mensuels en fonction des valeurs de l'année précendete a lieu de récupérer ces valeurs du site GRDF

# 04/03/2022
- Compatibilité avec les graphiques en fond de widget (contribution de @Spine34)

# 07/02/2022
- Compatibilité 4.2

# 01/02/2022
- Correction lorsque les données de comparaison ne sont pas disponibles
- Nouvelles options de configuration du plugin lorsqu'un captcha est détecté
- Nouvelles option de configuration du plugin pour spécifier le nombre de jours de retard

# 24/01/2022
- Correction sur le calcul de la consommation mensuelle du mois "m - 12"

# 16/01/2022
>**Important**
>
>Pour cette version majeure, il est fortement recommandé de supprimer et recréer les équipements afin de ne pas avoir un historique corrompu.
- Refonte comlète du plugin pour être compatible avec la nouvelle version du site web de GRDF
- Support de plusieurs compteurs
- Possibilité de visualiser les comparaisons de consommation sur les 12 derniers mois dans le widget

# 26/03/2021
- Seuil pour le mois en cours récupéré depuis le site et stocké dans une nouvelle commande

# 19/02/2021
- Option pour arrondir ou non les valeurs dans les widgets

# 23/01/2021
- Nouvelle commande index qui cumule les consommations journalières en m3 (pour compatibilité plugin suivi conso)

# 15/01/2021
- Correction de l'expression régulière pour les données de comparaison
- Nouveau template de widget (Jazpar 4)
- Nouvelle action pour rafraichir les données en interrogeant le site (hors cron)
- Nouvelle option pour utiliser des dates plutôt que les noms des commandes dans les widgets

# 08/01/2021
- Nouveau template de widget 
- Meilleure i18n

# 01/01/2021
- Icône flêche pour comparaison
- Niveaux de logs modifiés

# 30/12/2020
- Nouvelles données récupérées du site GRDF : consommations minimum maximum et moyenne des foyers similaires.
- Nouveau template de widget pour visualiser la comparaison de votre consommation avec des foyers similaires.

# 01/12/2020
- Gestion des periodes nulles.

# 15/11/2020
- Première version publique (beta)

