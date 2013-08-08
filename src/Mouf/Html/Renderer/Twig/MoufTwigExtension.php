<?php
namespace Mouf\Html\Renderer\Twig;
use Twig_Extension;
use Mouf\MoufManager;
use Mouf\Html\HtmlElement\HtmlElementInterface;
use Mouf\Utils\Value\ValueInterface;

/**
 * The Mouf Twig extension provides a number of functions (toHtml, val, ...) to the Twig template
 * 
 * @author David Negrier <david@mouf-php.com>
 *
 */
class MoufTwigExtension extends Twig_Extension {

	private $moufManager;
	
	public function __construct(MoufManager $moufManager) {
		$this->moufManager = $moufManager;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Twig_ExtensionInterface::getName()
	 */
	public function getName()
	{
		return 'mouf';
	}
	
	public function getFunctions()
	{
		$moufManager = $this->moufManager;
		return array(
				/**
				 * The toHtml Twig function takes an HtmlElementInterface 
				 * and calls the toHtml() method on it.
				 * You can also call it with a string as a parameter. It will fetch the
				 * instance with that name and call toHtml() method on it.
				 */
				new \Twig_SimpleFunction('toHtml', function($param) use ($moufManager) {
					ob_start();
					if ($param instanceof HtmlElementInterface) {
						$param->toHtml();
					} else {
						$moufManager->getInstance($param)->toHtml();
					}
					return ob_get_clean();
				}, array('is_safe' => array('html'))),
				
				/**
				 * The mouf Twig function takes an instance name and returns the instance object.
				 * You would usually use it in conjunction with the toHtml function.
				 */
				new \Twig_SimpleFunction('mouf', function($instanceName) use ($moufManager) {
					return $moufManager->getInstance($instanceName);
				}),
				
				/**
				 * The val function will call the val() method of the object passed in parameter
				 * (if the object extends the ValueInterface interface).
				 */
				new \Twig_SimpleFunction('val', function($param) use ($moufManager) {
					if ($param instanceof ValueInterface) {
						return $param->val();
					} else {
						return $moufManager->getInstance($param)->val();
					}
				}),
				
				/**
				 * The t function will call the eMsg() method of the string passed in parameter
				 */
				new \Twig_SimpleFunction('t', function() {
					$args = func_get_args();
					return call_user_func_array("iMsg", $args);
				})
		);
	}
}
