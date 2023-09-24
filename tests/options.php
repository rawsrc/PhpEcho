<?php declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

PhpEcho::setNullIfNotExists(false);
PhpEcho::setSeekValueMode('current');

$root = new PhpEcho(id: 'root');
$block = new PhpEcho(id: 'block_1');
$root['abc'] = 'root_value';
$block['def'] = 'block_value';

$root['block'] = $block;

$pilot->run(
    id: 'option_001',
    test: fn() => $block['def'],
    description: 'null if not exists deactivated, seek mode current, value is only available in the current block'
);
$pilot->assertIsString();
$pilot->assertEqual('block_value');

$pilot->run(
    id: 'option_002',
    test: fn() => $block['abc'],
    description: 'null if not exists deactivated, seek mode current, value in root is not accessible from the child'
);
$pilot->assertException(InvalidArgumentException::class);

PhpEcho::setNullIfNotExists(true);
$pilot->run(
    id: 'option_003',
    test: fn() => $block['xyz'],
    description: 'null if not exists activated, seek mode current, current block asked key does not exists'
);
$pilot->assertEqual(null);

$pilot->run(
    id: 'option_004',
    test: fn() => $block['abc'],
    description: 'null if not exist activated, seek mode current, asked key from the current block does not exist in the whole tree'
);
$pilot->assertEqual(null);

PhpEcho::setNullIfNotExists(false);
PhpEcho::setSeekValueMode('parents');

$sub_block = new PhpEcho(id: 'sub_block');
$block['sub_block'] = $sub_block;

$pilot->run(
    id: 'option_005',
    test: fn() => $sub_block['def'],
    description: 'null if not exist deactivated, seek mode parents, asked key from the current block only exist in the parent block'
);
$pilot->assertIsString();
$pilot->assertEqual('block_value');

$pilot->run(
    id: 'option_006',
    test: fn() => $block['abc'],
    description: 'null if not exist deactivated, seek in parents activated, asked key from the current block only exist in the root block'
);
$pilot->assertIsString();
$pilot->assertEqual('root_value');

PhpEcho::setNullIfNotExists(false);
PhpEcho::setSeekValueMode('root');

$pilot->run(
    id: 'option_007',
    test: fn() => $sub_block['def'],
    description: 'null if not exist deactivated, seek mode root, asked key from the current block only exist in the parent block'
);
$pilot->assertException(InvalidArgumentException::class);

$pilot->run(
    id: 'option_008',
    test: fn() => $block['abc'],
    description: 'null if not exist deactivated, seek mode root, asked key from the current block only exist in the root block'
);
$pilot->assertIsString();
$pilot->assertEqual('root_value');

PhpEcho::setNullIfNotExists(true);
PhpEcho::setSeekValueMode('root');

$pilot->run(
    id: 'option_009',
    test: fn() => $sub_block['def'],
    description: 'null if not exist activated, seek mode root, asked key from the current block only exist in the parent block'
);
$pilot->assertEqual(null);

$pilot->run(
    id: 'option_010',
    test: fn() => $block['abc'],
    description: 'null if not exist deactivated, seek mode root, asked key from the current block only exist in the root block'
);
$pilot->assertIsString();
$pilot->assertEqual('root_value');

PhpEcho::setNullIfNotExists(true);
PhpEcho::setSeekValueMode('parents');

$pilot->run(
    id: 'option_011',
    test: fn() => $sub_block['def'],
    description: 'null if not exist activated, seek mode parents, asked key from the current block only exist in the parent block'
);
$pilot->assertIsString();
$pilot->assertEqual('block_value');

$pilot->run(
    id: 'option_012',
    test: fn() => $block['abc'],
    description: 'null if not exist activated, seek mode parents, asked key from the current block only exist in the root block'
);
$pilot->assertIsString();
$pilot->assertEqual('root_value');

$pilot->run(
    id: 'option_013',
    test: fn() => $block['xyz'],
    description: 'null if not exist activated, seek mode parents, asked key does not exists in the whole tree'
);
$pilot->assertEqual(null);

PhpEcho::setNullIfNotExists(true);
PhpEcho::setSeekValueMode('parents');

$pilot->run(
    id: 'option_014',
    test: fn() => $sub_block['def'],
    description: 'all options activated, asked key from the current block only exist in the parent block'
);
$pilot->assertIsString();
$pilot->assertEqual('block_value');

$pilot->run(
    id: 'option_015',
    test: fn() => $block['abc'],
    description: 'all options activated, asked key from the current block only exist in the root block'
);
$pilot->assertIsString();
$pilot->assertEqual('root_value');

$pilot->run(
    id: 'option_016',
    test: fn() => $block['xyz'],
    description: 'all options activated, asked key does not exists in the whole tree'
);
$pilot->assertEqual(null);