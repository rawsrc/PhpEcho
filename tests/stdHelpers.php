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
$layout->setParam('layout_param', false);
$layout->setParam('block_02_param', 'never reached');
$block_01 = new PhpEcho('block block_01.php');
$block_01['key_01'] = 'value_of_key_01';
$block_02 = new PhpEcho('block block_02.php');
$block_02['key_02'] = 'value_of_key_02';
$block_02->setParam('block_02_param', true);
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


$pilot->run(
    id: 'stdHelper_017',
    test: fn() => $block_06->rootVar('body block_01_text block_02_text key_03'),
    description: 'reach the root and seek for a value descending the tree of PhpEcho blocks'
);
$pilot->assertIsString();
$pilot->assertEqual('value_of_key_03');


$pilot->run(
    id: 'stdHelper_018',
    test: fn() => $block_06->rootVar('body block_01_text block_02_text wrong_key'),
    description: 'reach the root and seek for a non existing key descending the tree of PhpEcho blocks'
);
$pilot->assertException(InvalidArgumentException::class);


$pilot->run(
    id: 'stdHelper_019',
    test: fn() => $block_06->seekParam('block_02_param'),
    description: 'seek the value of a parameter from the current block to the root'
);
$pilot->assertIsBool();
$pilot->assertEqual(true);


$pilot->run(
    id: 'stdHelper_020',
    test: fn() => $block_06->seekParam('block_02_param'),
    description: 'seek the value of a parameter from the current block to the root'
);
$pilot->assertIsBool();
$pilot->assertEqual(true);


$pilot->run(
    id: 'stdHelper_021',
    test: fn() => $block_06->seekParam('wrong_param'),
    description: 'seek the value of a no existing parameter from the current block to the root'
);
$pilot->assertException(InvalidArgumentException::class);


$pilot->run(
    id: 'stdHelper_022',
    test: fn() => $layout->selected('abc', 'abc'),
    description: 'html attribute selected'
);
$pilot->assertIsString();
$pilot->assertEqual(' selected ');

$pilot->run(
    id: 'stdHelper_023',
    test: fn() => $layout->selected('abc', 'abcdef'),
    description: 'html attribute selected'
);
$pilot->assertIsString();
$pilot->assertEqual('');


$pilot->run(
    id: 'stdHelper_024',
    test: fn() => $layout->checked('abc', 'abc'),
    description: 'html attribute checked'
);
$pilot->assertIsString();
$pilot->assertEqual(' checked ');

$pilot->run(
    id: 'stdHelper_025',
    test: fn() => $layout->checked('abc', 'abcdef'),
    description: 'html attribute checked'
);
$pilot->assertIsString();
$pilot->assertEqual('');


$pilot->run(
    id: 'stdHelper_026',
    test: fn() => $layout->attributes(['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']),
    description: 'html building secure attributes'
);
$pilot->assertIsString();
$pilot->assertEqual('type="text" name="name" required value=" &lt; &gt; &quot; &lt;script&gt;&lt;/script&gt;"');

$pilot->run(
    id: 'stdHelper_027',
    test: fn() => $layout->attributes(['type' => 'text', 'name' => 'name', 'required' => 'required', 'value' => ' < > " <script></script>']),
    description: 'html building secure attributes'
);
$pilot->assertIsString();
$pilot->assertEqual('type="text" name="name" required="required" value=" &lt; &gt; &quot; &lt;script&gt;&lt;/script&gt;"');

$pilot->run(
    id: 'stdHelper_028',
    test: fn() => $layout->attributes(['href' => 'https://localhost/path?q="<script> </script>"', 'src' => 'https://localhost/path?q="<script> </script>"']),
    description: 'html escaping url'
);
$pilot->assertIsString();
$pilot->assertEqual('href="https://localhost/path?q=&quot;&lt;script&gt; &lt;/script&gt;&quot;" src="https://localhost/path?q=&quot;&lt;script&gt; &lt;/script&gt;&quot;"');


$pilot->run(
    id: 'stdHelper_029',
    test: fn() => $layout->voidTag('input', ['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']),
    description: 'html creating a secure void tag'
);
$pilot->assertIsString();
$pilot->assertEqual('<input type="text" name="name" required value=" &lt; &gt; &quot; &lt;script&gt;&lt;/script&gt;">');


$pilot->run(
    id: 'stdHelper_030',
    test: fn() => $layout->tag('input', 'Please insert a value', ['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']),
    description: 'html creating an input tag'
);
$pilot->assertIsString();
$pilot->assertEqual('<input type="text" name="name" required value=" &lt; &gt; &quot; &lt;script&gt;&lt;/script&gt;">Please insert a value</input>');


$pilot->run(
    id: 'stdHelper_031',
    test: fn() => $layout->link(['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']),
    description: 'html try to create a link tag with missing required param'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelper_032',
    test: fn() => $layout->link(['rel' => 'https://localhost/path?q="<script> </script>"']),
    description: 'html create a link'
);
$pilot->assertIsString();
$pilot->assertEqual('<link rel="https://localhost/path?q=&quot;&lt;script&gt; &lt;/script&gt;&quot;">');

$pilot->run(
    id: 'stdHelper_033',
    test: fn() => $layout->style(['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']),
    description: 'html try to create a style tag with missing required param'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelper_034',
    test: fn() => $layout->style(['href' => 'https://localhost/css/css.min.css?q="<script> </script>"']),
    description: 'html create a style tag'
);
$pilot->assertIsString();
$pilot->assertEqual('<link rel="stylesheet" href="https://localhost/css/css.min.css?q=&quot;&lt;script&gt; &lt;/script&gt;&quot;">');

$pilot->run(
    id: 'stdHelper_035',
    test: fn() => $layout->style(['code' => 'h1 {color:red;} p {color:blue;}']),
    description: 'html create a plain style tag'
);
$pilot->assertIsString();
$pilot->assertEqual('<style>h1 {color:red;} p {color:blue;}</style>');


$pilot->run(
    id: 'stdHelper_036',
    test: fn() => $layout->style(['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']),
    description: 'html try to create a script tag with missing required param'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'stdHelper_037',
    test: fn() => $layout->script(['src' => 'https://localhost/js/js.min.js?q="<script> </script>"']),
    description: 'html create a script tag'
);
$pilot->assertIsString();
$pilot->assertEqual('<script src="https://localhost/js/js.min.js?q=&quot;&lt;script&gt; &lt;/script&gt;&quot;"></script>');

$pilot->run(
    id: 'stdHelper_038',
    test: fn() => $layout->script(['code' => 'document.getElementById("demo").innerHTML = "Hello JavaScript!";']),
    description: 'html create a plain script tag'
);
$pilot->assertIsString();
$pilot->assertEqual('<script>document.getElementById("demo").innerHTML = "Hello JavaScript!";</script>');


$block = new PhpEcho();
$block['foo'] = 'abc " < >';
$pilot->run(
    id: 'stdHelper_039',
    test: fn() => $block->renderIfNotSet('xyz', 'default value is: abc " < >'),
    description: 'renderIfNotSet helper'
);
$pilot->assertIsString();
$pilot->assertEqual('default value is: abc &quot; &lt; &gt;');