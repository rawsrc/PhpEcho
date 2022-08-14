<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

PhpEcho::injectStandardHelpers();

$pilot->run(
    id: 'helpers_001',
    test: fn() => PhpEcho::getHelper('hsc'),
    description: 'inject default helpers and check the declaration of standard helper'
);
$pilot->assertIsInstanceOf(Closure::class);



$block = new PhpEcho();

