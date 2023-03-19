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
    description: "check there's no collision between local param and global param"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$block->setParam('to_be_unset', 'klm123');
$pilot->run(
    id: 'param_051',
    test: fn() => $block->hasAnyParam('to_be_unset') === true,
    description: "once a parameter is defined locally, hasAnyParam() must return true"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_052',
    test: fn() => $block->hasAnyParam('unknown_param'),
    description: "check hasAnyParam with an unknown parameter"
);
$pilot->assertIsBool();
$pilot->assertEqual(false);

$pilot->run(
    id: 'param_053',
    test: fn() => $block->getParam('to_be_unset'),
    description: "check local parameter value storage"
);
$pilot->assertIsString();
$pilot->assertEqual('klm123');

$block->unsetParam('to_be_unset');
$pilot->run(
    id: 'param_054',
    test: fn() => $block->hasParam('to_be_unset'),
    description: 'unset a local parameter and check if it was removed from the local parameters array'
);
$pilot->assertIsBool();
$pilot->assertEqual(false);

$pilot->run(
    id: 'param_055',
    test: fn() => $block->unsetParam('unknown_param_name'),
    description: 'try to unset an unknown local parameter'
);
$pilot->assertException(InvalidArgumentException::class);

PhpEcho::setGlobalParam('to_be_unset', 'klm123');
$pilot->run(
    id: 'param_056',
    test: fn() => PhpEcho::getGlobalParam('to_be_unset'),
    description: "check global parameter value storage"
);
$pilot->assertIsString();
$pilot->assertEqual('klm123');

$pilot->run(
    id: 'param_057',
    test: fn() => $block->hasAnyParam('to_be_unset') === true,
    description: "once a parameter is defined globally, hasAnyParam() must return true"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

PhpEcho::unsetGlobalParam('to_be_unset');
$pilot->run(
    id: 'param_058',
    test: fn() => PhpEcho::hasGlobalParam('to_be_unset'),
    description: 'unset a global parameter and check if it was removed from the global parameters array'
);
$pilot->assertIsBool();
$pilot->assertEqual(false);

$pilot->run(
    id: 'param_059',
    test: fn() => PhpEcho::unsetGlobalParam('unknown_param_name'),
    description: 'try to unset an unknown global parameter'
);
$pilot->assertException(InvalidArgumentException::class);

$block->setParam('any_param', 'local_param_value');
PhpEcho::setGlobalParam('any_param', 'global_param_value');
$pilot->run(
    id: 'param_060',
    test: fn() => $block->getAnyParam('any_param') === 'local_param_value',
    description: "check getAnyParam returns the local parameter value first and by default"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_061',
    test: fn() => $block->getAnyParam('any_param', 'global') === 'global_param_value',
    description: "check getAnyParam returns the global parameter value first when order is global"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_062',
    test: fn() => $block->getAnyParam('any_param', 'local') === 'local_param_value',
    description: "check getAnyParam returns the local parameter value first when order is local"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_063',
    test: fn() => $block->getAnyParam('any_param', 'unknown_order') === 'local_param_value',
    description: "check getAnyParam throws an exception when the order is unknown"
);
$pilot->assertException(InvalidArgumentException::class);

$block->unsetParam('any_param');
$pilot->run(
    id: 'param_064',
    test: fn() => $block->getAnyParam('any_param') === 'global_param_value',
    description: "unset the local parameter value and check if getAnyParam returns the global parameter value instead"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$block->setParam('any_param', 'local_param_value');
PhpEcho::unsetGlobalParam('any_param');
$pilot->run(
    id: 'param_065',
    test: fn() => $block->getAnyParam('any_param') === 'local_param_value',
    description: "unset the global parameter value and check if getAnyParam returns the local parameter value instead"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_066',
    test: fn() => $block->getAnyParam('any_param', 'global') === 'local_param_value',
    description: "unset the local parameter value and check if getAnyParam returns the global parameter value instead even when seek order is local"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$block->setAnyParam('any_param_local_and_global', 'any_param_value_local_and_global');
$pilot->run(
    id: 'param_067',
    test: fn() => $block->hasParam('any_param_local_and_global'),
    description: "define local and global param at once, check if the param is in the local array"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_068',
    test: fn() => PhpEcho::hasGlobalParam('any_param_local_and_global'),
    description: "define local and global param at once, check if the param is in the global array"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_069',
    test: fn() => $block->getAnyParam('any_param_local_and_global') === 'any_param_value_local_and_global',
    description: "define local and global param at once, check the local param value when seek order is local first"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$pilot->run(
    id: 'param_070',
    test: fn() => $block->getAnyParam('any_param_local_and_global', 'global') === 'any_param_value_local_and_global',
    description: "define local and global param at once, check the global param value"
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$block->unsetAnyParam('any_param_local_and_global');
$pilot->run(
    id: 'param_071',
    test: fn() => $block->hasParam('any_param_local_and_global'),
    description: "unset local and global param at once, check if the param is removed from the local array"
);
$pilot->assertIsBool();
$pilot->assertEqual(false);

$pilot->run(
    id: 'param_072',
    test: fn() => PhpEcho::hasGlobalParam('any_param_local_and_global'),
    description: "unset local and global param at once, check if the param is removed from the global array"
);
$pilot->assertIsBool();
$pilot->assertEqual(false);