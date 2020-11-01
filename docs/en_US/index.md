# Enedis GRDF Gazpar

Plugin allowing the recovery of consumption of the communicating meter *Gazpar* by querying the customer account *GRDF*. As the data is not made available in real time, the plugin retrieves the gaz consumption data from the day before each day.

3 types of consumption data are accessible :
- the **daily consumption** *(in kWh)*.
- the **monthly consumption** *(in kWh)*.
- the **annual consumption** *(in kWh)*.

>**Important**      
>You must have a GRDF customer account. The plugin retrieves information from the game *my space* [of the GRDF site](https://monespace.grdf.fr/monespace/connexion/accueil){:target = "\_ blank"}, you must therefore check that you have access to it with your usual identifiers and that the data is visible there. Otherwise, the plugin will not work.

# Configuration

## Plugin configuration

The plugin **GRDF Gazpar** does not require any specific configuration and should only be activated after installation.

The data is checked every hour between 4 a.m. and 10 p.m. and updated only if not available in Jeedom.

## Equipment configuration

To access the different equipment **GRDF Gazpar**, go to the menu **Plugins → Energy → GRDF Gazpar**.

> **To know**    
> The button **+ Add** allows you to add a new account **GRDF Gazpar**.

On the equipment page, fill in the'**Login** as well as the **Password** of your customer account *GRDF* then click on the button **Save**.

The plugin will then check the correct connection to the site *GRDF* and retrieve and insert in history :
- **daily consumption** : the last 30 days,
- **monthly consumption** : the last 12 months,
- **annual consumption** : the last 3 years.

>**TRICK**     
>In desktop version, the information displayed on the widget adapts in size when resizing the tile.
