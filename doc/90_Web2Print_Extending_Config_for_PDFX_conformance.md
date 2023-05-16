# Extending PDF Creation Config for PDF/X Conformance

Sometimes it is necessary to add additional configuration options to the PDF processing configuration in the Pimcore backend UI - 
for example when creating PDF/X conform PDFs with PDF Reactor.

![Config Options](./img/configs.jpg)

But also for other use cases, it might be necessary to hook into the PDF creation process and modify the configuration before
creating the file.  

**Solution**

To do so, Pimcore provides two events:
- [`PRINT_MODIFY_PROCESSING_OPTIONS`](https://github.com/pimcore/web-to-print-bundle/blob/1.x/src/Event/DocumentEvents.php#L56):
  Event to modify the processing options displayed in the Pimcore backend UI. For example add additional options like `AppendLog` and `My Additional ...` 
  in the screenshot above. 
  
- [`PRINT_MODIFY_PROCESSING_CONFIG`](https://github.com/pimcore/web-to-print-bundle/blob/1.x/src/Event/DocumentEvents.php#L73)
  Event to modify the configuration for the PDF processor when the PDF gets created. For example read values for additional
  options and apply these values to the configuration of the PDF processor accordingly or do some other stuff. 
  

##### Example for adding Additional Config

Services in Container:
```yml
 app.event_listener.test:
        class: App\EventListener\PDFConfigListener
        tags:
            - { name: kernel.event_listener, event: pimcore.document.print.processor.modifyProcessingOptions, method: modifyProcessingOptions }
            - { name: kernel.event_listener, event: pimcore.document.print.processor.modifyConfig, method: modifyConfig }
```

Implementation of Listener

```php
<?php 
namespace App\EventListener;

class PDFConfigListener
{
    public function modifyProcessingOptions(\Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent $event): void
    {
        $arguments = $event->getArguments();
        $options = $arguments['options'];

        $processor = $event->getProcessor();
        if ($processor instanceof \Pimcore\Bundle\WebToPrintBundle\Processor\PdfReactor) {
            
            //add option to append log into generated PDF (pdf reactor functionality) 
            $options[] = ['name' => 'appendLog', 'type' => 'bool', 'default' => false];
        }

        $arguments['options'] = $options;
        $event->setArguments($arguments);
    }

    public function modifyConfig(\Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent $event): void
    {
        $arguments = $event->getArguments();

        $processor = $event->getProcessor();
        if ($processor instanceof \Pimcore\Bundle\WebToPrintBundle\Processor\PdfReactor) {
            
            //check if option for appending log to PDF is set in configuration and apply it to reactor config accordingly  
            if ($arguments['config']->appendLog == 'true'){
                $arguments['reactorConfig']['appendLog'] = true;
            }
        }

        $event->setArguments($arguments);
    }
}

```


##### Example for adding PDF/X Conformance    

Services in Container see above. 

Implementation of Listener

```php
<?php 
namespace App\EventListener;

class PDFConfigListener
{
    public function modifyProcessingOptions(\Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent $event): void
    {
        //optionally add some configuration options for user interface here - e.g. some select options for user
    }

    public function modifyConfig(\Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent $event): void
    {
        $arguments = $event->getArguments();

        $processor = $event->getProcessor();
        if($processor instanceof \Pimcore\Bundle\WebToPrintBundle\Processor\PdfReactor) {
            
            //Set pdf reactor config for generating PDF/X conform PDF  
            $arguments['reactorConfig']['conformance'] = \Conformance::PDFX4;
            $arguments['reactorConfig']["outputIntent"] = [
                'identifier' => "ISO Coated v2 300% (ECI)",
                'data' => base64_encode(file_get_contents('/path-to-color-profile/ISOcoated_v2_300_eci.icc'))
            ];
        }

        $event->setArguments($arguments);
    }
}

```

## Gotenberg PDF Engines

When using Gotenberg, please also take in consideration https://github.com/gotenberg/gotenberg-php#pdf-format
