<?php
class DomDoc extends Model {
	public static $_id_column = 'name';
	public static $repo;

	public function alter($mods) {
		$doc = $this->loadContent();
		$xpath = new DOMXpath($doc);
		foreach ($mods as $mod) {
			$elems = $xpath->query($mod->xpath);
			if (!is_null($elems)) {
				foreach ($elems as $el) {
					$el->nodeValue = $mod->value;
				}
			}
		}
		$this->newDom = $doc;
		return $this;
	}

	public function loadContent($encoding = 'UTF-8') {
		$content = $this->getContent();
		$doc = new DOMDocument('1.0', $encoding);
		$doc->preserveWhiteSpace = false;
		if ('UTF-8' == $encoding) {
			// @see http://be2.php.net/manual/en/domdocument.loadhtml.php#95251
			@$doc->loadHTML('<?xml encoding="UTF-8">' . $content);
			foreach ($doc->childNodes as $item)
				if ($item->nodeType == XML_PI_NODE)
					$doc->removeChild($item);
		} else {
			@$doc->loadHTML($content);
		}
		$doc->encoding = $encoding;
		return $doc;
	}

	private function getSubRepo() {
		return self::$repo . substr($this->name, 0, strrpos($this->name, '.')) . DIRECTORY_SEPARATOR;
	}

	public function getContent() {
		return file_get_contents($this->getSubRepo() . $this->name);
	}

	public function getAlteredContent() {
		return $this->newDom->saveHTML();
	}

	public function sanitizeAssets($path) {
		if (!isset($this->newDom))
			$this->alter(array());
		// build xpath selector for element having a url to correct
		$urlAttributes = array('src', 'href');
		$selector = array_map(function($attr) { return "//*[@{$attr}]"; }, $urlAttributes);

		$xpath = new DOMXpath($this->newDom);
		$elements = $xpath->query(join(' | ', $selector));
		$path .= $this->getSubRepo();

		// correct the urls on the fly
		if (!is_null($elements)) {
			foreach ($elements as $el) {
				foreach ($urlAttributes as $attr) {
					if ($el->hasAttribute($attr)) {
						$attrVal = $el->getAttribute($attr);
						if ('#' != $attrVal && 'http' != substr($attrVal, 0, 4))
							$el->setAttribute($attr, $path . $attrVal);
					}
				}
			}
		}
		return $this;
	}
}

class Mod extends Model {
}
