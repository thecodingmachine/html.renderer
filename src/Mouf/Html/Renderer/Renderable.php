<?php 

namespace Mouf\Html\Renderer;

use Mouf;

/**
 * Classes using this trait will have an automatic implementation of the toHtml method provided that
 * will call the default rendering system.
 * 
 * @author David Négrier <david@mouf-php.com>
 */
trait Renderable {
	
	public function toHtml() {
		Mouf::getDefaultRenderer()->render($this);
	}
}