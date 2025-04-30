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

namespace Pimcore\Bundle\WebToPrintBundle\Tests\Model\Document;

use Pimcore\Bundle\WebToPrintBundle\Model\Document\Printcontainer;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\Printpage;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Helper\Pimcore;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Class DocumentTest
 *
 * @package Pimcore\Tests\Model\Document
 *
 * @group model.document.document
 */
class DocumentTest extends ModelTestCase
{
    protected ?Printcontainer $testPrintContainer = null;

    protected ?Printpage $testprintPage = null;

    public function testPrintContainer(): void
    {
        // create
        $document = new Printcontainer();
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setKey(uniqid('', true) . rand(10, 99));
        $document->save();

        $document = Document::getById($document->getId());
        $this->assertInstanceOf(Printcontainer::class, $document);
    }

    public function testPrintPage(): void
    {
        // create
        $document = new Printpage();
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setKey(uniqid('', true) . rand(10, 99));
        $document->save();

        $document = Document::getById($document->getId());
        $this->assertInstanceOf(Printpage::class, $document);
    }
}
