<?php

namespace Mouf\Html\Renderer;

use Mouf\Html\Renderer\Fixtures\Foo;
use PHPUnit\Framework\TestCase;
use Simplex\Container;
use Symfony\Component\Cache\Simple\ArrayCache;

class ChainRendererTest extends TestCase
{
    public function testFind()
    {
        $container = new Container();

        $container->set('customRenderer1', function() use ($container) {
            return new FileBasedRenderer(
                'tests/customTemplates',
                new ArrayCache(),
                $container
            );
        });
        $container->set('packageRenderer1', function() use ($container) {
            return new FileBasedRenderer(
                'tests/templates',
                new ArrayCache(),
                $container
            );
        });
        $container->set('templateRenderer', function() use ($container) {
            return new FileBasedRenderer(
                'tests/templateTemplates',
                new ArrayCache(),
                $container
            );
        });

        $chainRenderer = new ChainRenderer($container, ['customRenderer1'], ['packageRenderer1'], new ArrayCache(), 'uniqueName');

        ob_start();
        $chainRenderer->render(new Foo());
        $html = ob_get_clean();
        $this->assertSame('Foo', $html);

        // Same test, for cache testing
        ob_start();
        $chainRenderer->render(new Foo());
        $html = ob_get_clean();
        $this->assertSame('Foo', $html);

        $chainRenderer->setTemplateRendererInstanceName('templateRenderer');

        ob_start();
        $chainRenderer->render(new Foo());
        $html = ob_get_clean();
        $this->assertSame('FooTemplate', $html);


    }
}
