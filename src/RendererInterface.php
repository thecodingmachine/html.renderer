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
interface RendererInterface
{

    /**
     * Renders the object as a HTML string, to the output.
     *
     * @param object $object  The object to render
     * @param string $context A string representing a context that might be used to choose another renderer for the object.
     */
    public function render($object, $context = null);
}
