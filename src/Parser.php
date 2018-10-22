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
            if ($this->fileHandle->eof()) {
                return null;
            }
            $buffer = '';
            $newLinePos = null;
            while ($chunk = yield $this->fileHandle->read()) {
                $buffer .= $chunk;
                $newLinePos = strpos($buffer, PHP_EOL);
                if ($newLinePos !== false) {
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
}
