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

namespace Pimcore\Bundle\WebToPrintBundle\Tests\Model\Document;

use Pimcore\Bundle\WebToPrintBundle\Model\Document\Printcontainer;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\Printpage;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

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

        $this->assertInstanceOf(Printcontainer::class, $document);

        $this->testPrintContainer = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Bundle\\WebToPrintBundle\\Model\\Document\\Printcontainer');
        $this->assertInstanceOf(Printcontainer::class, $this->testPrintContainer);

        $this->testPrintContainer = Printcontainer::getById($this->testPrintContainer->getId(), ['force' => true]);
        $this->assertInstanceOf(Printcontainer::class, $this->testPrintContainer);

        // change controller
        $controllerTest = 'App\Controller\Web2printController::defaultAction';
        $this->testPrintContainer->setController($controllerTest);
        $this->testPrintContainer->save();

        $this->testPrintContainer = Printcontainer::getById($this->testPrintContainer->getId(), ['force' => true]);
        $this->assertEquals($controllerTest, $this->testPrintContainer->getController());
    }
    public function testPrintPage(): void
    {
        // create
        $this->testprintPage = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Bundle\\WebToPrintBundle\\Model\\Document\\Printpage');
        $this->assertInstanceOf(PrintPage::class, $this->testprintPage);

        $this->testprintPage = PrintPage::getById($this->testprintPage->getId(), ['force' => true]);
        $this->assertInstanceOf(PrintPage::class, $this->testprintPage);

        // change controller
        $controllerTest = 'App\Controller\Web2printController::defaultAction';
        $this->testprintPage->setController($controllerTest);
        $this->testprintPage->save();

        $this->testprintPage = PrintPage::getById($this->testprintPage->getId(), ['force' => true]);
        $this->assertEquals($controllerTest, $this->testprintPage->getController());
    }
}
