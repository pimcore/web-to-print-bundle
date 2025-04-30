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

namespace Pimcore\Bundle\WebToPrintBundle\Twig\Extension;

use Pimcore\Bundle\WebToPrintBundle\Model\Document\PrintAbstract;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\Printcontainer;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\Printpage;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * @internal
 */
class DocumentHelperExtensions extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('pimcore_document_print', static function ($object) {
                return $object instanceof PrintAbstract;
            }),
            new TwigTest('pimcore_document_print_container', static function ($object) {
                return $object instanceof Printcontainer;
            }),
            new TwigTest('pimcore_document_print_page', static function ($object) {
                return $object instanceof Printpage;
            }),
        ];
    }
}
