<?php
/*
 * Copyright (c) 2013 David Negrier
 *
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Html\Renderer;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * This class is a renderer that renders objects using other renderers.
 * This renderer will automatically detect the renderers to be included.
 * They must extend the ChainableRendererInterface interface.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class ChainRenderer implements CanSetTemplateRendererInterface
{

    /**
     * @var ChainableRendererInterface|null
     */
    private $templateRenderer;
    /**
     * @var ChainableRendererInterface[]
     */
    private $packageRenderers = [];
    /**
     * @var ChainableRendererInterface[]
     */
    private $customRenderers = [];

    private $cacheService;

    private $initDone = false;
    /**
     * @var string[]
     */
    private $customRendererInstanceNames;
    /**
     * @var string
     */
    private $templateRendererInstanceName;
    /**
     * @var string[]
     */
    private $packageRendererInstanceNames;
    /**
     * @var string
     */
    private $uniqueName;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @param string[] $customRendererInstanceNames An array of names of custom renderers (container identifiers)
     * @param string[] $packageRendererInstanceNames An array of names of package renderers (container identifiers)
     * @param CacheInterface $cacheService This service is used to speed up the mapping between the object and the template.
     * @param string $uniqueName The unique name for this instance (used for caching purpose)
     */
    public function __construct(ContainerInterface $container, array $customRendererInstanceNames, array $packageRendererInstanceNames, CacheInterface $cacheService, string $uniqueName)
    {
        $this->container = $container;
        $this->customRendererInstanceNames = $customRendererInstanceNames;
        $this->packageRendererInstanceNames = $packageRendererInstanceNames;
        $this->cacheService = $cacheService;
        $this->uniqueName = $uniqueName;
    }

    /**
     * (non-PHPdoc)
     * @see \Mouf\Html\Renderer\RendererInterface::render()
     */
    public function render($object, string $context = null): void
    {
        $renderer = $this->getRenderer($object, $context);
        if ($renderer == null) {
            throw new NoRendererFoundException("Renderer not found. Unable to find renderer for object of class '".get_class($object)."'. Path tested: ".$this->getRendererDebugMessage($object, $context));
        }
        $renderer->render($object, $context);
    }

    /**
     * @param object $object
     * @param string|null $context
     * @return ChainableRendererInterface|null
     */
    private function getRenderer($object, string $context = null): ?ChainableRendererInterface
    {
        $cacheKey = "chainRendererByClass_".md5($this->uniqueName."/".$this->templateRendererInstanceName."/".get_class($object)."/".$context);

        $cachedInstanceName = $this->cacheService->get($cacheKey);
        if ($cachedInstanceName !== null) {
            return $this->container->get($cachedInstanceName);
        }

        $this->initRenderersList();

        $isCachable = true;
        $foundRenderer = null;
        $source = null;
        $foundInstanceName = null;

        do {
            foreach ($this->customRenderers as $instanceName => $renderer) {
                $result = $renderer->canRender($object, $context);
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CANNOT_RENDER_OBJECT) {
                    $isCachable = false;
                }
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CAN_RENDER_CLASS) {
                    $foundRenderer = $renderer;
                    $foundInstanceName = $instanceName;
                    break 2;
                }
            }

            if ($this->templateRendererInstanceName && !$this->templateRenderer) {
                $this->templateRenderer = $this->container->get($this->templateRendererInstanceName);
            }
            if ($this->templateRenderer) {
                $result = $this->templateRenderer->canRender($object, $context);
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CANNOT_RENDER_OBJECT) {
                    $isCachable = false;
                }
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CAN_RENDER_CLASS) {
                    $foundRenderer = $this->templateRenderer;
                    break;
                }
            }

            foreach ($this->packageRenderers as $instanceName => $renderer) {
                $result = $renderer->canRender($object, $context);
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CANNOT_RENDER_OBJECT) {
                    $isCachable = false;
                }
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CAN_RENDER_CLASS) {
                    $foundRenderer = $renderer;
                    $foundInstanceName = $instanceName;
                    break 2;
                }
            }
        } while (false);

        if ($isCachable && $foundRenderer) {
            $this->cacheService->set($cacheKey, $foundInstanceName);
        }

        return $foundRenderer;
    }

    /**
     * Returns a string explaining the steps done to find the renderer.
     *
     * @param  object $object
     * @param  string $context
     * @return string
     */
    private function getRendererDebugMessage($object, string $context = null): string
    {
        $debugMessage = '';

        $this->initRenderersList();

        do {
            foreach ($this->customRenderers as $renderer) {
                /* @var $renderer ChainableRendererInterface */

                $debugMessage .= $renderer->debugCanRender($object, $context);
                $result = $renderer->canRender($object, $context);
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CAN_RENDER_CLASS) {
                    break 2;
                }
            }

            /* @var $renderer ChainableRendererInterface */
            if ($this->templateRenderer) {
                $debugMessage .= $this->templateRenderer->debugCanRender($object, $context);
                $result = $this->templateRenderer->canRender($object, $context);
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CAN_RENDER_CLASS) {
                    break;
                }
            }

            foreach ($this->packageRenderers as $renderer) {
                /* @var $renderer ChainableRendererInterface */

                $debugMessage .= $renderer->debugCanRender($object, $context);
                $result = $renderer->canRender($object, $context);
                if ($result === ChainableRendererInterface::CAN_RENDER_OBJECT || $result === ChainableRendererInterface::CAN_RENDER_CLASS) {
                    break 2;
                }
            }
        } while (false);
        
        return $debugMessage;
    }
    
    /**
     * Initializes the renderers list (from cache if available)
     */
    private function initRenderersList(): void
    {
        if (!$this->initDone) {
            foreach ($this->customRendererInstanceNames as $instanceName) {
                $this->customRenderers[$instanceName] = $this->container->get($instanceName);
            }
            foreach ($this->packageRendererInstanceNames as $instanceName) {
                $this->packageRenderers[$instanceName] = $this->container->get($instanceName);
            }

            // Note: We ignore template renderers on purpose.
            $this->initDone = true;
        }
    }

    /**
     * Sets the renderer associated to the template.
     * There should be only one if these renderers.
     * It is the role of the template to subscribe to this renderer.
     *
     * @param string $templateRendererInstanceName The name of the template renderer in the container
     */
    public function setTemplateRendererInstanceName(string $templateRendererInstanceName): void
    {
        $this->templateRendererInstanceName = $templateRendererInstanceName;
    }
}
