> **Warning**
> This plugin is no longer maintained

# plugin-jazpar
This is a plugin for Jeedom aimed at retrieveing gaz consumptions metrics from GRDF. 

This implies to have a communicating gaz meter provided by GRDF called Gazpar and a proper local Jeedom installation.

You like this plugin? You can, if you wish, encourage its developer:

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/paypalme/hugoKs3)

# Credits
This plugin has been inspired by the work done by:
- [Jeedom](https://github.com/jeedom) through their Enedis plugin: [plugin-enedis](https://github.com/jeedom/plugin-enedis)
- [empierre](https://github.com/empierre) through his similar work done for Domoticz: [domoticz_gaspar](https://github.com/empierre/domoticz_gaspar)

# Disclaimer
- This code does not pretend to be bug-free
- Although it should not harm your Jeedom system, it is provided without any warranty or liability

# Limitations
- This plugin heavily relies on how the GRDF website is structured/designed. Any change on the website will most probably break the plugin and will then require to perform code changes on the plugin.
- During tests, it appeared that the GRDF website is quite "unstable" with direct impacts on the plugin. On Jeedom, the plugin is configured to gather data every hour. It may happen that it does not work each time: no issue, just wait for the next scheduled run.

# Contributions
This plugin is opened for contributions and even encouraged! Please submit your pull requests for improvements/fixes.
