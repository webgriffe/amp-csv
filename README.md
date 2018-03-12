# AMP Comma Separated Values Library

CSV library to use with [Amp](https://amphp.org/) PHP framework.
Currently it implements only an iterator which allows to parse CSV rows one at a time.

## Installation

Require this package using [Composer](https://getcomposer.org/):

  composer require webgriffe/amp-csv
  
## Iterator Usage

This library implements an Amp's [Iterator](https://amphp.org/amp/iterators/) which allows to iterate over CSV rows one at a time.
Potentially it can parse very large CSV files because only small chunks are kept in memory.
See the following example, given this CSV:

```csv
Name,Description,Price,Stock
RaspberryPi,"Raspberry PI Modell B, 512 MB",37.05,12
SanDisk Ultra SDHC,SanDisk Ultra SDHC 8 GB 30 MB/s Classe 10,6.92,54
```

We can have:

```php
<?php

require_once 'vendor/autoload.php';

\Amp\Loop::run(function () {
    $iterator = new \Webgriffe\AmpCsv\Iterator(__DIR__ . '/test.csv');
    while (yield $iterator->advance()) {
        $rows[] = $iterator->getCurrent();
    }
    var_dump($rows);
});
```

And the output will be:

```text
array(
    array(
        'Name' => 'RaspberryPi',
        'Description' => 'Raspberry PI Modell B, 512 MB',
        'Price' => 37.05,
        'Stock' => 12,
    ),
    array(
        'Name' => 'SanDisk Ultra SDHC',
        'Description' => 'SanDisk Ultra SDHC 8 GB 30 MB/s Classe 10',
        'Price' => 6.92,
        'Stock' => 54,
    ),
),
```

By default the iterator treats the first line as header and will use the column names to index the row values.
If a row has a different column number than header an exception will be thrown.
If your CSV doesn't have an header as first line you can disable header parsing by passing `false` as constructor's second argument:

```php
$iterator = new \Webgriffe\AmpCsv\Iterator(__DIR__ . '/test.csv',  false);
```

Contributing
------------

To contribute simply fork this repository, do your changes and then propose a pull requests.
You should run coding standards check and tests as well:

```bash
vendor/bin/phpcs --standard=PSR2 src
vendor/bin/phpunit
```

License
-------
This library is under the MIT license. See the complete license in the LICENSE file.

Credits
-------
Developed by [Webgriffe®](http://www.webgriffe.com/).
Thanks also to [Niklas Keller](https://github.com/kelunik) for his help about converting ReactPHP stream events to an Amp's Iterator (see [https://github.com/reactphp/promise-stream/issues/14](https://github.com/reactphp/promise-stream/issues/14)).
