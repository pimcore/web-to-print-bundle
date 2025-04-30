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

namespace Pimcore\Bundle\WebToPrintBundle\Tests\Model\Config;

use Pimcore\Bundle\WebToPrintBundle\Config;
use Pimcore\Tests\Support\Test\ModelTestCase;

class ConfigTest extends ModelTestCase
{
    public function testConfig()
    {
        $config = Config::get();
        $this->assertFalse(isset($config['pdfreactorServer']), 'Check if pdfreactorServer config is undefined');

        $config['pdfreactorServer'] = 'cloud.pdfreactor.com';
        $config['pdfreactorProtocol'] = 'https';
        $config['pdfreactorServerPort'] = '443';
        $config['pdfreactorApiKey'] = '';

        Config::save($config);
        $config = Config::get();
        $this->assertEquals($config['pdfreactorServer'], 'cloud.pdfreactor.com', 'Check if config is saved correctly');
    }
}
