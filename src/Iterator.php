<?php

namespace Webgriffe\AmpCsv;

use function Amp\call;
use Amp\Emitter;
use Amp\Iterator as AmpIterator;
use Amp\Promise;
use Amp\File;

class Iterator implements AmpIterator
{
    /**
     * @var Parser
     */
    private $csvParser;
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
     * @param Parser $csvParser
     * @param bool $firstLineIsHeader
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Error
     */
    public function __construct(Parser $csvParser, bool $firstLineIsHeader = true)
    {
        $this->csvParser = $csvParser;
        $this->firstLineIsHeader = $firstLineIsHeader;
        $this->emitter = new Emitter();
        $this->attachEmitter();
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

    private function attachEmitter()
    {
        call(function () {
            $header = null;
            if ($this->firstLineIsHeader) {
                $header = yield $this->csvParser->parseRow();
            }
            while ($row = yield $this->csvParser->parseRow()) {
                if ($this->firstLineIsHeader) {
                    if (\count($header) !== \count($row)) {
                        $this->emitter->fail(
                            new \LogicException(
                                sprintf(
                                    'Invalid number of columns at line %d of given CSV file. Header has %d columns, ' .
                                    'this line %d columns.',
                                    $this->csvParser->getRowsParsed(),
                                    \count($header),
                                    \count($row)
                                )
                            )
                        );
                        return;
                    }
                    $row = array_combine($header, $row);
                }
                $this->emitter->emit($row);
            }
            $this->emitter->complete();
        });
    }
}
