<?php
/**
 * Simple PHP class encapsulating file upload related functions.
 * @author qur2
 */
class FileUpload {
	private $name;
	public $messages = array(
		1 => 'Entry size exceeds upload_max_filesize limit : %s',
		2 => 'Entry size exceeds max_file_size limit : %s',
		3 => 'Entry was not completely uploaded : %s',
		4 => 'Entry was not uploaded : %s',
		6 => 'No temporary folder for entry : %s',
		7 => 'A disk write operation failed for entry : %s',
		8 => 'Entry extension was rejected : %s',
		'illegal' => 'Entry is illegal : %s',
	);


	public function __construct($name, &$files = null) {
		if (is_null($files)) $files = &$_FILES;
		$this->name = $name;
		$this->entry = (object)$files[$name];
	}

	public function isUploaded() {
		if (!is_uploaded_file($this->entry->tmp_name)) {
			if (!$this->entry->error)
				$this->entry->error = 'illegal';
			throw new RuntimeException($this->getErrorMessage());
		}
		return 0 === $this->entry->error;
	}

	public function move($dest, $name = null) {
		if ($this->isUploaded()) {
			if (is_null($name)) $name = $this->entry->name;
			return move_uploaded_file($this->entry->tmp_name, $dest . $name)
				? $dest . $name
				: false;
		} else {
			throw new RuntimeException($this->getErrorMessage());
		}
	}

	public function getErrorMessage() {
		return sprintf($this->messages[$this->entry->error], $this->name);
	}

	/**
	 * Provides a convenient way to access a entry values by emulating getters.
	 * e.g. getTmpName() will return entry->tmp_name.
	 */
	public function __call($method, $args) {
		if ('get' == substr($method, 0, 3)) {
			$attr = strtolower(preg_replace('/([A-Z])/', '_\\1', lcfirst(substr($method, 3))));
			return $this->entry->{$attr};
		}
	}

	/**
	 * Returns the directory path where the temp file lies.
	 * @return string The directory path where the temp file lies.
	 */
	public function getTmpDir() {
		return dirname($this->entry->tmp_name);
	}
}
