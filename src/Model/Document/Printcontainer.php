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

use Pimcore\Model\Document;

/**
 * @method Printcontainer\Dao getDao()
 */
class Printcontainer extends PrintAbstract
{
    protected string $type = 'printcontainer';

    /**
     * @internal
     *
     */
    protected string $action = 'container';

    private array $allChildren = [];

    /**
     *
     * @internal
     */
    public function getTreeNodeConfig(): array
    {
        $tmpDocument = [];
        $tmpDocument['leaf'] = false;
        $tmpDocument['expanded'] = !$this->hasChildren();
        $tmpDocument['iconCls'] = 'pimcore_icon_printcontainer';
        $tmpDocument['permissions'] = [
            'view' => $this->isAllowed('view'),
            'remove' => $this->isAllowed('delete'),
            'settings' => $this->isAllowed('settings'),
            'rename' => $this->isAllowed('rename'),
            'publish' => $this->isAllowed('publish'),
            'create' => $this->isAllowed('create'),
        ];

        return $tmpDocument;
    }

    public function getAllChildren(): array
    {
        $this->allChildren = [];
        $this->doGetChildren($this);

        return $this->allChildren;
    }

    private function doGetChildren(Document $document): void
    {
        $children = $document->getChildren();
        foreach ($children as $child) {
            if ($child instanceof Printpage) {
                $this->allChildren[] = $child;
            }

            if ($child instanceof Document\Folder || $child instanceof Printcontainer) {
                $this->doGetChildren($child);
            }

            if ($child instanceof Document\Hardlink) {
                if ($child->getSourceDocument() instanceof Printpage) {
                    $this->allChildren[] = $child;
                }

                $this->doGetChildren($child);
            }
        }
    }

    public function pdfIsDirty(): bool
    {
        $dirty = parent::pdfIsDirty();
        if (!$dirty) {
            $dirty = ($this->getLastGenerated() < $this->getDao()->getLastedChildModificationDate());
        }

        return $dirty;
    }
}
