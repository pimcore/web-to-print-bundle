<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\WebToPrintBundle\Processor;

use Gotenberg\Gotenberg as GotenbergAPI;
use Gotenberg\Stream;
use Pimcore\Bundle\WebToPrintBundle\Config;
use Pimcore\Bundle\WebToPrintBundle\Event\DocumentEvents;
use Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\PrintAbstract;
use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Logger;
use function Symfony\Component\String\s;

class Gotenberg extends Processor
{
    /**
     * @internal
     */
    protected function buildPdf(PrintAbstract $document, object $config): string
    {
        $web2printConfig = Config::getWeb2PrintConfig();
        $gotenbergSettings = $web2printConfig['gotenbergSettings'];
        $gotenbergSettings = json_decode($gotenbergSettings, true);

        $params = ['document' => $document];
        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);

        $params['hostUrl'] = 'http://nginx:80';
        if (isset($web2printConfig['gotenbergHostUrl'])) {
            $params['hostUrl'] = $web2printConfig['gotenbergHostUrl'];
        }

        $params['processor'] = $this;

        $html = $this->processHtml($html, $params);
        [$assets, $html] = $this->handleAssets($html);
        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        if ($gotenbergSettings) {
            foreach (['header', 'footer'] as $item) {
                if (key_exists($item, $gotenbergSettings) && $gotenbergSettings[$item] &&
                    file_exists($gotenbergSettings[$item])) {
                    $gotenbergSettings[$item . 'Template'] = $gotenbergSettings[$item];
                }
                unset($gotenbergSettings[$item]);
            }
        }

        if (empty($assets) === false) {
            $gotenbergSettings['assets'] = $assets;
        }

        try {
            $this->updateStatus($document->getId(), 50, 'pdf_conversion');
            $pdf = $this->getPdfFromString($html, $gotenbergSettings ?? []);
            $this->updateStatus($document->getId(), 100, 'saving_pdf_document');
        } catch (\Exception $e) {
            Logger::error((string) $e);
            $document->setLastGenerateMessage($e->getMessage());

            throw new \Exception('Error during PDF-Generation:' . $e->getMessage());
        }

        $document->setLastGenerateMessage('');

        return $pdf;
    }

    /**
     * @internal
     */
    public function getProcessingOptions(): array
    {
        $event = new PrintConfigEvent($this, [
            'options' => [],
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_OPTIONS);

        return (array)$event->getArgument('options');
    }

    /**
     * @internal
     */
    public function getPdfFromString(string $html, array $params = [], bool $returnFilePath = false): string
    {
        $params = $params ?: $this->getDefaultOptions();

        $assets = $params['assets'] ?? [];

        unset($params['assets']);

        $event = new PrintConfigEvent($this, [
            'params' => $params,
            'html' => $html,
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_CONFIG);

        ['html' => $html, 'params' => $params] = $event->getArguments();

        $tempFileName = uniqid('web2print_');

        $chromium = GotenbergAPI::chromium(\Pimcore\Config::getSystemConfiguration('gotenberg')['base_url']);

        $options = [
            'printBackground', 'landscape', 'preferCssPageSize', 'omitBackground', 'emulatePrintMediaType',
            'emulateScreenMediaType',
        ];

        foreach ($options as $option) {
            if (isset($params[$option]) && $params[$option] != false) {
                $chromium->$option();
            }
        }

        if ($params['marginTop'] ?? $params['marginBottom'] ?? $params['marginLeft'] ?? isset($params['marginRight'])) {
            $chromium->margins(
                $params['marginTop'] ?? 0.39,
                $params['marginBottom'] ?? 0.39,
                $params['marginLeft'] ?? 0.39,
                $params['marginRight'] ?? 0.39
            );
        }

        if (isset($params['scale'])) {
            $chromium->scale($params['scale']);
        }

        if (isset($params['nativePageRanges'])) {
            $chromium->nativePageRanges($params['nativePageRanges']);
        }

        foreach (['header', 'footer'] as $item) {
            if (isset($params[$item . 'Template'])) {
                $chromium->$item(Stream::path($params[$item . 'Template']));
            }
        }

        if ($params['paperWidth'] ?? isset($params['paperHeight'])) {
            $chromium->paperSize($params['paperWidth'] ?? 8.5, $params['paperHeight'] ?? 11);
        }

        if (isset($params['userAgent'])) {
            $chromium->userAgent($params['userAgent']);
        }

        if (isset($params['extraHttpHeaders'])) {
            $chromium->extraHttpHeaders($params['extraHttpHeaders']);
        }

        if (isset($params['pdfFormat'])) {
            $chromium->pdfFormat($params['pdfFormat']);
        }

        if (empty($assets) === false) {
            $assetStreams = [];

            foreach ($assets as $asset) {
                $assetStreams[] = Stream::path($asset['path'], $asset['filename']);
            }

            $chromium->assets(...$assetStreams);
        }

        $request = $chromium->outputFilename($tempFileName)->html(Stream::string('processor.html', $html));

        if ($returnFilePath) {
            $filename = GotenbergAPI::save($request, PIMCORE_SYSTEM_TEMP_DIRECTORY);

            return PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $filename;
        }
        $response = GotenbergAPI::send($request);

        return $response->getBody()->getContents();
    }

    private function getDefaultOptions(): array
    {
        return [
            //'paperWidth',
            //'paperHeight',
            //'marginTop',
            //'marginBottom',
            //'marginLeft',
            //'marginRight',
            //'preferCssPageSize',
            'printBackground' => true,
            //'omitBackground',
            'landscape' => false,
            //'scale' => 1,
            //'nativePageRanges',
            //'emulatePrintMediaType',
            //'emulateScreenMediaType',
            //'userAgent',
            //'extraHttpHeaders' => [],
            //'pdfFormat',
        ];
    }

    private function handleAssets(string $html): array
    {
        $assets = [];

        preg_match_all("@(href|src)\s*=[\"']([^(http|mailto|javascript|data:|#)].*?(css|jpe?g|gif|png)?)[\"']@is", $html, $matches);
        if (empty($matches[0]) === false) {
            foreach ($matches[0] as $key => $value) {
                $path = $matches[2][$key];

                if (s($path)->containsAny(['http://', 'https://', '//', 'file://'])) {
                    continue;
                }

                $subPath = '/var/assets';

                if (s($path)->containsAny(['css', 'js', 'ttf']) === true) {
                    $subPath = '';
                }

                if (s($path)->containsAny('image-thumb') === true) {
                    $subPath = '/var/tmp/thumbnails';
                }

                $localFilePath = sprintf(
                    '%s%s%s',
                    PIMCORE_WEB_ROOT,
                    $subPath,
                    $path
                );
                $fileName = basename($localFilePath);

                $assets[] = [
                    'path' => urldecode($localFilePath),
                    'filename' => urldecode($fileName),
                ];

                $path = preg_quote($path, '!');
                $html = preg_replace(
                    "!([\"'])$path([\"'])!is",
                    '\\1' . $fileName . '\\2',
                    $html
                );
            }
        }

        preg_match_all("@srcset\s*=[\"'](.*?)[\"']@is", $html, $matches);
        foreach ($matches[1] as $i => $value) {
            $parts = explode(',', $value);

            foreach ($parts as $key => $v) {
                $v = trim($v);

                if (s($v)->containsAny(['http://', 'https://', '//', 'file://'])) {
                    continue;
                }

                $subPath = '/var/assets';

                if (s($v)->containsAny(['css', 'js']) === true) {
                    $subPath = '';
                }

                if (s($v)->containsAny('image-thumb') === true) {
                    $subPath = '/var/tmp/thumbnails';
                }

                $localFilePath = sprintf(
                    '%s%s%s',
                    PIMCORE_WEB_ROOT,
                    $subPath,
                    $v
                );
                $fileName = basename($localFilePath);

                $assets[] = [
                    'path' => str_replace([' 1x', ' 2x', '@2x'], '', urldecode($localFilePath)),
                    'filename' => urldecode($fileName),
                ];

                $parts[$key] = $fileName;
            }

            $srcSet = sprintf(
                ' srcset="%s" ',
                implode(', ', $parts)
            );

            if ($matches[0][$i]) {
                $html = str_replace($matches[0][$i], $srcSet, $html);
            }
        }

        return [
            $assets,
            $html,
        ];
    }
}
