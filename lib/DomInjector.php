<?php
class DomInjector {
	public $doc;

	public function __construct($doc) {
		$this->doc = &$doc;
	}

	public function append($element, $parentTag = 'head') {
		$parent = $this->doc->getElementsByTagName($parentTag)->item(0);
		$el = $this->createElement($parent, (object)$element);
	}

	public function createElement($parent, $node) {
		$el = new DOMElement($node->tag);
		unset($node->tag);
		$parent->appendChild($el);
		if (isset($node->value)) {
			$text = new DOMText($node->value);
			$el->appendChild($text);
			unset($node->value);
		}
		foreach ($node as $attr => $value)
			$el->setAttribute($attr, $value);
	}
}
