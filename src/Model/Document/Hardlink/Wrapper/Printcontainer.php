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

namespace Pimcore\Bundle\WebToPrintBundle\Model\Document\Hardlink\Wrapper;

use Pimcore\Model\Document\Hardlink\Wrapper;

/**
 * @method \Pimcore\Model\Document\Hardlink\Dao getDao()
 */
class Printcontainer extends \Pimcore\Bundle\WebToPrintBundle\Model\Document\Printcontainer implements Wrapper\WrapperInterface
{
    use Wrapper;
}
