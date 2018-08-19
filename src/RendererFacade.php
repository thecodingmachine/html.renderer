<?php


namespace Mouf\Html\Renderer;

/**
 * A facade for the default renderer.
 * Used by the `Renderable` trait.
 * Initialized by the InitRendererFacadeMiddleware
 */
final class RendererFacade
{
    /**
     * @var RendererInterface
     */
    private static $renderer;

    public static function init(RendererInterface $renderer): void
    {
        self::$renderer = $renderer;
    }

    /**
     * Renders the object as a HTML string, to the output.
     *
     * @param object $object  The object to render
     * @param string|null $context A string representing a context that might be used to choose another renderer for the object.
     */
    public static function render($object, string $context = null): void
    {
        self::$renderer->render($object, $context);
    }
}
