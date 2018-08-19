<?php


namespace Mouf\Html\Renderer;


use Interop\Container\ServiceProviderInterface;
use PHPStan\Cache\Cache;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use TheCodingMachine\Funky\Annotations\Factory;
use TheCodingMachine\Funky\Annotations\Tag;
use TheCodingMachine\Funky\ServiceProvider;

/**
 * This class can be extended to implement easily a service provider that creates "package level" renderers.
 */
class RendererServiceProvider extends ServiceProvider
{
    /**
     * @Factory(aliases={RendererInterface::class})
     * @return ChainRenderer
     */
    public static function createChainRenderer(ContainerInterface $container, CacheInterface $cache): ChainRenderer
    {
        return new ChainRenderer($container,
            \iterator_to_array($container->get('customRenderers')),
            \iterator_to_array($container->get('packageRenderers')),
            $cache,
            'chainRenderer');
    }

    /**
     * @Factory(name="customRenderer", tags={@Tag(name="customRenderers")})
     * @return FileBasedRenderer
     */
    public static function createCustomRenderer(ContainerInterface $container, CacheInterface $cache, \Twig_Environment $twig): FileBasedRenderer
    {
        return new FileBasedRenderer(\ComposerLocator::getRootPath().'/src/templates', $cache, $container, $twig);
    }

    /**
     * @Factory(name="packageRenderers")
     * @return \SplPriorityQueue
     */
    public static function createPackageRenderers(): \SplPriorityQueue
    {
        return new \SplPriorityQueue();
    }

    /**
     * @Factory(name="customRenderers")
     * @return \SplPriorityQueue
     */
    public static function createCustomRenderers(): \SplPriorityQueue
    {
        return new \SplPriorityQueue();
    }
}
