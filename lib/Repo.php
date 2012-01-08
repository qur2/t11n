<?php
class Repo extends Model {
	public static $_id_column = 'name';
	public static $root;


	public function domDoc($domDocId) {
		return $this->domDocs()->where('id', $domDocId);
	}

	public function domDocs() {
		return $this->has_many('DomDoc', 'repo_name');
	}
}
