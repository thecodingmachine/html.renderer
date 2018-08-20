[![Latest Stable Version](https://poser.pugx.org/mouf/html.renderer/v/stable.svg)](https://packagist.org/packages/mouf/html.renderer)
[![Total Downloads](https://poser.pugx.org/mouf/html.renderer/downloads.svg)](https://packagist.org/packages/mouf/html.renderer)
[![Latest Unstable Version](https://poser.pugx.org/mouf/html.renderer/v/unstable)](https://packagist.org/packages/mouf/html.renderer)
[![License](https://poser.pugx.org/mouf/html.renderer/license)](https://packagist.org/packages/mouf/html.renderer)
[![Build Status](https://travis-ci.org/thecodingmachine/html.renderer.svg?branch=2.0)](https://travis-ci.org/thecodingmachine/html.renderer)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/html.renderer/badge.svg?branch=2.0&service=github)](https://coveralls.io/github/thecodingmachine/html.renderer?branch=2.0)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/html.renderer/badges/quality-score.png?b=2.0)](https://scrutinizer-ci.com/g/thecodingmachine/html.renderer/?branch=2.0)

TODO! CHANGE THE DOC FOR 2.0
TALK ABOUT SERVICE PROVIDER

What is this package?
=====================

This package contains a rendering mechanism to **render objects in HTML**.

For application developers
--------------------------

You are an application developer? [Learn how to use the rendering system](doc/for_application_developers.md) with PHP files or Twig templates
to render your objects or overload renderers provided by packages.

See the video!

<iframe width="480" height="360" src="//www.youtube.com/embed/f2MyYSUic1U" frameborder="0" allowfullscreen></iframe>

For package developers
----------------------

You are a package developer? [Learn how to use the rendering system to allow other users to overload
your renderers easily.](doc/for_package_developers.md)

Installation
------------

```bash
$ composer require mouf/html.renderer ^2
```

The easiest way to use this package is through a dependency injection container compatible with [container-interop/service-providers](https://github.com/container-interop/service-provider).

Once installed, you need to register the [`Mouf\Html\Renderer\RendererServiceProvider`](src/RendererServiceProvider.php) into your container.

If your container supports [thecodingmachine/discovery](https://github.com/thecodingmachine/discovery) integration, you have nothing to do. Otherwise, refer to your framework or container's documentation to learn how to register *service providers*.


### Provided services

This *service provider* provides the following services:

| Service name                | Description                          |
|-----------------------------|--------------------------------------|
| `RendererInterface::class`  | The default renderer. An alias to `ChainRenderer::class` |
| `ChainRenderer::class`  | A composite renderer asking all other renderers in turn if they can render an object. |
| `customRenderers`  | A list of `RendererInterface` objects at the "custom" level (top most level). This list is consumed by the `ChainRenderer` |
| `customRenderer`  | A default custom renderer is provided by this package. Out of the box, template files are expected to be in the `src/templates` directory. |
| `packageRenderers`  | A list of `RendererInterface` objects at the "package" level (bottom level). This list is consumed by the `ChainRenderer`. When a package you install has a renderer, it will add the renderer to this list (most of the time using the `AbstractPackageRendererServiceProvider` |
| `InitRendererFacadeMiddleware::class`  | A PSR-15 middleware that is used to initialize global access to the default renderer. This is needed for the `Renderable` trait to work. |

### Extended services

This *service provider* extends those services:

| Name                        | Compulsory | Description                            |
|-----------------------------|------------|----------------------------------------|
| `MiddlewareListServiceProvider::MIDDLEWARES_QUEUE`              | *yes*      | The `InitRendererFacadeMiddleware::class` registers itself in the list of PSR-15 middlewares. |


Mouf package
------------

This package was originally part of Mouf (http://mouf-php.com), an effort to ensure good developing practices by providing a graphical dependency injection framework.

V2 makes the package framework-agnostic, so it can be used in any framework.

Basically, you will find in this package some **Renderers**. These are classes in charge of rendering other objects.
They usually rely on *template files*, that contain the HTML to be rendered.
Renderers can be *chained*, and the first renderer that knows how to render an object will be in charge of the rendering.

Troubleshooting
---------------

Your template or a custom template is not applied.

* Purge the cache with the red button in mouf.
* You use Ajax and you return html with echo (for example BCE).
	* By default an echo don't apply the template to make it
		* add the defaultRenderer (a class of Mouf\Html\Renderer\AutoChainRenderer) in your class
		* add your templateRenderer (a class of Mouf\Html\Renderer\FileBasedRenderer) in Bootstrap this is bootstrapRenderer
		* add the code before your call to the function toHTML: $this->defaultRenderer->setTemplateRenderer($this->templateRenderer);
