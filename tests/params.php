<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

$block = new PhpEcho();

$block->setParam('param_a', 'a');

$pilot->run(
    id: 'param_001',
    test: fn() => $block->getParam('param_a'),
    description: 'set and retrieve simple raw param value'
);
$pilot->assertEqual('a');

$block->setParam('param_b', '<script></script>');
$pilot->run(
    id: 'param_002',
    test: fn() => $block->getParam('param_b'),
    description: 'set and retrieve raw html param value'
);
$pilot->assertEqual('<script></script>');

$block->setParam('param_a', 'xyz');
$pilot->run(
    id: 'param_003',
    test: fn() => $block->getParam('param_a'),
    description: 'overwriting param value'
);
$pilot->assertEqual('xyz');

$block->setParam('param_a', 1);
$pilot->run(
    id: 'param_004',
    test: fn() => $block->getParam('param_a'),
    description: 'set and retrieve param value preserving the type (int)'
);
$pilot->assertIsInt();
$pilot->assertEqual(1);

$block->setParam('param_a', new stdClass());
$pilot->run(
    id: 'param_005',
    test: fn() => $block->getParam('param_a'),
    description: 'set and retrieve param value preserving the type (object)'
);
$pilot->assertIsObject();
$pilot->assertIsInstanceOf(stdClass::class);

PhpEcho::setGlobalParam('global_param_a', 'abc');
$pilot->run(
    id: 'param_020',
    test: fn() => PhpEcho::getGlobalParam('global_param_a'),
    description: 'set and retrieve global param value'
);
$pilot->assertEqual('abc');

PhpEcho::setGlobalParam('global_param_a', 'def');
$pilot->run(
    id: 'param_021',
    test: fn() => PhpEcho::getGlobalParam('global_param_a'),
    description: 'overwriting global param value'
);
$pilot->assertEqual('def');

PhpEcho::setGlobalParam('global_param_a', 1);
$pilot->run(
    id: 'param_022',
    test: fn() => PhpEcho::getGlobalParam('global_param_a'),
    description: 'set and retrieve global param value preserving the type (int)'
);
$pilot->assertIsInt();
$pilot->assertEqual(1);

PhpEcho::setGlobalParam('global_param_a', new stdClass());
$pilot->run(
    id: 'param_023',
    test: fn() => PhpEcho::getGlobalParam('global_param_a'),
    description: 'set and retrieve global param value preserving the type (object)'
);
$pilot->assertIsObject();
$pilot->assertIsInstanceOf(stdClass::class);

$block->setParam('param_a', 'abc');
PhpEcho::setGlobalParam('param_a', 'def');
$pilot->run(
    id: 'param_050',
    test: fn() => PhpEcho::getGlobalParam('param_a') === 'def' && $block->getParam('param_a') === 'abc',
    description: "check there's no collision between param and global param"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);
