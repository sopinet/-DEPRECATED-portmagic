<?php
namespace Sopinet\PortMagic\Plugins\Level1;

class PluginWeb extends PluginAbstract {
	public function isIt($url) {
		return true;
	}

	public function getTitle() {
		$title = $this->getMetaKey('description');
		if ($title == null) $title = PModel::getNameFromUrl($this->getUrl());
		return $title;
	}

	function mySkills() {
		$skills = array();
		if ($this->isInContent("bootstrap")) $skills[] = "Bootstrap";
		if ($this->isInContent("jquery.mobile")) $skills[] = "jQueryMobile";
		if ($this->getMetaKey("viewport") == "width=device-width, initial-scale=1.0" ||
		$this->getMetaKey("viewport") == "width=device-width, initial-scale=1") $skills[] = "Mobile";
		return $skills;
	}
}