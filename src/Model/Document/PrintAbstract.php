<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\WebToPrintBundle\Model\Document;

use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Service;
use Pimcore\Model\Tool\TmpStore;

/**
 * @method PrintAbstract\Dao getDao()
 */
abstract class PrintAbstract extends PageSnippet
{
    /**
     * @internal
     *
     */
    protected ?int $lastGenerated = null;

    /**
     * @internal
     *
     */
    protected ?string $lastGenerateMessage = null;

    /**
     * @internal
     *
     */
    protected ?string $controller = 'web2print';

    public function setLastGeneratedDate(\DateTime $lastGenerated): void
    {
        $this->lastGenerated = $lastGenerated->getTimestamp();
    }

    public function getLastGeneratedDate(): ?\DateTime
    {
        if ($this->lastGenerated) {
            $date = new \DateTime();
            $date->setTimestamp($this->lastGenerated);

            return $date;
        }

        return null;
    }

    public function getInProgress(): ?TmpStore
    {
        return TmpStore::get($this->getLockKey());
    }

    public function setLastGenerated(int $lastGenerated): void
    {
        $this->lastGenerated = $lastGenerated;
    }

    public function getLastGenerated(): ?int
    {
        return $this->lastGenerated;
    }

    public function setLastGenerateMessage(string $lastGenerateMessage): void
    {
        $this->lastGenerateMessage = $lastGenerateMessage;
    }

    public function getLastGenerateMessage(): ?string
    {
        return $this->lastGenerateMessage;
    }

    public function generatePdf(array $config): bool
    {
        return Processor::getInstance()->preparePdfGeneration($this->getId(), $config);
    }

    public function renderDocument(array $params): string
    {
        $html = Service::render($this, $params, true);

        return $html;
    }

    public function getPdfFileName(): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-document-' . $this->getId() . '.pdf';
    }

    public function pdfIsDirty(): bool
    {
        return $this->getLastGenerated() < $this->getModificationDate();
    }

    /**
     * @internal
     *
     */
    public function getLockKey(): string
    {
        return 'web2print_pdf_generation_' . $this->getId();
    }
}
