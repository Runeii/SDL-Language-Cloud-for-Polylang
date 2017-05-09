# SDL Managed Translation plugin for Polylang

## Requirements
This plugin has been developed for use with Wordpress version 4.5+ and [Polylang multilingual plugin](https://en-gb.wordpress.org/plugins/polylang/) version 2.1+.

## Note
This plugin has been designed to support both standard and multi-site Wordpress environments, in both a site specific and network-wide capacity. More information about using plugins in a multisite environment can be found [here](https://codex.wordpress.org/Multisite_Network_Administration).

## Installation (standard/single site)
1. Install the [Polylang multilingual plugin](https://en-gb.wordpress.org/plugins/polylang/) for Wordpress.
2. Extract the contents of the Managed Translation .zip file to the Wordpress plugins directory, at /wp-content/plugins/managedtranslation
3. Activate "SDL Managed Translation" via the Wordpress Admin CMS (Plugins -> Installed Plugins)

## Installation (network level)
** More information on
1. In the Network Administration menu, install the [Polylang multilingual plugin](https://en-gb.wordpress.org/plugins/polylang/) for Wordpress
2. Extract the contents of the Managed Translation .zip file to the Network plugins directory, at /wp-content/plugins/managedtranslation
3. Activate "SDL Managed Translation" via the Network Admin CMS (**Plugins** -> **Installed Plugins**)

## Activation
1. Visit the **Managed Translation** tab in the Wordpress CMS.
2. On first visit, you will be prompted to input your Account Detils. These are the *Username* and *Password* you use to login to the SDL Managed Translation site.

*Note: When activated network wide, the **Account Details** settings tab and ability to login to the SDL Managed Cloud are restricted to management by the super admin only, via the Network admin CMS.*

## Configuration
### Standard/single site installation
The default Project Options set for translation projects, used for "Quick translation" jobs, can be defined via the **Managed Translation** -> **General Settings** panel. The drop down menu will list all Project Options sets that are available to your SDL Managed Translation account.

### Network wide installation
Super admins may assign a default Project Options set for each blog in the network via the **Network admin** -> **Managed Translation** -> **  

## Usage
### Quick translation ###
For: rapidly sending one/multiple posts for translation, using the default options.
1. On the **Posts** screen in the Admin CMS, use the tickboxes on the left hand side to select one/multiple posts you wish to send for translation.
2. Select the appropriate *"Quick translate in to [LANGUAGE]"* option from the *Bulk Actions* dropdown at the top of the page, click Apply.
3. The post/s will be sent for translation via SDL Managed Translation.

### Create a new translation ###
For: if you need greater control over a project's settings, or to target multiple languages in a single job.
1. On the **Posts** screen in the Admin CMS, use the tickboxes on the left hand side to select one/multiple posts you wish to send for translation.
2. Select *"Create Translation Project"* from the *Bulk Actions* dropdown at the top of the page, click Apply.
3. Use the form provided to set the options for the translation job.
    * **Project Name**: The name of the current job
    * **Project Description**: A short description that describes what the job contains
    * **Project options set**: The project options set that the job should use
    * **Source language**: The language we will be translating *from*
    * **Target languages**: The languages the provided Wordpress posts should be translated *to*
    * **Due date**: The latest date by when a project should be delivered. Defaults to one week from current date
4. Once completed, select "Create Project" to create a project listing in SDL Managed Translation and send the posts for translation.

### Downloading completed translation projects ###
1. The SDL Managed Translation plugin will automatically monitor the progress of translation projects, polling every 15 minutes for an update.
2. Once translation is completed, Wordpress will download the translation, add to database, and mark the project as complete in SDL Managed Translation.

## Notes on Multisite environments
In a multisite environment, there are a number of differences to be aware of:
* **Network level activation**: the plugin must be activated at a network level, rather than per site.
* **Authentication**: authentication _must_ be done by a Super Admin via the Network administration panel. These credentials will then be used by each other site in the network for translations.
* **Delegation**: by default, Super Admins must manage and assign specific Project Option sets to each site in the network. If this behaviour is not desirable, they can delegate management of Project Option sets to each site via the **Network admin** -> **SDL Managed Translation** -> **Network Management** panel, clicking the button marked *Disable Network-level management*.
