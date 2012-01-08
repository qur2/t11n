<?php
/**
 * Represents a directory used by a DomDoc.
 * It's a directory meant to contain a single HTML file and assets in subdirectories.
 * The assets have to be included using relative paths.
 * @todo handle multiple HTML files in the same directory.
 */
class DomDocDir {
	/**
	 * The resource path.
	 */
	private $path;


	/**
	 * Constructor.
	 */
	public function __construct($path = false) {
		$this->setPath($path);
	}

	/**
	 * Setter for the $path attribute. It also sets a $name attribute,
	 * which is the $path base name.
	 * @param string $path The path of the directory.
	 */
	private function setPath($path) {
		$this->path = $path;
		$this->name = false;
		if ($path) {
			$this->name = basename($path);
		}
	}

	/**
	 * Getter for the $path attribute.
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Tells if the directory exists.
	 * @return boolean True if the directory exists, false otherwise.
	 */
	public function exists() {
		return false !== $this->path && is_dir($this->path);
	}

	/**
	 * Consolidate the directory by checking that the content is not too deep.
	 * It may happen when unzipping a complete directory.
	 */
	private function moveBackUp() {
		$tooFar = $this->path . '/' . $this->name;
		if (is_dir($tooFar)) {
			exec("mv {$tooFar}/* {$this->path}");
			exec("rm {$tooFar}/*");
			exec("rm {$tooFar}");
		}
	}

	/**
	 * Checks if a file has the same name (without extension) than the directory base name.
	 * If not, it searches for a file of a given extension and renames it.
	 * @param string $ext The file extension to use for renaming.
	 * @return boolean A boolean telling if the renaming was successful or not.
	 * @todo Delete this method to handle multiple HTML files as it will become useless.
	 */
	private function makeRoot($ext = 'html') {
		$file = glob($this->path . '*.' . $ext);
		$file = reset($file);
		return rename($file, $this->path . $this->name . '.' . $ext);
	}

	/**
	 * Extracts a zip in a given destination and consolidates the directory.
	 * @param string $location The zip location.
	 * @param string $destination The directory path where the zip is extracted.
	 */
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