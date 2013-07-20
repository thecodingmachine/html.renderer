<?php
/*
 * Copyright (c) 2013 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Html\Renderer;

use Mouf\Utils\Cache\CacheInterface;

/**
 * This class is a renderer that renders objects using a directory containing template files.
 * Each file should be a Twig file named after the PHP class full name (respecting the PSR-0 notation). 
 *
 * For instance, the class Mouf\Menu\Item would be rendered by the file Mouf\Menu\Item.twig
 * 
 * If a context is passed, it will be appended after a double underscore to the file name.
 * If the file does not exist, we default to the base class.
 * 
 * For instance, the class Mouf\Menu\Item with context "primary" would be rendered by the file Mouf\Menu\Item__primary.twig
 * 
 * If the template for the class is not found, a test through the parents of the class is performed.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class DefaultRenderer implements RendererInterface {

	private $directory;
	
	/**
	 * A local copy of the mapping between class/context and the renderer file name.
	 * @var array<string,string>
	 */
	private $cache = array();

	private $cacheService;	

	/**
	 * 
	 * @param string $directory The directory of the templates, relative to the project root. Does not start and does not finish with a /
	 * @param CacheInterface $cacheService This service is used to speed up the mapping between the object and the template.
	 */
	public function __construct($directory = "src/templates", CacheInterface $cacheService) {
		$this->directory = trim($directory, '/\\');
		$this->cacheService = $cacheService;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Html\Renderer\RendererInterface::canRender()
	 */
	public function canRender($object, $context = null) {
		$fileName = $this->getTemplateFileName($object, $context);
		
		if ($fileName) {
			return RendererInterface::CAN_RENDER_CLASS;
		} else {
			return RendererInterface::CANNOT_RENDER;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Html\Renderer\RendererInterface::render()
	 */
	public function render($object, $context = null) {
		$fileName = $this->getTemplateFileName($object, $context);
		

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
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		} else {
			$cachedValue = $this->cacheService->get($this->cacheService->set("defaultRenderer_".$this->directory.'/'.$cacheKey));
			if ($cachedValue !== null) {
				return $cachedValue;
			}
		}
		
		$baseFileName = ROOT_PATH.$this->directory.'/'.str_replace('\\', '/', $fullClassName);
		
		if ($context) {
			if (file_exists($baseFileName.'__'.$context.'.twig')) {
				$this->cache[$cacheKey] = $baseFileName.'__'.$context.'.twig';
				$this->cacheService->set("defaultRenderer_".$this->directory.'/'.$cacheKey, $baseFileName.'__'.$context.'.twig');
				return $this->cache[$cacheKey];
			}
		}
		if (file_exists($baseFileName.'.twig')) {
			$this->cache[$cacheKey] = $baseFileName.'.twig';
			$this->cacheService->set("defaultRenderer_".$this->directory.'/'.$cacheKey, $baseFileName.'.twig');
			return $this->cache[$cacheKey];
		}
		$this->cache[$cacheKey] = false;
		$this->cacheService->set("defaultRenderer_".$this->directory.'/'.$cacheKey, false);
		return false;
	}
}
