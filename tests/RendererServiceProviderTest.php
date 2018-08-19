<?php

namespace Mouf\Html\Renderer;

use PHPUnit\Framework\TestCase;
use Simplex\Container;
use TheCodingMachine\SymfonyCacheServiceProvider;
use TheCodingMachine\TwigServiceProvider;

class RendererServiceProviderTest extends TestCase
{
    public function testServiceProvider()
    {
        $packageRendererServiceProvider1 = new class extends AbstractPackageRendererServiceProvider {
            public static function getTemplateDirectory(): string
            {
                return 'tests/templates';
            }
        };
        $packageRendererServiceProvider2 = new class extends AbstractPackageRendererServiceProvider {
            public static function getTemplateDirectory(): string
            {
                return 'tests/templateTemplates';
            }

            public static function getPriority(): int
            {
                return 1;
            }
        };

        $container = new Container([$packageRendererServiceProvider1, $packageRendererServiceProvider2, new RendererServiceProvider(), new SymfonyCacheServiceProvider(), new TwigServiceProvider()]);

        $renderersInstanceNames = $container->get('packageRenderers');
        $this->assertSame([1=>'packageRenderer_tests/templateTemplates', 0=>'packageRenderer_tests/templates'], \iterator_to_array($renderersInstanceNames));

        $renderer = $container->get(RendererInterface::class);
    }
}
