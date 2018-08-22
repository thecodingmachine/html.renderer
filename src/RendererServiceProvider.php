<?php


namespace Mouf\Html\Renderer;

use TheCodingMachine\MiddlewareListServiceProvider;
use TheCodingMachine\MiddlewareOrder;
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
        return new ChainRenderer(
            $container,
            \iterator_to_array($container->get('customRenderers')),
            \iterator_to_array($container->get('packageRenderers')),
            $cache,
            'chainRenderer'
        );
    }

    /**
     * @Factory(name="customRenderer")
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
        $queue = new \SplPriorityQueue();
        $queue->insert('customRenderer', 0);
        return $queue;
    }

    /**
     * @Factory(tags={@Tag(name=MiddlewareListServiceProvider::MIDDLEWARES_QUEUE, priority=MiddlewareOrder::UTILITY_EARLY)})
     */
    public static function createMiddleware(RendererInterface $renderer): InitRendererFacadeMiddleware
    {
        return new InitRendererFacadeMiddleware($renderer);
    }
}
