<?php

namespace Webgriffe\AmpCsv;

use Amp\Loop;
use PHPUnit\Framework\TestCase;
use Amp\File;

class ParserTest extends TestCase
{
    public function testParseRowsFromSimpleCsvFile()
    {
        $rows = [];
        Loop::run(function () use (&$rows) {
            $parser = new Parser(yield File\open(__DIR__ . '/countries-wihtout-header.csv', 'rb'));
            $expectedRowsParsed = 0;
            while ($row = yield $parser->parseRow()) {
                $this->assertEquals(++$expectedRowsParsed, $parser->getRowsParsed());
                $rows[] = $row;
            }
        });
        $this->assertCount(3, $rows);
        $this->assertEquals(
            ['the Islamic Republic of Afghanistan', 'Afghanistan', 'République islamique d\'Afghanistan'],
            $rows[0]
        );
        $this->assertEquals(
            ['the Republic of Albania', 'Albania', 'la République d\'Albanie'],
            $rows[1]
        );
        $this->assertEquals(
            [
                'the People\'s Democratic Republic of Algeria',
                'Algeria',
                'la République algérienne démocratique et populaire'
            ],
            $rows[2]
        );
    }

    public function testParseRowsWithManyCountries()
    {
        $rows = [];
        Loop::run(function () use (&$rows) {
            $parser = new Parser(yield File\open(__DIR__ . '/many-countries.csv', 'rb'));
            while ($row = yield $parser->parseRow()) {
                $rows[] = $row;
            }
        });
        $this->assertCount(251, $rows);
        $this->assertEquals('TW', $rows[1][6]);
        $this->assertEquals('Andorra', $rows[6][2]);
    }

    public function testParseRowsWithDifferentDelimiter()
    {
        $rows = [];
        Loop::run(function () use (&$rows) {
            $parser = new Parser(yield File\open(__DIR__ . '/pipe-separated-countries.csv', 'rb'), '|');
            while ($row = yield $parser->parseRow()) {
                $rows[] = $row;
            }
        });
        $this->assertCount(3, $rows);
        $this->assertEquals(
            ['the Islamic Republic of Afghanistan', 'Afghanistan', 'République islamique d\'Afghanistan'],
            $rows[0]
        );
        $this->assertEquals(
            ['the Republic of Albania', 'Albania', 'la République d\'Albanie'],
            $rows[1]
        );
        $this->assertEquals(
            [
                'the People\'s Democratic Republic of Algeria',
                'Algeria',
                'la République algérienne démocratique et populaire'
            ],
            $rows[2]
        );
    }

    public function testParseFileWithEmptyRows()
    {
        $rows = [];
        Loop::run(function () use (&$rows) {
            $parser = new Parser(yield File\open(__DIR__ . '/empty-rows.csv', 'rb'));
            while ($row = yield $parser->parseRow()) {
                $rows[] = $row;
            }
            $this->assertEquals(10, $parser->getRowsParsed());
        });
        $this->assertCount(10, $rows);
        $this->assertEquals(['sku', 'qty'], $rows[0]);
        $this->assertEquals(['AAA', '123'], $rows[1]);
        $this->assertEquals(['', '2'], $rows[3]);
        $this->assertEquals(['A'], $rows[4]);
        $this->assertEquals([''], $rows[8]);
        $this->assertEquals(['test'], $rows[9]);
    }
}
