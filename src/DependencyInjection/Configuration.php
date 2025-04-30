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

namespace Pimcore\Bundle\WebToPrintBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_web_to_print');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->scalarNode('pdf_creation_php_memory_limit')
                    ->defaultValue('2048M')
                ->end()
                ->scalarNode('default_controller_print_page')
                    ->defaultValue('App\\Controller\\Web2printController::defaultAction')
                ->end()
                ->scalarNode('default_controller_print_container')
                    ->defaultValue('App\\Controller\\Web2printController::containerAction')
                ->end()
                ->booleanNode('enableInDefaultView')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('generalTool')
                    ->defaultValue('')
                ->end()
                ->scalarNode('generalDocumentSaveMode')->end()
                ->scalarNode('pdfreactorVersion')->end()
                ->scalarNode('pdfreactorProtocol')->end()
                ->scalarNode('pdfreactorServer')->end()
                ->scalarNode('pdfreactorServerPort')->end()
                ->scalarNode('pdfreactorBaseUrl')->end()
                ->scalarNode('pdfreactorApiKey')->end()
                ->scalarNode('pdfreactorLicence')->end()
                ->booleanNode('pdfreactorEnableLenientHttpsMode')->end()
                ->booleanNode('pdfreactorEnableDebugMode')->end()
                ->scalarNode('gotenbergHostUrl')->end()
                ->scalarNode('gotenbergSettings')->end()
            ->end();

        ConfigurationHelper::addConfigLocationWithWriteTargetNodes($rootNode, ['web_to_print' => '/var/config/web_to_print'], ['read_target']);

        return $treeBuilder;
    }
}
