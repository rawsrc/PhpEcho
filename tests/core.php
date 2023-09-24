<?php declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

$block = new PhpEcho();

$block['foo'] = 'abc " < >';

$pilot->run(
    id: 'core_001',
    test: fn() => $block['foo'],
    description: 'native escaping values'
);
$pilot->assertIsString();
$pilot->assertEqual('abc &quot; &lt; &gt;');

$pilot->run(
    id: 'core_002',
    test: fn() => $block['abc'],
    description: 'try to extract a undefined key from a block'
);
$pilot->assertException(InvalidArgumentException::class);


PhpEcho::setNullIfNotExists(true);
$pilot->run(
    id: 'core_003',
    test: fn() => $block['abc'],
    description: 'after setting setNullIfNotExists to true try to extract a undefined key from a block'
);
$pilot->assertEqual(null);

PhpEcho::setNullIfNotExists(false);
$pilot->run(
    id: 'core_004',
    test: fn() => $block['abc'],
    description: 'after setting setNullIfNotExists to false try to extract a undefined key from a block'
);
$pilot->assertException(InvalidArgumentException::class);


$block['foo bar'] = 'xyz';
$pilot->run(
    id: 'core_005',
    test: fn() => $block['foo'],
    description: 'try to use removed feature: space notation, check auto creation of sub-arrays'
);
$pilot->assertIsString();
$pilot->assertEqual('abc &quot; &lt; &gt;');

$pilot->run(
    id: 'core_006',
    test: fn() => $block['foo bar'],
    description: 'using space notation by default, check auto expanding into sub-array'
);
$pilot->assertIsString();
$pilot->assertEqual('xyz');

$block['foo bar'] = 'xxx';
$pilot->run(
    id: 'core_007',
    test: fn() => $block['foo bar'],
    description: 'space notation removed, check if key with space is preserved'
);
$pilot->assertIsString();
$pilot->assertEqual('xxx');

$block['klm'] = 12;
$pilot->run(
    id: 'core_008',
    test: fn() => $block['klm'],
    description: 'for non string values, check data type preservation (int)'
);
$pilot->assertIsInt();
$pilot->assertEqual(12);

$block['klm'] = true;
$pilot->run(
    id: 'core_009',
    test: fn() => $block['klm'],
    description: 'for non string values, check data type preservation (bool)'
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$block['klm'] = new stdClass();
$pilot->run(
    id: 'core_010',
    test: fn() => $block['klm'],
    description: 'for non string values, check data type preservation (object without __toString)'
);
$pilot->assertIsObject();
$pilot->assertIsInstanceOf(stdClass::class);

class Foo {
    public function __toString(): string
    {
        return 'abc " < >';
    }
}

$block['klm'] = new Foo();
$pilot->run(
    id: 'core_011',
    test: fn() => $block['klm'],
    description: 'for non string values, object with __toString is assimilated to a string and escaped'
);
$pilot->assertIsString();
$pilot->assertEqual('abc &quot; &lt; &gt;');


$data = [new PhpEcho(), new PhpEcho()];
$pilot->runClassMethod(
    id: 'core_012',
    class: $block,
    description: 'check isArrayOfPhpEchoBlocks with a full array of PhpEcho blocks',
    method: 'isArrayOfPhpEchoBlocks',
    params: [$data]
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$data = [new PhpEcho(), new PhpEcho(), 25];
$pilot->runClassMethod(
    id: 'core_013',
    class: $block,
    description: 'check isArrayOfPhpEchoBlocks with an mixed array',
    method: 'isArrayOfPhpEchoBlocks',
    params: [$data]
);
$pilot->assertIsBool();
$pilot->assertEqual(false);


$data = [];
$pilot->runClassMethod(
    id: 'core_014',
    class: $block,
    description: 'check isArrayOfPhpEchoBlocks with an empty array',
    method: 'isArrayOfPhpEchoBlocks',
    params: [$data]
);
$pilot->assertIsBool();
$pilot->assertEqual(false);


PhpEcho::setTemplateDirRoot(__DIR__.DIRECTORY_SEPARATOR.'view');

$data = [
    'abc' => new PhpEcho('block/block_02.php', ['block_02_text' => 'abc'], 'block_abc'),
    'def' => new PhpEcho('block/block_02.php', ['block_02_text' => 'def'], 'block_def'),
    'ghi' => new PhpEcho('block/block_02.php', [
        'jkl' => new PhpEcho('block/block_02.php', ['block_02_text' => 'jkl'], 'block_jkl'),
        'block_02_text' => 'ghi',
    ], 'block_ghi'),
    'mno' => new PhpEcho('block/block_02.php', ['block_02_text' => 'mno'], 'block_mno'),
    'pqr' => new PhpEcho('block/block_02.php', ['block_02_text' => 'pqr'], 'block_pqr'),
];

$root = new PhpEcho(id: 'root');

$pilot->runClassMethod(
    id: 'core_015',
    class: $root,
    description: 'check isArrayOfPhpEchoBlocks with a recursive array',
    method: 'isArrayOfPhpEchoBlocks',
    params: [$data],
);
$pilot->assertIsBool();
$pilot->assertEqual(true);

$data['xyz'] = 'break php echo array';

$pilot->runClassMethod(
    id: 'core_016',
    class: $root,
    description: 'check isArrayOfPhpEchoBlocks with a recursive array',
    method: 'isArrayOfPhpEchoBlocks',
    params: [$data],
);
$pilot->assertIsBool();
$pilot->assertEqual(false);

$data = [
    'abc' => new PhpEcho('block/block_02.php', ['block_02_text' => 'abc'], 'block_abc'),
    'def' => new PhpEcho('block/block_02.php', ['block_02_text' => 'def'], 'block_def'),
    'ghi' => new PhpEcho('block/block_02.php', [
        'block_02_text' => [
            new PhpEcho('block/block_02.php', ['block_02_text' => 'ghi555'], 'block_555'),
            new PhpEcho('block/block_02.php', ['block_02_text' => 'ghi666'], 'block_666')],
    ], 'block_ghi'),
    'mno' => new PhpEcho('block/block_02.php', ['block_02_text' => 'mno'], 'block_mno'),
    'pqr' => new PhpEcho('block/block_02.php', ['block_02_text' => 'pqr'], 'block_pqr'),
];

$root = new PhpEcho(file: 'layout_01.php', vars: ['body' => $data], id: 'root');

ob_start();
echo $root;
$html = ob_get_clean();
$pilot->run(
    id : 'core_017',
    test : fn() => $html,
    description : 'recursive array of php echo block rendering'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  </head>
<body>
<p>abc</p>
<p>def</p>
<p><p>ghi555</p>
<p>ghi666</p>
</p>
<p>mno</p>
<p>pqr</p>
</body>
</html>
html);

