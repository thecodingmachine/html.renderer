<?php


namespace Mouf\Html\Renderer;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Injects the renderer passed in parameter of the constructor into the RendererFacade, making this
 * renderer the default renderer of the whole application, accessible globally through the RendererFacade.
 * Ugly.
 */
class InitRendererFacadeMiddleware implements MiddlewareInterface
{
    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        RendererFacade::init($this->renderer);
        return $handler->handle($request);
    }
}
