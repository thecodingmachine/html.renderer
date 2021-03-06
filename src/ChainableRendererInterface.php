<?php
/*
 * Copyright (c) 2013 David Negrier
 *
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Html\Renderer;

/**
 * Every object extending this interface can render other objects in HTML and can be
 * automatically picked for chaining by the AutoChainRenderer
 *
 * @author David Négrier <david@mouf-php.com>
 */
interface ChainableRendererInterface extends RendererInterface
{

    const TYPE_CUSTOM = "custom";
    const TYPE_TEMPLATE = "template";
    const TYPE_PACKAGE = "package";

    const CAN_RENDER_CLASS = 3;
    const CAN_RENDER_OBJECT = 2;
    const CANNOT_RENDER_OBJECT = 1;
    const CANNOT_RENDER = 0;

    /**
     * Tests if this render knows how to deal with this object in the particular context passed.
     *
     * Returns RendererInterface::CAN_RENDER_CLASS if this renderer can render this class (whatever object is passed)
     * Returns RendererInterface::CAN_RENDER_OBJECT if this renderer can render this particular object (but might not render any object from this class)
     * Returns RendererInterface::CANNOT_RENDER if this renderer does not know how to render this object
     * Returns RendererInterface::CANNOT_RENDER_OBJECT if this renderer does not know how to render this object, but could render another object from the same class.
     *
     * @param  object $object
     * @param  string $context
     * @return int
     */
    public function canRender($object, string $context = null): int;

    /**
     * Returns a string explaining the steps taken to find a particular template.
     * The function goes through the steps used by "canRender" and returns a string explaining what was tested.
     *
     * @param  object $object
     * @param  string $context
     * @return string
     */
    public function debugCanRender($object, string $context = null): string;
}
