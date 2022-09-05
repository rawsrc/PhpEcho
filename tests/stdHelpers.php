<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

$block = new PhpEcho();

$block['foo'] = 'abc " < >';

$pilot->run(
    id: 'stdHelpers_001',
    test: fn() => $block->hsc('abc " < >'),
    description: 'direct escaping using hsc helper as a function (__call)'
);
$pilot->assertIsString();
$pilot->assertEqual('abc &quot; &lt; &gt;');

$pilot->run(
    id: 'stdHelpers_002',
    test: fn() => $block('hsc', 'abc " < >'),
    description: 'direct escaping using hsc helper with invocation (__invoke)'
);
$pilot->assertIsString();
$pilot->assertEqual('abc &quot; &lt; &gt;');

$pilot->run(
    id: 'stdHelpers_003',
    test: fn() => $block->raw('foo'),
    description: 'extract raw value for the key foo using a function (__call)'
);
$pilot->assertIsString();
$pilot->assertEqual('abc " < >');

$pilot->run(
    id: 'stdHelpers_004',
    test: fn() => $block('raw', 'foo'),
    description: 'extract raw value for the key foo using invocation (__invoke)'
);
$pilot->assertIsString();
$pilot->assertEqual('abc " < >');

$pilot->run(
    id: 'stdHelpers_005',
    test: fn() => $block->raw('xxxxx'),
    description: 'try to extract the value of a missing key'
);
$pilot->assertException(InvalidArgumentException::class);

$data = [
    'abc' => 'abc',
    '"' => '"',
    '<' => '<',
    '>' => '>',
    'def' => [
        '"' => '"',
        '<' => '<',
        '>' => '>',
    ],
];
$pilot->run(
    id: 'stdHelpers_006',
    test: fn() => $block->hsc($data),
    description: 'direct escaping array and sub-array (keys + values)'
);
$pilot->assertIsArray();
$pilot->assertEqual([
    'abc' => 'abc',
    '&quot;' => '&quot;',
    '&lt;' => '&lt;',
    '&gt;' => '&gt;',
    'def' => [
        '&quot;' => '&quot;',
        '&lt;' => '&lt;',
        '&gt;' => '&gt;',
    ],
]);

$block['foo'] = $data;
$pilot->run(
    id: 'stdHelpers_007',
    test: fn() => $block['foo'],
    description: 'data extracting and escaping from the instance read as an array and sub-array (keys + values)'
);
$pilot->assertIsArray();
$pilot->assertEqual([
    'abc' => 'abc',
    '&quot;' => '&quot;',
    '&lt;' => '&lt;',
    '&gt;' => '&gt;',
    'def' => [
        '&quot;' => '&quot;',
        '&lt;' => '&lt;',
        '&gt;' => '&gt;',
    ],
]);


$block = new PhpEcho();
$pilot->run(
    id: 'stdHelpers_008',
    test: fn() => $block->keyUp(keys: 'any_key', strict_match: true),
    description: 'try to extract a value from a key from a parent block that does not exist, no strict match'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelpers_009',
    test: fn() => $block->keyUp(keys: 'any_key', strict_match: false),
    description: 'try to extract a value from a key from a parent block that does not exist, strict match'
);
$pilot->assertException(InvalidArgumentException::class);


// we create a tree of PhpEcho blocks
$layout = new PhpEcho('layout_01.php');
$block_01 = new PhpEcho('block block_01.php');
$block_01['key_01'] = 'value_of_key_01';
$block_02 = new PhpEcho('block block_02.php');
$block_02['key_02'] = 'value_of_key_02';
$block_03 = new PhpEcho('block block_03.php');
$block_03['key_03'] = 'value_of_key_03';
$block_06 = new PhpEcho('block block_06.php');
$block_06['key_06'] = 'value_of_key_06';
$block_06['block_06_text'] = 'dummy_value_for_block_06';

$layout['body'] = $block_01;
$block_01['block_01_text'] = $block_02;
$block_02['block_02_text'] = $block_03;
$block_03['block_03_text'] = $block_06;

$pilot->run(
    id: 'stdHelper_010',
    test: fn() => $block_06->keyUp('key_03 key_02 key_01', strict_match: true),
    description: 'climbing the tree of PhpEcho blocks to reach and retrieve the value key_01, strict match'
);
$pilot->assertIsString();
$pilot->assertEqual('value_of_key_01');

$pilot->run(
    id: 'stdHelper_011',
    test: fn() => $block_06->keyUp('key_03 key_01', strict_match: true),
    description: 'climbing the tree of PhpEcho blocks to reach and retrieve the value key_01 omitting the intermediate key, strict match'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelper_012',
    test: fn() => $block_06->keyUp('key_03 key_01', strict_match: false),
    description: 'climbing the tree of PhpEcho blocks to reach and retrieve the value key_01 omitting the intermediate key, no strict match'
);
$pilot->assertIsString();
$pilot->assertEqual('value_of_key_01');

$pilot->run(
    id: 'stdHelper_013',
    test: fn() => $block_01->keyUp('key_03 key_01', strict_match: false),
    description: 'climbing the tree of PhpEcho blocks to reach a non existing parent block, no strict match'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelper_014',
    test: fn() => $layout->keyUp('any_key', strict_match: false),
    description: 'climbing the tree of PhpEcho blocks above the root, no strict match'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelper_015',
    test: fn() => $block_06->keyUp('key_03 wrong_key', strict_match: false),
    description: 'climbing the tree of PhpEcho blocks and looking for a non existing key, no strict match'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelper_016',
    test: fn() => $block_06->root(),
    description: 'climbing the tree of PhpEcho blocks to reach the root'
);
$pilot->assertIsObject();
$pilot->assertIsInstanceOf(PhpEcho::class);
$pilot->assertEqual($layout);