<?php
class ModSet extends Model {
	public function mods() {
		return $this->has_many('Mod');
	}

	public function domDoc() {
		return $this->belongs_to('DomDoc');
	}
}
