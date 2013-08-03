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
	
	/**
	 * 
	 * @param string $directory The directory of the templates, relative to the project root. Does not start and does not finish with a /
	 * @param CacheInterface $cacheService This service is used to speed up the mapping between the object and the template.
	 * @param string $type The type of the renderer. Should be one of "custom", "template" or "package". Defaults to "custom" (see ChainableRendererInterface for more details)
	 * @param number $priority The priority of the renderer (within its type)
	 */
	public function __construct($directory = "src/templates", CacheInterface $cacheService = null, $type = "custom", $priority = 0) {
		$this->directory = trim($directory, '/\\');
		$this->cacheService = $cacheService;
		$this->type = $type;
		$this->priority = $priority;
		
		$loader = new \Twig_Loader_Filesystem(ROOT_PATH.$this->directory);
		$this->twig = new \Twig_Environment($loader, array(
				// The cache directory is in the temporary directory and reproduces the path to the directory (to avoid cache conflict between apps).
				'cache' => rtrim(sys_get_temp_dir().'/\\').'/mouftwigtemplate'.ROOT_PATH.$this->directory,
				'auto_reload' => true
		));
		$this->twig->addExtension(new MoufTwigExtension(MoufManager::getMoufManager()));
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
	 * @see \Mouf\Html\Renderer\RendererInterface::render()
	 */
	public function render($object, $context = null) {
		$fileName = $this->getTemplateFileName($object, $context);
		
		if ($fileName != false) {
			if ($fileName['type'] == 'twig') {
				$array = get_object_vars($object);
				if (!isset($array['this'])) {
					$array['this'] = $object;
				}
				echo $this->twig->render($fileName['fileName'], $array);
			} else {
				// Let's create a local variable
				foreach (get_object_vars($object) as $var__tplt=>$value__tplt) {
					$$var__tplt = $value__tplt;
				}
				include ROOT_PATH.$this->directory.'/'.$fileName['fileName'];
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
		if ($cachedValue !== null) {
			return $cachedValue;
		}
		
		$fileName = false;
		
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
				return array("fileName"=>$baseFileName.'__'.$context.'.twig',
					"type"=>"twig");
			} elseif (file_exists(ROOT_PATH.$this->directory.'/'.$baseFileName.'__'.$context.'.php')) {
				return array("fileName"=>$baseFileName.'__'.$context.'.php',
					"type"=>"php");
			}
		}
		if (file_exists(ROOT_PATH.$this->directory.'/'.$baseFileName.'.twig')) {
			return array("fileName"=>$baseFileName.'.twig',
					"type"=>"twig");
		} elseif (file_exists(ROOT_PATH.$this->directory.'/'.$baseFileName.'.php')) {
			return array("fileName"=>$baseFileName.'.php',
					"type"=>"php");
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
