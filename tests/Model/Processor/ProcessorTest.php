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

namespace Pimcore\Bundle\WebToPrintBundle\Tests\Model\Processor;

use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Bundle\WebToPrintBundle\Processor\Chromium;
use Pimcore\Bundle\WebToPrintBundle\Processor\Gotenberg;
use Pimcore\Bundle\WebToPrintBundle\Processor\PdfReactor;
use Pimcore\Logger;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Console;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessorTest extends ModelTestCase
{

    public function testGotenberg()
    {
        $this->checkProcessors('Gotenberg', []);
    }
    public function testChromium()
    {
        $this->checkProcessors('Chromium', []);
    }

    public function testPdfReactor()
    {
        $pdfReactorConfig = [
            'adapterConfig' => [
                'javaScriptMode' => 0,
                'addLinks' => true,
                'appendLog' => true,
                'enableDebugMode' => true
            ]
        ];
        $this->checkProcessors('PdfReactor', $pdfReactorConfig);
    }

    public function checkProcessors(string $processorName, array $config): void
    {

        $processorClass = 'Pimcore\Bundle\WebToPrintBundle\Processor\\'.$processorName;
        $processor = new $processorClass();
        $pdfContent = $this->getPDFfromProcessor($processor, $config);

        $file = tmpfile();
        $tempMetadata = stream_get_meta_data($file);
        $tempPath = $tempMetadata['uri'];
        file_put_contents($tempPath, $pdfContent);
        $pdfInfo = $this->getPDFInfo($tempPath);
        $this->debug($pdfInfo);
    }

    private function getPDFfromProcessor(Processor $processor, array $config): string
    {
        $html =  file_get_contents(__DIR__.'/../../Support/Resources/test_web2print.html.twig');
        return $processor->getPdfFromString($html, $config);
    }

    private function getPDFInfo(string $assetPath): string
    {
        try {
            $cmd = [$this->getPdfInfoCli()];
            array_push($cmd, $assetPath, '-');
            Console::addLowProcessPriority($cmd);
            $process = new Process($cmd);
            $process->setTimeout(120);
            $process->mustRun();

            return $process->getOutput();
        } catch (ProcessFailedException $e) {
            Logger::debug($e->getMessage());
        }
    }
    private function getPdfInfoCli(): string
    {
        return Console::getExecutable('pdfinfo', true);
    }
}
