What is this package?
=====================

This package contains the rendering mechanism used in Mouf to **render objects in HTML**.

For application developers
--------------------------
You are an application developer? [Learn how to use the rendering system](doc/for_application_developers.md) with PHP files or Twig templates
to render your objects or overload renderers provided by packages.

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
