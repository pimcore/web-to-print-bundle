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

namespace Pimcore\Bundle\WebToPrintBundle\Event\Model;

use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class PrintConfigEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Processor $processor;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(Processor $processor, array $arguments = [])
    {
        $this->processor = $processor;
        $this->arguments = $arguments;
    }

    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    /**
     * @return $this
     */
    public function setProcessor(Processor $processor): static
    {
        $this->processor = $processor;

        return $this;
    }
}
