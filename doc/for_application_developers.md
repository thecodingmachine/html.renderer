Renderers for application developers
====================================

You are an application developer? Here is what you need to know to use the rendering system efficiently.

The rendering system provides an easy way to render in HTML your objects. This is a 3 steps process:

1. Your class should implement the <code>HtmlElementInterface</code> (optional)
2. Your class should use the <code>Renderable</code> trait
3. You should add a template file in the *src/templates* directory

Then you can render your class using the <code>toHtml()</code> method provided by the <code>Renderable</code> trait.

Here is a minimalistic sample:

```php
namespace MyNamespace;

use Mouf\Html\HtmlElement\HtmlElementInterface;
use Mouf\Html\Renderer\Renderable;

// My class implements the HtmlElementInterface interface
class MyClass implements HtmlElementInterface {
	// My class uses the Renderable trait
	use Renderable;

	public $myValue;
}
```

Now, I need to provide a template file. The template file should be named after the PSR-0 convention and 
placed in the *src/templates* directory. It can have the ".php" or ".twig" extension.

In this example, if we are using Twig as a templating engine, the file name should 
be **src/templates/MyNamespace/MyClass.twig**

```twig
<h1>Sample class</h1>
{{ this.myValue }}
```

We could also use the PHP templating engine. In that case, the file name would  
be **src/templates/MyNamespace/MyClass.php**

```php
<h1>Sample class</h1>
<?php echo $object->myValue ?>
```

About Twig templates
--------------------

[Twig](http://twig.sensiolabs.org/) is a templating engine for PHP. The main reason to use Twig is that Twig
automatically protects you from XSS by escaping HTML strings (unless you tell it not to espace the string). It has 
also a very concise syntax making it pleasant to use.

When you are working with Twig, your object is accessible using the "this" variable.

<table>
	<tr>
		<th>In your class</th>
		<th>In Twig</th>
	</tr>
	<tr>
		<td><code>public $myVar</code></td>
		<td><code>{{ this.myVar }}</code> or <code>{{ myVar }}</code> (public properties can be accessed without the <code>this</code> keyword.</td>
	</tr>
	<tr>
		<td><code>public function getVar2() { ... }</code></td>
		<td><code>{{ this.var2 }}</code> or <code>{{ this.getVar2 }}</code></td>
	</tr>
</table>

Please note you cannot access *protected* or *private* properties.

Autoescaping is enabled by default.
Performance-wise, templates are compiled as PHP files and cached. When a template file is modified, Twig detects the
change and recompiles the template.

About PHP templates
-------------------

Plain PHP files can also be used as template files.
This is way more flexible than Twig, but there are also way more ways to make errors.
The $object variable is a pointer to the object you are rendering.

<table>
	<tr>
		<th>In your class</th>
		<th>In your PHP template</th>
	</tr>
	<tr>
		<td><code>public $myVar</code></td>
		<td><code><?= $myVar ?> or <?= $object->myVar ?> (public properties can be accessed as variables without using the $object variable.</code></td>
	</tr>
	<tr>
		<td><code>public function getVar2() { ... }</code></td>
		<td><code><?= $object->getVar2() ?></code> You can access any method of the object through the <code>$object</code> variable.</td>
	</tr>
</table>

Calling the renderer
--------------------

To call the renderer, you just have to call the <code>toHtml()</code> method.

Here is a sample:

```php
use MyNamespace\MyClass;

$myObject = new MyClass();
$myObject->myValue = 42;
$myObject->toHtml();
```

So this is very easy.

<div class="alert alert-info"><strong>Not working as expected?</strong> The rendering system is heavily relying
on the cache to speed up things. When you create a new template file or delete a template file, you should
purge the cache (the Red "Purge cache" button in Mouf2 UI).</div>

Extending existing renderers
----------------------------

Some packages are templates you are using might also be using renderers. The great news is that you can overload
these renderers with your own renderers very easily.

Let's take a sample. The *mouf/html.widgets.messageservice* package provides a **Mouf\Html\Widgets\MessageService\Widget\RenderedMessage** class that is "renderable".
It represents a warning/error/info message displayed usually at the top of the screen.
Want to change the markup of that message? Easy! Just create your own renderer in *src/template/Mouf/Html/Widgets/MessageService/Widget/RenderedMessage.twig*.

For instance:

```twig
<div class='alert alert-{{ this.userMessage.type|e('html_attr') }}'>
{{ this.userMessage.message|raw }}
{% if this.nbMessages > 1 %}
 <strong>{{ this.nbMessages }}</strong>
{% endif %}
</div>
```

Under the hood
--------------

You might be interested in knowning what is exactly happening when you call the "toHtml()" method of your object.

Here is what is happening:

- The *render* method of the default renderer is called, and your object is passed to it.
- What is the default renderer? It is a Mouf instance whose name is "defaultRenderer"
- What is the default renderer doing? It will chain all the renderers it can find in Mouf, in this order:
	- First, it will look at the developer renderers (by default in the "src/template" directory)
	- Then, if a template has been used, it will look at the template's renderers to find a match
	- Finally, it will try to find a template in the packages installed. A package can also have a renderer.
	  Renderers in the packages can be ordered by priority.
	  
<div class="alert alert-info">When a new renderer is added (for instance when you install a package
that uses its own renderer, do not forget to purge the cache (the Red "Purge cache" button in Mouf2 UI).</div>

Now that we saw how an application developer can use and overload a package template, let's have a look at the 
way things are done [on the package developer side](for_package_developers.md)