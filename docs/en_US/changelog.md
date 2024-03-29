# Changelog 

>**Important**
>
>As a reminder if there is no information on the update, it means that it only concerns the updating of documentation, translation or text.

# 02/04/2022
- Better management of comparison data

# 04/03/2022
- Plugin's option to compute monthly thresholds instead of retrieving the ones defined on the website

# 04/03/2022
- Compatibility with tile background graphics (contribution from @Spine34)

# 07/02/2022
- Compatibility 4.2

# 01/02/2022
- Fix for absent comparison data
- New plugin configuration options to react when a captcha is detected
- New plugin configuration to specify number of days delay 

# 24/01/2022
- Fix on monthly consumption computation for the last year month

# 16/01/2022
>**Important**
>
>For this major version, it is highly recommended to delete and recreate equipments in order to not have messy history.
- Full rework of the plugin to be compatible with the new GRDF website version
- Support of multiple meters 
- Possibility to visualize the last 12 months consumption comparison in the widget

# 26/03/2021
- Threshold for current month gathered from website and stored in new command

# 19/02/2021
- Option to round or not values in widgets

# 23/01/2021
- New index command which adds daily m3 consumptions (for suivi conso plugin compatibility)

# 15/01/2021
- Regular expression fix for comparison data
- New widget's template (Jazpar 4)
- New action to refresh data by querying the website (outside of the cron)
- Option to use dates in widgets instead of command names

# 08/01/2021
- New widget's template
- Better i18n

# 01/01/2021
- Arrow icon for comparison
- Changing logs level

# 30/12/2020
- New data fetched from GRDF website: local maximum, minimum and average cosumptions
- New widget's template available to visulalize the comparison with similar profiles

# 01/12/2020
- Proper management of null periods.

# 15/11/2020
- First public version (beta).
