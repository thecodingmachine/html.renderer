<?php


namespace Mouf\Html\Renderer;


use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * This class can be extended to implement easily a service provider that creates "package level" renderers.
 */
abstract class AbstractPackageRendererServiceProvider implements ServiceProviderInterface
{
    /**
     * Returns the path to the templates directory.
     *
     * @return string
     */
    abstract public static function getTemplateDirectory(): string;

    public static function getPriority(): int
    {
        return 0;
    }

    /**
     * Returns a list of all container entries registered by this service provider.
     *
     * - the key is the entry name
     * - the value is a callable that will return the entry, aka the **factory**
     *
     * Factories have the following signature:
     *        function(\Psr\Container\ContainerInterface $container)
     *
     * @return callable[]
     */
    public function getFactories()
    {
        return [
            'packageRenderer_'.static::getTemplateDirectory() => [static::class, 'createRenderer']
        ];
    }

    public static function createRenderer(ContainerInterface $container): FileBasedRenderer
    {
        return new FileBasedRenderer(static::getTemplateDirectory(), $container->get(CacheInterface::class), $container, $container->get(\Twig_Environment::class));
    }

    /**
     * Returns a list of all container entries extended by this service provider.
     *
     * - the key is the entry name
     * - the value is a callable that will return the modified entry
     *
     * Callables have the following signature:
     *        function(Psr\Container\ContainerInterface $container, $previous)
     *     or function(Psr\Container\ContainerInterface $container, $previous = null)
     *
     * About factories parameters:
     *
     * - the container (instance of `Psr\Container\ContainerInterface`)
     * - the entry to be extended. If the entry to be extended does not exist and the parameter is nullable, `null` will be passed.
     *
     * @return callable[]
     */
    public function getExtensions()
    {
        return [
            'packageRenderers' => [static::class, 'extendPackageRenderersList']
        ];
    }

    public static function extendPackageRenderersList(ContainerInterface $container, \SplPriorityQueue $priorityQueue): \SplPriorityQueue
    {
        $priorityQueue->insert('packageRenderer_'.static::getTemplateDirectory(), static::getPriority());
        return $priorityQueue;
    }
}
