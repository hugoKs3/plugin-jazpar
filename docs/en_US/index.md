# GRDF Gazpar

Plugin allowing the recovery of consumption of the communicating meter *Gazpar* by querying the customer account *GRDF*. As the data is not made available in real time, the plugin retrieves the gaz consumption data from the day before each day.

2 types of consumption data are accessible :
- the **daily consumption** *(in kWh and m3)*.
- the **monthly consumption** *(in kWh and m3)*.

>**Important**      
>You must have a GRDF customer account. The plugin retrieves information from the game *my space* <a href="https://monespace.grdf.fr/monespace/particulier/accueil" target="_blank">of the GRDF website</a>, you must therefore check that you have access to it with your usual identifiers and that the data is visible there. Otherwise, the plugin will not work.

# Configuration

## Plugin configuration

The plugin **GRDF Gazpar** does not require any specific configuration and should only be activated after installation.

The data is checked every hour between 4 a.m. and 10 p.m. and updated only if not available in Jeedom.

## Equipment configuration

To access the different equipment **GRDF Gazpar**, go to the menu **Plugins → Energy → GRDF Gazpar**.

> **To know**    
> The button **+ Add** allows you to add a new account **GRDF Gazpar**.

On the equipment page, fill in the'**Login** as well as the **Password** of your customer account *GRDF* then click on the button **Save**.

The option **Force data retrieval** to force data retrieval even if data is already present for concerned periods.

The plugin will then check the correct connection to the site *GRDF* and retrieve and insert in history :
- **daily consumption** : the last 10 days,
- **monthly consumption** : the last 12 months,
- **maximum, minimum and average monthly consumptions of similar profiles**
- **current month threshold** as defined on the website

Four widget's tmplates are available. The ones with comparison inform you how your previous month consumption compares with similar profiles.

# Remarks

During tests, it appeared that the GRDF website is quite "unstable" with direct impacts on the plugin. On Jeedom, the plugin is configured to gather data every hour. It may happen that it does not work each time: no issue, just wait for the next scheduled run.

This plugin heavily relies on how the GRDF website is structured/designed. Any change on the website will most probably break the plugin and will then require to perform code changes on the plugin.

# Contributions

This plugin is opened for contributions and even encouraged! Please submit your pull requests for improvements/fixes on <a href="https://github.com/hugoKs3/plugin-jazpar" target="_blank">Github</a>

# Credits

This plugin has been inspired by the work done by:

-   [Jeedom](https://github.com/jeedom)  through their Enedis plugin:  [plugin-enedis](https://github.com/jeedom/plugin-enedis)
-   [empierre](https://github.com/empierre)  through his similar work done for Domoticz:  [domoticz_gaspar](https://github.com/empierre/domoticz_gaspar)

# Disclaimer

-   This code does not pretend to be bug-free
-   Although it should not harm your Jeedom system, it is provided without any warranty or liability

# ChangeLog
Available [here](./changelog.html).
