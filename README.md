[![Latest Stable Version](https://poser.pugx.org/mouf/html.renderer/v/stable.svg)](https://packagist.org/packages/mouf/html.renderer)
[![Total Downloads](https://poser.pugx.org/mouf/html.renderer/downloads.svg)](https://packagist.org/packages/mouf/html.renderer)
[![Latest Unstable Version](https://poser.pugx.org/mouf/html.renderer/v/unstable)](https://packagist.org/packages/mouf/html.renderer)
[![License](https://poser.pugx.org/mouf/html.renderer/license)](https://packagist.org/packages/mouf/html.renderer)
[![Build Status](https://travis-ci.org/mouf/html.renderer.svg?branch=2.0)](https://travis-ci.org/mouf/html.renderer)
[![Coverage Status](https://coveralls.io/repos/mouf/html.renderer/badge.svg?branch=2.0&service=github)](https://coveralls.io/github/mouf/html.renderer?branch=2.0)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/html.renderer/badges/quality-score.png?b=2.0)](https://scrutinizer-ci.com/g/thecodingmachine/html.renderer/?branch=2.0)

What is this package?
=====================

This package contains the rendering mechanism used in Mouf to **render objects in HTML**.

For application developers
--------------------------
You are an application developer? [Learn how to use the rendering system](doc/for_application_developers.md) with PHP files or Twig templates
to render your objects or overload renderers provided by packages.

See the video!

<iframe width="480" height="360" src="//www.youtube.com/embed/f2MyYSUic1U" frameborder="0" allowfullscreen></iframe>

For package developers
--------------------------------
You are a package developer? Learn how to use the rendering system to allow other users to overload
your renderers easily.


Mouf package
------------

This package is part of Mouf (http://mouf-php.com), an effort to ensure good developing practices by providing a graphical dependency injection framework.

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
