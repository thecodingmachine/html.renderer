What is this package?
=====================

This package contains the rendering mechanism used in Mouf to render objects in HTML.
Basically, you will find in this package some **Renderers**. These are classes in charge of rendering other objects.
They usually rely on *template files*, that contain the HTML to be rendered.
Renderers can be *chained*, and the first renderer that knows how to render an object will be in charge of the rendering.


Mouf package
------------

This package is part of Mouf (http://mouf-php.com), an effort to ensure good developing practices by providing a graphical dependency injection framework.
