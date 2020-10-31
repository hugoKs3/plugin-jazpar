# Plugin Enedis Linky

Plugin permettant la récupération des consommations du compteur communicant *linky* par l'interrogation du compte-client *Enedis*. Les données n'étant pas mises à disposition en temps réel, le plugin récupère chaque jour les données de consommation électrique de la veille.

4 types de données de consommation sont accessibles :
- la **puissance soutirée** par demi-heure *(en kW)*.
> La courbe de consommation *(ou puissance soutirée)* restitue la puissance appelée par tous vos appareils électriques en moyenne sur une demi-heure.

- la **consommation journalière** *(en kWh)*.
- la **consommation mensuelle** *(en kWh)*.
- la **consommation annuelle** *(en kWh)*.

>**Important**      
>Il est nécessaire d'être en possession d'un compte-client Enedis. Le plugin récupère les informations à partir de la partie *professionnels* [du site Enedis](https://espace-client-connexion.enedis.fr/auth/XUI/#login/&realm=particuliers&goto=https://espace-client-particuliers.enedis.fr%2Fgroup%2Fespace-particuliers%2Faccueil){:target="\_blank"}, il faut donc vérifier que vous y avez bien accès avec vos identifiants habituels et que les données y sont visibles. Dans le cas contraire, le plugin ne fonctionnera pas.

# Configuration

## Configuration du plugin

Le plugin **Enedis Linky** ne nécessite aucune configuration spécifique et doit seulement être activé après l'installation.

Les données sont vérifiées toutes les heures entre 4h et 22h et mises à jour uniquement si non disponibles dans Jeedom.

## Configuration des équipements

Pour accéder aux différents équipements **Enedis Linky**, dirigez-vous vers le menu **Plugins → Energie → Enedis Linky**.

> **A savoir**    
> Le bouton **+ Ajouter** permet d'ajouter un nouveau compte **Enedis Linky**.

Sur la page de l'équipement, renseignez l'**identifiant** ainsi que le **mot de passe** de votre compte-client *Enedis* puis cliquez sur le bouton **Sauvegarder**.

Le plugin va alors vérifier la bonne connexion au site *Enedis* et récupérer et insérer en historique :
- **puissance soutirée** : les 48 valeurs de la veille *(1 valeur par demi-heure)*,
- **consommation journalière** : les 30 derniers jours,
- **consommation mensuelle** : les 12 derniers mois,
- **consommation annuelle** : les 3 dernières années.

# Template de widget

Le plugin offre la possibilité d'afficher les données de consommation dans un template de widget imitant l'aspect d'un compteur *Linky*. Vous avez la possibilité de sélectionner ou non ce template en cochant ou décochant la case **Template de widget** sur la page générale de l'équipement concerné.

Le template sera affiché aussi bien sur les versions desktop que mobile.

>**Astuce**     
>En version desktop, les informations affichées sur le widget s'adaptent en taille lors du redimensionnement de la tuile.
