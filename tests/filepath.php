<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */
$filepath = fn(string $path) => str_replace('/', DIRECTORY_SEPARATOR, $path);
$dir_root = 'projects/full/path/to/the/template/root/directory';

PhpEcho::setTemplateDirRoot($dir_root);
// we add the space as a directory separator

$pilot->run(
    id : 'path_001',
    test : fn() => PhpEcho::getFullFilepath('page/home.php'),
    description : 'path builder to a view file using dynamic filepath'
);
$pilot->assertIsString();
$pilot->assertEqual($filepath('projects/full/path/to/the/template/root/directory/page/home.php'));

$block = new PhpEcho('page/home.php');
$pilot->run(
    id : 'path_002',
    test : fn() => PhpEcho::getFullFilepath($block->getFilepath()),
    description : 'path builder to a view file using filepath in the constructor'
);
$pilot->assertIsString();
$pilot->assertEqual($filepath('projects/full/path/to/the/template/root/directory/page/home.php'));

$pilot->run(
    id: 'path_003',
    test: fn() => PhpEcho::getFullFilepath('sub blocks/block01.php'),
    description: 'space is now preserved in the file path'
);
$pilot->assertIsString();
$pilot->assertEqual($filepath('projects/full/path/to/the/template/root/directory/sub blocks/block01.php'));
