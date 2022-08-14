<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */
$filepath = $pilot->getResource('filepath');
$dir_root = $filepath('projects full path to the template root directory');

PhpEcho::setTemplateDirRoot($dir_root);

$pilot->run(
    id : 'path_001',
    test : fn() => PhpEcho::getFullFilepath('page home.php'),
    description : 'path builder to a view file using dynamic filepath'
);
$pilot->assertIsString();
$pilot->assertEqual($filepath("{$dir_root} page home.php"));

$block = new PhpEcho('page home.php');
$pilot->run(
    id : 'path_002',
    test : fn() => PhpEcho::getFullFilepath($block->getFilepath()),
    description : 'path builder to a view file using filepath in the constructor'
);
$pilot->assertIsString();
$pilot->assertEqual($filepath("{$dir_root} page home.php"));