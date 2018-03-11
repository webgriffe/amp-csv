<?php

namespace Webgriffe\AmpCsv;

use Amp\Emitter;
use Amp\Iterator as AmpIterator;
use Amp\Promise;
use Amp\ReactAdapter\ReactAdapter;
use Rakdar\React\Csv\Reader;
use React\Stream\ReadableResourceStream;

class Iterator implements AmpIterator
{
    /**
     * @var string
     */
    private $csvFile;
    /**
     * @var Emitter
     */
    private $emitter;
    /**
     * @var array
     */
    private $header;
    /**
     * @var bool
     */
    private $firstLineIsHeader;

    /**
     * Iterator constructor.
     * @param string $csvFile
     * @param bool $firstLineIsHeader
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Error
     */
    public function __construct(string $csvFile, bool $firstLineIsHeader = true)
    {
        $this->csvFile = $csvFile;
        $this->firstLineIsHeader = $firstLineIsHeader;
        $this->emitter = new Emitter();
        $this->attachHandlers();
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Error
     */
    private function attachHandlers()
    {
        $stream = new ReadableResourceStream(fopen($this->csvFile, 'rb'), ReactAdapter::get());
        if (!$stream->isReadable()) {
            $this->emitter->complete();
        }

        $reader = new Reader($stream);
        $reader->setParseHeader($this->firstLineIsHeader);
        $reader->on('end', function () {
            $this->emitter->complete();
        });
        $reader->on('error', function (\Throwable $error) {
            $this->emitter->fail($error);
        });
        $reader->on('close', function () {
            $this->emitter->fail(new \RuntimeException('Stream closed'));
        });
        $reader->on('header', function (array $header) {
            $this->header = $header;
        });
        $reader->on('data', function (array $row) use ($reader) {
            if ($this->firstLineIsHeader) {
                if (\count($this->header) !== \count($row)) {
                    $this->emitter->fail(
                        new \LogicException(
                            sprintf('Invalid number of columns at line %d of given CSV file.', $reader->getRowsParsed())
                        )
                    );
                    $reader->close();
                    return;
                }
                $row = array_combine($this->header, $row);
            }
            $this->emitter->emit($row);
        });
    }

    /**
     * Succeeds with true if an emitted value is available by calling getCurrent() or false if the iterator has
     * resolved. If the iterator fails, the returned promise will fail with the same exception.
     *
     * @return \Amp\Promise<bool>
     *
     * @throws \Error If the prior promise returned from this method has not resolved.
     * @throws \Throwable The exception used to fail the iterator.
     */
    public function advance(): Promise
    {
        return $this->emitter->iterate()->advance();
    }

    /**
     * Gets the last emitted value or throws an exception if the iterator has completed.
     *
     * @return mixed Value emitted from the iterator.
     *
     * @throws \Error If the iterator has resolved or advance() was not called before calling this method.
     * @throws \Throwable The exception used to fail the iterator.
     */
    public function getCurrent()
    {
        return $this->emitter->iterate()->getCurrent();
    }
}
