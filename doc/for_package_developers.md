Renderers for package developers
================================

If you are developing a package, and if you have items in your package that might be rendered in HTML, you
should definitely consider using the rendering system. Indeed, it is a simple way to allow your package
users to [overload your template](for_application_developers.md), if they need to.

For this, you need to provide a Mouf installer for your package, that will be in charge of creating the renderer's Mouf
instance. Hopefully, this is fairly easy to do.

First, if you are not used to writing Mouf install scripts for your packages, have a look at 
the [Mouf package installer documentation](http://mouf-php.com/packages/mouf/mouf/doc/install_process.md).

If you are used to install script
---------------------------------

The only thing you need to know is that you should put this line in your installer script:

```php
use Mouf\Html\Renderer\RendererUtils;

RendererUtils::createPackageRenderer($moufManager, "group/package_name");
```

This will create a renderer instance automatically. Your templates should go in the *src/templates* directory of 
your package.

If you are not used to install scripts
--------------------------------------

Here is a more detailed version of what you should do.

First, let's start by creating an install script in **src/install.php**

```php
require_once __DIR__."/../../../autoload.php";

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;
use Mouf\Html\Renderer\RendererUtils;

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

$moufManager = MoufManager::getMoufManager();

// Let's create the renderer
RendererUtils::createPackageRenderer($moufManager, "group/package_name");

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
```

You will need to replace the "group/package_name" string with the name of your package.

Finally, we must edit the **composer.json** file of your package and add this:

```json
{
    ....
    "extra": {
        "mouf": {
            "install": [
                {
                "type": "file",
                "file": "src/install.php"
                }
            ]
        }
    }
}
```

This will register our installer.
For your installer to be detected, you will have to commit/push your changes, and run <code>php composer.phar update</code>
so that Composer can detect your new install file.

Then, each time you install your package, Mouf will propose an installation step in the Mouf UI that will
create the renderer installer.

Once you have done this, you can use the renderer in your package, [just like a normal application developer would do](for_application_developers.md), e.g.:

1. Implement the <code>HtmlElementInterface</code> (optional)
2. Use the <code>Renderable</code> trait
3. Add a template file in the *src/templates* directory

Now that we saw how a package developer can provide templates for the application developers, let's have a look at the 
way things are done [on the template developer side](for_template_developers.md)
