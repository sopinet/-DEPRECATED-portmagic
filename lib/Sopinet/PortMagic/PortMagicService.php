<?php
namespace Sopinet\PortMagic;

use Sopinet\PortMagic\PortMagicHelper;

class PortMagicService
{
	static public function getDataFromUrl($url) {
		PortMagicHelper::whatpluginis($url);
		// TODO: Return data from url
	} 
}

?>