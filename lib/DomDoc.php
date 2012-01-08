<?php
require_once('DomDocDir.php');

/**
 * DomDoc class. Represents an HTML file stored in DB with its directory.
 * The directory contains the original file and its assets. This class is used
 * to load the file and to apply transformation on its DOM.
 * @todo Refactor getters to be more coherent for DomDoc, DOMDocument and HTML return values.
 */
class DomDoc extends Model {
	public static $repo;

	public $dir;
	private $dom = array('original' => false, 'altered' => false);


	public function repo() {
		return $this->belongs_to('Repo', 'repo_name');
	}

	public function modSet($modSetId) {
		return $this->has_many('ModSet')->where('id', $modSetId);
	}

	public function modSets() {
		return $this->has_many('ModSet');
	}

	/**
	 * Sets an empty dir on creation.
	 * Overriden method.
	 * @param $data The record attributes.
	 * @return DomDoc $this.
	 */
	public function create($data = array()) {
		parent::create($data);
		$this->dir = new DomDocDir(false);
		return $this;
	}

	/**
	 * Sets the directory on record attribute population.
	 * Overriden method.
	 * @param $data The record attributes.
	 * @return DomDoc $this.
	 */
	public function hydrate($data = array()) {
		parent::hydrate($data);
		$dir = $this->name
			? Repo::$root . substr($this->name, 0, strrpos($this->name, '.')) . DIRECTORY_SEPARATOR
			: false
		;
		$this->dir = new DomDocDir($dir);
		return $this;
	}

	/**
	 * Sets the creation date just before save.
	 * Overriden method.
	 * @return DomDoc $this.
	 */
	public function save() {
		if (!$this->created)
			$this->created = date('Y-m-d H:i:s');
		return parent::save();
	}

	
	/**
	 * Applies transformations to the document.
	 * @param array $mods An array of Mod.
	 * @return DomDoc $this.
	 */
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

	/**
	 * Loads the document content.
	 * @param string $encoding The encoding used when reading the document.
	 * @return DOMDocument The document loaded into a DOMDocument.
	 * @todo Handle $encoding in a dynamic way, by guessing the encoding of the file.
	 */
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

	/**
	 * Reads the file content.
	 * @return mixed A string containing the file content or false if an error occurred.
	 */
	public function getContent() {
		return file_get_contents($this->dir->getPath() . $this->name);
	}

	/**
	 * Getter for the altered DOMDocument HTML.
	 * @return string The altered DOMDocument.
	 */
	public function getAlteredContent() {
		return $this->newDom->saveHTML();
	}

	/**
	 * Sanitizes the document assets by correcting URI-related attributes on the fy.
	 * @param string $path The path to prepend to the attributes.
	 * @return DomDoc $this.
	 */
	public function sanitizeAssets($path) {
		if (!isset($this->newDom))
			$this->alter(array());
		// build xpath selector for element having a url to correct
		$urlAttributes = array('src', 'href');
		$selector = array_map(function($attr) { return "//*[@{$attr}]"; }, $urlAttributes);

		$xpath = new DOMXpath($this->newDom);
		$elements = $xpath->query(join(' | ', $selector));
		$path .= $this->dir->getPath();

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
