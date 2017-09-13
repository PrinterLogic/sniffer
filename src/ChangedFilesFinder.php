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
        $commands = [
            'added' => 'cd %s && git ls-files -o  --exclude-standard --full-name',
            'modified' => 'cd %s && git ls-files -m',
        ];

        $changedFilesList = [];
        foreach ($commands as $type => $command) {
            $parsedCommand = sprintf($command, $this->basePath);
            $changedFiles = array_filter(explode("\n", `$parsedCommand`), function($item) { return strlen($item) > 0; });
            foreach ($changedFiles as $file) {
                $lines = 0;
                if ('added' == $type) {
                    $lines = count(file($this->basePath . $file));
                } else if ('modified' == $type) {
                    $lines = "cd %s && git blame test.php | grep -n '^0\{8\} ' | cut -f1 -d:";
                }
                //dump($lines);

                $lineNumbers = [];
                for ($i = 0; $i < $lines; $i++) {
                    //dump($i + 0);
                    $lineNumbers[] = $i + 0;
                }
                //dump($lineNumbers);

                $changedFilesList[$file] = $lineNumbers;
            }
        }
        dump($changedFilesList);

        die();
        //die;

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
