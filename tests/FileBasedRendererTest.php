<?php
namespace Mouf\Html\Renderer;

use Cache\Adapter\Void\VoidCachePool;
use Mouf\Html\Renderer\Fixtures\ExtendedFoo;
use Mouf\Html\Renderer\Fixtures\Foo;
use Mouf\Html\Renderer\Fixtures\MyImplementation;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Interop\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Cache\Simple\ArrayCache;

class FileBasedRendererTest extends TestCase
{
    public function testFind()
    {
        $renderer = new FileBasedRenderer(
            'tests/templates',
            new ArrayCache(),
            new class implements ContainerInterface {
                public function get($id)
                {
                    return '';
                }
                public function has($id)
                {
                    return true;
                }
            }
        );

        $canRender = $renderer->canRender(new Foo());
        $this->assertSame(ChainableRendererInterface::CAN_RENDER_CLASS, $canRender);
        $debugCanRender = $renderer->debugCanRender(new Foo());
        $this->assertSame('Testing renderer for directory \'tests/templates\'
  Found file: tests/templates/Mouf/Html/Renderer/Fixtures/Foo.twig
', $debugCanRender);

        // Restart for cache testing
        $canRender = $renderer->canRender(new Foo());
        $this->assertSame(ChainableRendererInterface::CAN_RENDER_CLASS, $canRender);

        // Can use subclass for rendering?
        $canRender = $renderer->canRender(new ExtendedFoo());
        $this->assertSame(ChainableRendererInterface::CAN_RENDER_CLASS, $canRender);

        // Can use interface for rendering?
        $canRender = $renderer->canRender(new MyImplementation());
        $this->assertSame(ChainableRendererInterface::CAN_RENDER_CLASS, $canRender);
        $debugCanRender = $renderer->debugCanRender(new MyImplementation());
        $this->assertSame('Testing renderer for directory \'tests/templates\'
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyImplementation.twig
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyImplementation.php
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyInterface.twig
  Found file: tests/templates/Mouf/Html/Renderer/Fixtures/MyInterface.php
', $debugCanRender);

        $debugCanRender = $renderer->debugCanRender(new MyImplementation(), 'context');
        $this->assertSame('Testing renderer for directory \'tests/templates\'
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyImplementation__context.twig
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyImplementation__context.php
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyImplementation.twig
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyImplementation.php
  Tested file: tests/templates/Mouf/Html/Renderer/Fixtures/MyInterface__context.twig
  Found file: tests/templates/Mouf/Html/Renderer/Fixtures/MyInterface__context.php
', $debugCanRender);


        $debugCanRender = $renderer->debugCanRender(new Foo(), 'context');
        $this->assertSame('Testing renderer for directory \'tests/templates\'
  Found file: tests/templates/Mouf/Html/Renderer/Fixtures/Foo__context.twig
', $debugCanRender);


        $canRender = $renderer->canRender(new \stdClass());
        $this->assertSame(ChainableRendererInterface::CANNOT_RENDER, $canRender);
        ob_start();
        $renderer->render(new Foo());
        $html = ob_get_clean();
        $this->assertSame('Foo', $html);

        ob_start();
        $renderer->render(new Foo(), 'context');
        $html = ob_get_clean();
        $this->assertSame('Foocontext', $html);

        ob_start();
        $renderer->render(new MyImplementation());
        $html = ob_get_clean();
        $this->assertSame('bar', $html);


        $this->expectException(NoTemplateFoundException::class);
        $renderer->render(new \stdClass());
    }
}
