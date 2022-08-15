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