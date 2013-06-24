<?php
namespace Sopinet\PortMagic;

class PortMagicHelper
{
	// What is?
	static function whatpluginis($url)
	{
		foreach(array_reverse(PModel::$plugins) as $level) {
			foreach($level as $plugin) {
				if (call_user_func($plugin."::isIt", $url) == true) return new $plugin($url);
			}
		}
		return false;
	}
}
?>