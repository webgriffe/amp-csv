<?php

namespace Webgriffe\AmpCsv;

use Amp\Iterator as AmpIterator;
use Amp\Loop;
use PHPUnit\Framework\TestCase;
use Amp\File;
use function Amp\Promise\wait;

class IteratorTest extends TestCase
{
    public function testIterate()
    {
        $iterator = $this->createIterator(__DIR__ . '/many-countries.csv');

        $rows = $this->runIterator($iterator);

        $this->assertCount(250, $rows);
        $this->assertEquals('TW', $rows[0]['ISO3166-1-Alpha-2']);
        $this->assertEquals('Andorra', $rows[5]['official_name_en']);
    }

    public function testIterateWithFirstLineNotHeader()
    {
        $iterator = $this->createIterator(__DIR__ . '/countries-wihtout-header.csv', false);

        $rows = $this->runIterator($iterator);

        $this->assertCount(3, $rows);
        $this->assertEquals(
            [
                ['the Islamic Republic of Afghanistan', 'Afghanistan', 'République islamique d\'Afghanistan'],
                ['the Republic of Albania', 'Albania', 'la République d\'Albanie'],
                [
                    'the People\'s Democratic Republic of Algeria',
                    'Algeria',
                    'la République algérienne démocratique et populaire'
                ]
            ],
            $rows
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid number of columns at line 3 of given CSV file.
     */
    public function testDifferentNumberOfColumnsBetweenHeaderAndValuesShouldThrowAnException()
    {
        $iterator = $this->createIterator(__DIR__ . '/different-number-of-columns-between-header-and-values.csv');
        $this->runIterator($iterator);
    }

    /**
     * @param AmpIterator $iterator
     * @return array
     */
    private function runIterator(AmpIterator $iterator): array
    {
        $rows = [];
        Loop::run(
            function () use ($iterator, &$rows) {
                while (yield $iterator->advance()) {
                    $rows[] = $iterator->getCurrent();
                }
            }
        );
        return $rows;
    }

    /**
     * @param string $csvFile
     * @param bool $firstLineIsHeader
     * @return Iterator
     * @throws \Error
     * @throws \Throwable
     * @throws \TypeError
     */
    private function createIterator(string $csvFile, bool $firstLineIsHeader = true): Iterator
    {
        $iterator = new Iterator(new Parser(wait(File\open($csvFile, 'rb'))), $firstLineIsHeader);
        return $iterator;
    }
}
