<?php
class ModSet extends Model {
	public function mods() {
		return $this->has_many('Mod');
	}

	public function domDoc() {
		return $this->belongs_to('DomDoc');
	}

	public static function buildFromMods($data = array()) {
		if (!isset($data['mod_set_id']) && !isset($data['dom_doc_id']))
			throw new RuntimeException('Missing primary or foreign key to create a new mod set');
		$mods = array_filter($data, function($el) {
			return is_object($el) && 'Mod' == get_class($el);
		});
		$modSet = Model::factory('ModSet');
		if (isset($data['mod_set_id']))
			$modSet->find_one($data['mod_set_id']);
		else
			$modSet->dom_doc_id = $data['dom_doc_id'];

		Model::start_transaction();
		if ($modSet->loaded())
			ORM::for_table('mod')
				->where_equal('dom_doc_id', $modSet->dom_doc_id)
				->delete_many();
		else
			$modSet->save();
		foreach ($mods as $mod) {
			$mod->mod_set_id = $modSet->id;
			$mod->save();
		}
		Model::commit();
	}
}
