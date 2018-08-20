Renderers for package developers
================================

If you are developing a package, and if you have items in your package that might be rendered in HTML, you
should definitely consider using the rendering system. Indeed, it is a simple way to allow your package
users to [overload your template](for_application_developers.md), if they need to.

For this, you need to provide a service-provider for your package, that will be in charge of registering the renderer's
instance into your container. Hopefully, this is fairly easy to do.

First, if you are not used at writing universal service providers, have a look at 
the [container-interop/service-provider documentation](https://github.com/container-interop/service-provider).

Creating a service provider for the renderer
--------------------------------------------

Your package needs to create a renderer and register it in the container, and let the `ChainRenderer` it exists.

This package provides a simple abstract class you can extend to create a service provider that will do the registration for you.

Here is a sample:

```php
namespace My\Package;

use Mouf\Html\Renderer\AbstractPackageRendererServiceProvider;

class MyPackageRendererServiceProvider extends AbstractPackageRendererServiceProvider {
    public static function getTemplateDirectory(): string
    {
        // Here, return the path to the templates directory of your package.
        return __DIR__.'/templates';
    }
};
```

Providing auto-discovery for this service provider
--------------------------------------------------

For users using the `thecodingmachine/discovery` discovery system, you can register your service provider:

**discovery.json**
```json
{
  "Interop\\Container\\ServiceProviderInterface": "My\\Package\\MyPackageRendererServiceProvider"
}
```


Once you have done this, you can use the renderer in your package, [just like a normal application developer would do](for_application_developers.md), e.g.:

1. Implement the <code>HtmlElementInterface</code> (optional)
2. Use the <code>Renderable</code> trait
3. Add a template file in the *src/templates* directory

Now that we saw how a package developer can provide templates for the application developers, let's have a look at the 
way things are done [on the template developer side](for_template_developers.md)
