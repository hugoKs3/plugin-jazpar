# Plugin GRDF Gazpar

Plugin permettant la récupération des consommations du compteur communicant *Gazpar* par l'interrogation du compte-client *GRDF*. Les données n'étant pas mises à disposition en temps réel, le plugin récupère chaque jour les données de consommation de gaz de la veille.

3 types de données de consommation sont accessibles :
- la **consommation journalière** *(en kWh)*.
- la **consommation mensuelle** *(en kWh)*.
- la **consommation annuelle** *(en kWh)*.

>**Important**      
>Il est nécessaire d'être en possession d'un compte-client GRDF. Le plugin récupère les informations à partir de la partie *mon espace* [du site GRDF](https://monespace.grdf.fr/monespace/particulier/accueil){:target="\_blank"}, il faut donc vérifier que vous y avez bien accès avec vos identifiants habituels et que les données y sont visibles. Dans le cas contraire, le plugin ne fonctionnera pas.

# Configuration

## Configuration du plugin

Le plugin **GRDF Gazpar** ne nécessite aucune configuration spécifique et doit seulement être activé après l'installation.

Les données sont vérifiées toutes les heures entre 4h et 22h et mises à jour uniquement si non disponibles dans Jeedom.

## Configuration des équipements

Pour accéder aux différents équipements **GRDF Gazpar**, dirigez-vous vers le menu **Plugins → Energie → GRDF Gazpar**.

> **A savoir**    
> Le bouton **+ Ajouter** permet d'ajouter un nouveau compte **GRDF Gazpar**.

Sur la page de l'équipement, renseignez l'**identifiant** ainsi que le **mot de passe** de votre compte-client *GRDF* puis cliquez sur le bouton **Sauvegarder**.

Le plugin va alors vérifier la bonne connexion au site *GRDF* et récupérer et insérer en historique :
- **consommation journalière** : les 30 derniers jours,
- **consommation mensuelle** : les 12 derniers mois,
- **consommation annuelle** : les 3 dernières années.

>**Astuce**     
>En version desktop, les informations affichées sur le widget s'adaptent en taille lors du redimensionnement de la tuile.
