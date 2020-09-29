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

        // @phpstan-ignore-next-line
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
                    ->arrayNode('aws_lambda_client_options')
                        ->addDefaultsIfNotSet()
                        ->ignoreExtraKeys()
                        ->children()
                            ->scalarNode('region')->defaultValue('us-west-1')->end()
                            ->scalarNode('version')->defaultValue('2015-03-31')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $root;
    }
}
