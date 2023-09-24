<?php declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

PhpEcho::setSeekValueMode('parents');

$root = new PhpEcho(id: 'root');
$block_1 = new PhpEcho(id: 'block_1');
$block_11 = new PhpEcho(id: 'block_11');
$block_12 = new PhpEcho(id: 'block_12');
$block_121 = new PhpEcho(id: 'block_121');
$block_1211 = new PhpEcho(id: 'block_1211');

$root['block_1'] = $block_1;
$block_1['block_11'] = $block_11;
$block_1['block_12'] = $block_12;
$block_12['block_121'] = $block_121;
$block_121['block_1211'] = $block_1211;


$root->injectVars(['a' => 1, 'b' => 'abc " < >']);

$pilot->run(
    id: 'autowire_001',
    test: fn() => $block_1['a'],
    description: 'no scalar vars defined, after directly injecting them into the root, check the values are available for the whole tree components',
);
$pilot->assertIsInt();
$pilot->assertEqual(1);

$pilot->run(
    id: 'autowire_002',
    test: fn() => $block_121['b'],
    description: 'no scalar vars defined, after directly injecting them into the root, check the values are available for the whole tree components and escaped',
);
$pilot->assertIsString();
$pilot->assertEqual('abc &quot; &lt; &gt;');


$root = new PhpEcho(id: 'root');
$block_1 = new PhpEcho(id: 'block_1');
$block_11 = new PhpEcho(id: 'block_11');
$block_12 = new PhpEcho(id: 'block_12');
$block_121 = new PhpEcho(id: 'block_121');
$block_1211 = new PhpEcho(id: 'block_1211');

$root['block_1'] = $block_1;
$block_1['block_11'] = $block_11;
$block_1['block_12'] = $block_12;
$block_12['block_121'] = $block_121;
$block_121['block_1211'] = $block_1211;


$block_121->injectVars(['a' => 1, 'b' => 'abc " < >']);

$pilot->run(
    id: 'autowire_003',
    test: fn() => $block_1['a'],
    description: 'no scalar vars defined, after directly injecting them into one leaf, check the values are available for the whole tree components',
);
$pilot->assertIsInt();
$pilot->assertEqual(1);

$pilot->run(
    id: 'autowire_004',
    test: fn() => $block_121['b'],
    description: 'no scalar vars defined, after directly injecting them into one leaf, check the values are available for the whole tree components and escaped',
);
$pilot->assertIsString();
$pilot->assertEqual('abc &quot; &lt; &gt;');

