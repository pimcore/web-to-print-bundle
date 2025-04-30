<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\WebToPrintBundle\Model\Document\Printcontainer;

use Pimcore\Bundle\WebToPrintBundle\Model\Document\PrintAbstract;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\Printcontainer;
use Pimcore\Db\Helper;

/**
 * @internal
 *
 * @property Printcontainer $model
 */
class Dao extends PrintAbstract\Dao
{
    public function getLastedChildModificationDate(): string
    {
        $path = $this->model->getFullPath();

        return $this->db->fetchOne('SELECT modificationDate FROM documents WHERE `path` like ? ORDER BY modificationDate DESC LIMIT 0,1', [Helper::escapeLike($path) . '%']);
    }
}
