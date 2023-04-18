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

namespace Pimcore\Bundle\WebToPrintBundle\Tests\Model\Config;

use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Bundle\WebToPrintBundle\Config;

class ConfigTest extends ModelTestCase
{
    public function testConfig(){
        $config = Config::get();
        $this->assertFalse(isset($config['pdfreactorServer']),  'Check if pdfreactorServer config is undefined');

        $config['pdfreactorServer'] = 'cloud.pdfreactor.com';
        $config['pdfreactorProtocol'] = 'https';
        $config['pdfreactorServerPort'] = '443';
        $config['pdfreactorApiKey'] = '';

        Config::save($config);
        $config = Config::get();
        $this->assertEquals($config['pdfreactorServer'], 'cloud.pdfreactor.com', 'Check if config is saved correctly');
    }
}
