<?php
class Mod extends Model {
	public function modSet() {
		return $this->belongs_to('ModSet');
	}
}
