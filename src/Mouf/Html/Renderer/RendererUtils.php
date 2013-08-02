<?php 
namespace Mouf\Html\Renderer;

use Mouf\MoufManager;
use Mouf\Html\Renderer\ChainableRendererInterface;
/**
 * A set of utility functions related to renderers
 * 
 * @author David Négrier <david@mouf-php.com>
 */
class RendererUtils {
	
	/**
	 * Creates a package renderer instance for the package $packageName.
	 * Use this utility method in the package installer.
	 * The created package renderer will be a FileBasedRenderer with the template
	 * directory being "[package_dir]/src/templates".
	 * 
	 * @param MoufManager $moufManager
	 * @param string $packageName The name of the Composer package (e.g. mygroup/mypackage)
	 * @param number $priority Defaults to 0
	 */
	public static function createPackageRenderer(MoufManager $moufManager, $packageName, $priority = 0) {
		if (!$moufManager->instanceExists("packageRenderer_".$packageName)) {
			$renderer = $moufManager->createInstance("Mouf\\Html\\Renderer\\FileBasedRenderer");
			$renderer->setName("packageRenderer_".$packageName);
			$renderer->getProperty("directory")->setValue("vendor/".$packageName."/src/templates");
			$renderer->getProperty("cacheService")->setValue($moufManager->getInstanceDescriptor("rendererCacheService"));
			$renderer->getProperty("type")->setValue(ChainableRendererInterface::TYPE_PACKAGE);
			$renderer->getProperty("priority")->setValue($priority);
		}
	}
}