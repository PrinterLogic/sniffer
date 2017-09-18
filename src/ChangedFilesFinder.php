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
            $changedFiles = array_filter(explode("\n", `$parsedCommand`), function($item) {
                return strlen($item) > 0;
            });

            foreach ($changedFiles as $file) {
                dump($file);
                $lineNumbers = [];

                if ('added' == $type) {
                    $lines = count(file($this->basePath . $file));

                    for ($i = 1; $i <= $lines; $i++) {
                        $lineNumbers[] = (int) $i;
                    }
                } else if ('modified' == $type) {
                    $lineCommand = sprintf("cd %s && git blame %s | grep -n '^0\{8\} ' | cut -f1 -d:",
                        $this->basePath, $file);
                    $lines = array_filter(explode("\n", `$lineCommand`), function($item) {
                        return strlen($item) > 0;
                    });

                    if (count($lines)) {
                        for ($i = 0; $i < count($lines); $i++) {
                            $lineNumbers[] = (int) $lines[$i];
                        }
                    }
                }

                if (count($lineNumbers)) {
                    $changedFilesList[$file] = $lineNumbers;
                }
            }
        }
        dump($changedFilesList);

        //$changedLineNumbers = "cd %s && git blame -p %s -- %s | grep %s";

        $filesCollection = [];
        foreach ($changedFilesList as $filePath => $changedLineNumbers) {
            if (file_exists($filePath) === false) {
                continue; //skip deleted
            }

            $file = new File($filePath);

            //$xx = sprintf($changedLineNumbers, $this->basePath, $commitSHA, $filePath, $commitSHA);

            dump($changedLineNumbers);
            foreach ($changedLineNumbers as $row) {
                preg_match("#^[a-z0-9]{40}\s(\d+)\s(\d+)#", $row, $match);
                dump($match);

                if (isset($match[1])) {
                    $file->markLineAsChanged($match[1]);
                }

                if (isset($match[2])) {
                    $file->markLineAsChanged($match[2]);
                }
            }

            $filesCollection[] = $file;
        }
        dump($filesCollection);
        die;

        return $filesCollection;
    }

}
