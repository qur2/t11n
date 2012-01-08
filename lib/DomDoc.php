<?php
/**
 * DomDoc class. Represents an HTML file stored in DB with its directory.
 * The directory contains the original file and its assets. This class is used
 * to load the file and to apply transformation on its DOM.
 * @todo Refactor getters to be more coherent for DomDoc, DOMDocument and HTML return values.
 */
class DomDoc extends Model {
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
	public function alter($modSetId = null, $path = null) {
		$mods = is_null($modSetId)
			? array()
			: $this->modSet($modSetId)->find_one()->mods()->find_many()
		;
		$dom = $this->loadContent();
		$xpath = new DOMXpath($dom);
		foreach ($mods as $mod) {
			$elems = $xpath->query($mod->xpath);
			if (!is_null($elems)) {
				foreach ($elems as $el) {
					$el->nodeValue = $mod->value;
				}
			}
		}
		$this->sanitizeAssets($dom, $path);
		$this->dom['altered'] = $dom;
		return $this;
	}

	/**
	 * Loads the document content.
	 * @param string $encoding The encoding used when reading the document.
	 * @return DOMDocument The document loaded into a DOMDocument.
	 * @todo Handle $encoding in a dynamic way, by guessing the encoding of the file.
	 */
	private function loadContent($encoding = 'UTF-8') {
		$repo = $this->repo()->find_one();
		$content = file_get_contents($repo->dir->getPath() . $this->name);
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
	public function getContent($original = false) {
		return $this->getDom($original)->saveHTML();
	}

	public function getDom($original = false) {
		if ($original || empty($this->dom['altered'])) {
			if (empty($this->dom['original']))
				$this->dom['original'] = $this->loadContent();
			return $this->dom['original'];
		}
		return $this->dom['altered'];
	}

	/**
	 * Sanitizes the document assets by correcting URI-related attributes on the fy.
	 * @param string $path The path to prepend to the attributes.
	 * @return DomDoc $this.
	 */
	public function sanitizeAssets(&$dom, $path) {
		// build xpath selector for element having a url to correct
		$urlAttributes = array('src', 'href');
		$selector = array_map(function($attr) { return "//*[@{$attr}]"; }, $urlAttributes);

		$xpath = new DOMXpath($dom);
		$elements = $xpath->query(join(' | ', $selector));
		$repo = $this->repo()->find_one();
		$path .= $repo->dir->getPath();

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

	public static function buildFromRepoFiles($data) {
		if (!isset($data['repo_name']))
			throw new RuntimeException('Missing primary key to create or update a new repo');
		$domDocs = array_filter($data, function($el) {
			return is_object($el) && 'DomDoc' == get_class($el);
		});
		$repo = Model::factory('Repo')->find_one($data['repo_name']);

		Model::start_transaction();
		if (!$repo->loaded()) {
			$repo->name = $data['repo_name'];
			$repo->save();
		}
		foreach ($domDocs as $domDoc) {
			$domDoc->repo_name = $repo->name;
			$domDoc->save();
		}
		Model::commit();
	}
}
