<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

PhpEcho::injectStandardHelpers();

$pilot->run(
    id: 'helpers_001',
    test: fn() => PhpEcho::getHelper('hsc'),
    description: 'inject default helpers and check the declaration of a standard helper (hsc)'
);
$pilot->assertIsInstanceOf(Closure::class);

$pilot->run(
    id: 'helpers_002',
    test: fn() => PhpEcho::injectHelpers(__DIR__.DIRECTORY_SEPARATOR.'missing_helpers_file.php'),
    description: 'try to inject missing helpers file'
);
$pilot->assertException(InvalidArgumentException::class);



