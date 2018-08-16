<?php
/*
 * Copyright (c) 2013 David Negrier
 *
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Html\Renderer;

use Psr\Container\ContainerInterface;
use Mouf\MoufException;
use Mouf\Html\Renderer\Twig\MoufTwigExtension;
use Mouf\MoufManager;
use Psr\SimpleCache\CacheInterface;

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
class FileBasedRenderer implements ChainableRendererInterface
{

    private $directory;

    private $cacheService;

    private $twig;

	private $tmpFileName;
	
	private $debugMode;
	
	private $debugStr;
	
	/**
	 * 
	 * @param string $directory The directory of the templates, relative to the project root. Does not start and does not finish with a /
	 * @param CacheInterface $cacheService This service is used to speed up the mapping between the object and the template.
	 * @param string $type The type of the renderer. Should be one of "custom", "template" or "package". Defaults to "custom" (see ChainableRendererInterface for more details)
	 * @param number $priority The priority of the renderer (within its type)
	 */
    public function __construct(string $directory, CacheInterface $cacheService, ContainerInterface $container, Twig $twig = null)
    {
        $this->directory = $directory;
        $this->cacheService = $cacheService;

        $loader = new \Twig_Loader_Filesystem(ROOT_PATH.$this->directory);
        if (function_exists('posix_geteuid')) {
            $posixGetuid = posix_geteuid();
        } else {
            $posixGetuid = '';
        }
        $cacheFilesystem = new \Twig_Cache_Filesystem(rtrim(sys_get_temp_dir(),'/\\').'/mouftwigtemplatemain_'.$posixGetuid.'_'.str_replace(":", "", ROOT_PATH).$this->directory);
        if ($twig === null) {

            $this->twig = new \Twig_Environment($loader, array(
                // The cache directory is in the temporary directory and reproduces the path to the directory (to avoid cache conflict between apps).
                'cache' => $cacheFilesystem,
                'auto_reload' => true,
                'debug' => true
            ));
            $this->twig->addExtension(new MoufTwigExtension($container));
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
    public function canRender($object, $context = null)
    {
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
    public function debugCanRender($object, $context = null)
    {
        $this->debugMode = true;
        $this->debugStr = "Testing renderer for directory '".$this->directory."'\n";

        $this->canRender($object, $context);

        $this->debugMode = false;

        return $this->debugStr;
    }

    /**
     * (non-PHPdoc)
     * @see \Mouf\Html\Renderer\RendererInterface::render()
     */
    public function render($object, $context = null)
    {
        $fileName = $this->getTemplateFileName($object, $context);

        if ($fileName != false) {
            if (method_exists($object, 'getPrivateProperties')) {
                $array = $object->getPrivateProperties();
            } else {
                $array = get_object_vars($object);
            }
            if ($fileName['type'] == 'twig') {
                if (!isset($array['this'])) {
                    $array['this'] = $object;
                }
                echo $this->twig->render($fileName['fileName'], $array);
            } else {
                // Let's store the filename into the object ($this) in order to avoid name conflict between
                // the variables.
                $this->tmpFileName = $fileName;

                extract($array);
                // Let's create a local variable
                /*foreach ($array as $var__tplt=>$value__tplt) {
                    $$var__tplt = $value__tplt;
                }*/
                include $this->directory.'/'.$this->tmpFileName['fileName'];
            }
        } else {
            throw new NoTemplateFoundException("Cannot render object of class ".get_class($object).". No template found.");
        }
    }

    /**
     * Returns the filename of the template or false if no file found.
     *
     * @param  object      $object
     * @param  string      $context
     * @return array<string,string>|null An array with 2 keys: "filename" and "type", or null if nothing found
     */
    private function getTemplateFileName($object, ?string $context = null): ?array
    {
        $fullClassName = get_class($object);

        // Optimisation: let's see if we already performed the file_exists checks.
        $cacheKey = md5("FileBasedRenderer_".$this->directory.'/'.$fullClassName.'/'.$context);

        $cachedValue = $this->cacheService->get($cacheKey);
        if ($cachedValue !== null && !$this->debugMode) {
            return $cachedValue;
        }

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

        $this->cacheService->set($cacheKey, $fileName);

        return $fileName;
    }

    /**
     * @param string $className
     * @param null|string $context
     * @return array<string,string>|null An array with 2 keys: "filename" and "type", or null if nothing found
     */
    private function findFile(string $className, ?string $context): ?array
    {
        $baseFileName = str_replace('\\', '/', $className);
        if ($context) {
            if (file_exists($this->directory.'/'.$baseFileName.'__'.$context.'.twig')) {
                if ($this->debugMode) {
                    $this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.twig'."\n";
                }

                return array("fileName" => $baseFileName.'__'.$context.'.twig',
                    "type" => "twig", );
            }
            if ($this->debugMode) {
                $this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.twig'."\n";
            }

            if (file_exists($this->directory.'/'.$baseFileName.'__'.$context.'.php')) {
                if ($this->debugMode) {
                    $this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.php'."\n";
                }

                return array("fileName" => $baseFileName.'__'.$context.'.php',
                    "type" => "php", );
            }
            if ($this->debugMode) {
                $this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'__'.$context.'.php'."\n";
            }
        }
        if (file_exists($this->directory.'/'.$baseFileName.'.twig')) {
            if ($this->debugMode) {
                $this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'.twig'."\n";
            }

            return array("fileName" => $baseFileName.'.twig',
                    "type" => "twig", );
        }
        if ($this->debugMode) {
            $this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'.twig'."\n";
        }

        if (file_exists($this->directory.'/'.$baseFileName.'.php')) {
            if ($this->debugMode) {
                $this->debugStr .= "  Found file: ".$this->directory.'/'.$baseFileName.'.php'."\n";
            }

            return array("fileName" => $baseFileName.'.php',
                    "type" => "php", );
        }
        if ($this->debugMode) {
            $this->debugStr .= "  Tested file: ".$this->directory.'/'.$baseFileName.'.php'."\n";
        }

        return null;
    }
}
