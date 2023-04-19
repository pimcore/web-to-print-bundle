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

use com\realobjects\pdfreactor\webservice\client\ColorSpace;
use com\realobjects\pdfreactor\webservice\client\Encryption;
use com\realobjects\pdfreactor\webservice\client\HttpsMode;
use com\realobjects\pdfreactor\webservice\client\JavaScriptMode;
use com\realobjects\pdfreactor\webservice\client\LogLevel;
use com\realobjects\pdfreactor\webservice\client\ViewerPreferences;
use Pimcore\Bundle\WebToPrintBundle\Config;
use Pimcore\Bundle\WebToPrintBundle\Event\DocumentEvents;
use Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\PrintAbstract;
use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Logger;

class PdfReactor extends Processor
{
    /**
     * returns the default web2print config
     *
     * @param object $config
     *
     * @return array
     */
    protected function getConfig(object $config): array
    {
        $web2PrintConfig = Config::getWeb2PrintConfig();
        $reactorConfig = [
            'document' => '',
            'baseURL' => (string)$web2PrintConfig['pdfreactorBaseUrl'],
            'author' => $config->author ?? '',
            'title' => $config->title ?? '',
            'addLinks' => isset($config->links) && $config->links === true,
            'addBookmarks' => isset($config->bookmarks) && $config->bookmarks === true,
            'javaScriptMode' => $config->javaScriptMode ?? JavaScriptMode::ENABLED,
            'defaultColorSpace' => $config->colorspace ?? ColorSpace::CMYK,
            'encryption' => $config->encryption ?? Encryption::NONE,
            'addTags' => isset($config->tags) && $config->tags === true,
            'logLevel' => $config->loglevel ?? LogLevel::FATAL,
            'enableDebugMode' => $web2PrintConfig['pdfreactorEnableDebugMode'] || (isset($config->enableDebugMode) && $config->enableDebugMode === true),
            'addOverprint' => isset($config->addOverprint) && $config->addOverprint === true,
            'httpsMode' => $web2PrintConfig['pdfreactorEnableLenientHttpsMode'] ? HttpsMode::LENIENT : HttpsMode::STRICT,
        ];
        if (!empty($config->viewerPreference)) {
            $reactorConfig['viewerPreferences'] = [$config->viewerPreference];
        }
        if (trim($web2PrintConfig['pdfreactorLicence'])) {
            $reactorConfig['licenseKey'] = trim($web2PrintConfig['pdfreactorLicence']);
        }

        return $reactorConfig;
    }

    protected function getClient(): \com\realobjects\pdfreactor\webservice\client\PDFreactor
    {
        $web2PrintConfig = Config::getWeb2PrintConfig();
        $this->includeApi();

        $port = ($web2PrintConfig['pdfreactorServerPort']) ? (string)$web2PrintConfig['pdfreactorServerPort'] : '9423';
        $protocol = ($web2PrintConfig['pdfreactorProtocol']) ? (string)$web2PrintConfig['pdfreactorProtocol'] : 'http';

        $pdfreactor = new \com\realobjects\pdfreactor\webservice\client\PDFreactor($protocol . '://' . $web2PrintConfig['pdfreactorServer'] . ':' . $port . '/service/rest');

        if (trim($web2PrintConfig['pdfreactorApiKey'])) {
            $pdfreactor->apiKey = trim($web2PrintConfig['pdfreactorApiKey']);
        }

        return $pdfreactor;
    }

    /**
     * @internal
     */
    public function getPdfFromString(string $html, array $params = [], bool $returnFilePath = false): string
    {
        $pdfreactor = $this->getClient();

        $customConfig = (array)($params['adapterConfig'] ?? []);
        $reactorConfig = $this->getConfig((object)$customConfig);

        if (!array_keys($customConfig, 'addLinks')) {
            $customConfig['addLinks'] = true;
        }

        $reactorConfig = array_merge($reactorConfig, $customConfig); //add additional configs

        $reactorConfig['document'] = $this->processHtml($html, $params); //temporary disabled for tests
        $pdf = $pdfreactor->convert($reactorConfig);
        $pdf = base64_decode($pdf->document);
        if (!$returnFilePath) {
            return $pdf;
        } else {
            $dstFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . uniqid('web2print_') . '.pdf';
            file_put_contents($dstFile, $pdf);

            return $dstFile;
        }
    }

    /**
     * @internal
     */
    protected function buildPdf(PrintAbstract $document, object $config): string
    {
        $this->includeApi();

        $params = [];
        $params['printermarks'] = isset($config->printermarks) && $config->printermarks === true;
        $params['screenResolutionImages'] = isset($config->screenResolutionImages) && $config->screenResolutionImages === true;
        $params['colorspace'] = $config->colorspace ?? ColorSpace::CMYK;

        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);
        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        ini_set('default_socket_timeout', '3000');

        $pdfreactor = $this->getClient();

        $reactorConfig = $this->getConfig($config);
        $params['hostUrl'] = $reactorConfig['baseURL'] ?? null;
        $reactorConfig['document'] = $this->processHtml($html, $params);

        $event = new PrintConfigEvent($this, ['config' => $config, 'reactorConfig' => $reactorConfig, 'document' => $document]);
        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_CONFIG);

        $reactorConfig = $event->getArguments()['reactorConfig'];

        $progress = new \stdClass();
        $progress->finished = false;

        $connectionSettings = [];
        $processId = $pdfreactor->convertAsync($reactorConfig, $connectionSettings);

        while (!$progress->finished) {
            $progress = $pdfreactor->getProgress($processId, $connectionSettings);
            $this->updateStatus($document->getId(), 50 + (int)($progress->progress / 2), 'pdf_conversion');

            Logger::info('PDF converting progress: ' . $progress->progress . '%');
            sleep(2);
        }

        $this->updateStatus($document->getId(), 100, 'saving_pdf_document');
        $result = $pdfreactor->getDocument($processId, $connectionSettings);

        return base64_decode($result->document);
    }

    /**
     * @internal
     */
    public function getProcessingOptions(): array
    {
        $this->includeApi();

        $options = [];

        $options[] = ['name' => 'author', 'type' => 'text', 'default' => ''];
        $options[] = ['name' => 'title', 'type' => 'text', 'default' => ''];
        $options[] = ['name' => 'printermarks', 'type' => 'bool', 'default' => false];
        $options[] = ['name' => 'addOverprint', 'type' => 'bool', 'default' => false];
        $options[] = ['name' => 'links', 'type' => 'bool', 'default' => true];
        $options[] = ['name' => 'bookmarks', 'type' => 'bool', 'default' => true];
        $options[] = ['name' => 'tags', 'type' => 'bool', 'default' => true];
        $options[] = [
            'name' => 'javaScriptMode',
            'type' => 'select',
            'values' => [JavaScriptMode::ENABLED, JavaScriptMode::DISABLED, JavaScriptMode::ENABLED_NO_LAYOUT],
            'default' => JavaScriptMode::ENABLED,
        ];

        $options[] = [
            'name' => 'viewerPreference',
            'type' => 'select',
            'values' => [ViewerPreferences::PAGE_LAYOUT_SINGLE_PAGE, ViewerPreferences::PAGE_LAYOUT_TWO_COLUMN_LEFT, ViewerPreferences::PAGE_LAYOUT_TWO_COLUMN_RIGHT],
            'default' => ViewerPreferences::PAGE_LAYOUT_SINGLE_PAGE,
        ];

        $options[] = [
            'name' => 'colorspace',
            'type' => 'select',
            'values' => [ColorSpace::CMYK, ColorSpace::RGB],
            'default' => ColorSpace::CMYK,
        ];

        $options[] = [
            'name' => 'encryption',
            'type' => 'select',
            'values' => [Encryption::NONE, Encryption::TYPE_40, Encryption::TYPE_128],
            'default' => Encryption::NONE,
        ];

        $options[] = [
            'name' => 'loglevel',
            'type' => 'select',
            'values' => [LogLevel::FATAL, LogLevel::WARN, LogLevel::INFO, LogLevel::DEBUG, LogLevel::PERFORMANCE],
            'default' => LogLevel::FATAL,
        ];

        $options[] = ['name' => 'enableDebugMode', 'type' => 'bool', 'default' => false];

        $event = new PrintConfigEvent($this, [
            'options' => $options,
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_OPTIONS);

        return (array)$event->getArguments()['options'];
    }

    protected function includeApi(): void
    {
        include_once(__DIR__ . '/Api/PDFreactor.class.php');
    }
}
