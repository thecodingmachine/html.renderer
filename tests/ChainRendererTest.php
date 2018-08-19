<?php

namespace Mouf\Html\Renderer;

use Mouf\Html\Renderer\Fixtures\Foo;
use Mouf\Html\Renderer\Fixtures\MyImplementation;
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

        $this->expectException(NoRendererFoundException::class);
        $this->expectExceptionMessage('Renderer not found. Unable to find renderer for object of class \'stdClass\'. Path tested: Testing renderer for directory \'tests/customTemplates\'
  Tested file: tests/customTemplates/stdClass__context.twig
  Tested file: tests/customTemplates/stdClass__context.php
  Tested file: tests/customTemplates/stdClass.twig
  Tested file: tests/customTemplates/stdClass.php
Testing renderer for directory \'tests/templateTemplates\'
  Tested file: tests/templateTemplates/stdClass__context.twig
  Tested file: tests/templateTemplates/stdClass__context.php
  Tested file: tests/templateTemplates/stdClass.twig
  Tested file: tests/templateTemplates/stdClass.php
Testing renderer for directory \'tests/templates\'
  Tested file: tests/templates/stdClass__context.twig
  Tested file: tests/templates/stdClass__context.php
  Tested file: tests/templates/stdClass.twig
  Tested file: tests/templates/stdClass.php
');
        $chainRenderer->render(new \stdClass(), 'context');

    }
}
