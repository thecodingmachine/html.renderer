Renderers for template developers
=================================

If you are developing a template, you might want, just like a package developer to offer
objects using the rendering engine, or you might also want to overload default rendering of some packages
your template is using.

Templates renderer are a bit special. Unlike [package renderers](for_package_developers.md), template renderers
are not automatically picked by the "default" renderer. Instead, they need to be activated manually.
Why? Because you might want to have many templates in the project, and we don't want a template renderer to interfere
with another template renderer.

As a template developer, you will therefore have to **register your renderer** in the default renderer.

Registering your template
-------------------------

If you are developing your own [Mouf template](http://mouf-php.com/packages/mouf/html.template.templateinterface/README.md), 
it is likely you are extending the <code>BaseTemplate</code> class for your template class.
The <code>BaseTemplate</code> class comes with 2 properties:

- **defaultRenderer**: the default renderer (main renderer used by the rendering engine)
- **templateRendererInstanceName**: the container identifier of the instance of the renderer for this template

Basically, in your template instance, you should end up with something looking like this (this is a snapshot from the BootstrapTemplate):

<img src="images/template_instance_snippet.png" alt="" />

Now, when the <code>toHtml()</code> method of your template is called, you should register your template.
To do this, you just need to add one line at the top of your <code>toHtml()</code> method.

```php
public function toHtml(){
	// Let's register the template renderer in the default renderer.
	$this->getDefaultRenderer()->setTemplateRendererInstanceName($this->getTemplateRendererInstanceName());

	// Here goes the rest of your code.
	...	
}
```

Writing the template installer
------------------------------

Most of the time, you will want to create a default template instance when your package is installed.

Here is what you could add in your template installer:


// TODO: fix this with real code from bootstrapTemplate!
```php
// Let's create the template renderer
$bootstrapRenderer = InstallUtils::getOrCreateInstance("bootstrapRenderer", "Mouf\\Html\\Renderer\\FileBasedRenderer", $moufManager);
// Let's set the directory of the renderer
$bootstrapRenderer->getProperty("directory")->setValue("vendor/mygroup/mypackage/src/templates");
// Let's set the cache service
$bootstrapRenderer->getProperty("cacheService")->setValue($moufManager->getInstanceDescriptor("rendererCacheService"));
// This is a "template" renderer
$bootstrapRenderer->getProperty("type")->setValue(ChainableRendererInterface::TYPE_TEMPLATE);
$bootstrapRenderer->getProperty("priority")->setValue(0);

// We assume that $template points to your template's instance descriptor.
// We register your template renderer inside your template.
$template->getProperty("templateRenderer")->setValue($bootstrapRenderer);
// We register the default renderer inside your template.
$template->getProperty("defaultRenderer")->setValue($moufManager->getInstanceDescriptor("defaultRenderer"));
``` 
