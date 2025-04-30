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

namespace Pimcore\Bundle\WebToPrintBundle;

use Pimcore\Bundle\WebToPrintBundle\DependencyInjection\PimcoreWebToPrintExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class PimcoreWebToPrintBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function getContainerExtension(): ExtensionInterface
    {
        return new PimcoreWebToPrintExtension();
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorewebtoprint/css/icons.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorewebtoprint/js/startup.js',
            '/bundles/pimcorewebtoprint/js/settings.js',
            '/bundles/pimcorewebtoprint/js/document/printabstract.js',
            '/bundles/pimcorewebtoprint/js/document/printcontainer.js',
            '/bundles/pimcorewebtoprint/js/document/printpage.js',
            '/bundles/pimcorewebtoprint/js/document/printpages/pdf_preview.js',
        ];
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
