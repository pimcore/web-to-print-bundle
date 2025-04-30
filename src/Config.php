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

use Pimcore\Cache\RuntimeCache;
use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Model\Exception\ConfigWriteException;

/**
 * @internal
 */
final class Config
{
    private const CONFIG_ID = 'web_to_print';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    private static function getRepository(): LocationAwareConfigRepository
    {
        if (!self::$locationAwareConfigRepository) {
            $config = [];
            $containerConfig = \Pimcore::getContainer()->getParameter('pimcore_web_to_print');
            if ($containerConfig['generalTool']) {
                $config = [
                    self::CONFIG_ID => $containerConfig,
                ];
            }

            $storageConfig = $containerConfig['config_location'][self::CONFIG_ID];

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'pimcore_web_to_print',
                $storageConfig
            );
        }

        return self::$locationAwareConfigRepository;
    }

    /**
     *
     * @throws \Exception
     */
    public static function isWriteable(): bool
    {
        return self::getRepository()->isWriteable();
    }

    public static function get(): array
    {
        $repository = self::getRepository();

        $config = $repository->loadConfigByKey(self::CONFIG_ID);

        return $config[0] ?? [];
    }

    /**
     *
     * @throws \Exception
     */
    public static function save(array $data): void
    {
        $repository = self::getRepository();

        unset($data['pdf_creation_php_memory_limit']);
        unset($data['default_controller_print_page']);
        unset($data['default_controller_print_container']);

        if (!$repository->isWriteable()) {
            throw new ConfigWriteException();
        }

        $repository->saveConfig(self::CONFIG_ID, $data, function ($key, $data) {
            return [
                'pimcore_web_to_print' => $data,
            ];
        });
    }

    /**
     * @static
     *
     * @internal
     */
    public static function getWeb2PrintConfig(): array
    {
        if (RuntimeCache::isRegistered('pimcore_bundle_web2print_config')) {
            $config = RuntimeCache::get('pimcore_bundle_web2print_config');
        } else {
            $config = self::get();
            self::setWeb2PrintConfig($config);
        }

        return $config;
    }

    /**
     * @static
     *
     * @internal
     */
    public static function setWeb2PrintConfig(array $config): void
    {
        RuntimeCache::set('pimcore_bundle_web2print_config', $config);
    }
}
