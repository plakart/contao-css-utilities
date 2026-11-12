<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class PlakartCssUtilitiesBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('preset_file')
                    ->info('CSS class preset (JSON) imported on install; relative paths are resolved against the project directory.')
                    ->defaultValue('files/theme/css-classes.json')
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $presetFile = (string) $config['preset_file'];

        if (!Path::isAbsolute($presetFile)) {
            $presetFile = '%kernel.project_dir%/'.$presetFile;
        }

        $container->parameters()->set('plakart_css_utilities.preset_file', $presetFile);
        $container->import('../config/services.yaml');
    }
}
