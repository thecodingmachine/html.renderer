<?php
/*
 * Copyright (c) 2013 David Negrier
 *
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Html\Renderer;

/**
 * Classes implementing this interface are renderer system that can accept an additional renderer
 * (for the template renderer)
 *
 * @author David Négrier <david@mouf-php.com>
 */
interface CanSetTemplateRendererInterface extends RendererInterface
{
    /**
     * Sets the renderer associated to the template.
     * There should be only one if these renderers.
     * It is the role of the template to subscribe to this renderer.
     *
     * @param RendererInterface $templateRenderer
     */
    public function setTemplateRenderer(RendererInterface $templateRenderer);
}
