# Compendium-Resource-Center

This is a WordPress plugin that allows users to create a central location to simultaneously view multiple post types.  The front end interface includes two separate filters - Category and Type - along with a search field to allow users to more easily find the resources that interest them most.

###Setup

After installing the plugin, you will be prompted to also include the Advanced Custom Fields and AddThis plugins for some of the functionality.  
On the settings page, select the post types that you would like to display in the resource center and save the changes.  
You should then see a box containing the taxonomies associated with that post type.  Select the taxonomy that handles the categories for each post type in order to have those available as a filter on the front end.
Finally, add the shortcode [compendium] to the page that you would like the resource center to appear on.

###Future additions
- Ability to select only certain categories per post type to be displayed.
- Featured post options.
- Include RSS feed.
- Ability to create common post types within plugin.

###Change log

#####Version 0.2
* Fixes options issue and adds base of functions for population.

#####Version 0.3
* This update finalizes the output of the plugin and adds the page title and icons options. There still exists a bug with the jQuery UI select menu, and no css styles have been applied to the front end.

#####Version 0.3.1
* jQuery UI bug removed.  Dropdown menus fully functional.

#####Version 0.4
* Added social sharing button and menu to resource tiles.

#####Version 0.4.2
* Changed to only allow for public post types in resource center.

#####Version 0.5
* Added external url option
* Available Icon list styles changed for easier viewing. 

#####Version 0.5.1
* Minor bugfix for external url field