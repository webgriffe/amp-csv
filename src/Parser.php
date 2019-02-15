<?php

namespace Webgriffe\AmpCsv;

use function Amp\call;
use Amp\Promise;
use Amp\File;

class Parser
{
    /**
     * @var File\Handle
     */
    private $fileHandle;
    /**
     * @var string
     */
    private $delimiter;
    /**
     * @var string
     */
    private $enclosure;
    /**
     * @var string
     */
    private $escape;
    /**
     * @var int
     */
    private $rowsParsed = 0;

    public function __construct(
        File\Handle $fileHandle,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = "\\"
    ) {
        $this->fileHandle = $fileHandle;
        $this->delimiter = $delimiter[0];
        $this->enclosure = $enclosure[0];
        $this->escape = $escape[0];
    }

    public function parseRow(): Promise
    {
        return call(function () {
            $isFirstRead = $this->fileHandle->tell() === 0;
            if ($this->fileHandle->eof()) {
                return null;
            }
            $buffer = '';
            $newLinePos = null;
            while ($chunk = yield $this->fileHandle->read()) {
                if ($isFirstRead) {
                    $chunk = $this->removeBom($chunk);
                }
                $isFirstRead = false;
                $buffer .= $chunk;
                $newLinePos = strpos($buffer, PHP_EOL);
                if ($newLinePos !== false) {
                    $shouldBreak = false;
                    while (!$shouldBreak) {
                        $enclosuresFoundBeforeNewline = substr_count(substr($buffer, 0, $newLinePos), $this->enclosure);
                        $newslineIsInTheMiddleOfAnEnclosedString = (($enclosuresFoundBeforeNewline % 2) === 1);
                        if ($newslineIsInTheMiddleOfAnEnclosedString) {
                            $newLinePos = strpos($buffer, PHP_EOL, $newLinePos + 1);
                            continue;
                        }
                        $shouldBreak = true;
                    }
                    break;
                }
            }
            $row = $buffer;
            if ($newLinePos !== false) {
                $bufferSize = \strlen($buffer);
                $seekOffset = $bufferSize-$newLinePos;
                yield $this->fileHandle->seek(-($seekOffset-1), \SEEK_CUR);
                $row = substr($buffer, 0, $newLinePos);
            }
            $this->rowsParsed++;
            return str_getcsv($row, $this->delimiter, $this->enclosure, $this->escape);
        });
    }

    /**
     * @return int
     */
    public function getRowsParsed(): int
    {
        return $this->rowsParsed;
    }

    /**
     * @param $chunk
     * @return string
     */
    private function removeBom(string $chunk): string
    {
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strpos($chunk, $bom) === 0) {
            $chunk = (string)substr($chunk, 3);
        }
        return $chunk;
    }
}
