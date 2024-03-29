# Nextcloud Plugin

The **Nextcloud** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav). Upload your backups to Nextcloud.

## Installation

Installing the Nextcloud plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install nextcloud

This will install the Nextcloud plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/nextcloud`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `nextcloud`. You can find these files on [GitHub](https://github.com/the-dancing-code/grav-plugin-nextcloud) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/nextcloud

> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/the-dancing-code/grav-plugin-nextcloud/blob/master/blueprints.yaml).

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/nextcloud/nextcloud.yaml` to `user/config/plugins/nextcloud.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
webdav_url:
username:
password:
folder: /grav-plugin-nextcloud
purge:
  trigger: space
  max_backups_count: 25
  max_backups_space: 5
  max_backups_time: 365
```

| Option                    | Default                  | Values                            | Description                             |
| ------------------------- | ------------------------ | --------------------------------- | --------------------------------------- |
| `webdav_url`              | `null`                   | `string`                          | The WebDAV URL for your Nextcloud space |
| `username`                | `null`                   | `string`                          | Your Nextcloud username                 |
| `password`                | `null`                   | `string`                          | Your Nextcloud password                 |
| `folder`                  | `/grav-plugin-nextcloud` | `string`                          | The remote storage folder               |
| `purge.trigger`           | `space`                  | `none`, `number`, `space`, `time` | The trigger to remove remote backups    |
| `purge.max_backups_count` | `25`                     | `number`                          | Maximum number of backups               |
| `purge.max_backups_space` | `5`                      | `number`                          | Maximum storage space in GB             |
| `purge.max_backups_time`  | `365`                    | `number`                          | Maximum retention time in days          |

Note that if you use the Admin Plugin, a file with your configuration named nextcloud.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

The plugin will upload every newly created backup automatically.
