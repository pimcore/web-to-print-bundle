# Installation

## Installation Process
After installing the bundle and the required dependencies of the processor you wish to use, you need to configure the settings under *Settings >  Web-to-Print*. 
There you will find detailed notes about the options and settings available. depending on which processor you will use. 

## Uninstallation
Uninstalling the bundle does not clean up `printpages` or `printcontainers`. Before uninstalling make sure to remove or archive all dependent documents.
You can also use the following command to clean up you database. Create a backup before executing the command. All data will be lost.

```bash
 bin/console pimcore:document:cleanup printpage printcontainer
```

## Best Practice

- [Events and PDFX Conformance](./doc/90_Web2Print_Extending_Config_for_PDFX_conformance.md)

