Mouf renderer: Advanced topics
==============================

###I have several ways to render an object. How can I pass this to the renderer?

The default <code>toHtml()</code> will always call the same renderer for your object.
However, you might want to use different renderers for the same object.

To do this, you have to stop using the <code>toHtml()</code> utility method, and directly
call the renderer.
The renderer supports the notion of "context".

```php
$myObject = new MyObject();

// Let's get the default renderer
$defaultRenderer = Mouf::getDefaultRenderer();

// Let's render your object with a special context
$defaultRenderer->render($myObject, "mycontext");
```

As you can see, the second argument is the **context**. It has to be a string, that can be used by
the renderers to pick another renderer.

In this exemple, the rendering engine will try to search for these files:

- MyObject_mycontext.twig
- MyObject_mycontext.php
- MyObject.twig
- MyObject.php

As you can see, the renderer will append the context to the name of the class and see if a template
with this name exists.

###Will a template be used for children class?
**Yes**. If your class does not have a renderer, the rendering engine will try to find a renderer in your
parent class. 

###Can a template be applied to an interface?
**Yes**. If your class does not have a renderer, and none of its parent class has a renderer, the rendering 
engine will try to find a renderer in the interfaces. This means you can have renderers based
on interfaces. If you have a menu item representes by the **MenuInterface** interface, you can
have a **MenuInterface.twig** template file.

###I don't want to use neither PHP nor Twig, I want my own rendering engine!
You can implement your own rendering engine.

The html.renderer package comes with 2 packages:

- **FileBasedRenderer**: the class in charge of rendering the PHP and Twig templates
- **AutoChainRenderer**: the class used by the *default renderer*. Its sole purpose is to
  detect other renderers and call them sequentially.

The **AutoChainRenderer** will automatically detect any instance whose implemnts the **ChainableRendererInterface**
interface. If you want to provide your own renderer (Smarty for instance), you just have to
create a class implementing the **ChainableRendererInterface** and create an instance of it.
Do not forget to purge your cache if you want your renderer to be detected by the "default renderer"!