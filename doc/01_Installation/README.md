# Installation

## Installing processor dependencies
If you want to use Gotenberg as processor, you need to install additional dependencies. For more information and installation informations have a look at [Document Types and Available PDF Processors](../02_Doc_Types_and_Available_Processors.md#available-pdf-processors)


## Installation Process
After installing the bundle and the required dependencies of the processor you wish to use, you need to configure the settings under *Settings >  Web-to-Print*. 

If the *Save* button on bottom right is disabled, your [config location configuration](https://docs.pimcore.com/platform/next/Pimcore/Deployment/Configuration_Environments/) does not allow changing the config in Pimcore Admin UI. You can change this by editing `<Pimcore root>/config/config.yaml`:
```yaml
pimcore_web_to_print:
    config_location:
        web_to_print:
            write_target:
                type: 'settings-store'
```

There you will find detailed notes about the options and settings available (depending on which processor you will use).

## Uninstallation
Uninstalling the bundle does not clean up `printpages` or `printcontainers`. Before uninstalling make sure to remove or archive all dependent documents.
You can also use the following command to clean up you database. Create a backup before executing the command. All data will be lost.

```bash
 bin/console pimcore:document:cleanup printpage printcontainer
```

