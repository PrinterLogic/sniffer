<?php
namespace Ayeo\Sniffer;

class ChangedFilesFinder
{
	private $basePath;

	public function __construct($basePath)
	{
		$this->basePath = $basePath;
	}

	public function findAll($commitSHA)
	{
		$listFilesCommand = "cd %s && git diff -M --name-only %s^ %s";
		$parsedCommand = sprintf($listFilesCommand, $this->basePath, $commitSHA, $commitSHA);
		$changedFilesList = array_filter(explode("\n", `$parsedCommand`), function($item) { return strlen($item) > 0; });
		$changedLineNumbers = "cd %s && git blame -p %s -- %s | grep %s";

		$filesCollection = [];
		foreach ($changedFilesList as $filePath) {
			if (file_exists($filePath) === false) {
				continue; //skip deleted
			}

			$file = new File($filePath);

			$xx = sprintf($changedLineNumbers, $this->basePath, $commitSHA, $filePath, $commitSHA);

			foreach (explode("\n", `$xx`) as $row) {
				preg_match("#^[a-z0-9]{40}\s(\d+)\s(\d+)#", $row, $match);

				if (isset($match[1])) {
					$file->markLineAsChanged($match[1]);
				}

				if (isset($match[2])) {
					$file->markLineAsChanged($match[2]);
				}
			}

			$filesCollection[] = $file;
		}

		return $filesCollection;
	}

}
