<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

PhpEcho::injectStandardHelpers();

$pilot->run(
    id: 'helpers_001',
    test: fn() => PhpEcho::getHelperBase('hsc'),
    description: 'inject default helpers and check the declaration of a standard helper (hsc)'
);
$pilot->assertIsInstanceOf(Closure::class);

$pilot->run(
    id: 'helpers_002',
    test: fn() => PhpEcho::injectHelpers(__DIR__.DIRECTORY_SEPARATOR.'missing_helpers_file.php'),
    description: 'try to inject missing helpers file'
);
$pilot->assertException(InvalidArgumentException::class);

PhpEcho::addHelper('basic_helper', fn() => 'foo_bar_helper_result');
$pilot->run(
    id: 'helpers_003',
    test: fn() => PhpEcho::getHelperBase('basic_helper'),
    description: 'create a helper on the fly and retrieve it'
);
$pilot->assertIsInstanceOf(Closure::class);
$basic_helper = $pilot->getRunner()->getResult();

$pilot->run(
    id: 'helpers_004',
    test: fn() => $basic_helper() === 'foo_bar_helper_result',
    description: 'control the value returned by the helper created on the fly'
);
$pilot->assertEqual(true);

$pilot->run(
    id: 'helpers_005',
    test: fn() => PhpEcho::getHelperBase('wrong_helper'),
    description: 'try to extract a wrong helper'
);
$pilot->assertException(InvalidArgumentException::class);

PhpEcho::addHelper('basic_helper', fn() => 'foo_bar_helper_result_new');
$pilot->run(
    id: 'helpers_006',
    test: fn() => PhpEcho::getHelperBase('basic_helper'),
    description: 'redefine a helper on the fly and retrieve it'
);
$pilot->assertIsInstanceOf(Closure::class);
$basic_helper = $pilot->getRunner()->getResult();

$pilot->run(
    id: 'helpers_007',
    test: fn() => $basic_helper() === 'foo_bar_helper_result_new',
    description: 'control the value returned by the redefined helper on the fly'
);
$pilot->assertEqual(true);