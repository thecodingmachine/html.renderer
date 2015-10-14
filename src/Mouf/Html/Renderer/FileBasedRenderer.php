<?php
/*
 * Copyright (c) 2013 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Html\Renderer;

use Mouf\Utils\Cache\CacheInterface;
use Mouf\MoufException;
use Mouf\Html\Renderer\Twig\MoufTwigExtension;
use Mouf\MoufManager;

/**
 * This class is a renderer that renders objects using a directory containing template files.
 * Each file should be a Twig file or PHP file named after the PHP class full name (respecting the PSR-0 notation). 
 *
 * For instance, the class Mouf\Menu\Item would be rendered by the file Mouf\Menu\Item.twig or Mouf\Menu\Item.php
 * 
 * If a context is passed, it will be appended after a double underscore to the file name.
 * If the file does not exist, we default to the base class.
 * 
 * For instance, the class Mouf\Menu\Item with context "primary" would be rendered by the file Mouf\Menu\Item__primary.twig
 * 
 * If the template for the class is not found, a test through the parents of the class is performed.
 * 
 * If you are using PHP template files, the properties of the object are accessible using local vars.
 * For instance, if your object has a $a property, the property can be accessed using the $a variable.
 * Any property (even private properties can be accessed).
 * The object is accessible using the $object variable. Private methods or properties of the $object cannot
 * be accessed.
 * 
 *
 * @author David Négrier <david@mouf-php.com>
 */
class FileBasedRenderer implements ChainableRendererInterface {

	private $directory;

	private $cacheService;
	
	private $twig;
	
	private $type;

	private $priority;
	
	private $tmpFileName;
	
	private $debugMode;
	
	private $debugStr;

    /**
     * @param string $directory The directory of the templates, relative to the project root. Does not start and does not finish with a /
     * @param CacheInterface $cacheService This service is used to speed up the mapping between the object and the template.
     * @param string $type The type of the renderer. Should be one of "custom", "template" or "package". Defaults to "custom" (see ChainableRendererInterface for more details)
     * @param int $priority The priority of the renderer (within its type)
     * @param \Twig_Environment $twig  The twig environment, which is optional (by default we will use the one from the twig librairy), but we will prefer the Mouf one.
     */
	public function __construct($directory = "src/templates", CacheInterface $cacheService = null, $type = "custom", $priority = 0, \Twig_Environment $twig = null) {
		$this->directory = trim($directory, '/\\');
		$this->cacheService = $cacheService;
		$this->type = $type;
		$this->priority = $priority;

        $loader = new \Twig_Loader_Filesystem(ROOT_PATH.$this->directory);
        $cacheFilesystem = new \Twig_Cache_Filesystem(rtrim(sys_get_temp_dir(),'/\\').'/mouftwigtemplatemain_'.str_replace(":", "", ROOT_PATH).$this->directory);
        if ($twig === null) {

            $this->twig = new \Twig_Environment($loader, array(
                // The cache directory is in the temporary directory and reproduces the path to the directory (to avoid cache conflict between apps).
                'cache' => $cacheFilesystem,
                'auto_reload' => true,
                'debug' => true
            ));
            $this->twig->addExtension(new MoufTwigExtension(MoufManager::getMoufManager()));
            $this->twig->addExtension(new \Twig_Extension_Debug());
        } else {
            // We need to modify the loader of the twig environment.
            // Let's clone it.
            $this->twig = clone $twig;
            $this->twig->setLoader($loader);
            $this->twig->setCache($cacheFilesystem);
            $this->twig->setCompiler(new \Twig_Compiler($this->twig));
        }
	}

	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Html\Renderer\RendererInterface::canRender()
	 */
	public function canRender($object, $context = null) {
		$fileName = $this->getTemplateFileName($object, $context);
		
		if ($fileName) {
			return ChainableRendererInterface::CAN_RENDER_CLASS;
		} else {
			return ChainableRendererInterface::CANNOT_RENDER;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Html\Renderer\ChainableRendererInterface::debugCanRender()
	 */
	public function debugCanRender($object, $context = null) {
		$this->debugMode = true;
		$this->debugStr = "Testing renderer '".MoufManager::getMoufManager()->findInstanceName($this)."'\n";
		
		$this->canRender($object, $context);
		
		$this->debugFalse = true;
		return $this->debugStr;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Html\Renderer\RendererInterface::render()
	 */
	public function render($object, $context = null) {
		$fileName = $this->getTemplateFileName($object, $context);
		
		if ($fileName != false) {
			if ($fileName['type'] == 'twig') {
				if (method_exists($object, 'getPrivateProperties')) {
					$array = $object->getPrivateProperties();
				} else {
					$array = get_object_vars($object);
				}
				if (!isset($array['this'])) {
					$array['this'] = $object;
				}
				echo $this->twig->render($fileName['fileName'], $array);
			} else {
				if (method_exists($object, 'getPrivateProperties')) {
					$array = $object->getPrivateProperties();
				} else {
					$array = get_object_vars($object);
				}
				
				// Let's store the filename into the object ($this) in order to avoid name conflict between
				// the variables.
				$this->tmpFileName = $fileName;
				
				extract($array);
				// Let's create a local variable
				/*foreach ($array as $var__tplt=>$value__tplt) {
					$$var__tplt = $value__tplt;
				}*/
				include ROOT_PATH.$this->directory.'/'.$this->tmpFileName['fileName'];
			}
		} else {
			throw new MoufException("Cannot render object of class ".get_class($object).". No template found.");
		}
	}

	/**
	 * Returns the filename of the template or false if no file found. 
	 * 
	 * @param object $object
	 * @param string $context
	 * @return string|bool
	 */
	private function getTemplateFileName($object, $context = null)  {
		$fullClassName = get_class($object);
		
		// Optimisation: let's see if we already performed the file_exists checks.
		$cacheKey = $fullClassName.'/'.$context;

		$cachedValue = $this->cacheService->get("FileBasedRenderer_".$this->directory.'/'.$cacheKey);
		if ($cachedValue !== null && !$this->debugMode) {
			return $cachedValue;
		}
		
		$baseFileName = str_replace('\\', '/', $fullClassName);
		
		
		$fileName = $this->findFile($fullClassName, $context);
		$parentClass = $fullClassName;
		// If no file is found, let's go through the parents of the object.
		while (true) {
			if ($fileName != false) {
				break;
			}
			$parentClass = get_parent_class($parentClass);
			if ($parentClass == false) {
				break;
			}
			$fileName = $this->findFile($parentClass, $context);
		}
		
		// Still no objects? Let's browse the interfaces.
		if ($fileName == false) {
			$interfaces = class_implements($fullClassName);
			foreach ($interfaces as $interface) {
				$fileName = $this->findFile($interface, $context);
				if ($fileName != false) {
					break;
				}
			}
		}
		
		$this->cacheService->set("FileBasedRenderer_".$this->directory.'/'.$cacheKey, $fileName);
		return $fileName;
	}
	
	private function findFile($className, $context) {
		$baseFileName = str_replace('\\', '/', $className);
		if ($context) {
			if (file_exists(ROOT_PATH.$this->directory.'/'.$baseFileName.'__'.$context.'.twig')) {
				if ($this->debugMode) {
					$this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.twig'."\n";
				}
				return array("fileName"=>$baseFileName.'__'.$context.'.twig',
					"type"=>"twig");
			}
			if ($this->debugMode) {
				$this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.twig'."\n";
			}
				
			if (file_exists(ROOT_PATH.$this->directory.'/'.$baseFileName.'__'.$context.'.php')) {
				if ($this->debugMode) {
					$this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.php'."\n";
				}
				return array("fileName"=>$baseFileName.'__'.$context.'.php',
					"type"=>"php");
			}
			if ($this->debugMode) {
				$this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.php'."\n";
			}
		}
		if (file_exists(ROOT_PATH.$this->directory.'/'.$baseFileName.'.twig')) {
			if ($this->debugMode) {
				$this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'.twig'."\n";
			}
			return array("fileName"=>$baseFileName.'.twig',
					"type"=>"twig");
		}
		if ($this->debugMode) {
			$this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'.twig'."\n";
		}
		
		if (file_exists(ROOT_PATH.$this->directory.'/'.$baseFileName.'.php')) {
			if ($this->debugMode) {
				$this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'.php'."\n";
			}
			return array("fileName"=>$baseFileName.'.php',
					"type"=>"php");
		}
		if ($this->debugMode) {
			$this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'.php'."\n";
		}
		
		return false;
	}

	/* (non-PHPdoc)
	 * @see \Mouf\Html\Renderer\ChainableRendererInterface::getRendererType()
	 */
	public function getRendererType() {
		return $this->type;
	}

	/* (non-PHPdoc)
	 * @see \Mouf\Html\Renderer\ChainableRendererInterface::getPriority()
	 */
	public function getPriority() {
		return $this->priority;
	}

}
