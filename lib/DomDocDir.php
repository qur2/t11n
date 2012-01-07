<?php
class DomDocDir {
	private $path;


	public function __construct($path = false) {
		$this->setPath($path);
	}

	private function setPath($path) {
		$this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$this->name = false;
		if ($path) {
			$this->name = basename($path);
		}
	}

	public function getPath() {
		return $this->path;
	}

	public function exists() {
		return false !== $this->path && is_dir($this->path);
	}
	
	public function isRoot() {
		return $this->exists() && glob($this->path . $this->name . '.*');
	}

	private function moveBackUp() {
		$tooFar = $this->path . '/' . $this->name;
		if (is_dir($tooFar)) {
			exec("mv {$tooFar}/* {$this->path}");
			exec("rm {$tooFar}/*");
			exec("rm {$tooFar}");
		}
	}

	private function makeRoot($ext = 'html') {
		if ($this->isRoot())
			return;
		$file = glob($this->path . '*.' . $ext);
		$file = reset($file);
		return rename($file, $this->path . $this->name . '.' . $ext);
	}

	public function buildFromZip($location, $destination) {
		$zip = new ZipArchive;
		if (true !== $zip->open($location)) {
			return false;
		} else {
			$this->setPath($destination);
			$zip->extractTo($destination);
			$zip->close();
			$this->moveBackUp();
			$this->makeRoot();
		}
	}
}