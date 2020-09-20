<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $root = new TreeBuilder('mailer_extra');
        $root->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('mjml')
                ->canBeEnabled()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('renderer')
                        ->cannotBeEmpty()
                        ->defaultValue('local://%kernel.project_dir%')
                    ->end()
                ->end()
            ->end();

        return $root;
    }
}
