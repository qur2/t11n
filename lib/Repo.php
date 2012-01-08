<?php
require_once('RepoDir.php');

class Repo extends Model {
	public static $_id_column = 'name';
	public static $root;
	public $dir;


	public function domDoc($domDocId) {
		return $this->domDocs()->where('id', $domDocId);
	}

	public function domDocs() {
		return $this->has_many('DomDoc', 'repo_name');
	}

	/**
	 * Sets an empty dir on creation.
	 * Overriden method.
	 * @param $data The record attributes.
	 * @return DomDoc $this.
	 */
	public function create($data = array()) {
		parent::create($data);
		$this->dir = new RepoDir(false);
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
			? self::$root . $this->name . DIRECTORY_SEPARATOR
			: false
		;
		$this->dir = new RepoDir($dir);
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
}
