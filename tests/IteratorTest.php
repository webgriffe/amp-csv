<?php

namespace Webgriffe\AmpCsv;

use Amp\Iterator as AmpIterator;
use Amp\Loop;
use PHPUnit\Framework\TestCase;

class IteratorTest extends TestCase
{
    public function testIterate()
    {
        $csvFile = __DIR__ . '/many-countries.csv';
        $iterator = new Iterator($csvFile);

        $rows = $this->runIterator($iterator);

        $this->assertCount(250, $rows);
        $this->assertEquals('TW', $rows[0]['ISO3166-1-Alpha-2']);
        $this->assertEquals('Andorra', $rows[5]['official_name_en']);
    }

    public function testIterateWithFirstLineNotHeader()
    {
        $csvFile = __DIR__ . '/countries-wihtout-header.csv';
        $iterator = new Iterator($csvFile, false);

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
        $iterator = new Iterator(__DIR__ . '/different-number-of-columns-between-header-and-values.csv');
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
}
