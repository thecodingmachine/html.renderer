Mouf's Twig extensions
======================

The rendering engine comes with a set of Twig extensions.

toHtml function
---------------

In your Twig templates, you can use the **toHtml** function to call the **toHtml()** method of any object
implementing the **HtmlElementInterface** interface.

Here is a sample use case:

```twig
{{ toHtml(this.element) }}
```

If your object is a Mouf instance, you can also pass the instance name to "toHtml".
For instance:

```twig
{{ toHtml("myInstanceName") }}
```

Note that using an instance name is not the best practice, as you are 
tying your template to the dependency injection controller.

val function
------------

In your Twig templates, you can use the **val** function to call the **val()** method of any object
[implementing the **ValueInterface** interface](http://mouf-php.com/packages/mouf/utils.value.value-interface/README.md).

```twig
{% for row in val(this.myRequest) %}
	#doStuff
{% endfor %}
```

mouf function
-------------

In your Twig templates, you can use the **mouf** function to retrieve an instance from the dependency injection controller.
This is not a best practice, but can still be useful some times.

