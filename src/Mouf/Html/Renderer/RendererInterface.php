<?php
/*
 * Copyright (c) 2013 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Html\Renderer;

/**
 * Every object extending this interface can render other objects in HTML.
 *
 * @author David Négrier <david@mouf-php.com>
 */
interface RendererInterface {

	const CAN_RENDER_CLASS = 1;
	const CAN_RENDER_OBJECT = 2;
	const CANNOT_RENDER = 0;
	
	/**
	 * Tests if this render knows how to deal with this object in the particular context passed.
	 * 
	 * Returns RendererInterface::CAN_RENDER_CLASS if this renderer can render this class (whatever object is passed)
	 * Returns RendererInterface::CAN_RENDER_OBJECT if this renderer can render this particular object (but might not render any object from this class)
	 * Returns RendererInterface::CANNOT_RENDER if this renderer does not know how to render this object
	 * 
	 * @param object $object
	 * @param string $context
	 * @return int
	 */
	function canRender($object, $context = null);
	
	/**
	 * Renders the object as a HTML string, to the output.
	 * 
	 * @param object $object The object to render
	 * @param string $context A string representing a context that might be used to choose another renderer for the object.
	 */
	function render($object, $context = null);
}
