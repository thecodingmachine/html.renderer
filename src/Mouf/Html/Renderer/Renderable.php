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

	/**
	 * Returns an array containing all the public and protected properties.
	 * 
	 * @return array
	 */
	public function getPrivateProperties() {
		return get_object_vars($this);
	}
	
	public function toHtml() {
		Mouf::getDefaultRenderer()->render($this);
	}
}